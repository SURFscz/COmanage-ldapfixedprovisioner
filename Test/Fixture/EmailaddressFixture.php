<?php
/**
 * COmanage Registry EmailAddressFixture
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

class EmailaddressFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'EmailAddress', 'connection' => 'default');

    public function init() {
      $records = array(
        array(
            "id" => 1,
            "mail" => "nosuchmail@example.com",
            "type"=>"official",
            "verified"=>1,
            "co_person_id" =>1,
            "org_identity_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "email_address_id"=> null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 2,
            "mail" => "testemail@example.com",
            "type"=>"delivery",
            "verified"=>1,
            "co_person_id" =>1,
            "org_identity_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "email_address_id"=> null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 3,
            "mail" => "potus@whitehouse.gov.us",
            "type"=>"official",
            "verified"=>1,
            "co_person_id" =>5,
            "org_identity_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "email_address_id"=> null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 4,
            "mail" => "potus@example.com",
            "type"=>"delivery",
            "verified"=>1,
            "co_person_id" =>5,
            "org_identity_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "email_address_id"=> null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
      );

      $this->records = $records;
      parent::init();
    }
}