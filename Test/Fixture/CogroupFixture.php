<?php
/**
 * COmanage Registry CoGroupFixture
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

class CogroupFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'CoGroup', 'connection' => 'default');

    public function init() {

      $records = array(
        array(
            "id" => 101,
            "co_id" => 1,
            "cou_id" => null,
            "name" => "CO:admins",
            "description" => "COmanage Administrators",
            "open" => 0,
            "status" => "A",
            "group_type" => "A",
            "auto" => 0,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "co_group_id" => null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 102,
            "co_id" => 1,
            "cou_id" => null,
            "name" => "CO:members:active",
            "description" => "COmanage Active Members",
            "open" => 0,
            "status" => "A",
            "group_type" => "MA",
            "auto" => 1,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "co_group_id" => null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 103,
            "co_id" => 1,
            "cou_id" => null,
            "name" => "CO:members",
            "description" => "COmanage Members",
            "open" => 0,
            "status" => "A",
            "group_type" => "M",
            "auto" => 1,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "co_group_id" => null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
        array(
            "id" => 104,
            "co_id" => 3,
            "cou_id" => null,
            "name" => "Group+with+special,chars==",
            "description" => "COmanage Members",
            "open" => 0,
            "status" => "A",
            "group_type" => "M",
            "auto" => 1,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
            "co_group_id" => null,
            "revision" => 0,
            "deleted" => 0,
            "actor_identifier" => null
        ),
      );

      $this->records = $records;

      parent::init();
    }
}
