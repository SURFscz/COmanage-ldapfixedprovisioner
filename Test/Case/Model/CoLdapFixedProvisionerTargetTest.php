<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('CakeEmail', 'Network/Email');

class CoLdapFixedProvisionerTargetTest extends CakeTestCase {

  public $useDbConfig=false;

  public $fixtures = array(
	'plugin.ldapFixedProvisioner.coprovisioningtarget',
	'plugin.ldapFixedProvisioner.coldapfixedprovisionertarget',
	'plugin.ldapFixedProvisioner.coldapfixedprovisionerdn',
	"plugin.ldapFixedProvisioner.co",
	"plugin.ldapFixedProvisioner.cogroup",
	"plugin.ldapFixedProvisioner.consfdemographic",
	"plugin.ldapFixedProvisioner.coinvite",
	"plugin.ldapFixedProvisioner.conotification",
	"plugin.ldapFixedProvisioner.orgidentity",
	"plugin.ldapFixedProvisioner.coorgidentitylink",
	"plugin.ldapFixedProvisioner.copersonrole",
	"plugin.ldapFixedProvisioner.copetition",
	"plugin.ldapFixedProvisioner.copetitionhistoryrecord",
	"plugin.ldapFixedProvisioner.cotandcagreement",
	"plugin.ldapFixedProvisioner.emailaddress",
	"plugin.ldapFixedProvisioner.historyrecord",
	"plugin.ldapFixedProvisioner.coprovisioningexport",
	"plugin.ldapFixedProvisioner.sshkey",
	"plugin.ldapFixedProvisioner.cou",
	"plugin.ldapFixedProvisioner.coenrollmentflow",
	"plugin.ldapFixedProvisioner.coexpirationpolicy",
	"plugin.ldapFixedProvisioner.cosetting",
	"plugin.ldapFixedProvisioner.coservice",
	"plugin.ldapFixedProvisioner.name",
	"plugin.ldapFixedProvisioner.coperson",
	"plugin.ldapFixedProvisioner.identifier",
	"plugin.ldapFixedProvisioner.cogroupmember",
	"plugin.ldapFixedProvisioner.telephone",
	"plugin.ldapFixedProvisioner.address",
  "plugin.ldapFixedProvisioner.attributeenumeration",
  "plugin.ldapFixedProvisioner.coextendedattribute",
  "plugin.ldapFixedProvisioner.coextendedtype",
  "plugin.ldapFixedProvisioner.coidentifierassignment",
  "plugin.ldapFixedProvisioner.coidentifiervalidator",
  "plugin.ldapFixedProvisioner.cojob",
  "plugin.ldapFixedProvisioner.colocalization",
  "plugin.ldapFixedProvisioner.copipeline",
  "plugin.ldapFixedProvisioner.coselfservicepermission",
  "plugin.ldapFixedProvisioner.cotermsandconditions",
  "plugin.ldapFixedProvisioner.cotheme",
  "plugin.ldapFixedProvisioner.orgidentitysource",
  "plugin.ldapFixedProvisioner.authenticator",
  "plugin.ldapFixedProvisioner.codepartment",
  "plugin.ldapFixedProvisioner.coemaillist",
  "plugin.ldapFixedProvisioner.codashboard",
  "plugin.ldapFixedProvisioner.comessagetemplate",
  "plugin.ldapFixedProvisioner.conavigationlink",
  "plugin.ldapFixedProvisioner.server",
  );

  public $CEPT;

