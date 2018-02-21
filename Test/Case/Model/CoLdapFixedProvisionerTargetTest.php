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
          'eduMember'
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
          'hasMember' => 'uid',
        ),
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
        '["ldap_add",["ou=CO 1,dc=example,dc=com",{"ou":"CO 1","objectClass":"organizationalUnit"}]],'.
        '["ldap_add",["ou=People,ou=CO 1,dc=example,dc=com",{"ou":"People","objectClass":"organizationalUnit"}]],'.
        '["ldap_add",["ou=Groups,ou=CO 1,dc=example,dc=com",{"ou":"Groups","objectClass":"organizationalUnit"}]],'.
        '["ldap_is_connected",[]],'.
        '["ldap_search",["ou=People,ou=CO 1,dc=example,dc=com","(objectclass=*)",["dn"]]],'.
        '["ldap_get_entries",[[1,2]]],'.
        '["ldap_unbind",[]],'.
        '["ldap_is_connected",[]],'.
        '["ldap_search",["ou=Groups,ou=CO 1,dc=example,dc=com","(objectclass=*)",["dn"]]],'.
        '["ldap_get_entries",[[1,2]]],'.
        '["ldap_unbind",[]]'.
        ']';
    $this->CLPT->contain(array('CoProvisioningTarget'=>'Co'));
    $lpt = $this->CLPT->find('first',array(
        'conditions' => array('CoLdapFixedProvisionerTarget.id' => 1)));
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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
    $co=$lpt['CoProvisioningTarget']['Co']['name'];
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

    $expectedPerson1hashes=array(
      ProvisioningActionEnum::CoGroupAdded => "97d170e1550eee4afc0af065b78cda302a97674c", // no op
      ProvisioningActionEnum::CoGroupDeleted => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupReprovisionRequested => "97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoGroupUpdated =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonAdded =>"5fee094e3883f3e758eb29ef22aa73b65f7ffb20", // add
      ProvisioningActionEnum::CoPersonDeleted =>"e1fafe45762337e349a7454504a231ab743e0db6", // delete
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"27daefd1a50294845add582b1b4fc2f440b7754e", // replace
      ProvisioningActionEnum::CoPersonExpired =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonUnexpired =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonUpdated =>"27daefd1a50294845add582b1b4fc2f440b7754e"
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
      //print "person1 data is ".json_encode($person1)."<br/>";
      $this->CLPT->provision($target, $op, $person1);
      $hash=sha1(json_encode(LdapServiceBehavior::$content));
      //print "hash '$hash' for op '$op', content is ".nl2br(json_encode(LdapServiceBehavior::$content)). "<br>";
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
      ProvisioningActionEnum::CoPersonDeleted =>"26d0472fc5eb21445a9c239ff18379b52b24b86f", // main groups added
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"26d0472fc5eb21445a9c239ff18379b52b24b86f",
      ProvisioningActionEnum::CoPersonExpired =>"26d0472fc5eb21445a9c239ff18379b52b24b86f",
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonUnexpired =>"97d170e1550eee4afc0af065b78cda302a97674c",
      ProvisioningActionEnum::CoPersonUpdated =>"26d0472fc5eb21445a9c239ff18379b52b24b86f"
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
      ProvisioningActionEnum::CoGroupAdded => "e8829903b5289f71d48790accac15cd8d82f3c19", // delete, add
      ProvisioningActionEnum::CoGroupDeleted => "b0352d6080c91203eae077b296d5e53ede0c39db", // delete
      ProvisioningActionEnum::CoGroupReprovisionRequested => "b8390093fd0b05c4883e26cb25a26eaa46504b6d", // del+add
      ProvisioningActionEnum::CoGroupUpdated =>"5b702d41b390568b1e9797471a09d8fe6b88a770", // replace
      ProvisioningActionEnum::CoPersonAdded =>"5fee094e3883f3e758eb29ef22aa73b65f7ffb20", // add
      ProvisioningActionEnum::CoPersonDeleted =>"e1fafe45762337e349a7454504a231ab743e0db6", // delete
      ProvisioningActionEnum::CoPersonEnteredGracePeriod =>"27daefd1a50294845add582b1b4fc2f440b7754e", // replace
      ProvisioningActionEnum::CoPersonExpired =>"27daefd1a50294845add582b1b4fc2f440b7754e", //
      ProvisioningActionEnum::CoPersonPetitionProvisioned =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonPipelineProvisioned =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonReprovisionRequested =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonUnexpired =>"27daefd1a50294845add582b1b4fc2f440b7754e",
      ProvisioningActionEnum::CoPersonUpdated =>"27daefd1a50294845add582b1b4fc2f440b7754e"
    );
    $group3base = $this->CG->find('first',array('conditions'=>array("CoGroup.id"=>103),'contain'=>array('CoGroupMember')));
    //print "Group data is ".json_encode($group3)."<br/>";
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

    public function ldap_delete(Model $Model, $dn) {
      LdapServiceBehavior::$content[]=array("ldap_delete",array($dn));
      return $this->next_result();
    }
}
