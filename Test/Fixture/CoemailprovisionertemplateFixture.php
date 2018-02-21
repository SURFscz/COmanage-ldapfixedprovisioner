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

class CoemailprovisionertemplateFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'EmailProvisioner.CoEmailProvisionerTemplate', 'connection' => 'default');

    public function init() {

      $records = array(
        array(
            "id" => 1,
            "co_email_provisioner_target_id" => 1,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewPerson,
            "subject" => "Subject for New Person",
            "message" => "Template for New Person"
        ),
        array(
            "id" => 2,
            "co_email_provisioner_target_id" => 1,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewGroup,
            "subject" => "Subject for New Group",
            "message" => "Template for New Group"
        ),
        array(
            "id" => 3,
            "co_email_provisioner_target_id" => 2,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewPerson,
            "subject" => "Subject with {VAR} and {name}",
            "message" => "{VAR} in a {{template} {NAME}}"
        ),
        array(
            "id" => 4,
            "co_email_provisioner_target_id" => 2,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewGroup,
            "subject" => "{{{{0NOVAR",
            "message" => "{ADDRESS} {NAME} {GROUP}{template}"
        ),
        array(
            "id" => 5,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewPerson,
            "subject" => "Subject New Person {NAME}",
            "message" => "Message New Person {NAME}"
        ),
        array(
            "id" => 6,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::UpdatePerson,
            "subject" => "Subject Update Person {NAME}",
            "message" => "Message Update Person {NAME}"
        ),
        array(
            "id" => 7,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::RemovePerson,
            "subject" => "Subject Remove Person {NAME}",
            "message" => "Message Remove Person {NAME}"
        ),
        array(
            "id" => 8,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::NewGroup,
            "subject" => "Subject New Group {NAME}",
            "message" => "Message New Group {NAME}"
        ),
        array(
            "id" => 9,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::UpdateGroup,
            "subject" => "Subject Update Group {NAME}",
            "message" => "Message Update Group {NAME}"
        ),
        array(
            "id" => 10,
            "co_email_provisioner_target_id" => 3,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "template_type" => EmailTemplateEnum::RemoveGroup,
            "subject" => "Subject Remove Group {NAME}",
            "message" => "Message Remove Group {NAME}"
        ),
      );

      $this->records = $records;

      parent::init();
    }
}
