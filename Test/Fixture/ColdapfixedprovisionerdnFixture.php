<?php
/**
 * COmanage Registry CoLdapFixedProvisionerDn Fixture
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

class ColdapfixedprovisionerdnFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'LdapFixedProvisioner.CoLdapFixedProvisionerDn', 'connection' => 'default');

    public function init() {

      $records = array(
        array(
            "id" => 1,
            "co_ldap_fixed_provisioner_target_id" => 1,
            "co_person_id" => 1,
            "co_group_id" => null,
            "co_id" => null,
            "dn" => "eppn=test@example.com,ou=People,ou=CO 1,dc=example,dc=com",
            "created" => "1970-01-01 00:00:00",
            "modified" => "1970-01-01 00:00:00"
        ),
        array(
            "id" => 2,
            "co_ldap_fixed_provisioner_target_id" => 1,
            "co_person_id" => 2,
            "co_group_id" => null,
            "co_id" => null,
            "dn" => "eppn=test3@example.com,ou=People,ou=CO 1,dc=example,dc=com",
            "created" => "1970-01-01 00:00:00",
            "modified" => "1970-01-01 00:00:00"
        ),
        array(
            "id" => 3,
            "co_ldap_fixed_provisioner_target_id" => 1,
            "co_person_id" => null,
            "co_group_id" => 103,
            "co_id" => null,
            "dn" => "cn=CO:members,ou=Groups,ou=CO 1,dc=example,dc=com",
            "created" => "1970-01-01 00:00:00",
            "modified" => "1970-01-01 00:00:00"
        ),
        array(
            "id" => 4,
            "co_ldap_fixed_provisioner_target_id" => 1,
            "co_person_id" => null,
            "co_group_id" => null,
            "co_id" => 1,
            "dn" => "ou=CO 1,dc=example,dc=com",
            "created" => "1970-01-01 00:00:00",
            "modified" => "1970-01-01 00:00:00"
        ),
      );

      $this->records = $records;

      parent::init();
    }
}
