<?php
/**
 * COmanage Registry CO LDAP Fixed Provisioner DN Model
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

class CoLdapFixedProvisionerDn extends AppModel {
  // Define class name for cake
  public $name = "CoLdapFixedProvisionerDn";

  // Add behaviors
  public $actsAs = array('Containable');

  // Association rules from this model to other models
  public $belongsTo = array(
    "LdapFixedProvisioner.CoLdapFixedProvisionerTarget",
    "CoPerson",
    "CoGroup",
    "Co",
    "Cou"
  );

  // Default display field for cake generated views
  public $displayField = "dn";

  // Validation rules for table elements
  public $validate = array(
    'co_ldap_fixed_provisioner_target_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO LDAP Fixed Provisioning Target ID must be provided'
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_group_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'co_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'cou_id' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'dn' => array(
      'rule' => 'notBlank'
    )
  );

  public $_cache=array("Co"=>array(),"Cou"=>array(),"CoGroup"=>array(),'CoPerson'=>array());  
  /**
   * Assign a DN for a CO.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  Array CO data
   * @return String DN
   * @throws RuntimeException
   */

  public function assignCoDn($coProvisioningTargetData, $coData) {
    $dn = "";

    // For now, we always construct the DN using cn.
    if(empty($coData['Co']['name'])) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.component', 'o'));
    }

    $basedn = Configure::read('fixedldap.basedn');
    if(empty($basedn)) {
      // Throw an exception... this should be defined
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.config'));
    }

    $dn = "o=" . $this->escape_dn($coData['Co']['name']) . ",".$basedn;

    return $dn;
  }


  /**
   * Assign a DN for a COU.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  Array COU data
   * @return String DN
   * @throws RuntimeException
   */

  public function assignCouDn($coProvisioningTargetData, $couData) {
    $dn = "";
    // For now, we always construct the DN using cn.
    if(empty($couData['Cou']['name'])) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.component', 'cn'));
    }

    $basedn = $this->obtainDn($coProvisioningTargetData, $couData, "co",true);
    if(empty($basedn)) {
      // Throw an exception... this should be defined
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.config'));
    }

    $dn = "cn=" . $this->escape_dn($this->CoLdapFixedProvisionerTarget->prefix('cou').$couData['Cou']['name']) . ",ou=Groups,".$basedn['newdn'];

    return $dn;
  }

  /**
   * Assign a DN for a CO Group.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  Array CO Group data
   * @return String DN
   * @throws RuntimeException
   */

  public function assignGroupDn($coProvisioningTargetData, $coGroupData) {
    $dn = "";

    // For now, we always construct the DN using cn.
    if(empty($coGroupData['CoGroup']['name'])) {
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.component', 'ou'));
    }

    $basedn = $this->obtainDn($coProvisioningTargetData, $coGroupData, "co",true);
    if(empty($basedn)) {
      // Throw an exception... this should be defined
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.config'));
    }

    $dn = "cn=" . $this->escape_dn($this->CoLdapFixedProvisionerTarget->prefix('group'). $coGroupData['CoGroup']['name']) . ",ou=Groups,".$basedn['newdn'];
    return $dn;
  }

  

  /**
   * Get a specific identifier type from a list
   *
   * @since  COmanage Registry vTODO
   * @param  Array List of identifiers
   * @param  string identifier type
   * @return String identifier value
   */
  private function getIdentifierType($identifiers, $type) 
  {
    foreach($identifiers as $identifier) {
      if(!empty($identifier['type'])
         && $identifier['type'] == $type
         && !empty($identifier['identifier'])
         && $identifier['status'] == StatusEnum::Active) {
        return $identifier['identifier'];
      }
    }
    return null;
  }

  /**
   * Assign a DN for a CO Person.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  Array CO Person data
   * @return String DN
   * @throws RuntimeException
   */

  public function assignPersonDn($coProvisioningTargetData, $coPersonData) {
    // Start by checking the DN configuration
    $dn_attribute_name = Configure::read('fixedldap.dn_attribute_name');
    $dn_identifier_type = Configure::read('fixedldap.dn_identifier_type');
    $basedn = $this->obtainDn($coProvisioningTargetData, $coPersonData, "co",true);
    if(empty($dn_attribute_name) || empty($basedn)) {
      // Throw an exception... these should be defined
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.config'));
    }

    // Walk through available identifiers looking for a match

    $dn = "";
    $uid = $this->getIdentifierType($coPersonData['Identifier'],$dn_identifier_type);
    if(empty($uid))
    {
      if(!empty($coPersonData['CoOrgIdentityLink'])) {
        foreach($coPersonData['CoOrgIdentityLink'] as $lnk)
        {
          if(!empty($lnk['OrgIdentity']) && !empty($lnk['OrgIdentity']['Identifier']))
          {
            $uid = $this->getIdentifierType($lnk['OrgIdentity']['Identifier'],$dn_identifier_type);
            if(!empty($uid)) break;
          }
        }
      }
    }
    if(!empty($uid))
    {
      // Match. We'll use the first active row found... it's undefined how to behave
      // if multiple active identifiers of a given type are found. (We don't actually
      // need to check for Status=Active since ProvisionerBehavior will filter out
      // non-Active status.)
      $dn = $dn_attribute_name . "=" . $this->escape_dn($uid) . ",ou=People," . $basedn['newdn'];
    }

    if($dn == "") {
      // We can't proceed without a DN
      throw new RuntimeException(_txt('er.ldapfixedprovisioner.dn.component', array($dn_identifier_type)));
    }
    return $dn;
  }

  /**
   * Determine the attributes used to generate a DN.
   *
   * @since  COmanage Registry vTODO
   * @param  String DN
   * @param  String Mode ('group' or 'person')
   * @return Array Attribute/value pairs used to generate the DN, not including the base DN
   * @throws RuntimeException
   */

  public function dnAttributes($dn) {
    // We assume dn is of the form attr1=val1, attr2=val2, basedn
    // Strip off basedn and then split up the remaining string. 
    // Note we'll fail if the base DN changes. Currently, that 
    // would require manual cleanup.

    $ret = array();

    $basedn = Configure::read('fixedldap.basedn');
    $attrs = explode(",", rtrim(str_replace($basedn, "", $dn), " ,"));

    foreach($attrs as $a) {
      $av = explode("=", $a, 2);

      $ret[ $av[0] ] = $this->unescape_dn($av[1]);
    }

    return $ret;
  }

  /**
   * Map a set of CO Group Members to their DNs.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Group Members
   * @return Array Array of DNs found -- note this array is not in any particular order, and may have fewer entries
   */

  public function dnsForMembers($coGroupMembers, $stripuid=false) {
    return $this->mapCoGroupMembersToDns($coGroupMembers, false, $stripuid);
  }

  /**
   * Map a set of CO Group Member owners to their DNs.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Group Members
   * @return Array Array of DNs found -- note this array is not in any particular order
   */

  public function dnsForOwners($coGroupMembers, $stripuid=false) {
    return $this->mapCoGroupMembersToDns($coGroupMembers, true, $stripuid);
  }


  /**
   * Map a COU to a set of administrators
   *
   * @since  COmanage Registry vTODO
   * @param  Array COU or CO object
   * @param  Bool stripuid  if set, strips off all but the first attribute of a DN
   * @return Array Array of DNs found -- note this array is not in any particular order
   */

  public function dnsForAdmins($cou,$stripuid=false) {
    // the owners are the members of the related admin group
    $args = array();
    $args['conditions']['CoGroup.cou_id'] =$cou['Co']['id'];
    $args['conditions']['CoGroup.cou_id'] =isset($cou['Cou']) ? $cou['Cou']['id'] : null;
    $args['conditions']['CoGroup.group_type'] = GroupEnum::Admins; 
    $args['contain'] = false;
    $admingroup = $this->CoGroup->find('first', $args);
    if(!empty($admingroup)) {
      $args = array();
      $args['conditions']['CoGroupMember.co_group_id'] = $admingroup['CoGroup']['id'];
      $args['contain'] = false;
      $members = $this->CoGroup->CoGroupMember->find('all', $args);
      $owners = $this->mapCoGroupMembersToDns($members,false,$stripuid);
      return $owners;
    }
    return array();
  }

  /**
   * Map all given objects (COUs and CoGroups) to a DN
   * Also map all members of the CoGroup and members of the COU::members::active group
   *
   * @since  COmanage Registry vTODO
   * @param  Array $target     provisioningtarget data
   * @param  Array $children   list of Cou objects and subgroups
   * @param  bool  $stripuid   Wether to strip the result to just the first dn attribute
   * @return Array Array of DNs found 
   */
  public function dnsForCous($target, $cou, $children, $stripuid=false) {

    $retval=array();
    $basedn = Configure::read('fixedldap.basedn');
    $groups = array();

    if(!empty($children)) {
      foreach($children as $obj) {
        try {
          $obj['Co']=$cou['Co'];
          if(isset($obj['Cou'])) {
            $item = $this->obtainDn($target, $obj, "cou",true);
          }
          else if(isset($obj['CoGroup'])) {
            $item = $this->obtainDn($target, $obj, "group",true);

            // if this is the active:members group, add its members as well
            if($obj['CoGroup']['group_type'] == GroupEnum::ActiveMembers) {
              $groups[]=$obj;
            }
          }
          
          $dn = isset($item['newdn']) ? $item['newdn'] : $item['olddn'];
          if($stripuid) {
            $dn=$this->stripDN($dn, $basedn,true ); 
          }
          if(!empty($dn)) {
            $retval[]=$dn;
          }
        }
        catch(RuntimeException $e) {
        }
      }
    } 

    $ids=array();
    foreach($groups as $grp) $ids[] = $grp['CoGroup']['id'];

    $args = array();
    $args['conditions']['CoGroupMember.co_group_id'] = $ids;
    $args['contain'] = false;
    $members = $this->CoGroup->CoGroupMember->find('all', $args);

    $memberDNs = $this->mapCoGroupMembersToDns($members,false,$stripuid);
    $retval = array_merge($retval,$memberDNs);

    return $retval;
  }

  /**
   * Strip a DN down to its base value
   *
   * @since  COmanage Registry vTODO
   * @param  String DN        full DN
   * @param  String basedn    base domain value
   * @param  Bool   stripuid  if true, strip of the attribute name part as well (uid=...)  
   * @return String UID       first attribute part of the whole DN
   */
  private function stripDN($dn, $basedn, $stripuid) {
    $attrs = explode(",", rtrim(str_replace($basedn, "", $dn), " ,"));
          
    if(sizeof($attrs)>0) {
      $item = $attrs[0];
              
      if($stripuid) {
        $attrs=explode("=",$attrs[0],2);
        if(sizeof($attrs)>1) {
          $item=$attrs[1];
        }
      }
      $dn=$item;
     }
     return $dn;
  }


  /**
   * Map a set of CO Group Members to their DNs. A similar function is in CoGroupMember.php.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Group Members
   * @param  Boolean True to map owners, false to map members
   * @param  Boolean True to map full DNs, False to only use the identifier
   * @return Array Array of DNs found -- note this array is not in any particular order, and may have fewer entries
   */

  private function mapCoGroupMembersToDns($coGroupMembers, $owners=false, $stripuid=false) {
    // Walk through the members and pull the COPerson IDs
    $coPeopleIds = array();

    foreach($coGroupMembers as $m) {
      if(($owners && $m['CoGroupMember']['owner'])
         || (!$owners && $m['CoGroupMember']['member'])) {
        $coPeopleIds[] = $m['CoGroupMember']['co_person_id'];
      }
    }

    if(!empty($coPeopleIds)) {
      // Now perform a find to get the list. Note using the IN notation like this
      // may not scale to very large sets of members.

      $args = array();
      $args['conditions']['CoLdapFixedProvisionerDn.co_person_id'] = $coPeopleIds;
      $args['fields'] = array('CoLdapFixedProvisionerDn.co_person_id', 'CoLdapFixedProvisionerDn.dn');

      $retval = array_values($this->find('list', $args));
      array_walk($retval, function(&$item, $key) {
        $item = $this->unescape_full_dn($item);
      });
      if($stripuid) {
        $basedn = Configure::read('fixedldap.basedn');        
        array_walk($retval, function(&$item, $key, $basedn) {
          $item = $this->stripDN($item,$basedn,false);
        }, $basedn);
      }
      return $retval;
    } else {
      return array();
    }
  }
  
  /**
   * Obtain a DN for a provisioning subject, possibly assigning or reassigning one.
   *
   * @since  COmanage Registry vTODO
   * @param  Array CO Provisioning Target data
   * @param  Array CO Provisioning data
   * @param  String Mode: 'group' or 'person'
   * @param  Boolean Whether to assign a DN if one is not found and reassign if the DN should be changed
   * @return Array An array of the following:
   *               - olddn: Old (current) DN (may be null)
   *               - olddnid: Database row ID of old dn (may be null, to facilitate delete)
   *               - newdn: New DN (may be null)
   *               - newdnerr: Error message if new in cannot be assigned
   * @throws RuntimeException
   */

  public function obtainDn($coProvisioningTargetData, $provisioningData, $mode, $assign=true) {
    $curDn = null;
    $curDnId = null;
    $newDn = null;
    $newDnErr = null;

    // defaults for mode='group'    
    $subarray='CoGroup';
    $object_id=-1;
    $cond="CoLdapFixedProvisionerDn.co_group_id";
    $field='co_group_id';
    switch($mode) {
    case 'person':
      $object_id = $provisioningData['CoPerson']['id'];
      $cond = 'CoLdapFixedProvisionerDn.co_person_id';
      $subarray='CoPerson';
      $field='co_person_id';
      break;
    case 'co':
      $object_id = $provisioningData['Co']['id'];
      $cond = 'CoLdapFixedProvisionerDn.co_id';
      $subarray='Co';
      $field='co_id';
      break;
    case 'cou':
      $object_id = $provisioningData['Cou']['id'];
      $cond = 'CoLdapFixedProvisionerDn.cou_id';
      $subarray='Cou';
      $field='cou_id';
      break;
    default:
    case 'group':
      $object_id = $provisioningData['CoGroup']['id'];
      $cond="CoLdapFixedProvisionerDn.co_group_id";
      $subarray='CoGroup';
      $field='co_group_id';
      break;
    }    

    // check the cache first
    if(!isset($this->_cache[$subarray]["key_".$object_id])) {
      
      // Check the database
      $args = array();
      $args['conditions']['CoLdapFixedProvisionerDn.co_ldap_fixed_provisioner_target_id'] = $coProvisioningTargetData['CoLdapFixedProvisionerTarget']['id'];
      $args['conditions'][$cond] = $object_id;
      $args['contain'] = false;
      $dnRecord = $this->find('first', $args);

      if(!empty($dnRecord)) {
        $curDn = $dnRecord['CoLdapFixedProvisionerDn']['dn'];
        $curDnId = $dnRecord['CoLdapFixedProvisionerDn']['id'];
      }

      // We always try to (re)calculate the DN, but only store it if $assign is true.
      try {
        if($mode == 'person') {
          $newDn = $this->assignPersonDn($coProvisioningTargetData, $provisioningData);
        } else if($mode == 'co') {
          $newDn = $this->assignCoDn($coProvisioningTargetData, $provisioningData);
        } else if($mode == 'cou') {
          $newDn = $this->assignCouDn($coProvisioningTargetData, $provisioningData);
        } else {
          $newDn = $this->assignGroupDn($coProvisioningTargetData, $provisioningData);
        }
      }
      catch(Exception $e) {
        // Rather than throw an exception, store the error in the return array.
        // We do this because there are many common times we will fail to assign a
        // DN (especially on user creation and deletion), so we'll pass the error
        // up the stack and let the calling function decide what to do.
        $newDnErr = $e->getMessage();
      }

      if($assign) {
        // If the the DN doesn't match the existing DN (including if there is no
        // existing DN), update it
        if($newDn && ($curDn != $newDn)) {
          $newDnRecord = array();
          $newDnRecord['CoLdapFixedProvisionerDn']['co_ldap_fixed_provisioner_target_id'] = $coProvisioningTargetData['CoLdapFixedProvisionerTarget']['id'];
          $newDnRecord['CoLdapFixedProvisionerDn'][$field] = $object_id;
          $newDnRecord['CoLdapFixedProvisionerDn']['dn'] = $newDn;

          if(!empty($dnRecord)) {
            $newDnRecord['CoLdapFixedProvisionerDn']['id'] = $dnRecord['CoLdapFixedProvisionerDn']['id'];
          }
          $this->clear();
          if(!$this->save($newDnRecord)) {
            throw new RuntimeException(_txt('er.db.save'));
          }
        }
      }
      $this->_cache[$subarray]["key_".$object_id] = array('olddn'    => $curDn,
                 'olddnid'  => $curDnId,
                 'newdn'    => $newDn,
                 'newdnerr' => $newDnErr);
    }
    return $this->_cache[$subarray]["key_".$object_id];                
  }

  /**
   * Escape specific characters in the DN
   *
   * @param  String DN attribute value
   * @return String escaped attribute value
   *
   * According to https://rlmueller.net/CharactersEscaped.htm
   * certain characters need to be escaped in a DN:
   * , \ # + < > ; " = and leading-or-trailing-space
   * Also, forward slash needs to be escaped sometimes (ADSI)
   */
  public function escape_dn($dn) {
    //CakeLog::write('debug','escaping dn "'.$dn.'"');
    $dn = ldap_escape($dn,null, LDAP_ESCAPE_DN);

    // ldap_escape does not escape the spaces at start and end though...
    // the post at
    // https://stackoverflow.com/questions/8560874/php-ldap-add-function-to-escape-ldap-special-characters-in-dn-syntax
    // indicates it would however...
    if(substr($dn,-1) == ' ') {
      $dn=substr($dn,0,-1).'\20';
    }
    if($dn[0] == ' ') {
      $dn='\20'.substr($dn,1);
    }
    //CakeLog::write('debug','returning escaped dn "'.$dn.'"');
    return $dn;
  }

  /**
   * Unescape DN characters....
   * this function is required, because we first create a DN, and then
   * we try to see if the required attributes of this DN are actually
   * present. If not, we add them. But then we need to de-escape the value
   * to properly test attribute value and DN part
      *
   * @param  String DN attribute value
   * @return String escaped attribute value
   */
  public function unescape_dn($dn) {
    //CakeLog::write('debug','unescaping "'.$dn.'"');
    $dn = str_replace(
      array('\20','\3d','\2b','\2c','\\22','\3c','\3e'),
      array(' ','=','+',',','"','<','>'),
      $dn);
    $dn = str_replace('\5c','\\',$dn);
    //CakeLog::write('debug','returning unescaped "'.$dn.'"');
    return $dn;
  }

  // convenience function to unescape a full DN, need to get back group names
  public function unescape_full_dn($dn) {
    CakeLog::write('debug','unescaping '.$dn);
    $attrs = explode(",", $dn);
    $newattrs=array();
    foreach($attrs as $a) {
      $av = explode("=", $a, 2);
      $a = $av[0]."=". $this->unescape_dn($av[1]);
      $newattrs[]=$a;
    }
    $dn = implode(",",$newattrs);
    CakeLog::write('debug','returning unescaped '.$dn);
    return $dn;
  }
}
