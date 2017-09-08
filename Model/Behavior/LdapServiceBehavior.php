<?php
/**
 * LDAP Service behavior class.
 *
 * Enables objects to easily tie into an LDAP system
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

App::uses('ModelBehavior', 'Model');
class LdapServiceBehavior extends ModelBehavior {
    public $settings=array();

    public function setup(Model $Model, $settings = array()) {
      if (!isset($this->settings[$Model->alias])) {
        $this->settings[$Model->alias] = array("cxn"=>null);
      }
      $this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array)$settings);
    }

    public function ldap_connect(Model $Model, $host) {
      $this->settings[$Model->alias]["cxn"] = ldap_connect($host);
      return $this->settings[$Model->alias]["cxn"] !== FALSE;
    }

    public function ldap_set_option(Model $Model, $opt, $val) {
      return ldap_set_option($this->settings[$Model->alias]["cxn"], $opt, $val);
    }

    public function ldap_bind(Model $Model, $binddn,$password) {
      return ldap_bind($this->settings[$Model->alias]["cxn"], $binddn, $password);
    }

    public function ldap_unbind(Model $Model) {
     ldap_unbind($this->settings[$Model->alias]["cxn"]);
     $this->settings[$Model->alias]["cxn"]=null;
     return TRUE;
    }

    public function ldap_search(Model $Model, $baseDn, $filter, $attributes) {
      return ldap_search($this->settings[$Model->alias]["cxn"], $baseDn, $filter, $attributes);
    }

    public function ldap_get_entries(Model $Model, $s) {
      return ldap_get_entries($this->settings[$Model->alias]["cxn"], $s);
    }

    public function ldap_error(Model $Model) {
      return ldap_error($this->settings[$Model->alias]["cxn"]);
    }

    public function ldap_errno(Model $Model) {
      return ldap_errno($this->settings[$Model->alias]["cxn"]);
    }

    public function ldap_add(Model $Model, $dn, $attributes) {
      return ldap_add($this->settings[$Model->alias]["cxn"], $dn, $attributes);
    }

    public function ldap_rename(Model $Model, $olddn, $newdn) {
      return ldap_rename($this->settings[$Model->alias]["cxn"], $olddn, $newdn, null, true);
    }

    public function ldap_mod_replace(Model $Model, $dn, $attributes) {
      return ldap_mod_replace($this->settings[$Model->alias]["cxn"], $dn, $attributes);
    }

    public function ldap_delete(Model $Model, $dn) {
      return ldap_delete($this->settings[$Model->alias]["cxn"], $dn);
    }
}