  public function startTest($method) {
    ClassRegistry::addObject('LdapFixedProvisioner.LdapServiceBehavior', new LdapServiceBehavior());
  	$this->CLPT = ClassRegistry::init('LdapFixedProvisioner.CoLdapFixedProvisionerTarget');
    $this->CLD = ClassRegistry::init('LdapFixedProvisioner.CoLdapFixedProvisionerDn');
    $this->CPT = ClassRegistry::init('CoProvisioningTarget');
    $this->CP = ClassRegistry::init('CoPerson');
    $this->CG = ClassRegistry::init('CoGroup');

    _bootstrap_plugin_txt(); // this is normally done in the Controller, but we do not have a controller

    Configure::write(array(
      'fixedldap' => array(
        'basedn'  => 'dc=example,dc=com',
        'dn_attribute_name' => 'eppn',
        'dn_identifier_type' => 'eppn',
        'scope_suffix' => 'example-scope',
        'server' => array(
          'url' => 'ldap:///',
          'binddn' => 'cn=admin,dc=example,dc=com',
          'password' => 'SuperSecret',
        ),
        # list all enabled schemata
        'schemata' => array(
          'person',
          'organizationalPerson',
          'inetOrgPerson',
          'groupOfNames',
          'eduPerson',
          'eduMember',          
          'ldapPublicKey',
          'posixAccount',
//        'posixGroup', // do not generate posixGroup; not compatible with groupOfnames          
          'voPerson',
        ),
        'person' => array(
          'sn' => 'official',
          'cn' => 'official',
        ),
        'organizationalPerson' => array (
          'title' => TRUE,
          'ou' => TRUE,
          'telephoneNumber' => 'office',
          'facsimileTelephoneNumber' => 'fax',
          'street' => 'office',
          'l' => 'office',
          'st' => 'office',
          'postalCode' => 'office',
        ),
        'inetOrgPerson' => array(
          'givenName' => 'official',
          'displayName' => 'preferred',
          'o' => TRUE,
          'mail' => 'official',
          'mobile' => 'mobile',
          'employeeNumber' => 'eppn',
          'employeeType' => TRUE,
          'roomNumber' => TRUE,
          'uid' => 'uid;org'
        ),
        'groupOfNames' => array(
          'cn' => TRUE,
          'member' => TRUE,
          'owner' => TRUE,
          'description' => TRUE,
        ),
        'eduMember' => array(
          'isMemberOf' => TRUE,
          'hasMember' => TRUE,
        ),
        'posixAccount' => array(
          'cn' => TRUE,
          'uid' => 'uid;org',
          'uidNumber' => TRUE,
          'gidNumber' => TRUE,
          'homeDirectory' => TRUE,
          'loginShell' => TRUE,
          'gecos' => TRUE,
          'userPassword' => TRUE,
          'description' => TRUE,
        ),
        'posixGroup' => array(
          'cn' => TRUE,
          'gidNumber' => TRUE,
          'userPassword' => TRUE,
          'memberUID' => TRUE,
          'description' => TRUE,
        ),
        'ldapPublicKey' => array(
          'sshPublicKey' => TRUE,
          'uid' => 'uid;org'
        ),
        'eduPerson' => array(
          'eduPersonAffiliation' => TRUE,
          'eduPersonEntitlement' => TRUE,
          'eduPersonNickname' => 'official',
          'eduPersonOrcid' => ';org',
          'eduPersonPrincipalName' => 'eppn;org',
          'eduPersonPrincipalNamePrior' => 'eppn;org',
          'eduPersonScopedAffiliation' => TRUE,
          'eduPersonUniqueId' => 'enterprise',
         ),
        'voPerson' => array(
          'voPersonApplicationUID' => TRUE,
          'voPersonAuthorName' => TRUE,
          'voPersonCertificateDN' => TRUE,
          'voPersonCertificateIssuerDN' => TRUE,
          'voPersonExternalID' => 'uid;org',
          'voPersonID' => 'enterprise',
          'voPersonPolicyAgreement' => TRUE,
          'voPersonSoRID' => 'sorid',
          'voPersonStatus' => TRUE,
        )
      )
    ));
  }

  public function endTest($method) {
	unset($this->CLPT);
	unset($this->CLD);
	unset($this->CPT);
	unset($this->CP);
	unset($this->CG);
  }

