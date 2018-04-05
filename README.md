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
* dn_attribute_name: the attribute to use for generating the DN, usually one of ```eppn```, ```cn``` or ```uid```
* dn_identifier_type: the Identifier attribute to retrieve when generating the DN, usually also one of ```eppn```, ```sorid``` or ```uid```
* scope_suffix: a suffix to apply on scoped attributes like eduPersonScopedAffiliation and eduPersonUniqueId.  
  This parameter allows template replacement for the tag ```{CO}```, which is replaced with the name of the CO  
  of the current group or person.
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
* ```posixGroup``` (optional)
* ```ldapPublicKey``` (optional)
* ```voPerson``` (optional)

The attributes required for the classes (```sn```, ```cn```, ```member```, ```uidNumber```, ```gidNumber```, ```sshPublicKey```, ```uid```, etc.) are generated automatically whenever the associated objectclass is enabled. This follows the same behaviour as the well known LdapProvisioner plugin.

For each attribute, the configuration can specify a relevant type of item and the object origin. For a lot of types this is 
irrelevant, but especially for the name, address and identifier types this allows control over the exact name, address or
identifier type used to generate the attribute, as well as the origin (```COPerson``` or ```OrgIdentity```).

The type is determined by using the string representation of the relevant COmanage enum (see ```app/Lib/enums.php``` in the 
COmanage source code). Examples are '```uid```', '```eppn```', '```orcid```' for identifiers, '```official```', '```campus```'
and '```home```' for contact addresses and '```official```' or '```preferred```' for names, email and telephone. The type name is
converted to lowercase to make sure it matches user specified extended types. 

For the group membership attributes ```member```, ```memberUID``` and ```hasMember```, the configured DNs of the relevant
COPerson records are used. It is not possible to indicate a different DN generation scheme for members of a group. This
also means that records that are member of a group, but that are not provisioned for some reason (missing attribute for
DN generation usually), are absent from the membership attributes.

If the relevant information should be taken from data associated with the ```OrgIdentity``` of this person instead of the
data associated with the ```COPerson``` object, a second entry can be specified, semicolon separated, containing the 
text '```org```'. For relevant identifiers and address or name components this will force the algorithm to use the
```OrgIdentity``` as source. Examples are shown below.
  
Whenever the specification does not differ from the default setting, a value of ```TRUE``` can be used to keep the
defaults as specified in the comments below:  
Use defaults:  
```<attribute> => TRUE```  
Use <type> as basic type for data related to this attribute, use COPerson or OrgIdentity as specified in the defaults:
```<attribute> => '<type>'```  
Use OrgIdentity as base object:  
```<attribute> => '<type>;org'```  
Use COPerson as base object:  
```<attribute> => '<type>;person'```


Please note that the configuration array key should match the relevant switch case in the provisioner code. I.e., this
name cannot be changed, the line can only be enabled. The following example is an exhaustive list of all possible 
attribute generation cases. The things that are configurable are:
* whether the attribute is generated or not (required attributes are always generated)
* the identifier or address type searched for when generating the attribute
* whether identifiers or address information is taken from the COPerson (which is usually user managed) or OrgIdentity 
  (which is normally IdP provided).
  

posixGroup
==========
```posixGroup``` requires a ```gidNumber``` identifier, but groups in COmanage cannot have attributes. At the moment,
a ```gidNumber``` is fabricated by adding the internal COmanage ```COGroup.id``` value to a base value. The default 
value of this base value is ```10000```, but you can specify a different base number as the 'type' of ```gidNumber```:
```
    'gidNumber' => '15000'
```
This is a questionable use of the 'type' field though, so this configuration may change in the future.

Both ```posixGroup``` and ```groupOfNames``` are supported as objectclass for group type structures. However, both of
these LDAP structures are of type ```STRUCTURAL```, which means they cannot be both used for exporting groups.



