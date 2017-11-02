# comanage-ldapfixedprovisioner
This is a plugin for the [COManage Registry](https://www.internet2.edu/products-services/trust-identity/comanage/) application as provided and maintained by the [Internet2](https://www.internet2.edu/) foundation.

This project has the following deployment goals:
- create a Provisioner Plugin for COManage that provisions to a LDAP server using a fixed scheme


COManage LdapFixedProvisioner Plugin
====================================
COManage comes with a configurable LDAP Provisioning Plugin that allows CO administrators to configure with great detail what kind of information needs to be synchronised with which LDAP connection. However, in the specific case for which this plugin was intended, the hosting party requires that the LDAP scheme follows a fixed, predetermined setup that must not, under any circumstance, be changed by the CO administrator. For that reason, this plugin allows provisioning to an LDAP server using a scheme that is configurable using the app configuration instead of using the regular LDAP configuration.

Setup
=====
The provisioning plugin must be installed in the `local/Plugin` directory of the COManage installation. Optionally, you can install it in the `app/AvailablePlugins` directory and link to it from the `local/Plugin` directory.

After installation, run the Cake database update script as per the COManage instructions:
```
app/Console/cake database
```
You can now select the LdapFixedProvisioner plugin for your COManage Registry groups.

Configuration
=============
The LdapFixedProvisioner allows configuration of an LDAP server connection and LDAP scheme using the Cake application configuration. Configuration uses the ```PhpReader``` COnfiguration reader to read an array of values. An example configuration is provided in the ```Config``` directory of this Plugin.

The configuration resides inside a ```config``` variable and specifies basic server information, behaviour information and objectclass and attribute information:
* basedn: base DN for people and groups, usually in the form ```dc=example,dc.com```
* dn_attribute_name: the attribute to use for generating the DN, usually one of ```eppn``` or ```uid```
* dn_identifier_type: the Identifier attribute to retrieve when generating the DN, usually also one of ```eppn``` or ```uid```
* scope_suffix: a suffix to apply on scoped attributes like eduPersonScopedAffiliation and eduPersonUniqueId
* remove_unused: a boolean value to indicate whether empty but specified attributes should be removed from the LDAP
* server: a hash/dictionary/keyed array of the server connection values ```url```, ```binddn``` and ```password```
* person_ocs: an array of all additional objectclasses to add to COPerson records
* group_ocs: an array of all additional objectclasses to add to COGroup records
* schemata: an array of all enabled objectclasses

People are exported in an OU with DN ```ou=People,o=<CO>,<base DN>``` and groups are exported in an OU with DN ```ou=Groups,o=<CO>,<base DN>```. The ```<CO>``` is replaced with the name of the CO of which the COPerson or COGroup record is a part.

For each enabled objectclass, a configuration hash/dictionary/keyed array can be specified with all attributes that need to be determined for that objectclass. Basic objectclasses supported by this plugin are:
* ```person```
* ```organizationalPerson```
* ```inetOrgPerson```
* ```eduPerson``` (optional)
* ```groupOfNames``` (optional)
* ```eduMember``` (optional)
* ```posixAccount``` (optional)
* ```ldapPublicKey``` (optional)

The attributes required for the classes (```sn```, ```cn```, ```member```, ```uidNumber```, ```gidNumber```, ```sshPublicKey```, ```uid```) are generated automatically whenever the associated objectclass is enabled. This follows the same behaviour as the well know LdapProvisioner plugin.

Example
=======
```<?php
$config=array(
  'fixedldap' => array(
    'basedn'  => 'ou=People,dc=example,dc=com',
    'groupdn' => 'ou=Groups,dc=example,dc=com',
    'dn_attribute_name' => 'uid',
    'dn_identifier_type' => 'uid',

    # set an optional scope suffix
    #'scope_suffix' => '',

    # remove unused attributes (default FALSE)
    #'remove_unused' => FALSE,

    'server' => array(
      'url' => 'ldap:///',
      'binddn' => 'cn=admin,dc=example,dc=com',
      'password' => 'SuperSecret',
     ),

    # list all additional objectclasses for a COPerson record
    'person_ocs' => array(),

    # list all additional objectclasses for a Group record
    'group_ocs' => array(),

    # list all enabled schemata
    'schemata' => array(
      'person',                 # required
      'organizationalPerson',   # required
      'inetOrgPerson',          # required
#     'eduPerson',              # optional
#     'groupOfNames',           # optional
#     'eduMember',              # optional
#     'posixAccount',           # optional
#     'ldapPublicKey',          # optional
    ),
    'person' => array(
      'sn' => 'official',              # required
      'cn' => 'official',              # required
#     'pwdAccountLockedTime' => TRUE,  # optional
    ),
    'organizationalPerson' => array (
#     'title' => TRUE,                      # optional
#     'ou' => TRUE,                         # optional
#     'telephoneNumber' => 'office',        # optional
#     'facsimileTelephoneNumber' => 'fax',  # optional
#     'street' => 'office',                 # optional
#     'l' => 'office',                      # optional
#     'st' => 'office',                     # optional
#     'postalCode' => 'office',             # optional
    ),
    'inetOrgPerson' => array(
#     'givenName' => 'official',     # optional
#     'displayName' => 'preferred',  # optional
#     'o' => TRUE,                   # optional
#     'mail' => 'official',          # optional
#     'mobile' => 'mobile',          # optional
#     'employeeNumber' => 'eppn',    # optional
#     'employeeType' => TRUE,        # optional
#     'roomNumber' => TRUE,          # optional
#     'uid' => 'uid;org',            # optional
    ),
    'eduPerson' => array(
#     'eduPersonAffiliation' => TRUE,               # optional
#     'eduPersonEntitlement' => TRUE,               # optional
#     'eduPersonNickname' => 'official',            # optional
#     'eduPersonOrcid' => ';org',                   # optional
#     'eduPersonPrincipalName' => 'eppn;org',       # optional
#     'eduPersonPrincipalNamePrior' => 'eppn;org',  # optional
#     'eduPersonScopedAffiliation' => TRUE,         # optional
#     'eduPersonUniqueId' => 'enterprise',          # optional
    ),
    'groupOfNames' => array(
      'cn' => TRUE,          # required
      'member' => TRUE,      # required
#     'owner' => TRUE,       # optional
#     'description' => TRUE, # optional
    ),
    'eduMember' => array(
#      'isMemberOf' => TRUE,   # optional
#      'hasMember' => 'uid',   # optional
    ),
    'posixAccount' => array(
      'uidNumber' => TRUE,     # required
      'gidNumber' => TRUE,     # required
      'homeDirectory' => TRUE, # required
#     'loginShell' => TRUE,    # optional
#     'gecos' => TRUE,         # optional
    ),
    'ldapPublicKey' => array(
      'sshPublicKey' => TRUE,  # required
      'uid' => 'uid'           # required
    )
  )
);
```

Tests
=====
This plugin comes with unit tests for the main CoLdapFixedProvisionerTarget model. Access the Cake unit test page at:
````
<your path>/registry/test.php
````
You can select the CoLdapFixedProvisioner plugin for testing there. At the moment of writing, code coverage is not complete.

Disclaimer
==========
This plugin is provided AS-IS without any claims whatsoever to its functionality. The code is based largely on COManage Registry code, distributed under the [Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0).