  protected static function getMethod($obj, $name) {
    $class = new ReflectionClass(get_class($obj));
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

  public function testVerifyLdapServer() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      FALSE, // ldap_is_connected
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      TRUE, // ldap_bind
      TRUE, // ldap_add
      TRUE, // ldap_add
      TRUE, // ldap_add
      TRUE, // ldap_search
      array(1,2),// ldap_get_entries
      TRUE, // ldap_unbind
      TRUE, // ldap_is_connected
      TRUE, // ldap_search
      array(1,2),// ldap_get_entries
      TRUE,// ldap_unbind
    );
    $content= '['.
        '["ldap_is_connected",[]],'.
        '["ldap_connect",["ldap:\/\/\/"]],'.
        '["ldap_set_option",[17,3]],'.
        '["ldap_bind",["cn=bind,dc=example,dc=com","password"]],'.
        '["ldap_add",["o=CO 1,dc=example,dc=com",{"o":"CO 1","objectClass":"organization"}]],'.
        '["ldap_add",["ou=People,o=CO 1,dc=example,dc=com",{"ou":"People","objectClass":"organizationalUnit"}]],'.
        '["ldap_add",["ou=Groups,o=CO 1,dc=example,dc=com",{"ou":"Groups","objectClass":["organizationalUnit"]}]],'.
        '["ldap_is_connected",[]],'.
        '["ldap_search",["ou=People,o=CO 1,dc=example,dc=com","(objectclass=*)",["dn"]]],'.
        '["ldap_get_entries",[[1,2]]],'.
        '["ldap_unbind",[]],'.
        '["ldap_is_connected",[]],'.
        '["ldap_search",["ou=Groups,o=CO 1,dc=example,dc=com","(objectclass=*)",["dn"]]],'.
        '["ldap_get_entries",[[1,2]]],'.
        '["ldap_unbind",[]]'.
        ']';
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));
    $this->assertEquals($content,json_encode(LdapServiceBehavior::$content),"verify of ldap calls");
  }

  public function testVerifyLdapServerConnect() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      FALSE, // ldap_connect
    );
    $this->expectException("RuntimeException");
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));
  }

  public function testVerifyLdapServerBind() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      FALSE, // ldap_bind
    );
    $this->expectException("RuntimeException");
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));

  }

  public function testVerifyLdapServerSearch() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      TRUE, // ldap_bind
      FALSE, // ldap_search
    );
    $this->expectException("RuntimeException");
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));
  }

  public function testVerifyLdapServerEntries1() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      TRUE, // ldap_bind
      TRUE, // ldap_search
      array(),// ldap_get_entries
    );
    $this->expectException("RuntimeException");
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));
  }

  public function testVerifyLdapServerEntries2() {
    $method = $this->getMethod($this->CLPT,"verifyLdapServer");
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=array(
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      TRUE, // ldap_bind
      TRUE, // ldap_search
      array(1,2),// ldap_get_entries
      TRUE, // ldap_unbind
      TRUE, // ldap_connect
      TRUE, // ldap_set_options
      TRUE, // ldap_bind
      TRUE, // ldap_search
      array(),// ldap_get_entries
    );
    $this->expectException("RuntimeException");
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget'];
    $return_value=$method->invokeArgs($this->CLPT, array("ldap:///", "cn=bind,dc=example,dc=com", "password", "dc=example,dc=com",$co));
  }

  public function testProvision() {
    $target = $this->CLPT->find('first',array("conditions"=>array("CoLdapFixedProvisionerTarget.id"=>1)));
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=TRUE;

    $statuses = array(ProvisioningActionEnum::CoGroupAdded, ProvisioningActionEnum::CoGroupDeleted,
            ProvisioningActionEnum::CoGroupReprovisionRequested, ProvisioningActionEnum::CoGroupUpdated,
            ProvisioningActionEnum::CoPersonAdded, ProvisioningActionEnum::CoPersonDeleted,
            ProvisioningActionEnum::CoPersonEnteredGracePeriod, ProvisioningActionEnum::CoPersonExpired,
            ProvisioningActionEnum::CoPersonPetitionProvisioned,
            ProvisioningActionEnum::CoPersonPipelineProvisioned,
            ProvisioningActionEnum::CoPersonReprovisionRequested, ProvisioningActionEnum::CoPersonUnexpired,
            ProvisioningActionEnum::CoPersonUpdated);

    // PA content:
    //  [["ldap_is_connected",[]],
    //  ["ldap_is_connected",[]],
    //  ["ldap_add",["o=CO 1,dc=example,dc=com",{"o":"CO 1","objectClass":"organization"}]],
    //  ["ldap_add",["ou=People,o=CO 1,dc=example,dc=com",{"ou":"People","objectClass":"organizationalUnit"}]],
    //  ["ldap_add",["ou=Groups,o=CO 1,dc=example,dc=com",{"ou":"Groups","objectClass":["organizationalUnit"]}]],
    //  ["ldap_rename",["eppn=test@example.com,ou=People,ou=CO 1,dc=example,dc=com","eppn=test@example.com"]],
    //  ["ldap_mod_replace",["eppn=test@example.com,ou=People,o=CO 1,dc=example,dc=com",
    //  {"sn":["name"],"cn":["such name ever"],"userPassword":[],"title":["Secretary"],"ou":["Sales"],"telephoneNumber":[],
    //   "facsimileTelephoneNumber":[],"street":[],"l":[],"st":[],"postalCode":[],"givenName":"such","displayName":[],
    //   "o":["Antartica Mining Corp"],"mail":["nosuchmail@example.com"],"mobile":[],"employeeNumber":"test@example.com",
    //   "employeeType":["CO:CO 1:staff"],"roomNumber":"101","uid":[],"eduPersonAffiliation":[],"eduPersonEntitlement":[],
    //   "eduPersonNickname":["such name ever"],"eduPersonOrcid":[],"eduPersonPrincipalName":"example@example.com",
    //   "eduPersonPrincipalNamePrior":["example@example.com"],"eduPersonScopedAffiliation":[],"eduPersonUniqueId":[],
    //   "isMemberOf":["cn=GRP:CO:admins,ou=Groups,o=CO 1,dc=example,dc=com",
    //   "cn=GRP:CO:members:active,ou=Groups,o=CO 1,dc=example,dc=com","cn=GRP:CO:members,ou=Groups,o=CO 1,dc=example,dc=com"],
    //   "hasMember":[],"voPersonApplicationUID":[],"voPersonAuthorName":[],"voPersonCertificateDN":[],
    //   "voPersonCertificateIssuerDN":[],"voPersonExternalID":[],"voPersonID":[],"voPersonPolicyAgreement":[],
    //   "voPersonSoRID":[],"voPersonStatus":["Active"],"description":[],"uidNumber":[],"gidNumber":[],"homeDirectory":[],
    //   "loginShell":[],"gecos":[],"sshPublicKey":[],"eppn":[],"objectclass":["person","organizationalPerson","inetOrgPerson",
    //   "eduPerson","eduMember","voPerson"]}]],
    // ["ldap_unbind",[]]]

    $expectedPerson1hashes=array(
      ProvisioningActionEnum::CoGroupAdded => "97d170e1550eee4afc0af065b78cda302a97674c", // no op
      ProvisioningActionEnum::CoGroupDeleted => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupReprovisionRequested => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupUpdated =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonAdded =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a", // delete
      ProvisioningActionEnum::CoPersonDeleted =>"98b3d6a1d363f5b272388c489f9e850424f9922a", // delete
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a", // replace
      ProvisioningActionEnum::CoPersonExpired =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a",
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a",
      ProvisioningActionEnum::CoPersonUnexpired =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a",
      ProvisioningActionEnum::CoPersonUpdated =>"f21a5fa2ec33cabc5e37514f66ec83b16c213c3a"
    );
    // these arguments come from the ProvisionerBehavior::marshallCoPersonData
    $args=array();
    $args['conditions']=array("CoPerson.id"=>1);
    $args['contain'] = array(
      'Co',
      'CoGroupMember' => array('CoGroup'),
      // 'CoGroup'
      // 'CoGroupMember.CoGroup',
      'CoOrgIdentityLink' => array('OrgIdentity' => array('Identifier')),
      //'CoOrgIdentityLink',
      // We normally don't pull org identity data, but we'll make an exception
      // for Identifier to be able to expose eppn
      //'CoOrgIdentityLink.OrgIdentity.Identifier',
      'CoPersonRole' => array('Address', 'Cou', 'TelephoneNumber'),
      //'CoPersonRole',
      //'CoPersonRole.Address',
      //'CoPersonRole.Cou',
      //'CoPersonRole.TelephoneNumber',
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
      'SshKey'
    );
    $person1 = $this->CP->find('first',$args);
    //print "person record is <br>".nl2br(json_encode($person1))."<br/>";
    if(true){
    foreach($statuses as $op)
    {
      LdapServiceBehavior::$content=array();
      //print "person1 data is ".json_encode($person1)."<br/><br/>";
      $this->CLPT->provision($target, $op, $person1);
      $hash=sha1(json_encode(LdapServiceBehavior::$content));
      //print "hash '$hash' for op '$op', content is ".nl2br(json_encode(LdapServiceBehavior::$content)). "<br><br/>";
      $this->assertTextEquals($expectedPerson1hashes[$op],$hash,"Person 1: expected different hash for operation '$op'");
    }

    // person5 has status Approved, which is not sufficient for provisioning, so several states are
    // changed to a delete instead of an update operation
    // person5 also has no valid identifier for provisioning
    $expectedPerson5hashes=array(
      ProvisioningActionEnum::CoGroupAdded => "97d170e1550eee4afc0af065b78cda302a97674c", // no-op
      ProvisioningActionEnum::CoGroupDeleted => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupReprovisionRequested => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupUpdated =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonAdded =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonDeleted =>"fe820e36f65bafd82c4624ee6bfa9b1191ca8ac0", // main groups added
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"fe820e36f65bafd82c4624ee6bfa9b1191ca8ac0",
      ProvisioningActionEnum::CoPersonExpired =>"fe820e36f65bafd82c4624ee6bfa9b1191ca8ac0",
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonUnexpired =>"fe820e36f65bafd82c4624ee6bfa9b1191ca8ac0",
      ProvisioningActionEnum::CoPersonUpdated =>"fe820e36f65bafd82c4624ee6bfa9b1191ca8ac0"
    );
    $args['conditions']=array("CoPerson.id"=>5);
    $person5 = $this->CP->find('first',$args);
    foreach($statuses as $op)
    {
      LdapServiceBehavior::$content=array();
      $this->CLPT->provision($target, $op, $person5);
      $hash=sha1(json_encode(LdapServiceBehavior::$content));
      //print "hash '$hash' for op '$op', content is ".json_encode(LdapServiceBehavior::$content). "<br>";
      $this->assertTextEquals($expectedPerson5hashes[$op],$hash,"Person 5: expected different hash for operation '$op'");
    }}

    $expectedGroup3hashes=array(
      ProvisioningActionEnum::CoGroupAdded => "947257af7c1842c1a62def2d917f202cff16091b", // delete, add
      ProvisioningActionEnum::CoGroupDeleted => "81c398d3cd4d2d4d894587a5160476bb29a04859", // delete
      ProvisioningActionEnum::CoGroupReprovisionRequested => "b42fd896c505a466edda01630516711a9d810e40", // del+add
      ProvisioningActionEnum::CoGroupUpdated =>"8e25e8a2c1044e5c1271143586206257905c4c39", // replace
      ProvisioningActionEnum::CoPersonAdded =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f", // delete
      ProvisioningActionEnum::CoPersonDeleted =>"98b3d6a1d363f5b272388c489f9e850424f9922a", // delete
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f", // replace
      ProvisioningActionEnum::CoPersonExpired =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f", //
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f",
      ProvisioningActionEnum::CoPersonUnexpired =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f",
      ProvisioningActionEnum::CoPersonUpdated =>"ec97b3c650bee2f07cf4dc513cfd61805e38152f"
    );
    $group3base = $this->CG->find('first',array('conditions'=>array("CoGroup.id"=>103),'contain'=>array('CoGroupMember')));
    //print "Group data is ".json_encode($group3)."<br/>";
    // expected result along the lines of:
    // [["ldap_is_connected",[]],
    //  ["ldap_is_connected",[]],
    //  ["ldap_add",["o=CO 1,dc=example,dc=com",{"o":"CO 1","objectClass":"organization"}]],
    //  ["ldap_add",["ou=People,o=CO 1,dc=example,dc=com",{"ou":"People","objectClass":"organizationalUnit"}]],
    //  ["ldap_add",["ou=Groups,o=CO 1,dc=example,dc=com",{"ou":"Groups","objectClass":["organizationalUnit"]}]],
    //  ["ldap_rename",["cn=CO:members,ou=Groups,ou=CO 1,dc=example,dc=com","cn=GRP:CO:members,ou=Groups,o=CO 1"]],
    //  ["ldap_add",["cn=GRP:CO:members,ou=Groups,o=CO 1,dc=example,dc=com",
    //   {"cn":["GRP:CO:members"],"member":["eppn=test3@example.com,ou=People,ou=CO 1,dc=example,dc=com"],
    //    "description":"COmanage Members","isMemberOf":["cn=CO:CO 1,ou=Groups,o=CO 1,dc=example,dc=com"],
    //    "hasMember":["eppn=test3@example.com,ou=People,ou=CO 1,dc=example,dc=com"],
    //    "objectclass":["groupOfNames","eduMember"]}]],
    // ["ldap_unbind",[]]]
    foreach($statuses as $op)
    {
      $group3=$group3base;
      switch($op)
      {
        case ProvisioningActionEnum::CoGroupAdded:
        case ProvisioningActionEnum::CoGroupDeleted:
        case ProvisioningActionEnum::CoGroupReprovisionRequested:
        case ProvisioningActionEnum::CoGroupUpdated:
          break;
        default:
          // merge in group information for Person oriented cases
          // this breaks the cases where no group information is
          // expected (like CoPersonAdded or CoPersonDeleted), but
          // that is okay for this test
          $group3 = array_merge($group3, $person1);
          break;
      }
      LdapServiceBehavior::$content=array();
      $this->CLPT->provision($target, $op, $group3);
      $hash=sha1(json_encode(LdapServiceBehavior::$content));
      //print "hash '$hash' for op '$op', content is ".json_encode(LdapServiceBehavior::$content)."<br/>";
      $this->assertTextEquals($expectedGroup3hashes[$op],$hash,"Group 3: expected different hash for operation '$op'");
    }

    // finally test that unknown operators end up with an exception
    $this->expectException("RuntimeException","Not Implemented");
    $this->CLPT->provision($target, "NO SUCH OP", $person1);
  }

  public function testDnEscapes()
  {
    $tests=array(
      "nochanges" => "nochanges",
      "comm,a" => "comm\\2ca",
      "equ=al" => "equ\\3dal",
      "pl+us" => "pl\\2bus",
      "qu\"ote"=> "qu\\22ote",
      "brak<" => "brak\\3c",
      ">ket" => "\\3eket",
      "sl\\ash" => "sl\\5cash",
      "  space in front" => "\\20 space in front",
      "space at end  " => "space at end \\20",
      "  and, a,comb=i<nat>on\" of+chars  " => "\\20 and\\2c a\\2ccomb\\3di\\3cnat\\3eon\\22 of\\2bchars \\20"
    );
    
    foreach($tests as $test=>$result) 
    {
      $res = $this->CLPT->CoLdapFixedProvisionerDn->escape_dn($test);
      $this->assertTextEquals($result,$res,"Escaping '".$test."' yields '".$res."' instead of expected '".$result."'");
    }
    foreach($tests as $test=>$result) 
    {
      $res = $this->CLPT->CoLdapFixedProvisionerDn->unescape_dn($result);
      $this->assertTextEquals($test,$res,"Unescaping '".$result."' yields '".$res."' instead of expected '".$test."'");
    }
  }

  public function testProvisionWithEscapes() {
    $target = $this->CLPT->find('first',array("conditions"=>array("CoLdapFixedProvisionerTarget.id"=>1)));
    LdapServiceBehavior::$content=array();
    LdapServiceBehavior::$expected=TRUE;

    $statuses = array(ProvisioningActionEnum::CoGroupAdded, ProvisioningActionEnum::CoGroupDeleted,
            ProvisioningActionEnum::CoGroupReprovisionRequested, ProvisioningActionEnum::CoGroupUpdated,
            ProvisioningActionEnum::CoPersonAdded, ProvisioningActionEnum::CoPersonDeleted,
            ProvisioningActionEnum::CoPersonEnteredGracePeriod, ProvisioningActionEnum::CoPersonExpired,
            ProvisioningActionEnum::CoPersonPetitionProvisioned,
            ProvisioningActionEnum::CoPersonPipelineProvisioned,
            ProvisioningActionEnum::CoPersonReprovisionRequested, ProvisioningActionEnum::CoPersonUnexpired,
            ProvisioningActionEnum::CoPersonUpdated);

    // Expected content along the lines of:
    // [["ldap_is_connected",[]],
    //  ["ldap_is_connected",[]],
    //  ["ldap_add",["o=CO with\\2cspecial\\3dcharacters\\2b \\20,dc=example,dc=com",
    //    {"o":"CO with,special=characters+ ","objectClass":"organization"}]],
    //  ["ldap_add",["ou=People,o=CO with\\2cspecial\\3dcharacters\\2b \\20,dc=example,dc=com",
    //    {"ou":"People","objectClass":"organizationalUnit"}]],
    //  ["ldap_add",["ou=Groups,o=CO with\\2cspecial\\3dcharacters\\2b \\20,dc=example,dc=com",
    //    {"ou":"Groups","objectClass":["organizationalUnit"]}]],
    //  ["ldap_mod_replace",[
    //    "eppn=eppn\\2bwith\\2cspecial\\3cchars\\3e,ou=People,o=CO with\\2cspecial\\3dcharacters\\2b \\20,dc=example,dc=com",
    //    {"sn":["."],"cn":[""],"userPassword":[],"title":["Secretary+special+char"],"ou":["Sales+department"],
    //     "telephoneNumber":[],"facsimileTelephoneNumber":[],"street":[],"l":[],"st":[],"postalCode":[],
    //     "givenName":".","displayName":[],"o":["Russian Mining Corp"],"mail":[],"mobile":[],
    //     "employeeNumber":"eppn+with,special","employeeType":["CO:CO with,special=characters+ :staff"],
    //     "roomNumber":[],"uid":[],"eduPersonAffiliation":[],"eduPersonEntitlement":[],"eduPersonNickname":[],
    //     "eduPersonOrcid":[],"eduPersonPrincipalName":"another==eppn+with,special",
    //     "eduPersonPrincipalNamePrior":["another==eppn+with,special"],"eduPersonScopedAffiliation":[],
    //     "eduPersonUniqueId":[],
    //     "isMemberOf":["cn=GRP:Group+with+special,chars==,ou=Groups,o=CO with,special=characters+ ,dc=example,dc=com"]
    //     "hasMember":[],"voPersonApplicationUID":[],"voPersonAuthorName":[],"voPersonCertificateDN":[],
    //     "voPersonCertificateIssuerDN":[],"voPersonExternalID":[],"voPersonID":[],"voPersonPolicyAgreement":[],
    //     "voPersonSoRID":[],"voPersonStatus":["Approved"],"description":[],"uidNumber":[],
    //     "gidNumber":[],"homeDirectory":[],"loginShell":[],"gecos":[],"sshPublicKey":[],"eppn":[],
    //     "objectclass":["person","organizationalPerson","inetOrgPerson","eduPerson","eduMember","voPerson"]}]],
    //  ["ldap_unbind",[]]]

    $expectedPerson9hashes=array(
      ProvisioningActionEnum::CoGroupAdded => "97d170e1550eee4afc0af065b78cda302a97674c", // no-op
      ProvisioningActionEnum::CoGroupDeleted => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupReprovisionRequested => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupUpdated =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonAdded =>"5097d60cf0659b4c39eab7a09fccc35771e88d2b",
      ProvisioningActionEnum::CoPersonDeleted =>"424c11594be48da31d6a1fb47d9278a6c84b9500",
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"424c11594be48da31d6a1fb47d9278a6c84b9500",
      ProvisioningActionEnum::CoPersonExpired =>"424c11594be48da31d6a1fb47d9278a6c84b9500",
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"5097d60cf0659b4c39eab7a09fccc35771e88d2b",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"5097d60cf0659b4c39eab7a09fccc35771e88d2b",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"5097d60cf0659b4c39eab7a09fccc35771e88d2b",
      ProvisioningActionEnum::CoPersonUnexpired =>"424c11594be48da31d6a1fb47d9278a6c84b9500",
      ProvisioningActionEnum::CoPersonUpdated =>"424c11594be48da31d6a1fb47d9278a6c84b9500"
    );
    // these arguments come from the ProvisionerBehavior::marshallCoPersonData
    $args=array();
    $args['conditions']=array("CoPerson.id"=>9);
    $args['contain'] = array(
      'Co',
      'CoGroupMember' => array('CoGroup'),
      'CoOrgIdentityLink' => array('OrgIdentity' => array('Identifier')),
      'CoPersonRole' => array('Address', 'Cou', 'TelephoneNumber'),
      'EmailAddress',
      'Identifier',
      'Name',
      'PrimaryName' => array('conditions' => array('PrimaryName.primary_name' => true)),
      'SshKey'
    );
    $person9 = $this->CP->find('first',$args);
    print "person record is <br>".nl2br(json_encode($person9))."<br/>";

    foreach($statuses as $op)
    {
      LdapServiceBehavior::$content=array();
      //print "person1 data is ".json_encode($person9)."<br/><br/>";
      $this->CLPT->provision($target, $op, $person9);
      $hash=sha1(json_encode(LdapServiceBehavior::$content));
      //print "hash '$hash' for op '$op', content is ".nl2br(json_encode(LdapServiceBehavior::$content)). "<br><br/>";
      $this->assertTextEquals($expectedPerson9hashes[$op],$hash,"Person 9: expected different hash for operation '$op'");
    }
  }


}