Example
=======
```<?php
$config=array(
  'fixedldap' => array(
    'basedn'  => 'ou=dc=example,dc=com',
    # configuring like this will lead to DNs like
    # uid=<sorid ID>,ou=People,ou=<CO name>,dc=example,dc=com
    'dn_attribute_name' => 'uid',
    'dn_identifier_type' => 'sorid',

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
#     'posixGroup',             # optional
#     'ldapPublicKey',          # optional
#     'voPerson',               # optional
    ),
    'person' => array(
      'sn' => 'official',              # required
      'cn' => 'official',              # required
#     'pwdAccountLockedTime' => TRUE,  # optional
#     'userPassword' => TRUE,          # optional
    ),
    'organizationalPerson' => array (
#     'title' => TRUE,                      # optional
#     'ou' => TRUE,                         # optional
#     'telephoneNumber' => 'office',        # optional, default 'office' contact
#     'facsimileTelephoneNumber' => 'fax',  # optional, default 'fax' contact
#     'street' => 'office',                 # optional
#     'l' => 'office',                      # optional
#     'st' => 'office',                     # optional
#     'postalCode' => 'office',             # optional
    ),
    'inetOrgPerson' => array(
#     'givenName' => 'official',     # optional
#     'displayName' => 'preferred',  # optional, default 'preferred' name
#     'o' => TRUE,                   # optional
#     'mail' => 'official',          # optional, default 'official' address
#     'mobile' => 'mobile',          # optional, default 'mobile' number
#     'employeeNumber' => 'eppn',    # optional, default 'eppn' identifier
#     'employeeType' => TRUE,        # optional
#     'roomNumber' => TRUE,          # optional
#     'uid' => 'uid;org',            # optional, default 'uid' identifier of OrgIdentity
#     'labeldUri' => 'official,      # optional, default 'official' URI
    ),
    'eduPerson' => array(
#     'eduPersonAffiliation' => TRUE,               # optional
#     'eduPersonEntitlement' => TRUE,               # optional
#     'eduPersonNickname' => 'preferred',           # optional, default 'preferred' name
#     'eduPersonOrcid' => ';org',                   # optional, default of OrgIdentity
#     'eduPersonPrincipalName' => 'eppn;org',       # optional, default 'eppn' identifier, of OrgIdentity
#     'eduPersonPrincipalNamePrior' => 'eppn',      # optional, default 'eppn' identifier
#     'eduPersonScopedAffiliation' => TRUE,         # optional
#     'eduPersonUniqueId' => 'enterprise',          # optional, default 'enterprise' identifier
    ),
    'groupOfNames' => array(
      'cn' => TRUE,          # required
      'member' => TRUE,      # required
#     'owner' => TRUE,       # optional
#     'description' => TRUE, # optional
    ),
    'eduMember' => array(
#      'isMemberOf' => TRUE,   # optional
#      'hasMember' => TRUE,    # optional
    ),
    'posixAccount' => array(
      'cn' => TRUE,            # required
      'uid' => 'uid;org',      # required
      'uidNumber' => TRUE,     # required
      'gidNumber' => TRUE,     # required
      'homeDirectory' => TRUE, # required
#     'loginShell' => TRUE,    # optional
#     'gecos' => TRUE,         # optional
#     'userPassword' => TRUE,  # optional
    ),
    'posixGroup' => array(
      'cn' => TRUE,            # required
      'gidNumber' => TRUE,     # required
#     'userPassword' => TRUE,  # optional
#     'memberUID' => TRUE,     # optional
#     'description' => TRUE,   # optional
    ),
    'ldapPublicKey' => array(
      'sshPublicKey' => TRUE,  # required
      'uid' => 'uid'           # required, default 'uid' identifier of OrgIdentity
    ),
    'voPerson' => array(
#     'voPersonApplicationUID' => 'uid',      # optional, default 'uid' identifier
#     'voPersonAuthorName' => 'author',       # optional, default 'author' name
#     'voPersonCertificateDN' => TRUE,        # optional
#     'voPersonCertificateIssuerDN' => TRUE,  # optional
#     'voPersonExternalID' => 'uid',          # optional, default 'uid' identifier
#     'voPersonID' => 'enterprise',           # optional, default 'enterprise' identifier
#     'voPersonPolicyAgreement' => TRUE,      # optional
#     'voPersonSoRID' => 'sorid',             # optional, default 'sorid' identifier 
#     'voPersonStatus' => TRUE,               # optional
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
