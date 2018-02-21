<?php
/**
 * COmanage Registry CoEmailProvisionerTemplate Fixture
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

class IdentifierFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'Identifier', 'connection' => 'default');

    public function init() {

      $records = array(
        array(
            "id" => 1,
            "identifier" => "test@example.com",
            "type" => "eppn",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "1",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 2,
            "identifier" => "test2@example.com",
            "type" => "eppn",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "1",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 3,
            "identifier" => "test3@example.com",
            "type" => "eppn",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "2",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 4,
            "identifier" => "test4@example.com",
            "type" => "eppn",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "2",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 5,
            "identifier" => "1002",
            "type" => "Gidnumber",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "1",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 6,
            "identifier" => "/home/example",
            "type" => "HomeDirectory",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "1",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 7,
            "identifier" => "1001",
            "type" => "UIDNumber",
            "login" => "0",
            "status" => "A",
            "co_person_id" => "1",
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 8,
            "identifier" => "example@example.com",
            "type" => "eppn",
            "login" => "1",
            "status" => "A",
            "co_person_id" => null,
            "org_identity_id" =>1,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
        array(
            "id" => 9,
            "identifier" => "orcid-identifier",
            "type" => "orcid",
            "login" => "0",
            "status" => "A",
            "co_person_id" => 1,
            "org_identity_id" =>null,
            "source_identifier_id" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "identifier_id" => null,
            "revision" => "0"
        ),
      );

      $this->records = $records;

      parent::init();
    }
}