App::uses('ModelBehavior', 'Model');
class LdapServiceBehavior extends ModelBehavior {
    public static $content=array();
    public static $expected=array();

/*
    public function setup() {}
    public function cleanup() {}
    public function beforeFind() {}
    public function afterFind() {}
    public function beforeValidate() {}
    public function afterValidate() {}
    public function beforeSave() {}
    public function afterSave() {}
    public function beforeDelete() {}
    public function afterDelete() {}
    public function onError() {}
    public function addToWhiteList() {}
*/
    private function next_result()
    {
      if(is_array(LdapServiceBehavior::$expected))
      {
        return array_shift(LdapServiceBehavior::$expected);
      }
      else if(is_bool(LdapServiceBehavior::$expected))
      {
        return LdapServiceBehavior::$expected;
      }
      return TRUE;
    }

    public function ldap_connect(Model $Model, $host) {
      LdapServiceBehavior::$content[]=array("ldap_connect",array($host));
      return $this->next_result();
    }

    public function ldap_is_connected(Model $Model) {
      LdapServiceBehavior::$content[]=array("ldap_is_connected",array());
      return $this->next_result();
    }

    public function ldap_set_option(Model $Model, $opt, $val) {
      LdapServiceBehavior::$content[]=array("ldap_set_option",array($opt,$val));
      return $this->next_result();
    }

