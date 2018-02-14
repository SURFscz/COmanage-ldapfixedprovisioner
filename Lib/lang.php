<?php
/**
 * COmanage Registry LDAP Fixed Provisioner Plugin Language File
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

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_ldap_fixed_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_ldap_fixed_provisioner_targets.1'  => 'LDAP Fixed Provisioner Target',
  'ct.co_ldap_fixed_provisioner_targets.pl' => 'LDAP Fixed Provisioner Targets',

  // Error messages
  'er.ldapfixedprovisioner.config'         => 'Base configuration not found',
  'er.ldapfixedprovisioner.basedn.gr.none' => 'Group DN configured, but not found on the LDAP server.',
  'er.ldapfixedprovisioner.basedn'         => 'Base DN not found',
  'er.ldapfixedprovisioner.connect'        => 'Failed to connect to LDAP server',
  'er.ldapfixedprovisioner.bind'           => 'Failed to bind to LDAP server',
  'er.ldapfixedprovisioner.query'          => 'Failed to perform query on LDAP server',
  'er.ldapfixedprovisioner.rename1'        => 'Rename failed: no new DN could be created',
  'er.ldapfixedprovisioner.rename2'        => 'LDAP error during rename',
  'er.ldapfixedprovisioner.modify'         => 'LDAP error during modify',
  'er.ldapfixedprovisioner.add'            => 'LDAP error during add',
  'er.ldapfixedprovisioner.add1'           => 'LDAP error during add of CO OU',
  'er.ldapfixedprovisioner.add2'           => 'LDAP error during add of people OU of CO',
  'er.ldapfixedprovisioner.add3'           => 'LDAP error during add of groups OU of CO',
  'er.ldapfixedprovisioner.dn.component'   => 'DN component %1$s not available',
  'er.ldapfixedprovisioner.dn.config'      => 'DN configuration invalid',
  'er.ldapfixedprovisioner.dn.noattr'      => 'DN attributes not found for CO Person %1$s',
  'er.ldapfixedprovisioner.dn.none'        => 'DN not found for %1$s %2$s (%3$s)',

  // Plugin texts
  'pl.ldapfixedprovisioner.info'         => 'The LDAP Fixed Provisioner provisions the group and its members to a preconfigured LDAP server using a fixed scheme. There are no further configuration options.',
);
