<?php
/**
 * COmanage Registry CoFixture
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

class CoFixture extends CakeTestFixture {
    // Import schema for the model from the default database.
    // The fixture data itself will be written to test and
    // not default.
    public $import = array('model' => 'Co', 'connection' => 'default');

    public function init() {

      $records = array(
        array(
            "id" => 1,
            "name" => "CO 1",
            "description" => "First test CO",
            "status" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
        ),
        array(
            "id" => 2,
            "name" => "CO 2",
            "description" => "Second test CO",
            "status" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
        ),
        array(
            "id" => 3,
            "name" => "CO with,special=characters+  ",
            "description" => "Third test CO",
            "status" => null,
            "created" => "1999-12-11 11:23:45",
            "modified" => "1999-12-11 11:23:45",
        ),
      );

      $this->records = $records;

      parent::init();
    }
}
