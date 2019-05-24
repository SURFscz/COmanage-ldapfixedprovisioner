<?php
/**
 * COmanage Registry CO LDAP Fixed Provisioner Targets Controller
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
 * @package       registry
 * @since         COmanage Registry vTODO
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SPTController", "Controller");

class CoLdapFixedProvisionerTargetsController extends SPTController {
  // Class name, used by Cake
  public $name = "CoLdapFixedProvisionerTargets";

  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry vTODO
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */

  function checkWriteDependencies($reqdata, $curdata = null) {
    // Make sure we can connect to the specified server
    $config = Configure::load('ldapfixedprovisioner','default');
    $url=Configure::read('fixedldap.server.url');
    $binddn=Configure::read('fixedldap.server.binddn');
    $password=Configure::read('fixedldap.server.password');
    $basedn=Configure::read('fixedldap.basedn');
    $dn_attr=Configure::read('fixedldap.dn_attribute_name');
    $dn_ident=Configure::read('fixedldap.dn_identifier_type');

    if(  $url===null || $binddn === null || $password === null) {
        $this->Flash->set(_txt('er.ldapfixedprovisioner.config'), array('key' => 'error'));
        return false;
    } else if( $basedn === null || $dn_attr === null || $dn_ident === null) {
        $this->Flash->set(_txt('er.ldapfixedprovisioner.dn.config'), array('key' => 'error'));
        return false;
    } else {
      try {
        $coid = $reqdata["CoLdapFixedProvisionerTarget"]["co_id"];
        $args = array();
        $args['conditions']['Co.id'] = intval($coid);
        $co = $this->Co->find('first', $args);
CakeLog::write('debug','verifying ldap server');
        $this->CoLdapFixedProvisionerTarget->verifyLdapServer($url,$binddn,$password,$basedn,$co);
      }
      catch(RuntimeException $e) {
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
        return false;
      }
    }
    return true;
  }


  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.8
   * @return Array Permissions
   */

  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();

    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();

    // Determine what operations this user can perform

    // Delete an existing CO Provisioning Target?
    $p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

    // Edit an existing CO Provisioning Target?
    $p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View all existing CO Provisioning Targets?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

    // View an existing CO Provisioning Target?
    $p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

    $this->set('permissions', $p);
    return($p[$this->action]);
  }
}
