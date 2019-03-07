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


  /**
   * Convenience function to determine the correct group prefix
   *
   * @since  COmanage Registry vTODO
   * @param  String object type
   * @return String Prefix to apply before database name
   */
  public function prefix($type) {
    switch($type) {
    case 'co': return 'CO:';
    case 'cou': return 'COU:';
    case 'group': return 'GRP:';
    default:
      break;
    }
    return '';
  }

  /**
   * Create a DN for the CO root group
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO data
   * @return String CO DN within the ou=Groups,o=<CO name>,<baseDN> tree
   */
  public function coDn($codata) {
    return "cn=".$this->CoLdapFixedProvisionerDn->escape_dn($this->prefix('co').$codata['Co']['name']).",".$this->groupdn;
  }

  /**
   * Create a gidNumber for a CoGroup
   *
   * @since  COmanage Registry vTODO
   * @param  Array    $group  COGroup or COU data
   * @return Mixed    $type   If set to ineteger, use as base value
   */
  protected function createGidNumber($group, $type)
  {
    $retval=null;
    $this->dev_log("creating gidNumber using ".json_encode($group));
    if(!empty($group) && !empty($group['id'])) {
      $id = intval($group['id']);
      $base=10000;
      if(is_numeric($type)) $base = intval($type);
      $retval = $base + $id;
    }
    $this->dev_log("returning gidNumber ".json_encode($retval));
    return $retval;
  }

  /**
   * Generate a single attribute
   *
   * @since  COmanage Registry vTODO
   * @param  Array   $attr                     name of the attribute
   * @param  Array   $config                   attribute configuration
   * @param  Array   $provisioningData         object data to generate attribute for
   * @return Array   $attribute                hash of one or more attribute settings (key=>list)
   */
  private function generateAttribute($attr, $config, $provisioningData) {
    $attribute=array();

    // Does this attribute support multiple values?
    $multiple = (isset($config['multiple']) && $config['multiple']);
    $targetType = isset($config['type']) ? $config['type'] : '';
    $attropts = isset($config['attropts']) ? $config['attropts'] : FALSE;
    $groupMembers = isset($config['members']) ? $config['members'] : array();                     
    $scope_suffix = isset($config['scope']) ? $config['scope'] : '';

    // Labeled attribute, used to construct attribute options
    $lattr = $attr;
    
    $group = isset($provisioningData['CoGroup']);
    $person = isset($provisioningData['CoPerson']) && !$group;
    $cou = isset($provisioningData['Cou']);
    $co = isset($provisioningData['Co']) && !($person || $group || $cou);
    $this->dev_log('type is '.($group?"group":($person?"person":($cou?"cou":($co ? "co" : "unknown")))));

    $this->dev_log("looking for attribute $attr of type $targetType");
    switch ($attr) {
      // Name attributes
      case 'cn':
        if($cou) {
          $attribute[$lattr][] = $this->prefix('cou').$provisioningData['Cou']['name'];
          break;
        } 
        else if($co) {
          $attribute[$lattr][] = $this->prefix('co').$provisioningData['Co']['name'];
          break;
        } 
        else if($group) {
          $attribute[$lattr][] = $this->prefix('group').$provisioningData['CoGroup']['name'];
          break;
        }
        // else person, fall through
      case 'givenName':
      case 'sn':
        if($attropts && !empty($provisioningData['PrimaryName']['language'])) {
          $lattr = $lattr . ";lang-" . $provisioningData['PrimaryName']['language'];
        }

        if($attr == 'cn') {
          $attribute[$lattr][] = generateCn($provisioningData['PrimaryName']);
        } else {
          $f = ($attr == 'givenName' ? 'given' : 'family');

          // Registry doesn't permit given to be blank, so we can safely
          // assume we're going to populate it. However, Registry does not
          // require a family name. The person schema DOES require sn to be
          // populated, so if we don't have one we have to insert a default
          // value, which for now will just be a dot (.).

          if(!empty($provisioningData['PrimaryName'][$f])) {
            $attribute[$lattr][] = $provisioningData['PrimaryName'][$f];
          } else {
            $attribute[$lattr][] = ".";
          }
        }
        break;
      case 'displayName':
      case 'eduPersonNickname':
      case 'voPersonAuthorName':
        // Walk through each name
        if($attropts && !empty($n['language'])) {
          $lattr = $lattr . ";lang-" . $n['language'];
        }
        foreach ($provisioningData['Name'] as $n) {
          if (empty($targetType) || ($targetType == $n['type'])) {
            $attribute[$lattr][] = generateCn($n);

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
        // a COU always falls under the Groups organizationalUnit
        if($cou || $co || $group) {
          switch($attr)  {
          case 'ou':
            if($group) $attribute[$attr]='group';
            if($cou) $attribute[$attr]='cou';
            if($co) $attribute[$attr]='co';
            break;
          case 'o':
            $attribute[$attr]=$provisioningData['Co']['name'];
            break;
          }
          break;          
        }      
      
        // Map the attribute to the column
        $cols = array(
          'eduPersonAffiliation' => 'affiliation',
          'eduPersonScopedAffiliation' => 'affiliation',
          'employeeType' => 'affiliation',
          'o' => 'o',
          'ou' => 'ou',
          'title' => 'title'
        );
        if ($attr == 'eduPersonScopedAffiliation' && empty($scope_suffix)) {
          // Don't add this attribute since we don't have a scope
          break;
        }
        // Walk through each role
        $found = false;

        foreach ($provisioningData['CoPersonRole'] as $r) {
          if (!empty($r[ $cols[$attr] ])) {
            $lrattr = $lattr;

            if($attropts) {
              $lrattr = $lattr . ";role-" . $r['id'];
            }

            if ($attr == 'eduPersonAffiliation'
               || $attr == 'eduPersonScopedAffiliation') {
              $affilmap = $this->CoProvisioningTarget->Co->CoExtendedType->affiliationMap($provisioningData['Co']['id']);

              if (!empty($affilmap[ $r[ $cols[$attr] ]])) {
                // Append scope, if so configured
                $scope = '';

                if ($attr == 'eduPersonScopedAffiliation') {
                  $scope = '@' . $scope_suffix;
                }

                $attribute[$lrattr][] = $affilmap[ $r[ $cols[$attr] ] ] . $scope;
              }
            } else if($attr == "employeeType" ) {
              // if this is a role for a specific COU, scope it with the name of the COU
              $scope = 'CO:'.$provisioningData['Co']['name'];

              if($r['cou_id']!== null && $r['Cou']) {
                $scope = 'COU:'.$r['Cou']['name'];
              }
              $attribute[$lrattr][] = $scope . ':'.$r[ $cols[$attr] ];
            } else {
              $attribute[$lrattr][] = $r[ $cols[$attr] ];
            }

            $found = true;
          }

          if (!$multiple && $found) {
            break;
          }
        }
        break;

      // Attributes from models attached to CO Person
      case 'eduPersonOrcid':
      case 'eduPersonPrincipalName':
      case 'eduPersonPrincipalNamePrior':
      case 'eduPersonUniqueId':
      case 'employeeNumber':
      case 'labeledURI':
      case 'mail':
      case 'uid':
      case 'voPersonApplicationUID':
      case 'voPersonExternalID':
      case 'voPersonID':
      case 'voPersonSoRID':
        // Map the attribute to the model and column
        $mods = array(
          'eduPersonOrcid' => 'Identifier',
          'eduPersonPrincipalName' => 'Identifier',
          'eduPersonPrincipalNamePrior' => 'Identifier',
          'eduPersonUniqueId' => 'Identifier',
          'employeeNumber' => 'Identifier',
          'labeledURI' => 'Url',
          'mail' => 'EmailAddress',
          'uid' => 'Identifier',
          'voPersonApplicationUID' => 'Identifier',
          'voPersonExternalID' => 'Identifier',
          'voPersonID' => 'Identifier',
          'voPersonSoRID' => 'Identifier'
        );

        $cols = array(
          'eduPersonOrcid' => 'identifier',
          'eduPersonPrincipalName' => 'identifier',
          'eduPersonPrincipalNamePrior' => 'identifier',
          'eduPersonUniqueId' => 'identifier',
          'employeeNumber' => 'identifier',
          'labeledURI' => 'url',
          'mail' => 'mail',
          'uid' => 'identifier',
          'voPersonApplicationUID' => 'identifier',
          'voPersonExternalID' => 'identifier',
          'voPersonID' => 'identifier',
          'voPersonSoRID' => 'identifier'
        );

        if ($attr == 'eduPersonUniqueId' && empty($scope_suffix)) {
          // Don't add this attribute since we don't have a scope
          break;
        }
        
        if ($attr == 'eduPersonOrcid') {
          // Force target type to Orcid. Note we don't validate that the value is in
          // URL format (http://orcid.org/0000-0001-2345-6789) but perhaps we should.
          $targetType = IdentifierEnum::ORCID;
        }

        $scope = '';

        if ($attr == 'eduPersonUniqueId') {
          // Append scope if set, skip otherwise
          $scope = '@' . $scope_suffix;
        }

        $modelList = null;
        if (isset($config['use_org_value']) && $config['use_org_value']) {
          //$this->dev_log('attribute '.$attr.' uses org value, searching for '.$mods[$attr]);
          // Use organizational identity value for this attribute
          //
          // The structure is something like
          // $provisioningData['CoOrgIdentityLink'][0]['OrgIdentity']['Identifier'][0][identifier]
          if (isset($provisioningData['CoOrgIdentityLink'])) {
            foreach ($provisioningData['CoOrgIdentityLink'] as $lnk) {
              if (isset($lnk['OrgIdentity'][ $mods[$attr] ])) {
                //$this->dev_log('adding orgidentity link to modellist');
                foreach ($lnk['OrgIdentity'][ $mods[$attr] ] as $x) {
                  $modelList[] = $x;
                }
              }
            }
          }
        } 
        elseif (isset($provisioningData[ $mods[$attr] ])) {
          //$this->dev_log("attribute uses CoPerson value");
          // Use CO Person value for this attribute
          $modelList = $provisioningData[ $mods[$attr] ];
        }

        // Walk through each model instance
        $found = false;
        if (isset($modelList)) {
          foreach ($modelList as $m) {
            // If a type is set, make sure it matches
            if (empty($targetType) || ($targetType == $m['type'])) {
              //$this->dev_log("found attribute of requested type");
              // And finally that the attribute itself is set
              if (!empty($m[ $cols[$attr] ])) {
                //$this->dev_log("attribute is set to '".$m[ $cols[$attr] ] . $scope."'");
                // Check for attribute options
                if($attropts && $attr == 'voPersonApplicationUID') {
                  // Map the identifier type to a service short label.
                  // There can be more than one service linked to a given
                  // identifier type, so we may insert more than one copy
                  // of the attribute (which is fine, as long as the app
                  // labels are different).

                  // XXX it'd be better to pass this with the provisioning data
                  // rather than call it once per identifer, or at least to pull
                  // a map once
                  $labels = $this->CoProvisioningTarget
                                 ->Co
                                 ->CoGroup
                                 ->CoService
                                 ->mapIdentifierToLabels($provisioningData['Co']['id'],
                                                         $m['type']);

                  if(!empty($labels)) {
                    foreach($labels as $id => $sl) {
                      $lrattr = $lattr . ';app-' . $sl;

                      $attribute[$lrattr][] = $m[ $cols[$attr] ] . $scope;
                    }
                  } 
                  else {
                    // There was no matching label, so we won't export the identifier.
                    // $attribute[$attr][] = $m[ $cols[$attr] ] . $scope;
                  }
                } 
                elseif($attr == 'labeledURI' && !empty($m['description'])) {
                  // Special case for labeledURI, which permits a description to be appended
                  $attribute[$attr][] = $m[ $cols[$attr] ] . " " . $m['description'];
                } 
                else {
                  $this->dev_log("setting attr $attr to ".$m[$cols[$attr]]);
                  $attribute[$attr][] = $m[ $cols[$attr] ] . $scope;
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
        break;

      case 'voPersonPolicyAgreement':
        if(!$attropts) {
          $attribute[$attr] = array();
        }
        if(!isset($provisioningData['CoTAndCAgreement'])) break;

        foreach($provisioningData['CoTAndCAgreement'] as $tc) {
          if(!empty($tc['agreement_time'])
             && !( empty($tc['CoTermsAndConditions']['url'])
                && empty($tc['CoTermsAndConditions']['tc_body']))
             && $tc['CoTermsAndConditions']['status'] == SuspendableStatusEnum::Active) {

           $url = empty($tc['CoTermsAndConditions']['tc_body'])
                  ? $tc['CoTermsAndConditions']['url']
                  : Router::url(array(
                    "controller" => "CoTermsAndConditions",
                    "action" => "raw_view",
                    $tc['CoTermsAndConditions']['id']
                  ), true);

            if($attropts) {
              $lrattr = $lattr . ";time-" . strtotime($tc['agreement_time']);
              $attribute[$lrattr][] = $url;
            } else {
              $attribute[$attr][] = $url;
            }
          }
        }

        break;
      case 'voPersonStatus':
        $attribute[$attr] = StatusEnum::$to_api[ $provisioningData['CoPerson']['status'] ];

        if($attropts) {
          // If attribute options are enabled, emit person role status as well

          foreach($provisioningData['CoPersonRole'] as $r) {
            $lrattr = $lattr . ";role-" . $r['id'];

            $attribute[$lrattr] = StatusENum::$to_api[ $r['status'] ];
          }
        }
        break;
      case 'sshPublicKey':
        foreach ($provisioningData['SshKey'] as $sk) {
          global $ssh_ti;
          $attribute[$attr][] = $ssh_ti[ $sk['type'] ] . " " . $sk['skey'] . " " . $sk['comment'];
        }
        break;

      case 'userPassword':
        if(!empty($provisioningData['Password'])) {
          foreach($provisioningData['Password'] as $up) {
            // Skip locked passwords
            if(!isset($up['AuthenticatorStatus']['locked']) || !$up['AuthenticatorStatus']['locked']) {
              // There's probably a better place for this (an enum somewhere?)
              switch($up['password_type']) {
                // XXX we can't use PasswordAuthenticator's enums in case the plugin isn't installed
                case 'CR':
                  $attribute[$attr][] = '{CRYPT}' . $up['password'];
                  break;
                default:
                  // Silently ignore other types
                  break;
              }
            }
          }
        }
        break;
      case 'voPersonCertificateDN':
      case 'voPersonCertificateIssuerDN':
        if(!$attropts) {
          $attribute[$attr] = array();
        }
        if(!isset($provisioningData['Certificate'])) break;
        foreach($provisioningData['Certificate'] as $cr) {
          // Skip locked certs
          if(!isset($cr['AuthenticatorStatus']['locked']) || !$cr['AuthenticatorStatus']['locked']) {
            $f = ($attr == 'voPersonCertificateDN' ? 'subject_dn' : 'issuer_dn');

            if($attropts) {
              $lrattr = $lattr . ";scope-" . $cr['id'];

              $attribute[$lrattr][] = $cr[$f];
            } 
            else {
              $attribute[$attr][] = $cr[$f];
            }
          }
        }
        break;

      // Attributes from models attached to CO Person Role
      case 'facsimileTelephoneNumber':
      case 'l':
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
                  // Check for attribute options
                  $lrattr = $lattr;

                  if($attropts) {
                    $lrattr .= ";role-" . $r['id'];

                    if(!empty($m['language'])) {
                      $lrattr .= ";lang-" . $m['language'];
                    }
                  }

                  if ($mods[$attr] == 'TelephoneNumber') {
                    // Handle these specially... we want to format the number
                    // from the various components of the record
                    $attribute[$lrattr][] = formatTelephone($m);
                  } else {
                    $attribute[$lrattr][] = $m[ $cols[$attr] ];
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
        break;

      // Group attributes (cn is covered above)
      case 'description':
        if($person) {
          # description is used in posixAccount as cn
          $attribute[$attr] = generateCn($provisioningData['PrimaryName']);
        }
        // A blank description is invalid, so don't populate if empty
        else if ($group && !empty($provisioningData['CoGroup']['description'])) {
          $attribute[$attr] = $provisioningData['CoGroup']['description'];
        }
        else if ($cou && !empty($provisioningData['Cou']['description'])) {
          $attribute[$attr] = $provisioningData['Cou']['description'];
        }
        else if ($co && !empty($provisioningData['Co']['description'])) {
          $attribute[$attr] = $provisioningData['Co']['description'];
        }
        break;
      // hasMember and isMember of are both part of the eduMember objectclass, which can apply
      // to both people and group entries. Check what type of data we're working with for both.
      case 'hasMember':
        if ($group) {
          $this->dev_log('generating hasMember for group');
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForMembers($groupMembers, false);
        }
        else if ($cou || $co) {
          $this->dev_log('generating hasMember for COU/CO');
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForCous($this->targetData, $provisioningData, $groupMembers, false);
        }
        break;
      case 'isMemberOf':
        if ($person) {
          $this->dev_log('isMemberOf for a person record');
          if (!empty($provisioningData['CoGroupMember'])) {
            foreach ($provisioningData['CoGroupMember'] as $gm) {
              //$this->dev_log('checking group member '.json_encode($gm));
              if (isset($gm['member']) && $gm['member']
                 && !empty($gm['CoGroup']['name'])) {
                $dt=array_merge($gm,array('Co'=>$provisioningData['Co']));
                //$this->dev_log('calling obtainDn with '.json_encode($dt));
                $dn=$this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $dt,'group',false);
                //$this->dev_log('dn returns '.json_encode($dn));
                if(!empty($dn['newdn'])) {
                  $attribute[$attr][] = $dn['newdn'];
                }

                if(  isset($gm['CoGroup']['cou_id'])
                  && !empty($gm['CoGroup']['cou_id'])
                  && ($gm['CoGroup']['group_type'] == GroupEnum::ActiveMembers)
                  ) {
                  // find the COU and add membership of the COU as well
                  $args = array();
                  $args['conditions']['Cou.id'] = $gm['CoGroup']['cou_id'];
                  $args['contain'] = false;
                  $data = $this->CoLdapFixedProvisionerDn->Cou->find('first', $args);
                  if(!empty($data)) {
                    $dt=array_merge($data,array('Co'=>$provisioningData['Co']));
                    //$this->dev_log('calling obtainDn with '.json_encode($dt));
                    $dn=$this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $dt,'cou',false);
                    if(!empty($dn['newdn'])) {
                      $attribute[$attr][] = $dn['newdn'];
                    }
                  }
                }
              }
            }
          } else $this->dev_log('no groupmember records found');
        }
        else if($group) {
          $this->dev_log('isMemberOf for a group record');
          if (!empty($provisioningData['CoGroup']['cou_id'])) {
            $args = array();
            $args['conditions']['Cou.id'] = $provisioningData['CoGroup']['cou_id'];
            $args['contain'] = false;
            $parent = $this->CoLdapFixedProvisionerDn->Cou->find('first', $args);
            if(!empty($parent)) {
              $dt=array_merge($parent,array('Co'=>$provisioningData['Co']));
              $this->dev_log('calling obtainDn with '.json_encode($dt));
              $dn=$this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $dt,'cou',true);
              $this->dev_log('dn returns '.json_encode($dn));
              if(!empty($dn['newdn']))
              {
                $attribute[$attr][] = $dn['newdn'];
              }
            }
          }
          else {
            // a member of the CO top group
            $attribute[$attr][]=$this->coDn($provisioningData);
          }
        }
        else if($cou) {
          $this->dev_log('isMemberOf for a COU record');
          if (!empty($provisioningData['Cou']['parent_id'])) {
            $args = array();
            $args['conditions']['Cou.id'] = $provisioningData['Cou']['parent_id'];
            $args['contain'] = false;
            $parent = $this->CoLdapFixedProvisionerDn->Cou->find('first', $args);
            if(!empty($parent)) {
              $dt=array_merge($parent,array('Co'=>$provisioningData['Co']));
              $this->dev_log('calling obtainDn with '.json_encode($dt));
              $dn=$this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $dt,'cou',true);
              $this->dev_log('dn returns '.json_encode($dn));
              if(!empty($dn['newdn']))
              {
                $attribute[$attr][] = $dn['newdn'];
              }
            }
          }
          else {
            // a member of the CO top group
            $attribute[$attr][]=$this->coDn($provisioningData);
          }
        }
        break;
      case 'member':
        if($group) {
          # groupOfNames
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForMembers($groupMembers, FALSE);
        }
        else if($cou || $co) {
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForCous($this->targetData, $provisioningData, $groupMembers, FALSE);
        }
        if (empty($attribute[$attr])) {
          $this->dev_log('group has no members');
          // groupofnames requires at least one member
          // XXX seems like a better option would be to deprovision the group?
          throw new UnderflowException('member');
        }
        break;
      case 'memberUID':
        # posixGroup, which allows empty groups
        if($group) {
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForMembers($groupMembers, true);
        } 
        else if($cou || $co) {
          $attribute[$attr] = $this->CoLdapFixedProvisionerDn->dnsForCous($this->targetData, $provisioningData, $groupMembers, true);
        }
        break;
      case 'owner':
        if($group) {
          $owners = $this->CoLdapFixedProvisionerDn->dnsForOwners($groupMembers, FALSE);
          if (!empty($owners)) {
            // Can't have an empty owners list (it should either not be present
            // or have at least one entry)
            $attribute[$attr] = $owners;
          }
        }
        else if($cou || $co) {
          $owners = $this->CoLdapFixedProvisionerDn->dnsForAdmins($provisioningData, FALSE);
          if (!empty($owners)) {
            // Can't have an empty owners list (it should either not be present
            // or have at least one entry)
            $attribute[$attr] = $owners;
          }
        }
        break;
      case 'eduPersonEntitlement':
        // eduPersonEntitlement is based on Group memberships
        if (!empty($provisioningData['CoGroupMember'])) {
          $entGroupIds = Hash::extract($provisioningData['CoGroupMember'], '{n}.co_group_id');
          $attribute[$attr] = $this->CoProvisioningTarget
                                    ->Co
                                    ->CoGroup
                                    ->CoService
                                    ->mapCoGroupsToEntitlements($provisioningData['Co']['id'], $entGroupIds);
        }
        break;

      // posixAccount attributes
      case 'gecos':
        // Construct using same name as cn
        $attribute[$attr] = generateCn($provisioningData['PrimaryName']) . ",,,";
        break;
      case 'homeDirectory':
      case 'uidNumber':
      case 'gidNumber':
        if ($person) {
          // We pull these attributes from Identifiers with types of the same name
          // as an experimental implementation for CO-863.
          foreach ($provisioningData['Identifier'] as $m) {
            if (isset($m['type'])
               && strtolower($m['type']) == strtolower($attr)
               && $m['status'] == StatusEnum::Active) {
              $attribute[$attr] = $m['identifier'];
              break;
            }
          }
        } else if($attr == "gidNumber"){
          // generate a group ID number based on the group data
          $this->dev_log("creating gid number");
          $nr = $this->createGidNumber($provisioningData['CoGroup'], $targetType);
          if(!empty($nr)) {
            $attribute[$attr] = $nr;
          }
        }
        break;
      case 'loginShell':
        // XXX hard coded for now (CO-863)
        $attribute[$attr] = "/bin/tcsh";
        break;

      // Internal attributes
      case 'pwdAccountLockedTime':
        // Our initial support is simple: set to 000001010000Z for
        // expired or suspended Person status
        if ($provisioningData['CoPerson']['status'] == StatusEnum::Expired
           || $provisioningData['CoPerson']['status'] == StatusEnum::Suspended) {
          $attribute[$attr] = '000001010000Z';
        }
        break;
      default:
        throw new InternalErrorException("Unknown attribute: " . $attr);
        break;
    }
    $this->dev_log("attribute is ".json_encode($attribute));
    return $attribute;
  }

  /**
   * Remove empty attribute definitions
   * This routine removes empty attribute definitions, which are used in a modify
   * operation to delete existing-but-no-longer-set values (ie: cleanup)
   * We remove these attributes before doing the ADD, because ldap_add is too lame
   * to do that itself and throws an error instead.
   *
   * @since  COmanage Registry vTODO
   * @param  Array    attributes    associative hash of all attributes and values
   * @return Array    Attribute     data suitable for passing to ldap_add, etc
   */

  private function removeEmptyAttributes($attributes) {
    $pattrs = array_filter($attributes, function ($attrValue) {
      return !(is_array($attrValue) && empty($attrValue));
    });
    return $pattrs;
  }

  /**
   * Check attributes for an LDAP record.
   * This routine checks:
   * - if the (case insensitive) attributes used for the DN are present as attribute
   * - removes duplicate values from the list of values
   * - replaces newline with $
   *
   * @since  COmanage Registry vTODO
   * @param  Array    attributes    associative hash of all attributes and values
   * @param  Array    dnAttributes  array of all elements in the DN, so we can check for presence
   * @return Array    Attribute     data suitable for passing to ldap_add, etc
   */

  private function checkAttributes($attributes, $dnAttributes) {
    
    // make sure all attributes are defined as an array
    foreach($attributes as $attr=>$vals) {
      if(!is_array($vals)) $attributes[$attr]=array($vals);
    }     
    
    // Make sure the DN values are in the list (check case insensitively, in case
    // the user-entered case used to build the DN doesn't match). First, map the
    // outbound attributes to lowercase.
    $lcattributes = array();
    foreach (array_keys($attributes) as $a) {
      $lcattributes[strtolower($a)] = $a;
    }

    // Now walk through each DN attribute
    foreach (array_keys($dnAttributes) as $a) {
      // Lowercase the attribute for comparison purposes
      $lca = strtolower($a);

      if (isset($lcattributes[$lca])) {
        // Map back to the mixed case version
        $mca = $lcattributes[$lca];

        if (empty($attributes[$mca])
           || !in_array($dnAttributes[$a], $attributes[$mca])) {
          // Key isn't set, so store the value
          if(!in_array($a,array('o','ou','dc'))) {
            $attributes[$a][] = $dnAttributes[$a];
          }
        }
      } else {
        // Key isn't set, so store the value
        if(!in_array($a,array('o','ou','dc'))) {
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
          $tv = str_replace("\r\n", "$", $v);

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
    
    // create a list of attributes and their actual attribute name. This is required 
    // for attribute options
    $attroptions=array();
    foreach(array_keys($attributes) as $attr) {
      $nonoptattr = explode(';', $attr)[0];
      if(isset($attributes[$attr])) {
        if(!isset($attroptions[$nonoptattr])) $attroptions[$nonoptattr]=array();
        $attroptions[$nonoptattr][]=$attr;
      }
    }    
    
    // Check that all the required fields of the configured objectclasses are available
    // If not, remove the relevant objectclass    
    $ocs=$attributes['objectclass'];
    $newocs=array();
    $supported=$this->supportedAttributes();
    foreach($supported as $oc=>$cfg) {
      // only check objectclasses we are going to emit
      if(in_array($oc,$ocs)) {
        $removeThis=false;
        
        // check all required attributes
        foreach($cfg['attributes'] as $attr=>$acfg) {
          if(isset($acfg['required']) && $acfg['required']) {
            $this->dev_log('found required attribute '.$attr.' for oc '.$oc);
            // see if the attribute is set in a attribute-option version 
            if(!isset($attroptions[$attr])) {
              $removeThis=true;
            } 
            else {
              $allempty=true;
              foreach($attroptions[$attr] as $opt) {
                if(!empty($attributes[$opt])) $allempty=false;
              } 
              if($allempty) $removeThis=true;
            }
          }
        }
        
        if(!$removeThis) {
          $newocs[]=$oc;
        }
        else {
          $this->dev_log('Removing '.$oc.' from objectclasses');
        }
      }
    }
    $ocs=$newocs;
    unset($attributes["objectclass"]);

    // export only fields covered by one of the objectclasses we export
    // We copy the attribute values of those fields, and anything we leave
    // behind is discarded
    $newattributes=array();
    foreach($supported as $oc=>$cfg) {
      if(in_array($oc,$ocs)) {
        foreach($cfg['attributes'] as $attr=>$acfg) {
          // copy the attribute and all its versions
          if(isset($attroptions[$attr])) {
            foreach($attroptions[$attr] as $attropt) {
              if(isset($attributes[$attropt])) {

                // if this attribute does not allow multiple values, take the first only
                if(!isset($acfg['multiple']) || !$acfg['multiple']) {
                  if(sizeof($attributes[$attropt]) > 0) {
                    $newattributes[$attropt]=$attributes[$attropt][0];
                  }
                  else {
                    $newattributes[$attropt]=[];
                  }
                }
                else {
                  $newattributes[$attropt]=$attributes[$attropt];
                }
                unset($attributes[$attropt]);
              }
            }
          }
        }
      }
    }

    // all left-over attributes, even if we do not support the objectClass,
    // need to be set to an empty array, so they are deleted if they are
    // present nonetheless. This is required for cases where we had an
    // LdapPublicKey objectclass at first, but the sshPublicKey is deleted.
    // Because the LdapPublicKey OC is no longer complete, we do not export
    // that OC. But we do need to delete the existing entry for sshPublicKey
    // in the LDAP, or else we get an OC error.
    foreach($attributes as $attr=>$val) {
      if(!isset($newattributes[$attr])) {
        $newattributes[$attr]=[];
      }
      if(isset($attroptions[$attr])) {
        foreach($attroptions[$attr] as $attropt) {
          if(!isset($newattributes[$attropt])) {
            $newattributes[$attropt]=[];
          }
        }
      }
    }

    $this->dev_log("explicitely deleting attributes ".json_encode($attributes));
    $attributes=$newattributes;
    $attributes["objectclass"]=$ocs;    
    
    return $attributes;
  }

  /**
   * Assemble attributes for an LDAP record.
   *
   * @since  COmanage Registry v0.8
   * @param  Array    $provisioningData         CO Person or CO Group Data used for provisioning
   * @param  Array    $dnAttributes             Attributes used to generate the DN for this person, 
   *                                            as returned by CoLdapProvisionerDn::dnAttributes
   * @return Array Attribute data suitable for passing to ldap_add, etc
   * @throws UnderflowException
   */
  protected function assembleAttributes($provisioningData, $dnAttributes)
  {
    // First see if we're working with a Group record or a Person record
    // If person is set, we are dealing with a CoPerson related provisioning. For
    // strictly CoGroup related provisioning, person is always false
    $person = isset($provisioningData['CoPerson']['id']);
    $group = isset($provisioningData['CoGroup']['id']);

    // Make it easier to see if attribute options are enabled
    $attropts = ($person && Configure::read('fixedldap.attr_opts'));
    //$this->dev_log("attropts is ".($attropts ? "TRUE":"FALSE"));

    $scope_suffix = $this->templateReplace(Configure::read('fixedldap.scope_suffix'), $provisioningData);
    //$this->dev_log("provisioning for person: ".($person?"TRUE":"FALSE")." and group: ".($group?"TRUE":"FALSE")." and scope $scope_suffix");

    // Marshalled attributes ready for export
    $attributes = array();

    // Cached group membership, interim solution for CO-1348 (see below)
    $groupMembers = array();

    $supported = $this->supportedAttributes();
    $configured = $this->configuredAttributes();
    //$this->dev_log('configured is '.json_encode($configured,JSON_PRETTY_PRINT));

    // Note we don't need to check for inactive status where relevant since
    // ProvisionerBehavior will remove those from the data we get.
    foreach (array_keys($supported) as $oc) {
      //$this->dev_log("objectclass $oc");
      // First see if this objectclass is handled by a plugin
      if (!empty($supported[$oc]['plugin'])) {
        // Ask the plugin to assemble the attributes for this objectclass for us.
        // First, get a pointer to the plugin model.
        $pmodel = $this->plugins[ $supported[$oc]['plugin'] ];
        $pattrs = $pmodel->assemblePluginAttributes($configured[$oc], $provisioningData);

        // Filter out any attributes in $pattrs that are not defined in $configured
        $pattrs = array_intersect_key($pattrs, $configured[$oc]);

        // Merge into the marshalled attributes.
        $attributes = array_merge($attributes, $pattrs);

        // Insert an objectclass
        $attributes['objectclass'][] = $oc;

        // Continue the loop (skip the standard processing)
        continue;
      }

      // Skip objectclasses that aren't relevant for the sort of data we're working with
      if (($person && in_array($oc, array('groupOfNames', 'posixGroup')))
         || ($group && !in_array($oc, array('groupOfNames','eduMember','posixGroup')))) {
        //$this->dev_log("skipping class ".$oc." because it is not relevant");
        continue;
      }

      if ($group && empty($groupMembers) && in_array($oc, array('groupOfNames','eduMember','posixGroup'))) {
        // As an interim solution to CO-1348 we'll pull all group members here (since we no longer get them)
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $provisioningData['CoGroup']['id'];
        $args['contain'] = false;

        $groupMembers = $this->CoLdapFixedProvisionerDn->CoGroup->CoGroupMember->find('all', $args);
        //$this->dev_log("retrieved ".sizeof($groupMembers). " group members");
      }
      
      // Iterate across objectclasses. All configured objectclasses are required
      //$this->dev_log("testing class is " .
      //  (($supported[$oc]['objectclass']['required'])?"required and ":"optional and ").
      //  (isset($configured[$oc])?"configured":"not configured"));
      if ($supported[$oc]['objectclass']['required'] || isset($configured[$oc])) {
        // Within the objectclass, iterate across the supported attributes looking
        // for required or enabled attributes. We need to add at least one $attr
        // before we add $oc to the list of objectclasses.
        $attrEmitted = false;

        $this->dev_log("attributes in this class: ".json_encode(array_keys($supported[$oc]['attributes'])));
        foreach (array_keys($supported[$oc]['attributes']) as $attr) {
          //$this->dev_log("testing attr '$attr' is " .
          //  (($supported[$oc]['attributes'][$attr]['required'])?" required and ":" optional and ").
          //  (isset($configured[$oc][$attr])?"configured":"not configured"));
          if ($supported[$oc]['attributes'][$attr]['required'] || isset($configured[$oc][$attr])) {

            $cfg = $supported[$oc]['attributes'][$attr];
            if(isset($configured[$oc][$attr])) {
              $cfg = array_merge($cfg, $configured[$oc][$attr]);
            }
            $cfg['members']=$groupMembers;
            $cfg['scope']=$scope_suffix;
            $cfg['attropts']=$attropts;

            $attribute = $this->generateAttribute($attr, $cfg, $provisioningData);
            //$this->dev_log('attribute '.json_encode($attribute).' generated using '.json_encode($cfg));

            // Make sure to 'clear out' unset attributes in case we do a 'modify'
            if(empty($attribute)) {
              $attribute[$attr]=array();
            }

            $attributes = array_merge($attributes, $attribute);   
            
            // Check if we emitted anything
            $attrEmitted = $attrEmitted || !empty($attribute);                                                         
          }
        }

        // Add $oc to the list of objectclasses if an attribute was emitted, or if
        // the objectclass is required (in which case the LDAP server will likely
        // throw an error if a required attribute is missing).

        if ($attrEmitted || $supported[$oc]['objectclass']['required']) {
          $attributes['objectclass'][] = $oc;
        }
      }
    }

    // Add additionally configured objectclasses
    $group_ocs = Configure::read('fixedldap.group_ocs');
    if (!$group && !empty($group_ocs)) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'], $group_ocs);
    }

    $group_ocs = Configure::read('fixedldap.person_ocs');
    if ($person && !empty($person_ocs)) {
      $attributes['objectclass'] = array_merge($attributes['objectclass'], $person_ocs);
    }

    return $this->checkAttributes($attributes, $dnAttributes);
  }

  /**
   * Template Replace the attribute value
   *
   * @param  String Attribute content
   * @param  Array provisioningData
   * @return String Modified attribute
   */
  private function templateReplace($attribute, $provisioningData) {
    return str_replace(array("{CO}"),array($provisioningData['Co']['name']), $attribute);
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

    // determine if the operation is for a person or for a group
    $person   = false;
    $group    = false;

    if(!isset($provisioningData['Co'])) {
      $data = $this->CoProvisioningTarget->Co->find('first',array("conditions"=>array("Co.id"=>$provisioningData[$person ? "CoPerson": "CoGroup"]['co_id'])));
      $provisioningData['Co'] = $data['Co'];
    }

    $this->dev_log("\r\n*********************************************");
    $this->dev_log("provisioning $op for ".
        (isset($provisioningData['CoPerson']) ? 
            "person ".$provisioningData['CoPerson']['id'] : 
            "group ".$provisioningData['CoGroup']['name']));

    switch ($op) {
    case ProvisioningActionEnum::CoPersonAdded:
      // this state should only apply to a person model
      if(empty($provisioningData['CoPerson']['id'])) {
        $this->log('PersonAdded operation without CoPerson data','debug');
        return true;
      }
      $assigndn = true;
      $modify = true;
      $person = true;
      break;
    case ProvisioningActionEnum::CoPersonDeleted:
      // this state should only apply to a person model
      if(empty($provisioningData['CoPerson']['id'])) {
        $this->log('PersonDeleted operation without CoPerson data','debug');
        return true;
      }
      // Because of the complexity of how related models are deleted and the
      // provisioner behavior invoked, we do not allow dependent=true to delete
      // the DN. Instead, we manually delete it
      $deletedn = true;
      $delete = true;
      $person = true;
      break;
    case ProvisioningActionEnum::CoPersonPetitionProvisioned:
    case ProvisioningActionEnum::CoPersonPipelineProvisioned:
    case ProvisioningActionEnum::CoPersonReprovisionRequested:
      // The Unexpired op can apply to group membership as well, but the CoPerson
      // information must be present
      if(empty($provisioningData['CoPerson']['id'])) {
        $this->log('Person state-change (add) operation without CoPerson data','debug');
        return true;
      }
      // For these actions, there may be an existing record with externally managed
      // attributes that we don't want to change. Treat them all as modifies.
      $assigndn = true;
      $modify = true;
      $person = true;
      break;
    case ProvisioningActionEnum::CoPersonExpired:
    case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
    case ProvisioningActionEnum::CoPersonUpdated:
    case ProvisioningActionEnum::CoPersonUnexpired:
      // These ops can apply to group membership as well, in which case the relevant
      // CoPerson data is marshalled in
      if(empty($provisioningData['CoPerson']['id'])) {
        $this->log('Person state-change (remove) operation without CoPerson data','debug');
        return true;
      }
      if (!in_array(
        $provisioningData['CoPerson']['status'],
        array(StatusEnum::Active)
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
      $person = true;
      break;
    case ProvisioningActionEnum::CoGroupAdded:
      // this state should only apply to a group model
      if(empty($provisioningData['CoGroup']['id'])) {
        $this->log('GroupAdded operation without CoGroup data','debug');
        return true;
      }
      $assigndn = true;
      $add = true;
      break;
    case ProvisioningActionEnum::CoGroupDeleted:
      // this state should only apply to a group model
      if(empty($provisioningData['CoGroup']['id'])) {
        $this->log('GroupDeleted operation without CoGroup data','debug');
        return true;
      }
      $delete = true;
      $deletedn = true;
      $group = true;
      break;
    case ProvisioningActionEnum::CoGroupUpdated:
      // this state is triggered when group members are added or removed
      // so this could apply to both person or group, but the group data
      // should be present
      if(empty($provisioningData['CoGroup']['id'])) {
        $this->log('GroupUpdated operation without CoGroup data','debug');
        return true;
      }
      $assigndn = true;
      $modify = true;
      $group = true;
      break;
    case ProvisioningActionEnum::CoGroupReprovisionRequested:
      // this state should only apply to a group model
      if(empty($provisioningData['CoGroup']['id'])) {
        $this->log('GroupReprovision operation without CoGroup data','debug');
        return true;
      }
      $assigndn = true;
      $delete = true;
      $add = true;
      $group = true;
      break;
    case ProvisioningActionEnum::AuthenticatorUpdated:
    case ProvisioningActionEnum::CoEmailListAdded:
    case ProvisioningActionEnum::CoEmailListDeleted:
    case ProvisioningActionEnum::CoEmailListReprovisionRequested:
    case ProvisioningActionEnum::CoEmailListUpdated:    
    default:
      throw new RuntimeException("Not Implemented");
      break;
    }

    if(!Configure::read('fixedldap')) Configure::load('ldapfixedprovisioner');
    $basedn=Configure::read('fixedldap.basedn');
    $schemata=Configure::read('fixedldap.schemata');
    $this->peopledn = "ou=People,o=" . $this->CoLdapFixedProvisionerDn->escape_dn($provisioningData['Co']['name']) .",".$basedn;
    $this->groupdn="ou=Groups,o=" . $this->CoLdapFixedProvisionerDn->escape_dn($provisioningData['Co']['name']) .",".$basedn;
    
    // 'cache' data on this provisioning object, so we don't have to pass it around to
    // all methods
    $this->targetData = $coProvisioningTargetData;

    if ($group) {
      // If this is a group action and no Group Base DN is defined, or the object class groupOfNames is not generated,
      // then don't try to do anything.
      if (empty($basedn)
          || !is_array($schemata)
          || empty(array_intersect(array('groupOfNames','posixGroup'),$schemata))) {
            $this->dev_log("returning early because '$basedn' not set or groupOfNames and posixGroup not in schema list: ".
                json_encode($schemata));
        return true;
      }
    }

    // for logging purposes, use the COPersonId to make errors traceable
    $cid = $person ? $provisioningData['CoPerson']['id'] : $provisioningData['CoGroup']['id'];
    // Next, obtain a DN for this person or group
    try {
      $type= $person ? 'person' : 'group';
      $dns = $this->CoLdapFixedProvisionerDn->obtainDn(
                $this->targetData,
                $provisioningData,
                $type,
                $assigndn);
      $this->dev_log("retrieved new dns for $type: ".json_encode($dns));
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
        $dnAttributes = $this->CoLdapFixedProvisionerDn->dnAttributes($dns['newdn']);
      } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
      }
     
      // Assemble an LDAP record
      try {
        $attributes = $this->assembleAttributes($provisioningData, $dnAttributes);
        $this->dev_log("object id $cid attributes found: ".json_encode($attributes));
      } catch (UnderflowException $e) {
        // We have a group with no members. Convert to a delete operation since
        // empty groups are meaningless
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
    if (!$this->connectLdap($url, $binddn, $password)) {
      $this->dev_log("failed to provision due to missing connection");
      return false;
    }

    // make sure the CO organization and Groups and People OUs exist so we can put objects under them
    $this->verifyOrCreateCo($url, $binddn, $password, $basedn, $provisioningData['Co']['name']);

    if ($delete) {
      $this->dev_log('Delete operation');
      // Delete any previous entry. For now, ignore any error.
      if ($rename || !$dns['newdn']) {
        // Use the old DN if we're renaming or if there is no new DN
        // (which should be the case for a delete operation).
        if(!$this->ldap_delete($dns['olddn'])) {
          $this->dev_log("Delete of old entry: ".$this->ldap_error() ." (".$this->ldap_errno() .", coperson: $cid)");
        }
      } else {
        // It's actually not clear when we'd get here -- perhaps cleaning up
        // a record that exists in LDAP even though it's new to Registry?
        if(!$this->ldap_delete($dns['newdn'])) {
          $this->dev_log("Delete of new entry: ".$this->ldap_error() ." (".$this->ldap_errno() .", coperson: $cid)");
        }
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
        $this->ldap_unbind();
        return true;
      }

      // Perform the rename operation before we try to do anything else. Note that
      // the old DN is complete while the new DN is relative.
      if ($person) {
        $basedn = $this->peopledn;
      } else if ($group) {
        $basedn = $this->groupdn;
      }

      $newrdn = rtrim(str_replace($basedn, "", $dns['newdn']), " ,");

      if (!$this->ldap_rename($dns['olddn'], $newrdn)) {
        // XXX We should probably try to reset CoLdapFixedProvisionerDn here since we're
        // now inconsistent with LDAP
        $this->log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", coperson: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", coperson: $cid)");
        $this->ldap_unbind();
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
        $this->ldap_unbind();
        return true;
      }
      if (!$this->ldap_mod_replace($dns['newdn'], $attributes)) {
        if ($this->ldap_errno() == 0x20 /*LDAP_NO_SUCH_OBJECT*/) {
          // Change to an add operation.
          $this->dev_log('changing to ADD operation');
          $add = true;
        } else {
          $this->log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . 
              " (".$this->ldap_errno() .", coperson: $cid)", 'error');
          $this->dev_log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . 
              " (".$this->ldap_errno() .", coperson: $cid)");
          $this->ldap_unbind();
          return false;
        }
      }
      else
      {
        $this->dev_log("replace was succesful");
      }
    }

    if ($add) {
      $attributes=$this->removeEmptyAttributes($attributes);
      // Write a new entry
      if (!$dns['newdn']) {
        // silently ignore cases where we do not have a valid LDAP DN
        $this->log(_txt('er.ldapfixedprovisioner.rename1')." (coperson: $cid)", 'debug');
        $this->ldap_unbind();
        return true;
      }

      if (!$this->ldap_add($dns['newdn'], $attributes)) {
        $this->log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", coperson: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", coperson: $cid)");
        $this->ldap_unbind();
        return false;
      }
      else
      {
        $this->dev_log("add was succesful");
      }
    }

    // if we are provisioning (or deprovisioning) a group, check to see if it is part of a COU
    // If so, we might need to provision (or deprovision) the COU
    if($group) {
      $this->dev_log('group provisioning, so testing for cou_id in '.json_encode($provisioningData["CoGroup"]));
      if(!empty($provisioningData['CoGroup']['cou_id'])) {
        $this->dev_log('Provisioning a COU');
        $this->provisionCOU($provisioningData, $op);
      }
      else {
        $this->dev_log('Provisioning CO as top-group');
        // this is a top-group. Make sure we add it to our ou=Groups object as a member
        $this->provisionCO($provisioningData, $op);
      }

      // Also, if we are renaming a group, make sure all the isMemberOf entries of all group members are
      // updated as well
      if($rename) {
        $this->dev_log("group rename, so checking for member attributes");
        $this->updateMemberAttribute($provisioningData, $dns['olddn'],$dns['newdn']);
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
      $this->log(_txt('er.ldapfixedprovisioner.bind').": ".$this->ldap_error() . 
          " (".$this->ldap_errno() .")", 'error');
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
      $this->log(_txt('er.ldapfixedprovisioner.query').": ".$this->ldap_error() . 
          " (".$this->ldap_errno() .")", 'error');
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
   * @param  Model   $Model                  Model being queried for status (eg: CoPerson, CoGroup, CoEmailList)
   * @param  Integer $id                     $Model ID to check status for
   * @return Array ProvisioningStatusEnum, Timestamp of last update in epoch seconds, Comment
   */
  public function status($coProvisioningTargetId, $Model, $id)
  {
    $this->dev_log('lfp status for '.json_encode($Model)." and ".json_encode($id));
    $ret = array(
      'status'    => ProvisioningStatusEnum::Unknown,
      'timestamp' => null,
      'comment'   => ""
    );

    if(!Configure::read('fixedldap')) Configure::load('ldapfixedprovisioner');

    // Pull the DN for this person, if we have one.
    // Cake appears to correctly figure out the join (because no contain?)
    $args = array();
    $args['conditions']['CoLdapFixedProvisionerDn.' . Inflector::underscore($Model->name) . '_id'] = $id;
    $args['conditions']['CoLdapFixedProvisionerTarget.co_provisioning_target_id'] = $coProvisioningTargetId;
    
    $dnRecord = $this->CoLdapFixedProvisionerDn->find('first', $args);
    $this->dev_log('dnRecord is '.json_encode($dnRecord));

    if (!empty($dnRecord)) {
      // Query LDAP and see if there is a record
      try {
        $url=Configure::read('fixedldap.server.url');
        $binddn=Configure::read('fixedldap.server.binddn');
        $password=Configure::read('fixedldap.server.password');
        $ldapRecord = $this->queryLdap($url, 
                                       $binddn, 
                                       $password, 
                                       $dnRecord['CoLdapFixedProvisionerDn']['dn'], 
                                       "(objectclass=*)", 
                                       array('modifytimestamp'));

        if (!empty($ldapRecord)) {
          // Get the last provision time from the parent status function
          $pstatus = parent::status($coProvisioningTargetId, $Model, $id);

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
    // cache the results in an object variable so we can reuse it
    if(isset($this->_supportedAttributes) && !empty($this->_supportedAttributes)) {
      return $this->_supportedAttributes;
    }
    
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
            'multiple'    => true
          ),
          'cn' => array(
            'required'    => true,
            'multiple'    => true
          ),
          'userPassword' => array(
            'required'    => false,
            'multiple'    => true
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
          'labeledURI' => array(
            'required'    => false,
            'multiple'    => true,
            'extendedtype' => 'url_types',
            'defaulttype' => UrlEnum::Official
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
            'multiple'    => true
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
          ),
          'o' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'ou' => array(
            'required'    => false,
            'multiple'    => false
          ),
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
            'multiple'  => true
          )
        )
      ),
      'posixAccount' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'uid' => array(
            'required'   => true,
            'multiple'   => false,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          ),
          'cn' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'description' => array(
            'required'    => false,
            'multiple'    => false
          ),
          'uidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'gidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'userPassword' => array(
            'required'    => false,
            'multiple'    => true
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
      'posixGroup' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'cn' => array(
            'required'   => true,
            'multiple'   => true
          ),
          'gidNumber' => array(
            'required'   => true,
            'multiple'   => false
          ),
          'userPassword' => array(
            'required'    => false,
            'multiple'    => true
          ),
          'memberUID' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'description' => array(
            'required'    => false,
            'multiple'    => false
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
          ),
          'uid' => array(
            'required'    => true,
            'multiple'    => true,
            'alloworgvalue' => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          )
        )
      ),
      'voPerson' => array(
        'objectclass' => array(
          'required'    => false
        ),
        'attributes' => array(
          'voPersonApplicationUID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::UID
          ),
          'voPersonAuthorName' => array(
            'required'    => false,
            'multiple'    => true,
            'typekey'     => 'en.name.type',
            'defaulttype' => NameEnum::Author
          ),
          'voPersonCertificateDN' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonCertificateIssuerDN' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonExternalID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::ePPN
          ),
          'voPersonID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::Enterprise
          ),
          'voPersonPolicyAgreement' => array(
            'required'   => false,
            'multiple'   => true
          ),
          'voPersonSoRID' => array(
            'required'  => false,
            'multiple'  => true,
            'extendedtype' => 'identifier_types',
            'defaulttype' => IdentifierEnum::SORID
          ),
          'voPersonStatus' => array(
            'required'   => false,
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

    $this->_supportedAttributes = $attributes;
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
    // cache the results so we can easily reuse it instead of having to reparse the
    // configuration
    if(isset($this->_configuredAttributes) && !empty($this->_configuredAttributes)) {
      return $this->_configuredAttributes;
    }
  
    $ocs = array();
    $schemata=Configure::read('fixedldap.schemata');
    if (is_array($schemata)) {
      foreach ($schemata as $oc) {
        $ocs[$oc]=array();
      }
    }

    $supported = $this->supportedAttributes();

    // Rework the attribute definitions into something resembling a row from the LdapProvisionerAttribute model
    // We need this to accomodate our ldapschema plugins
    foreach ($ocs as $oc=>$lst) {
      $attributes = Configure::read('fixedldap.'.$oc);
      //$this->dev_log('read configuration for '.$oc.': '.json_encode($attributes));
      if (is_array($attributes)) {
        foreach ($attributes as $key=>$val) {
          // extract type and origin from the value definition
          $type= "";
          $use_org=false;

          if(isset($supported[$oc]) && isset($supported[$oc]['attributes'][$key])) {
            if(isset($supported[$oc]['attributes'][$key]['defaulttype'])) {
              $type = $supported[$oc]['attributes'][$key]['defaulttype'];
            }
            if(isset($supported[$oc]['attributes'][$key]['alloworgvalue'])
               && $supported[$oc]['attributes'][$key]['alloworgvalue']) {
              $use_org=true;
            }
          }

          //$this->dev_log('read value '.$key.'='.$val);
          if (is_string($val)) {
            $values = explode(';', $val, 2);
            //$this->dev_log("values array is ".json_encode($values));
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
    
    $this->_configuredAttributes = $ocs;
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
    $this->peopledn = "ou=People,o=" . $this->CoLdapFixedProvisionerDn->escape_dn($co).",".$basedn;
    $this->groupdn="ou=Groups,o=" . $this->CoLdapFixedProvisionerDn->escape_dn($co).",".$basedn;
    $this->verifyOrCreateCo($url, $binddn, $password, $basedn, $co);

    $results = $this->queryLdap($url, $binddn, $password, $this->peopledn, "(objectclass=*)", array("dn"));

    if (count($results) < 1) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.basedn'));
    }

    // Check for a Group DN if one is configured
    $results = $this->queryLdap($url, $binddn, $password, $this->groupdn, "(objectclass=*)", array("dn"));
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
  private function verifyOrCreateCo($url, $binddn, $password, $basedn, $coData)
  {
    $dn = "o=".$this->CoLdapFixedProvisionerDn->escape_dn($coData).",$basedn";
    $retval=array("","");

    if (!$this->connectLdap($url, $binddn, $password)) {
      return $retval;
    }
    
    $this->dev_log('verifying CO organization');
    $attributes = array("o"=>$coData,"objectClass"=>"organization");
    if (!$this->ldap_add($dn,$attributes)) {        
      if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
        $this->dev_log("error adding CO as organization: ".json_encode($dn)." ".json_encode($attributes)." ".$this->ldap_error());
        $this->log(_txt('er.ldapfixedprovisioner.add1').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
        return $retval;
      }
    }

    // to accomodate situations were the tree is partially removed, we test for the other objects as well
    // just to be sure 

    $attributes=array("ou"=>"People","objectClass"=>"organizationalUnit");
    if (!$this->ldap_add($this->peopledn, $attributes )) {
      if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
        $this->dev_log("error adding People group as organizationalunit: ".json_encode($this->peopledn)." ".json_encode($attributes)." ".$this->ldap_error());
        $this->log(_txt('er.ldapfixedprovisioner.add2').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
        return $retval;
      }
    }

    $attributes = array("ou"=>"Groups","objectClass"=>array("organizationalUnit"));
    if (!$this->ldap_add($this->groupdn, $attributes)) {
      if ($this->ldap_errno() != 0x44 /* LDAP_ALREADY_EXISTS */) {
        $this->dev_log("error adding Groups group as organizationalunit: ".json_encode($this->groupdn)." ".json_encode($attributes)." ".$this->ldap_error());
        $this->log(_txt('er.ldapfixedprovisioner.add3').": ".$this->ldap_error() . " (".$this->ldap_errno() .")", 'error');
        return $retval;
      }
    }
    $this->dev_log('verify success');
  }

  /**
   * Provision or Deprovision a COU
   * Based on the provisioning operation, we try to create a COU group with 
   * relevant members, or delete the object
   *
   * @since  COmanage Registry vTODO
   * @param  String $basedn             Group DN for this CO
   * @param  Array provisioningData     Array of provisioning data
   * @param  ProvisioningActionEnum op  Provisioning operation
   * @return Boolean True
   */
  private function provisionCOU($provisioningData, $op)
  {
    $this->dev_log("provisioning COU");
    $args = array();
    $args['conditions']['Cou.id'] = $provisioningData['CoGroup']['cou_id'];
    $args['contain'] = false;
    $cou = $this->CoLdapFixedProvisionerDn->CoGroup->Cou->find('first', $args);
    
    // no COU found?
    if(empty($cou) || !isset($cou['Cou'])) {
      $this->dev_log("no such COU");
      return;
    }
    // copy the main CO object
    $cou['Co']=$provisioningData['Co'];

    // configure the attributes we are going to generate for a COU
    $cfg = array(
      "cn" => array("oc"=>"groupOfNames"),
      "member" => array("oc"=>"groupOfNames"),
      "owner" => array("oc"=>"groupOfNames"),
      "description" => array("oc"=>"groupOfNames"),
      "o" => array("oc"=>"groupOfNames"),
      "ou" => array("oc"=>"groupOfNames")
    );

    // transform the configuration into a set usable by generateAttribute
    $configured = array();
    foreach($cfg as $attr => $val) {
      if(!isset($configured[$val["oc"]])) $configured[$val["oc"]] = array();
      $configured[$val["oc"]][$attr] = array(
            'attribute' => $attr,
            'objectclass' => $val['oc'],
            'type' => isset($val['type']) ? $val["type"] : TRUE,
            'export' => 1,
            'use_org_value' => 0,
            'id' => -1,
            'co_ldap_fixed_provisioner_id' => -1,
            'created' => '1970-01-01 01-01-01',
            'modified' => '1970-01-01 01-01-01'
          );
    }

    switch($op) {
    case ProvisioningActionEnum::CoGroupAdded:
    case ProvisioningActionEnum::CoGroupReprovisionRequested:
    case ProvisioningActionEnum::CoGroupUpdated:
      return $this->addCOU($cou, $configured);
      break;
    case ProvisioningActionEnum::CoGroupDeleted:
      return $this->deleteCOU($cou, $configured);
      break;
    default:
      break;
    }
  }
  
  /**
   * Add a COU
   * Assemble COU attributes and provision it
   *
   * @since  COmanage Registry vTODO
   * @param  Array  $cou             COU or CO data
   * @param  String $groupdn         base DN to put COU under
   * @param  Array  $objectclasses   attribute configuration
   * @param  Array  $child           if set, this is a COU that is deprovisioned
   * @return Boolean True
   */
  private function addCOU($cou, $objectclasses, $child=null) {
    $this->dev_log("adding COU using ".json_encode($objectclasses));
    if(!empty($cou['Cou']['parent_id'])) {
      $args = array();
      $args['conditions']['Cou.id'] = $cou['Cou']['parent_id'];
      $args['contain'] = false;
      $parent = $this->CoLdapFixedProvisionerDn->Cou->find('first', $args);
      if(!empty($parent)) {
        $this->dev_log("adding parent first");
        $parent['Co']=$cou['Co'];
        $this->addCOU($parent, $objectclasses);
      } 
    }

    $is_cou = isset($cou['Cou']['id']);
    $cid = $is_cou ? $cou['Cou']['id'] : $cou['Co']['id'];
    $attropts = false;
    $scope_suffix = $this->templateReplace(Configure::read('fixedldap.scope_suffix'), $cou);

    $supported = $this->supportedAttributes();
    $attributes = array();

    // always get the list of members
    $groupMembers = array();
    if($is_cou) {
      $args = array();
      $args['conditions']['Cou.parent_id'] = $cid;
      $args['contain'] = false;
      $groupMembers = $this->CoLdapFixedProvisionerDn->Cou->find('all', $args);
    
    }
    else {
      $args = array();
      $args['conditions']['Cou.parent_id'] = null;
      $args['conditions']['Cou.co_id'] = $cid;
      $args['contain'] = false;
      $groupMembers = $this->CoLdapFixedProvisionerDn->Cou->find('all', $args);
    }

    // if we have a child passed along, this is a member we are deprovisioning.
    // Make sure it is not part of the groupMembers list, as the database update
    // might still be pending (we are removing the CoGroups first, then the COU
    // object and the sequence of provisioning is not defined)
    if($child !== null) {
      for($i=0;$i<sizeof($groupMembers);$i++) {
        if($groupMembers[$i]['Cou']['id'] == $child['Cou']['id']) {
          unset($groupMembers[$i]);
        }
      }
    }
    
    // combine the groupMembers with a list of subgroups
    $args = array();
    if($is_cou) {
      $args['conditions']['CoGroup.cou_id'] = $cid;
    }
    else {
      $args['conditions']['CoGroup.cou_id'] = null;
      $args['conditions']['CoGroup.co_id'] = $cid;
    }
    $args['contain'] = false;
    $groupMembers = array_merge($groupMembers, $this->CoLdapFixedProvisionerDn->CoGroup->find('all', $args));
    
    $this->dev_log('group members of COU are '.json_encode($groupMembers));    
    
    // Note we don't need to check for inactive status where relevant since
    // ProvisionerBehavior will remove those from the data we get.
    foreach (array_keys($supported) as $oc) {
      $this->dev_log("objectclass $oc");
      // no support for plugins here      

      // Iterate across objectclasses. All configured objectclasses are required
      if (isset($objectclasses[$oc])) {
        // Within the objectclass, iterate across the supported attributes looking
        // for required or enabled attributes. We need to add at least one $attr
        // before we add $oc to the list of objectclasses.
        $attrEmitted = false;
        $this->dev_log('emitting objectclass '.$oc);
        foreach (array_keys($supported[$oc]['attributes']) as $attr) {
          if (isset($objectclasses[$oc][$attr])) {
            $this->dev_log("generating attribute ".$attr);

            $cfg = $supported[$oc]['attributes'][$attr];
            if(isset($objectclasses[$oc][$attr])) {
              $cfg = array_merge($cfg, $objectclasses[$oc][$attr]);
            }
            $cfg['members']=$groupMembers;
            $cfg['scope'] = $scope_suffix;
            $cfg['attropts']=$attropts;
            try
            {
              $attribute = $this->generateAttribute($attr, $cfg, $cou);
              $this->dev_log("attribute $attr returns ".json_encode($attribute));
            } catch (UnderflowException $e) {
              // We have a group with no members. Convert to a delete operation since
              // groupOfNames requires at least one member.
              if($is_cou) {                
                return $this->deleteCOU($cou);
              }
              else {
                // never delete a CO due to missing members... it is just not possible
                return false;
              }
            }            
            
            // if we have an empty attribute, add an empty array so we
            // can delete the attribute on a modify operation
            if(empty($attribute)) {
              $attribute[$attr]=array();
            }

            $attributes = array_merge($attributes, $attribute);   
            
            // Check if we emitted anything
            $attrEmitted = $attrEmitted || !empty($attribute);
                                                         
          }
        }

        // Add $oc to the list of objectclasses if an attribute was emitted, or if
        // the objectclass is required (in which case the LDAP server will likely
        // throw an error if a required attribute is missing).

        if ($attrEmitted) {
          $attributes['objectclass'][] = $oc;
        }
      }
    }
    
    $this->dev_log("attributes for COU are ".json_encode($attributes));

    // obtain a DN for this object
    try {
      if($is_cou) {
        $this->dev_log("fetching DN for COU");
        $dns = $this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $cou,"cou",true);
      }
      else {
        $this->dev_log("setting DN based on name and groupdn $this->groupdn");
        $dn=$this->coDn($cou);
        $dns = array('olddn' => $dn, 'newdn' => $dn);
      }
      $this->dev_log("retrieved new dns for COU: ".json_encode($dns));
    } catch (RuntimeException $e) {
      $this->dev_log("error retrieving DN: ".$e->getMessage());
      // This mostly never matches because $dns['newdnerr'] will usually be set
      throw new RuntimeException($e->getMessage());
    }

    $rename=false;
    // We might have to handle a rename if the DN changed
    if ($dns['olddn'] && $dns['newdn'] && ($dns['olddn'] != $dns['newdn'])) {
      $rename = true;
    }

    if ($dns['newdn']) {
      // Find out what attributes went into the DN to make sure they got populated into
      // the attribute array
      try {
        $dnAttributes = $this->CoLdapFixedProvisionerDn->dnAttributes($dns['newdn'],$is_cou ? 'cou' : 'co');
        $this->dev_log("dnAttributes for COU are ".json_encode($dnAttributes));
      } catch (RuntimeException $e) {
        throw new RuntimeException($e->getMessage());
      }
    }
    $attributes = $this->checkAttributes($attributes, $dnAttributes);
    $this->dev_log("checked attributes for COU are ".json_encode($attributes));
   
    if ($is_cou && $rename) {
      if (!$dns['newdn']) {
        // silently ignore cases where we do not have a valid LDAP DN
        $this->log(_txt('er.ldapfixedprovisioner.rename1')." (cou: $cid)", 'debug');
        $this->ldap_unbind();
        return true;
      }

      // Perform the rename operation before we try to do anything else. Note that
      // the old DN is complete while the new DN is relative.
      $newrdn = rtrim(str_replace($this->groupdn, "", $dns['newdn']), " ,");

      if (!$this->ldap_rename($dns['olddn'], $newrdn)) {
        // XXX We should probably try to reset CoLdapFixedProvisionerDn here since we're
        // now inconsistent with LDAP
        $this->log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", cou: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.rename2').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", cou: $cid)");
        return false;
      }
      else {
        $this->dev_log("rename was succesful");
      }
    }

    // we can now try a modify
    if (!$dns['newdn']) {
      // silently ignore cases where we do not have a valid LDAP DN
      $this->log(_txt('er.ldapfixedprovisioner.rename1')." (cou: $cid)", 'debug');
      return true;
    }

    if (!$this->ldap_mod_replace($dns['newdn'], $attributes)) {
      // if we receive an LDAP_NO_SUCH_OBJECT for a COU, create it. 
      if ($this->ldap_errno() == 0x20) {
        $this->dev_log('modify of '.$dns['newdn'].' failed with no-such-object, trying add');
        $attributes = $this->removeEmptyAttributes($attributes);
        $this->dev_log('add attributes are '.json_encode($attributes));
        if (!$this->ldap_add($dns['newdn'], $attributes)) {
          $this->log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . 
              " (".$this->ldap_errno() .", cou: $cid)", 'error');
          $this->dev_log(_txt('er.ldapfixedprovisioner.add').": ".$this->ldap_error() . 
              " (".$this->ldap_errno() .", cou: $cid)");
          return false;
        }
        else
        {
          $this->dev_log("add was succesful");
        }
      } else {
        $this->log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", cou: $cid)", 'error');
        $this->dev_log(_txt('er.ldapfixedprovisioner.modify').": ".$this->ldap_error() . 
            " (".$this->ldap_errno() .", cou: $cid)");
        return false;
      }
    }
    else
    {
      $this->dev_log("replace was succesful");
    }
    return true;
  }

  /**
   * Delete a COU
   * Delete a COU from LDAP
   *
   * @since  COmanage Registry vTODO
   * @param  Array  $cou             COU data
   * @param  String $groupdn         base DN to delete COU from
   * @return Boolean True
   */
  private function deleteCOU($cou, $configured) {
    // deleting a COU will not provision the parent groups, as 
    // the group memberships do not change. So we need to update the
    // parent COU
    if(!empty($cou['Cou']['parent_id'])) {
      $args = array();
      $args['conditions']['Cou.id'] = $cou['Cou']['parent_id'];
      $args['contain'] = false;
      $parent = $this->CoLdapFixedProvisionerDn->Cou->find('first', $args);
      if(!empty($parent)) {
        $parent['Co']=$cou['Co'];
        $this->addCOU($parent, $configured, $cou);
      } 
    }

    $cid = $cou['Cou']['id'];

    // obtain a DN for this object
    try {
      $dns = $this->CoLdapFixedProvisionerDn->obtainDn($this->targetData, $cou,"cou",true);
      $this->dev_log("retrieved new dns for COU: ".json_encode($dns));
    } catch (RuntimeException $e) {
      // This mostly never matches because $dns['newdnerr'] will usually be set
      throw new RuntimeException($e->getMessage());
    }

    // delete. We do not care if this succeeds or not, but we log the error
    if(!$this->ldap_delete($dns['olddn'])) {
      $this->log(_txt('er.ldapfixedprovisioner.delete').": ".$this->ldap_error() . 
          " (".$this->ldap_errno() .", cou: $cid)", 'error');
      $this->dev_log(_txt('er.ldapfixedprovisioner.delete').": ".$this->ldap_error() . 
          " (".$this->ldap_errno() .", cou: $cid)");
    }
    
    // Delete the old DN from the database. (It's not done via dependency to ensure
    // we have it when we finally delete the record.)
    if ($dns['olddnid']) {
      $this->CoLdapFixedProvisionerDn->delete($dns['olddnid']);
    }

    return true;
  }

  /**
   * Provision or Deprovision a CO
   * Based on the provisioning operation, we need to replace the relevant Groups OU object 
   *
   * @since  COmanage Registry vTODO
   * @param  String $basedn             Group DN for this CO
   * @param  Array provisioningData     Array of provisioning data
   * @param  ProvisioningActionEnum op  Provisioning operation
   * @return Boolean True
   */
  private function provisionCO($provisioningData, $op)
  {
    $this->dev_log("provisioning CO");
    
    // configure the attributes we are going to generate for the CO
    $cfg = array(
      "cn" => array("oc"=>"groupOfNames"),
      "member" => array("oc"=>"groupOfNames"),
      "owner" => array("oc"=>"groupOfNames"),
      "description" => array("oc"=>"groupOfNames"),
      "o" => array("oc"=>"groupOfNames"),
      "ou" => array("oc"=>"groupOfNames")
    );

    // transform the configuration into a set usable by generateAttribute
    $configured = array();
    foreach($cfg as $attr => $val) {
      if(!isset($configured[$val["oc"]])) $configured[$val["oc"]] = array();
      $configured[$val["oc"]][$attr] = array(
            'attribute' => $attr,
            'objectclass' => $val['oc'],
            'type' => isset($val['type']) ? $val["type"] : TRUE,
            'export' => 1,
            'use_org_value' => 0,
            'id' => -1,
            'co_ldap_fixed_provisioner_id' => -1,
            'created' => '1970-01-01 01-01-01',
            'modified' => '1970-01-01 01-01-01'
          );
    }

    switch($op) {
    case ProvisioningActionEnum::CoGroupAdded:
    case ProvisioningActionEnum::CoGroupReprovisionRequested:
    case ProvisioningActionEnum::CoGroupUpdated:
      $codata = array('Co'=>$provisioningData['Co']);
      return $this->addCOU($codata, $configured);
      break;
    // there can never be a delete operation on a CO in this way
    default:
      break;
    }
  }

  // convenience function to quickly check if a certain attribute from an objectclass
  // is actually configured for export
  private function attributeEnabled($oc, $attr) {
    $supported = $this->supportedAttributes();
    $configured = $this->configuredAttributes();

    $this->dev_log("checking for $oc.$attr");
    if(in_array($oc,array_keys($supported))) {
      $this->dev_log("in supported ocs");
      if ($supported[$oc]['objectclass']['required'] || isset($configured[$oc])) {
        $this->dev_log("objectclass required or configured");
        if(in_array($attr, array_keys($supported[$oc]["attributes"]))) {
          $this->dev_log("attribute in supported oc");
          return $supported[$oc]['attributes'][$attr]['required'] || isset($configured[$oc][$attr]);
        }
      }
    }
    return FALSE;
  }

  // Update group names of group members in case of group DN renaming
  //
  // In case the Group is provisioned due to a rename, the Group DN changes. However, the CoPerson
  // records are not reprovisioned. The reverse is true: if a CoPerson Identifier changes, all the
  // groups of that CoPerson are reprovisioned as well, just in case the DN changes.
  //
  // We determine all group members and replace their relevant attribute
  // COU and COs are already reprovisioned when a group is provisioned, so the member attributes
  // of those entries have been updated

  private function updateMemberAttribute($group, $olddn, $newdn) {
    $isMemberOfEnabled = $this->attributeEnabled('eduMember','isMemberOf');

    if($isMemberOfEnabled) {
      $this->dev_log("IsMemberOf is enabled, replacing group DN");
      $oldattrs=array("IsMemberOf"=>$olddn);
      $newattrs=array("IsMemberOf"=>$newdn);

      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $group['CoGroup']['id'];
      $args['contain'] = false;

      $groupMembers = $this->CoLdapFixedProvisionerDn->CoGroup->CoGroupMember->find('all', $args);
      $dns = $this->CoLdapFixedProvisionerDn->dnsForMembers($groupMembers, false);

      foreach($dns as $dn) {
        $this->dev_log("trying del/add on ".$dn);
        if($this->ldap_mod_del($dn, $oldattrs)) {
          $this->dev_log("del succeeded");
          $this->ldap_mod_add($dn, $newattrs);
        }
        $this->dev_log($this->ldap_error());
      }
    }
  }

  // convenience function to enable/disable the development/trace logs
  private function dev_log($msg)
  {
    //CakeLog::write('debug',$msg);
  }
}