    public function ldap_bind(Model $Model, $binddn,$password) {
      LdapServiceBehavior::$content[]=array("ldap_bind",array($binddn,$password));
      return $this->next_result();
    }

    public function ldap_unbind(Model $Model) {
      LdapServiceBehavior::$content[]=array("ldap_unbind",array());
      return $this->next_result();
    }

    public function ldap_search(Model $Model, $baseDn, $filter, $attributes) {
      LdapServiceBehavior::$content[]=array("ldap_search",array($baseDn, $filter,$attributes));
      return $this->next_result();
    }

    public function ldap_get_entries(Model $Model, $s) {
      LdapServiceBehavior::$content[]=array("ldap_get_entries",array($s));
      return $this->next_result();
    }

    public function ldap_error(Model $Model) {
      LdapServiceBehavior::$content[]=array("ldap_error",array());
      return $this->next_result();
    }

    public function ldap_errno(Model $Model) {
      LdapServiceBehavior::$content[]=array("ldap_errno",array());
      return $this->next_result();
    }

    public function ldap_add(Model $Model, $dn, $attributes) {
      LdapServiceBehavior::$content[]=array("ldap_add",array($dn, $attributes));
      return $this->next_result();
    }

    public function ldap_rename(Model $Model, $olddn, $newdn) {
      LdapServiceBehavior::$content[]=array("ldap_rename",array($olddn,$newdn));
      return $this->next_result();
    }

    public function ldap_mod_replace(Model $Model, $dn, $attributes) {
      LdapServiceBehavior::$content[]=array("ldap_mod_replace",array($dn, $attributes));
      return $this->next_result();
    }

    public function ldap_mod_add(Model $Model, $dn, $attributes) {
      LdapServiceBehavior::$content[]=array("ldap_mod_add",array($dn, $attributes));
      return $this->next_result();
    }
    public function ldap_mod_del(Model $Model, $dn, $attributes) {
      LdapServiceBehavior::$content[]=array("ldap_mod_del",array($dn, $attributes));
      return $this->next_result();
    }

    public function ldap_delete(Model $Model, $dn) {
      LdapServiceBehavior::$content[]=array("ldap_delete",array($dn));
      return $this->next_result();
    }
}
