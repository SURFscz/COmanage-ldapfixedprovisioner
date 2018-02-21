<?php
$config=array(
  'fixedldap' => array(
    'basedn'  => 'dc=example,dc=com',
    'dn_attribute_name' => 'uid',

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
      'uid' => 'uid;org'       # required
    )
  )
);
