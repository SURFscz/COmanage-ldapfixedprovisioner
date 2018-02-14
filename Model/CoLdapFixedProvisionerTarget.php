<?php
/**
 * COmanage Registry CO LDAP Fixed Provisioner Target Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry vTODO
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");

class CoLdapFixedProvisionerTarget extends CoProvisionerPluginTarget
{
  // Define class name for cake
  public $name = "CoLdapFixedProvisionerTarget";

  // Add behaviors
  public $actsAs = array('Containable', 'LdapFixedProvisioner.LdapService');

  // Association rules from this model to other models
  public $belongsTo = array("CoProvisioningTarget");

  public $hasMany = array(
    "CoLdapFixedProvisionerDn" => array(
      'className' => 'LdapFixedProvisioner.CoLdapFixedProvisionerDn',
      'dependent' => true
    )
  );

  // Default display field for cake generated views
  public $displayField = "serverurl";

  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO Provisioning Target ID must be provided'
    )
  );

  // Cache of schema plugins, populated by supportedAttributes
  protected $plugins = array();

  // LDAP resource object
  private $_ldap_resource=null;

  /**
   * Assemble attributes for an LDAP record.
   *
   * @since  COmanage Registry v0.8
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  Array                  $provisioningData         CO Person or CO Group Data used for provisioning
   * @param  Boolean                $modify                   Whether or not this will be for a modify operation
   * @param  Array                  $dnAttributes             Attributes used to generate the DN for this person, as returned by CoLdapProvisionerDn::dnAttributes
   * @return Array Attribute data suitable for passing to ldap_add, etc
   * @throws UnderflowException
   */
  protected function assembleAttributes($coProvisioningTargetData, $provisioningData, $modify, $dnAttributes)
  {
    // First see if we're working with a Group record or a Person record
    $person = isset($provisioningData['CoPerson']['id']);
    $group = isset($provisioningData['CoGroup']['id']);
    $uam = Configure::read('fixedldap.remove_unused');
    $uam = ($uam !== null && $uam) ? true : false;
    $scope_suffix = Configure::read('fixedldap.scope_suffix');

    // Marshalled attributes ready for export
    $attributes = array();

    // Cached group membership, interim solution for CO-1348 (see below)
    $groupMembers = array();

    // Full set of supported attributes (not what's configured, but including any plugin schemas)
    $supportedAttributes = $this->supportedAttributes();
    $configuredAttributes = $this->configuredAttributes();
    $this->dev_log("configured attributes: ".json_encode($configuredAttributes));

    // Note we don't need to check for inactive status where relevant since
    // ProvisionerBehavior will remove those from the data we get.
    foreach (array_keys($supportedAttributes) as $oc) {
      // First see if this objectclass is handled by a plugin
      if (!empty($supportedAttributes[$oc]['plugin'])) {
        // Ask the plugin to assemble the attributes for this objectclass for us.
        // First, get a pointer to the plugin model.
        $pmodel = $this->plugins[ $supportedAttributes[$oc]['plugin'] ];
        $pattrs = $pmodel->assemblePluginAttributes($configuredAttributes[$oc], $provisioningData);

        // Filter out any attributes in $pattrs that are not defined in $configuredAttributes.
        $pattrs = array_intersect_key($pattrs, $configuredAttributes[$oc]);

        // If this is not a modify operation than filter out any array() values.
        if (!$modify) {
          $pattrs = array_filter($pattrs, function ($attrValue) {
            return !(is_array($attrValue) && empty($attrValue));
          });
        }

        // Merge into the marshalled attributes.
        $attributes = array_merge($attributes, $pattrs);

        // Insert an objectclass
        $attributes['objectclass'][] = $oc;

        // Continue the loop (skip the standard processing)
        continue;
      }

      // Skip objectclasses that aren't relevant for the sort of data we're working with
      if (($person && $oc == 'groupOfNames')
         || ($group && !in_array($oc, array('groupOfNames','eduMember')))) {
        continue;
      }

      if ($group && empty($groupMembers) && in_array($oc, array('groupOfNames','eduMember'))) {
        // As an interim solution to CO-1348 we'll pull all group members here (since we no longer get them)
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $provisioningData['CoGroup']['id'];
        $args['contain'] = false;

        $groupMembers = $this->CoLdapFixedProvisionerDn->CoGroup->CoGroupMember->find('all', $args);
      }

      // Iterate across objectclasses. All configured objectclasses are required
      if ($supportedAttributes[$oc]['objectclass']['required']
        || isset($configuredAttributes[$oc])) {
        // Within the objectclass, iterate across the supported attributes looking
        // for required or enabled attributes. We need to add at least one $attr
        // before we add $oc to the list of objectclasses.
        $attrEmitted = false;

        foreach (array_keys($supportedAttributes[$oc]['attributes']) as $attr) {
          if($supportedAttributes[$oc]['attributes'][$attr]['required']) $this->dev_log("attribute $attr is required");
          if ($supportedAttributes[$oc]['attributes'][$attr]['required']
             || isset($configuredAttributes[$oc][$attr])) {
            // Does this attribute support multiple values?
            $multiple = (isset($supportedAttributes[$oc]['attributes'][$attr]['multiple'])
                        && $supportedAttributes[$oc]['attributes'][$attr]['multiple']);

            $targetType = isset($configuredAttributes[$oc][$attr])
                       && isset($configuredAttributes[$oc][$attr]['type'])
                       ? $configuredAttributes[$oc][$attr]['type']
                       : "";
            $this->dev_log("looking for attribute $attr of type $targetType");
            switch ($attr) {
            // Name attributes
            case 'cn':
              if ($person) {
                // Currently only preferred name supported (CO-333)
                $attributes[$attr] = generateCn($provisioningData['PrimaryName']);
              } else {
                $attributes[$attr] = $provisioningData['CoGroup']['name'];
              }
              break;
            case 'givenName':
              // Currently only preferred name supported (CO-333)
              $attributes[$attr] = $provisioningData['PrimaryName']['given'];
              break;
            case 'sn':
              // Currently only preferred name supported (CO-333)
              if (!empty($provisioningData['PrimaryName']['family'])) {
                $attributes[$attr] = $provisioningData['PrimaryName']['family'];
              }
              break;
            case 'displayName':
            case 'eduPersonNickname':
              // Walk through each name
              foreach ($provisioningData['Name'] as $n) {
                if (empty($targetType) || ($targetType == $n['type'])) {
                  $attributes[$attr][] = generateCn($n);

                  if (!$multiple) {
                    // We're only allowed one name in the attribute
                    break;
                  }
                }
              }
              break;

            // Attributes from CO Person Role
            case 'eduPersonAffiliation':
            case 'eduPersonScopedAffiliation':
            case 'employeeType':
            case 'o':
            case 'ou':
            case 'title':
              // Map the attribute to the column
              $cols = array(
                'eduPersonAffiliation' => 'affiliation',
                'eduPersonScopedAffiliation' => 'affiliation',
                'employeeType' => 'affiliation',
                'o' => 'o',
                'ou' => 'ou',
                'title' => 'title'
              );

              // Walk through each role
              $found = false;

              foreach ($provisioningData['CoPersonRole'] as $r) {
                if (!empty($r[ $cols[$attr] ])) {
                  if ($attr == 'eduPersonAffiliation'
                     || $attr == 'eduPersonScopedAffiliation') {
                    $affilmap = $this->CoProvisioningTarget->Co->CoExtendedType->affiliationMap($provisioningData['Co']['id']);

                    if (!empty($affilmap[ $r[ $cols[$attr] ]])) {
                      // Append scope, if so configured
                      $scope = '';

                      if ($attr == 'eduPersonScopedAffiliation') {
                        if (!empty($scope_suffix)) {
                          $scope = '@' . $scope_suffix;
                        } else {
                          // Don't add this attribute since we don't have a scope
                          continue;
                        }
                      }

                      $attributes[$attr][] = $affilmap[ $r[ $cols[$attr] ] ] . $scope;
                    }
                  } else {
                    $attributes[$attr][] = $r[ $cols[$attr] ];
                  }

                  $found = true;
                }

                if (!$multiple && $found) {
                  break;
                }
              }

              if (!$found && $modify) {
                $attributes[$attr] = array();
              }
              break;

            // Attributes from models attached to CO Person
            case 'eduPersonOrcid':
            case 'eduPersonPrincipalName':
            case 'eduPersonPrincipalNamePrior':
            case 'eduPersonUniqueId':
            case 'employeeNumber':
            case 'mail':
            case 'uid':
              // Map the attribute to the model and column
              $mods = array(
                'eduPersonOrcid' => 'Identifier',
                'eduPersonPrincipalName' => 'Identifier',
                'eduPersonPrincipalNamePrior' => 'Identifier',
                'eduPersonUniqueId' => 'Identifier',
                'employeeNumber' => 'Identifier',
                'mail' => 'EmailAddress',
                'uid' => 'Identifier'
              );

              $cols = array(
                'eduPersonOrcid' => 'identifier',
                'eduPersonPrincipalName' => 'identifier',
                'eduPersonPrincipalNamePrior' => 'identifier',
                'eduPersonUniqueId' => 'identifier',
                'employeeNumber' => 'identifier',
                'mail' => 'mail',
                'uid' => 'identifier'
              );

              if ($attr == 'eduPersonOrcid') {
                // Force target type to Orcid. Note we don't validate that the value is in
                // URL format (http://orcid.org/0000-0001-2345-6789) but perhaps we should.
                $targetType = IdentifierEnum::ORCID;
              }

              $scope = '';

              if ($attr == 'eduPersonUniqueId') {
                // Append scope if set, skip otherwise
                if (!empty($scope_suffix)) {
                  $scope = '@' . $scope_suffix;
                } else {
                  // Don't add this attribute since we don't have a scope
                  continue;
                }
              }

              $modelList = null;
              if (isset($configuredAttributes[$oc][$attr]['use_org_value'])
                 && $configuredAttributes[$oc][$attr]['use_org_value']) {
                // Use organizational identity value for this attribute
                //
                // If there is more than one CoOrgIdentityLink, for attributes
                // that support multiple values (mail, uid) push them all onto $modelList.
                // For the others, it's unclear what to do. For now, we'll just
                // pick the first one.
                if ($attr == 'mail'
                   || $attr == 'uid'
                   || $attr == 'eduPersonOrcid'
                   || $attr == 'eduPersonPrincipalNamePrior') {
                  // Multi-valued
                  //
                  // The structure is something like
                  // $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity']['Identifier'][0][identifier]
                  $this->dev_log("multivalued attribute using org identity");
                  if (isset($provisioningData['CoOrgIdentityLink'])) {
                    foreach ($provisioningData['CoOrgIdentityLink'] as $lnk) {
                      if (isset($lnk['OrgIdentity'][ $mods[$attr] ])) {
                        foreach ($lnk['OrgIdentity'][ $mods[$attr] ] as $x) {
                          $modelList[] = $x;
                        }
                      }
                    }
                  }
                } else {
                  // Single valued
                  if (isset($provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ])) {
                    // Don't use =& syntax here, it changes $provisioningData
                    $modelList = $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity'][ $mods[$attr] ];
                  }
                }
              } elseif (isset($provisioningData[ $mods[$attr] ])) {
                $this->dev_log("attribute uses CoPerson value");
                // Use CO Person value for this attribute
                $modelList = $provisioningData[ $mods[$attr] ];
              }

              // Walk through each model instance
              $found = false;
              if (isset($modelList)) {
                foreach ($modelList as $m) {
                  // If a type is set, make sure it matches
                  if (empty($targetType) || ($targetType == $m['type'])) {
                    $this->dev_log("found attribute of requested type");
                    // And finally that the attribute itself is set
                    if (!empty($m[ $cols[$attr] ])) {
                      $this->dev_log("attribute is set to '".$m[ $cols[$attr] ] . $scope."'");
                      $attributes[$attr][] = $m[ $cols[$attr] ] . $scope;
                      $found = true;
                    }
                  }

                  if (!$multiple && $found) {
                    break;
                  }
                }

                if (!$multiple && $found) {
                  break;
                }
              }

              if (!$found && $modify) {
                $attributes[$attr] = array();
              }
              break;
            case 'sshPublicKey':
              foreach ($provisioningData['SshKey'] as $sk) {
                global $ssh_ti;
                $attributes[$attr][] = $ssh_ti[ $sk['type'] ] . " " . $sk['skey'] . " " . $sk['comment'];
              }
              break;

            // Attributes from models attached to CO Person Role
            case 'facsimileTelephoneNumber':
            case 'l':
            case 'mail':
            case 'mobile':
            case 'postalCode':
            case 'roomNumber':
            case 'st':
            case 'street':
            case 'telephoneNumber':
              // Map the attribute to the model and column
              $mods = array(
                'facsimileTelephoneNumber' => 'TelephoneNumber',
                'l' => 'Address',
                'mail' => 'EmailAddress',
                'mobile' => 'TelephoneNumber',
                'postalCode' => 'Address',
                'roomNumber' => 'Address',
                'st' => 'Address',
                'street' => 'Address',
                'telephoneNumber' => 'TelephoneNumber'
              );

              $cols = array(
                'facsimileTelephoneNumber' => 'number',
                'l' => 'locality',
                'mail' => 'mail',
                'mobile' => 'number',
                'postalCode' => 'postal_code',
                'roomNumber' => 'room',
                'st' => 'state',
                'street' => 'street',
                'telephoneNumber' => 'number'
              );

              // Walk through each role, each of which can have more than one
              $found = false;
              foreach ($provisioningData['CoPersonRole'] as $r) {
                if (isset($r[ $mods[$attr] ])) {
                  foreach ($r[ $mods[$attr] ] as $m) {
                    // If a type is set, make sure it matches
                    if (empty($targetType) || ($targetType == $m['type'])) {
                      // And finally that the attribute itself is set
                      if (!empty($m[ $cols[$attr] ])) {
                        if ($mods[$attr] == 'TelephoneNumber') {
                          // Handle these specially... we want to format the number
                          // from the various components of the record
                          $attributes[$attr][] = formatTelephone($m);
                        } else {
                          $attributes[$attr][] = $m[ $cols[$attr] ];
                        }
                        $found = true;
                      }
                    }

                    if (!$multiple && $found) {
                      break;
                    }
                  }

                  if (!$multiple && $found) {
                    break;
                  }
                }
              }

              if (!$found && $modify) {
                $attributes[$attr] = array();
              }
              break;

            // Group attributes (cn is covered above)
            case 'description':
              // A blank description is invalid, so don't populate if empty
              if (!empty($provisioningData['CoGroup']['description'])) {
                $attributes[$attr] = $provisioningData['CoGroup']['description'];
              }
              break;
            // hasMember and isMember of are both part of the eduMember objectclass, which can apply
            // to both people and group entries. Check what type of data we're working with for both.
            case 'hasMember':
              if ($group && !empty($provisioningData['CoGroup']['id'])) {
                $members = $this->CoLdapFixedProvisionerDn
                                ->CoGroup
                                ->CoGroupMember
                                ->mapCoGroupMembersToIdentifiers($groupMembers, $targetType);

                if (!empty($members)) {
                  // Unlike member, hasMember is not required. However, like owner, we can't have
                  // an empty list.
                  $attributes[$attr] = $members;
                } elseif ($modify) {
                  // Unless we're modifying an entry, in which case an empty list
                  // says to remove any previous entry
                  $attributes[$attr] = array();
                }
              }
              break;
            case 'isMemberOf':
              if ($person) {
                if (!empty($provisioningData['CoGroupMember'])) {
                  foreach ($provisioningData['CoGroupMember'] as $gm) {
                    if (isset($gm['member']) && $gm['member']
                       && !empty($gm['CoGroup']['name'])) {
                      $attributes['isMemberOf'][] = $gm['CoGroup']['name'];
                    }
                  }
                }

                if ($modify && empty($attributes[$attr])) {
                  $attributes[$attr] = array();
                }
              }
              break;
            case 'member':
              $attributes[$attr] = $this->CoLdapFixedProvisionerDn->dnsForMembers($groupMembers);
              if (empty($attributes[$attr])) {
                // groupofnames requires at least one member
                // XXX seems like a better option would be to deprovision the group?
                throw new UnderflowException('member');
              }
              break;
            case 'owner':
              $owners = $this->CoLdapFixedProvisionerDn->dnsForOwners($groupMembers);
              if (!empty($owners)) {
                // Can't have an empty owners list (it should either not be present
                // or have at least one entry)
                $attributes[$attr] = $owners;
              } elseif ($modify) {
                // Unless we're modifying an entry, in which case an empty list
                // says to remove any previous entry
                $attributes[$attr] = array();
              }
              break;
            // eduPersonEntitlement is based on Group memberships
            case 'eduPersonEntitlement':
              if (!empty($provisioningData['CoGroupMember'])) {
                $entGroupIds = Hash::extract($provisioningData['CoGroupMember'], '{n}.co_group_id');
                $attributes[$attr] = $this->CoProvisioningTarget
                                          ->Co
                                          ->CoGroup
                                          ->CoService
                                          ->mapCoGroupsToEntitlements($provisioningData['Co']['id'], $entGroupIds);
              }

              if (!$modify && empty($attributes[$attr])) {
                // Can't have empty values on add
                unset($attributes[$attr]);
              }
              break;

            // posixAccount attributes
            case 'gecos':
              // Construct using same name as cn
              $attributes[$attr] = generateCn($provisioningData['PrimaryName']) . ",,,";
              break;
            case 'gidNumber':
            case 'homeDirectory':
            case 'uidNumber':
              // We pull these attributes from Identifiers with types of the same name
              // as an experimental implementation for CO-863.
              foreach ($provisioningData['Identifier'] as $m) {
                if (isset($m['type'])
                   && $m['type'] == $attr
                   && $m['status'] == StatusEnum::Active) {
                  $attributes[$attr] = $m['identifier'];
                  break;
                }
              }
              break;
            case 'loginShell':
              // XXX hard coded for now (CO-863)
              $attributes[$attr] = "/bin/tcsh";
              break;

            // Internal attributes
            case 'pwdAccountLockedTime':
              // Our initial support is simple: set to 000001010000Z for
              // expired or suspended Person status
              if ($provisioningData['CoPerson']['status'] == StatusEnum::Expired
                 || $provisioningData['CoPerson']['status'] == StatusEnum::Suspended) {
                $attributes[$attr] = '000001010000Z';
              } elseif ($modify) {
                $attributes[$attr] = array();
              }
              break;
            default:
              throw new InternalErrorException("Unknown attribute: " . $attr);
              break;
            }
          } elseif ($modify && $uam) {
            // In case this attribute is probably no longer being exported (but was previously),
            // set an empty value to indicate delete. Note there are use cases where this isn't
            // desirable, such as when an attribute is externally managed, or when a server is
            // using an older schema definition, so we let the admin configure this behavior.
            //
            // If set to Remove, don't do this for serverInternal attributes since they may not
            // actually be enabled on a given server (we don't currently have a good way to know).
            if (!isset($supportedAttributes[$oc]['attributes'][$attr]['serverInternal'])
               || !$supportedAttributes[$oc]['attributes'][$attr]['serverInternal']) {
              $attributes[$attr] = array();
            }
          }

          // Check if we emitted anything
          if (!empty($attributes[$attr])) {
            $attrEmitted = true;
          }
        }

        // Add $oc to the list of objectclasses if an attribute was emitted, or if
        // the objectclass is required (in which case the LDAP server will likely
        // throw an error if a required attribute is missing).

        if ($attrEmitted || $supportedAttributes[$oc]['objectclass']['required']) {
          $attributes['objectclass'][] = $oc;
        }
      }
    }

    // Add additionally configured objectclasses
    $group_ocs = Configure::read('fixedldap.group_ocs');
    if ($group && !empty($group_ocs)) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'], $group_ocs);
    }

    $group_ocs = Configure::read('fixedldap.person_ocs');
    if ($person && !empty($person_ocs)) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'], $person_ocs);
    }

    // Make sure the DN values are in the list (check case insensitively, in case
    // the user-entered case used to build the DN doesn't match). First, map the
    // outbound attributes to lowercase.
    $lcattributes = array();
    foreach (array_keys($attributes) as $a) {
      $lcattributes[strtolower($a)] = $a;
    }

    // Now walk through each DN attribute, but only multivalued ones.
    // At the moment we don't check, say cn (which is single valued) even though
    // we probably should.
    foreach (array_keys($dnAttributes) as $a) {
      if (is_array($dnAttributes[$a])) {
        // Lowercase the attribute for comparison purposes
        $lca = strtolower($a);

        if (isset($lcattributes[$lca])) {
          // Map back to the mixed case version
          $mca = $lcattributes[$lca];

          if (empty($attributes[$mca])
             || !in_array($dnAttributes[$a], $attributes[$mca])) {
            // Key isn't set, so store the value
            $attributes[$a][] = $dnAttributes[$a];
          }
        } else {
          // Key isn't set, so store the value
          $attributes[$a][] = $dnAttributes[$a];
        }
      }
    }

    // We can't send the same value twice for multi-valued attributes. For example,
    // eduPersonAffiliation can't have two entries for "staff", though it can have
    // one for "staff" and one for "employee". We'll walk through the multi-valued
    // attributes and remove any duplicate values. (We wouldn't have to do this here
    // if we checked before inserting each value, above, but that would require a
    // fairly large refactoring.)

    // While we're here, convert newlines to $ so the attribute doesn't end up
    // base-64 encoded, and also trim leading and trailing whitespace. While
    // normalization will typically handle this, in some cases (normalization
    // disabled, some attributes that are not normalized) we can still end up
    // with extra whitespace, which can be confusing/problematic in LDAP.
    foreach (array_keys($attributes) as $a) {
      if (is_array($attributes[$a])) {
        // Multi-valued. The easiest thing to do is reconstruct the array. We can't
        // just use array_unique since we have to compare case-insensitively.
        // (Strictly speaking, we should set case-sensitivity based on the attribute
        // definition.)
        // This array is what we'll put back -- we need to preserve case.
        $newa = array();

        // This hash is what we'll use to see if there are existing values.
        $h = array();

        foreach ($attributes[$a] as $v) {
          // Clean up the attribute before checking
          $tv = str_replace("\r\n", "$", trim($v));

          if (!isset($h[ strtolower($tv) ])) {
            $newa[] = $tv;
            $h[ strtolower($tv) ] = true;
          }
        }

        $attributes[$a] = $newa;
      } else {
        $attributes[$a] = str_replace("\r\n", "$", $attributes[$a]);
      }
    }
    return $attributes;
  }

  /**
   * Provision for the specified CO Person.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  ProvisioningActionEnum Registry transaction type triggering provisioning
   * @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
   * @return Boolean True on success
   * @throws InvalidArgumentException If $coPersonId not found
   * @throws RuntimeException For other errors
   */
  public function provision($coProvisioningTargetData, $op, $provisioningData)
  {
    // First figure out what to do
    $assigndn = false;
    $delete   = false;
    $deletedn = false;
    $add      = false;
    $modify   = false;
    $rename   = false;
    $person   = false;
    $group    = false;

    if (!empty($provisioningData['CoGroup']['id'])) {
      $group = true;
    }

    if (!empty($provisioningData['CoPerson']['id'])) {
      $person = true;
    }

    switch ($op) {
    case ProvisioningActionEnum::CoPersonAdded:
      // On add, we issue a delete (for housekeeping purposes, it will mostly fail)
      // and then an add. Note that various other operations will be promoted from
      // modify to add if there is no record in LDAP, so don't make this modify.
      $assigndn = true;
      $delete = true;
      $add = true;
      break;
    case ProvisioningActionEnum::CoPersonDeleted:
      // Because of the complexity of how related models are deleted and the
      // provisioner behavior invoked, we do not allow dependent=true to delete
      // the DN. Instead, we manually delete it
      $deletedn = true;
      $assigndn = false;
      $delete = true;
      $add = false;
      $person = true;
      break;
    case ProvisioningActionEnum::CoPersonPetitionProvisioned:
    case ProvisioningActionEnum::CoPersonPipelineProvisioned:
    case ProvisioningActionEnum::CoPersonReprovisionRequested:
    case ProvisioningActionEnum::CoPersonUnexpired:
      // For these actions, there may be an existing record with externally managed
      // attributes that we don't want to change. Treat them all as modifies.
      $assigndn = true;
      $modify = true;
      break;
    case ProvisioningActionEnum::CoPersonExpired:
    case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
    case ProvisioningActionEnum::CoPersonUnexpired:
    case ProvisioningActionEnum::CoPersonUpdated:
      if (!in_array(
        $provisioningData['CoPerson']['status'],
        array(StatusEnum::Active,
              StatusEnum::Expired,
              StatusEnum::GracePeriod,
              StatusEnum::Suspended)
        )) {
        // Convert this to a delete operation. Basically we (may) have a record in LDAP,
        // but the person is no longer active. Don't delete the DN though, since
        // the underlying person was not deleted.
        $delete = true;
      } else {
        // An update may cause an existing person to be written to LDAP for the first time
        // or for an unexpectedly removed entry to be replaced
        $assigndn = true;
        $modify = true;
      }
      break;
    case ProvisioningActionEnum::CoGroupAdded:
      $assigndn = true;
      $delete = false;  // Arguably, this should be true to clear out any prior debris
      $add = true;
      break;
    case ProvisioningActionEnum::CoGroupDeleted:
      $delete = true;
      $deletedn = true;
      $group = true;
      break;
    case ProvisioningActionEnum::CoGroupUpdated:
      $assigndn = true;
      $modify = true;
      break;
    case ProvisioningActionEnum::CoGroupReprovisionRequested:
      $assigndn = true;
      $delete = true;
      $add = true;
      break;
    default:
      throw new RuntimeException("Not Implemented");
      break;
    }

    Configure::load('ldapfixedprovisioner');
    $groupdn=Configure::read('fixedldap.basedn');
    $schemata=Configure::read('fixedldap.schemata');

    if ($group) {
      // If this is a group action and no Group Base DN is defined, or oc_groupofnames is false,
      // then don't try to do anything.
      if (empty($groupdn)
          || !is_array($schemata)
          || !isset($schemata['groupOfNames'])) {
        return true;
      }
    }

    // for logging purposes, use the COPersonId to make errors traceable
    $cid = $person ? $provisioningData['CoPerson']['id'] : $provisioningData['CoGroup']['id'];
    // Next, obtain a DN for this person or group
    try {
      $dns = $this->CoLdapFixedProvisionerDn->obtainDn(
                $coProvisioningTargetData,
                $provisioningData,
                $person ? 'person' : 'group',
                $assigndn);
    } catch (RuntimeException $e) {
      // This mostly never matches because $dns['newdnerr'] will usually be set
      throw new RuntimeException($e->getMessage());
    }

    if ($person
        && $assigndn
        && !$dns['newdn']
        && (!isset($provisioningData['CoPerson']['status'])
           || $provisioningData['CoPerson']['status'] != StatusEnum::Active)) {
      // If a Person is not active and we were unable to create a new DN (or recalculate
      // what it should be), fail silently. This will typically happen when a new Petition
      // is created and the Person is not yet Active (and therefore has no identifiers assigned).
      return true;
    }

    // We might have to handle a rename if the DN changed
    if ($dns['olddn'] && $dns['newdn'] && ($dns['olddn'] != $dns['newdn'])) {
      $rename = true;
    }

    if ($dns['newdn'] && ($add || $modify)) {
      // Find out what attributes went into the DN to make sure they got populated into
      // the attribute array
      try {
        $dnAttributes = $this->CoLdapFixedProvisionerDn->dnAttributes(
                            $coProvisioningTargetData,
                            $dns['newdn'],
                            $person ? 'person' : 'group');
      } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
      }

      // Assemble an LDAP record
      try {
        $attributes = $this->assembleAttributes(
                          $coProvisioningTargetData,
                          $provisioningData,
                          $modify,
                          $dnAttributes);
        $this->dev_log("coperson $cid attributes found: ".json_encode($attributes));
      } catch (UnderflowException $e) {
        // We have a group with no members. Convert to a delete operation since
        // groupOfNames requires at least one member.
        if ($group) {
          $add = false;
          $modify = false;
          $delete = true;
        }
      }
    }

    // Bind to the server
    $url = Configure::read('fixedldap.server.url');
    $binddn=Configure::read('fixedldap.server.binddn');
    $password=Configure::read('fixedldap.server.password');
    $basedn=Configure::read('fixedldap.basedn');
    if (!$this->connectLdap($url, $binddn, $password)) {
      $this->dev_log("failed to provision due to missing connection");
      return false;
    }

    $this->verifyOrCreateCo($url, $binddn, $password, $basedn, $provisioningData['Co']['name']);

    if ($delete) {
      // Delete any previous entry. For now, ignore any error.
      if ($rename || !$dns['newdn']) {
        // Use the old DN if we're renaming or if there is no new DN
        // (which should be the case for a delete operation).
        $this->ldap_delete($dns['olddn']);
      } else {
        // It's actually not clear when we'd get here -- perhaps cleaning up
        // a record that exists in LDAP even though it's new to Registry?
        $this->ldap_delete($dns['newdn']);
      }

      if ($deletedn) {
        // Delete the old DN from the database. (It's not done via dependency to ensure
        // we have it when we finally delete the record.)
        if ($dns['olddnid']) {
        $this->CoLdapFixedProvisionerDn->delete($dns['olddnid']);
        }
      }
    }

    if ($rename
      // Skip this if we're doing a delete and an add, which is basically a rename
      && !($delete && $add)) {
      if (!$dns['newdn']) {
        // silently ignore cases where we do not have a valid LDAP DN
        $this->log(_txt('er.ldapfixedprovisioner.rename1')." (coperson: $cid)", 'debug');
        return true;
      }

      // Perform the rename operation before we try to do anything else. Note that
      // the old DN is complete while the new DN is relative.
      if ($person) {
        $basedn = Configure::read('fixedldap.basedn');
      } else {
        $basedn = Configure::read('fixedldap.groupdn');
      }

      $newrdn = rtrim(str_replace($basedn, "", $dns['newdn']), " ,");

      if (!$this->ldap_rename($dns['olddn'], $newrdn)) {
        // XXX We should probably try to reset CoLdapFixedProvisionerDn here since we're
        // now inconsistent with LDAP
        $this->log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)");
        return false;
      }
      else {
        $this->dev_log("rename was succesful");
      }
    }

    if ($modify) {
      if (!$dns['newdn']) {
        // silently ignore cases where we do not have a valid LDAP DN
        $this->log(_txt('er.ldapfixedprovisioner.rename1')." (coperson: $cid)", 'debug');
        return true;
      }
      if (!$this->ldap_mod_replace($dns['newdn'], $attributes)) {
        if ($this->ldap_errno() == 0x20 /*LDAP_NO_SUCH_OBJECT*/) {
          // Change to an add operation. We call ourselves recursively because
          // we need to recalculate $attributes. Modify wants array() to indicate
          // an empty attribute, whereas Add throws an error if that is the case.
          // As a side effect, we'll rebind to the LDAP server, but this should
          // be a pretty rare event.
          $this->provision(
                    $coProvisioningTargetData,
                    ($person ? ProvisioningActionEnum::CoPersonAdded : ProvisioningActionEnum::CoGroupAdded),
                    $provisioningData);
        } else {
          $this->log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)", 'error');
          $this->dev_log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)");
          return false;
        }
      }
      else
      {
        $this->dev_log("replace was succesful");
      }
    }

    if ($add) {
      // Write a new entry
      if (!$dns['newdn']) {
        // silently ignore cases where we do not have a valid LDAP DN
        $this->log(_txt('er.ldapfixedprovisioner.rename1')." (coperson: $cid)", 'debug');
        return true;
      }

      if (!$this->ldap_add($dns['newdn'], $attributes)) {
        $this->log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . " (".$this->ldap_errno() .", coperson: $cid)");
        return false;
      }
      else
      {
        $this->dev_log("add was succesful");
      }
    }

    // Drop the connection
    $this->ldap_unbind();

    // We rely on the LDAP server to manage last modify time
    return true;
  }

  /**
   * Connect to an LDAP server.
   *
   * @since  COmanage Registry vTODO
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN
   * @param  String Search filter
   * @param  Array Attributes to return (or null for all)
   * @return Array Search results
   */
  protected function connectLdap($serverUrl, $bindDn, $password)
  {
    if ($this->ldap_is_connected()) {
      return true;
    }

    $cxn = $this->ldap_connect($serverUrl);
    if (!$cxn) {
      $this->log(_txt('er.ldapfixedprovisioner.connect'), 'error');
      return false;
    }

    // Use LDAP v3 (this could perhaps become an option at some point)
    $this->ldap_set_option(LDAP_OPT_PROTOCOL_VERSION, 3);

    if (!$this->ldap_bind($bindDn, $password)) {
      $this->log(_txt('er.ldapfixedprovisioner.bind').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
      return false;
    }
    return true;
  }

  /**
   * Query an LDAP server.
   *
   * @since  COmanage Registry vTODO
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN
   * @param  String Search filter
   * @param  Array Attributes to return (or null for all)
   * @return Array Search results
   */
  protected function queryLdap($serverUrl, $bindDn, $password, $baseDn, $filter, $attributes=array())
  {
    $ret = array();
    if (!$this->connectLdap($serverUrl, $bindDn, $password)) {
      // reason already logged
      return $ret;
    }

    // Try to search using base DN; look for any matching object under the base DN
    $s = $this->ldap_search($baseDn, $filter, $attributes);

    if (!$s) {
      $this->log(_txt('er.ldapfixedprovisioner.query').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
      return $ret;
    }
    $ret = $this->ldap_get_entries($s);

    $this->ldap_unbind();

    return $ret;
  }

  /**
   * Determine the provisioning status of this target for a CO Person ID.
   *
   * @since  COmanage Registry vTODO
   * @param  Integer CO Provisioning Target ID
   * @param  Integer CO Person ID (null if CO Group ID is specified)
   * @param  Integer CO Group ID (null if CO Person ID is specified)
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   */
  public function status($coProvisioningTargetId, $coPersonId, $coGroupId=null)
  {
    $ret = array(
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );

    // Pull the DN for this person, if we have one. Cake appears to correctly interpret
    // these conditions into a JOIN.
    $args = array();
    $args['conditions']['CoLdapFixedProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    if ($coPersonId) {
      $args['conditions']['CoLdapFixedProvisionerDn.co_person_id'] = $coPersonId;
    } elseif ($coGroupId) {
      $args['conditions']['CoLdapFixedProvisionerDn.co_group_id'] = $coGroupId;
    }

    Configure::load('ldapfixedprovisioner');
    $dnRecord = $this->CoLdapFixedProvisionerDn->find('first', $args);

    if (!empty($dnRecord)) {
      // Query LDAP and see if there is a record
      try {
        $url=Configure::read('fixedldap.server.url');
        $binddn=Configure::read('fixedldap.server.binddn');
        $password=Configure::read('fixedldap.server.password');
        $ldapRecord = $this->queryLdap($url, $binddn, $password, $dnRecord['CoLdapProvisionerDn']['dn'], "(objectclass=*)", array('modifytimestamp'));

        if (!empty($ldapRecord)) {
          // Get the last provision time from the parent status function
          $pstatus = parent::status($coProvisioningTargetId, $coPersonId, $coGroupId);

          if ($pstatus['status'] == ProvisioningStatusEnum::Provisioned) {
            $ret['timestamp'] = $pstatus['timestamp'];
          }

          $ret['status'] = ProvisioningStatusEnum::Provisioned;
          $ret['comment'] = $dnRecord['CoLdapFixedProvisionerDn']['dn'];
        } else {
          $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
          $ret['comment'] = $dnRecord['CoLdapFixedProvisionerDn']['dn'];
        }
      } catch (RuntimeException $e) {
        if ($e->getCode() == 32) { // LDAP_NO_SUCH_OBJECT
          $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
          $ret['comment'] = $dnRecord['CoLdapFixedProvisionerDn']['dn'];
        } else {
          $ret['status'] = ProvisioningStatusEnum::Unknown;
          $ret['comment'] = $e->getMessage();
        }
      }
    } else {
      // No DN on file
      $ret['status'] = ProvisioningStatusEnum::NotProvisioned;
    }

    return $ret;
  }

  /**
   * Obtain the list of attributes supported for export.
   *
   * @since  COmanage Registry vTODO
   * @return Array Array of supported attributes
   */
  public function supportedAttributes()
  {
    // Attributes should be listed in the order they are to be rendered in.
    // The outermost key is the object class. If the objectclass is flagged
    // as required => false, it MUST have a corresponding column oc_FOO in
    // the cm_co_ldap_provisioner_targets.
    $attributes = array(
      'person' => array(
        'objectclass' => array(
          'required'    => true
        ),
        // RFC4519 requires sn and cn for person
        // For now, CO Person is always attached to preferred name (CO-333)
        'attributes' => array(
          'sn' => array(
            'required'    => true,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          'cn' => array(
            'required'    => true,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          // This isn't actually defined in an object class, it's part of the
          // server internal schema (if supported), but we don't have a better
          // place to put it
          'pwdAccountLockedTime' => array(
            'required'       => false,
            'multiple'       => false,
            'serverInternal' => true
          )
        )
      ),
      'organizationalPerson' => array(
        'objectclass' => array(
          'required'    => true
        ),
        'attributes' => array(
          'title' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'ou' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'telephoneNumber' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'telephone_number_types',
            'defaulttype' => ContactEnum::Office
          ),
          'facsimileTelephoneNumber' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'telephone_number_types',
            'defaulttype' => ContactEnum::Fax
          ),
          'street' => array(
            'required'    => false,
          ),
          'l' => array(
            'required'    => false,
          ),
          'st' => array(
            'required'    => false,

          ),
          'postalCode' => array(
            'required'    => false,
          )
        ),
      ),
      'inetOrgPerson' => array(
        'objectclass' => array(
          'required'    => true
        ),
        'attributes' => array(
          // For now, CO Person is always attached to preferred name (CO-333)
          // This isn't true anymore (CO-716)
          'givenName' => array(
            'required'    => false,
            'multiple'    => false
//            'multiple'    => true,
//            'typekey'     => 'en.name.type',
//            'defaulttype' => NameEnum::Official
          ),
          // And since there is only one name, there's no point in supporting displayName
          'displayName' => array(
            'required'    => false,
            'multiple'    => false,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Preferred
          ),
          'o' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'mail' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'email_address_types',
            'defaulttype' => EmailAddressEnum::Official
          ),
          'mobile' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'telephone_number_types',
            'defaulttype' => ContactEnum::Mobile
          ),
          'employeeNumber' => array(
            'required'    => false,
            'multiple'    => false,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'employeeType' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'roomNumber' => array(
            'required'    => false,
          ),
          'uid' => array(
            'required'    => false,
            'multiple'    => true,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          )
        )
      ),
      'eduPerson' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'eduPersonAffiliation' => array(
            'required'  => false,
            'multiple'  => true
          ),
          'eduPersonEntitlement' => array(
            'required'  => false,
            'multiple'  => true
          ),
          'eduPersonNickname' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Preferred
          ),
          'eduPersonOrcid' => array(
            'required'  => false,
            'multiple'  => false,
            'alloworgvalue' => true
          ),
          'eduPersonPrincipalName' => array(
            'required'  => false,
            'multiple'  => false,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'eduPersonPrincipalNamePrior' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'eduPersonScopedAffiliation' => array(
            'required'  => false,
            'multiple'  => true
          ),
          'eduPersonUniqueId' => array(
            'required'  => false,
            'multiple'  => false,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::Enterprise
          )
        )
      ),
      'groupOfNames' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'cn' => array(
            'required'    => true,
            'multiple'    => false
          ),
          'member' => array(
            'required'    => true,
            'multiple'    => true
          ),
          'owner' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'description' => array(
            'required'    => false,
            'multiple'    => false
          )
        )
      ),
      'eduMember' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'isMemberOf' => array(
            'required'  => false,
            'multiple'  => true,
          ),
          'hasMember' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID,
          )
        )
      ),
      'posixAccount' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'uidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'gidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'homeDirectory' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'loginShell' => array(
            'required'   => false,
            'multiple'   => false
          ),
          'gecos' => array(
            'required'   => false,
            'multiple'   => false
          )
        )
      ),
      'ldapPublicKey' => array(
        'objectclass' => array(
          'required'     => false
        ),
        'attributes' => array(
          'sshPublicKey' => array(
            'required'   => true,
            'multiple'   => true
          )
        )
      )
    );

    // Now check for any schema plugins, and add them to the attribute array.
    // We don't have a concept of ordering these plugins (especially since unlike
    // eg provisioners these plugins aren't explicitly instantiated), so we'll
    // sort them alphabetically for now.
    $this->plugins = $this->loadAvailablePlugins('ldapschema');

    foreach ($this->plugins as $name => $p) {
      // Inject the plugin name into the attribute array so that we know
      // which plugin to call during attribute assembly
      $pattrs = $p->attributes;
      foreach (array_keys($pattrs) as $pschema) {
        $pattrs[$pschema]['plugin'] = $name;
      }
      $attributes = array_merge($attributes, $pattrs);
    }

    return $attributes;
  }


  /**
   * Return a keyed list of objectclass and attributes as configured in the INI file
   *
   * @since  COmanage Registry vTODO
   * @return Array List of configured attributes
   */
  public function configuredAttributes()
  {
    $ocs = array();
    $schemata=Configure::read('fixedldap.schemata');
    if (is_array($schemata)) {
      foreach ($schemata as $oc) {
        $ocs[$oc]=array();
      }
    }

    // Rework the attribute definitions into something resembling a row from the LdapProvisionerAttribute model
    // We need this to accomodate our ldapschema plugins
    foreach ($ocs as $oc=>$attributes) {
      $oclist = Configure::read('fixedldap.'.$oc);
      if (is_array($oclist)) {
        $attributes = $oclist;
        foreach ($attributes as $key=>$val) {
          // extract type and origin from the value definition
          $type="";
          $use_org=false;
          if (is_string($val)) {
            $values = explode(';', $val, 1);
            if (is_array($values) && sizeof($values)>0) {
              $type=$values[0];
            }
            if (is_array($values) && sizeof($values)>1) {
              $use_org=$values[1];
              // if use_org contains a string containing 'ORG', assume we mean it is meant to be retrieved from the OrgId
              // instead of the COPersonId
              $use_org = (strstr(strtoupper($use_org), "ORG") !== false) ? true : false;
            }
          }

          $row=array(
            'attribute' => $key,
            'objectclass' => $oc,
            'type' => $type,
            'export' => 1,
            'use_org_value' => $use_org ? 1 : 0,
             // The following fields are added for completeness, to avoid the off chance a ldapschema plugin actually
             // tries to use them before testing their validity. That could be considered a bug.
             // a real id is not required, as we don't expect the plugins to support this LdapFixedProvisioner plugin
            'id' => -1,
            'co_ldap_fixed_provisioner_id' => -1,
            'created' => '1970-01-01 01-01-01',
            'modified' => '1970-01-01 01-01-01'
          );
          $ocs[$oc][$key]=$row;
        }
      }
    }

    return $ocs;
  }

  /**
   * Test an LDAP server to verify that the connection available is valid.
   *
   * @since  COmanage Registry vTODO
   * @param  String Server URL
   * @param  String Bind DN
   * @param  String Password
   * @param  String Base DN (People)
   * @param  String Base DN (Group)
   * @return Boolean True if parameters are valid
   * @throws RuntimeException
   */
  public function verifyLdapServer($url, $binddn, $password, $basedn, $co)
  {
    $this->verifyOrCreateCo($url, $binddn, $password, $basedn, $co);
    $peopledn = "ou=People,ou=$co,".$basedn;
    $groupdn = "ou=Groups,ou=$co,".$basedn;

    $results = $this->queryLdap($url, $binddn, $password, $peopledn, "(objectclass=*)", array("dn"));

    if (count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.basedn'));
    }

    // Check for a Group DN if one is configured
    $results = $this->queryLdap($url, $binddn, $password, $groupdn, "(objectclass=*)", array("dn"));
    if (count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.basedn.gr.none'));
    }
    return true;
  }

  /**
   * Test the existance of the main CO ou or add it
   * This routine requires that the LDAP bind was made (either in provision or in queryLdap)
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO data
   * @return Boolean True
   */
  public function verifyOrCreateCo($url, $binddn, $password, $basedn, $coData)
  {
    if (!$this->connectLdap($url, $binddn, $password)) {
      return false;
    }
    $dn = "ou=$coData,$basedn";
    if (!$this->ldap_add($dn, array("ou"=>$coData,"objectClass"=>"organizationalUnit"))) {
      if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
        $this->log(_txt('er.ldapfixedprovisioner.add1').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
        return false;
      }
    } else {
      // If the ldap_add was succesfull, the OU did not exist yet, so we need to add the
      // underlying OUs for People and Groups as well
      if (!$this->ldap_add("ou=People,$dn", array("ou"=>"People","objectClass"=>"organizationalUnit"))) {
        if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
          $this->log(_txt('er.ldapfixedprovisioner.add2').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
          return false;
        }
      }
      if (!$this->ldap_add("ou=Groups,$dn", array("ou"=>"Groups","objectClass"=>"organizationalUnit"))) {
        if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
          $this->log(_txt('er.ldapfixedprovisioner.add3').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
          return false;
        }
      }
    }

    return true;
  }

  // convenience function to enable/disable the development/trace logs
  private function dev_log($msg)
  {
    //CakeLog::write('debug',$msg);
  }
}
