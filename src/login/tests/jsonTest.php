<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class loginJsonTest extends PHPUnit_Framework_TestCase {
  private static $base;
  private static $pgHost;
  private static $pgUser;
  private static $pgPass;
  private static $pgPort;
  private static $pgDatabase;

  public static function setUpBeforeClass() {
    
    // Get connection params
    global $pg_host, $pg_user, $pg_pass, $pg_database, $pg_port;
    self::$pgHost = $pg_host;
    self::$pgUser = $pg_user;
    self::$pgPass = $pg_pass;
    self::$pgPort = $pg_port;
    self::$pgDatabase = $pg_database;
    self::assertNotNull(self::$pgHost);
    self::assertNotNull(self::$pgUser);
    self::assertNotNull(self::$pgPass);
    self::assertNotNull(self::$pgDatabase);
    
    // Create object
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase,
				    self::$pgPort, '.traces');
    self::assertNotNull(self::$base);    
  }

  protected function assertPreConditions()
  {
    //    echo "\n".'*** pre conditions'."\n";
    self::$base->startTransaction();
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{structure,organization,users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    //    echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }

  public function testUsergroupGroupJson() {
    $name = 'Usergroup';
    $ugr = self::$base->login->usergroup_add($this->token, $name, null, 
					     '{preadmission, admission, present, left}');

    $orgName = 'Organization';
    $orgId = self::$base->organ->organization_add($this->token, $orgName, 'desc', true);
    $grpName1 = 'Group 1';
    $grpName2 = 'Group 2';
    $grpDesc1 = 'desc 1';
    $grpDesc2 = 'desc 2';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId, $grpName1, 
					    $grpDesc1, false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, $grpName2, 
					    $grpDesc2, false, 'organization');
    self::$base->login->usergroup_set_groups($this->token, $ugr, 
					     array($grpId1, $grpId2));
    
    $req = [ 'grp_id' => true,
	     'grp_name' => true,
	     'grp_description' => true ];
    $res = self::$base->login->usergroup_group_json($this->token, $ugr, json_encode($req));

    $grp1 = self::$base->organ->group_get($this->token, $grpId1);
    $grp2 = self::$base->organ->group_get($this->token, $grpId2);
    $this->assertEquals($grp1['grp_id'], $res[0]->grp_id);
    $this->assertEquals($grp1['grp_name'], $res[0]->grp_name);
    $this->assertEquals($grp1['grp_description'], $res[0]->grp_description);
    $this->assertEquals($grp2['grp_id'], $res[1]->grp_id);
    $this->assertEquals($grp2['grp_name'], $res[1]->grp_name);
    $this->assertEquals($grp2['grp_description'], $res[1]->grp_description);
  }

  public function testUsergroupPortalJson() {
    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porDesc1 = 'portal desc 1';
    $porDesc2 = 'portal desc 2';
    $porDesc3 = 'portal desc 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1, $porDesc1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2, $porDesc2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3, $porDesc3);

    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');
    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId2, $porId1));
    
    $req = [ 'por_id' => true,
	     'por_name' => true,
	     'por_description' => true ];
    $json = self::$base->login->usergroup_portal_json($this->token, $ugr, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $por1 = self::$base->portal->portal_get($this->token, $porId1);
    $por2 = self::$base->portal->portal_get($this->token, $porId2);

    $this->assertEquals([$por1, $por2], $res);

    $req = [ 'por_id' => true,
	     'por_name' => null,
	     'por_description' => null ];
    $json = self::$base->login->usergroup_portal_json($this->token, $ugr, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNotNull($res[0]['por_id']);
    $this->assertNull($res[0]['por_name']);
    $this->assertNull($res[0]['por_description']);
  }

  public function testUsergroupJson() {
    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porDesc1 = 'portal desc 1';
    $porDesc2 = 'portal desc 2';
    $porDesc3 = 'portal desc 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1, $porDesc1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2, $porDesc2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3, $porDesc3);

    $usergroupName1 = 'A user group';
    $ugr1 = self::$base->login->usergroup_add($this->token, $usergroupName1, null, '{preadmission, admission, present, left}');
    self::$base->login->usergroup_set_portals($this->token, $ugr1, array($porId2, $porId1));

    $usergroupName2 = 'Another user group';
    $ugr2 = self::$base->login->usergroup_add($this->token, $usergroupName2, null, '{preadmission}');
    self::$base->login->usergroup_set_portals($this->token, $ugr2, array($porId2));
    
    $req = [ 'ugr_id' => true,
	     'ugr_name' => true,
	     'groups' => [ 'grp_id' => true,
			   'grp_name' => true ],
	     'portals' => [ 'por_id' => true,
			    'por_name' => true,
			    'por_description' => true ] ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals(1, count($res));
    $this->assertEquals($res[0]['ugr_name'], $usergroupName1);

    $json = self::$base->login->usergroup_json($this->token, NULL, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals(2, count($res));
    $this->assertEquals($res[0]['ugr_name'], $usergroupName1);
    $this->assertEquals($res[1]['ugr_name'], $usergroupName2);
  }

  public function testUsergroupReqJson() {
    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porDesc1 = 'portal desc 1';
    $porDesc2 = 'portal desc 2';
    $porDesc3 = 'portal desc 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1, $porDesc1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2, $porDesc2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3, $porDesc3);

    $usergroupName1 = 'A user group';
    $ugr1 = self::$base->login->usergroup_add($this->token, $usergroupName1, null, '{preadmission, admission, present, left}');
    self::$base->login->usergroup_set_portals($this->token, $ugr1, array($porId2, $porId1));

    $orgName = 'Organization';
    $orgId = self::$base->organ->organization_add($this->token, $orgName, 'desc', true);
    $grpName1 = 'Group 1';
    $grpName2 = 'Group 2';
    $grpDesc1 = 'desc 1';
    $grpDesc2 = 'desc 2';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId, $grpName1, 
					    $grpDesc1, false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, $grpName2, 
					    $grpDesc2, false, 'organization');
    self::$base->login->usergroup_set_groups($this->token, $ugr1, 
					     array($grpId1, $grpId2));
    
    $req = [ 'ugr_id' => true,
	     'ugr_name' => true,
	     'groups' => [ 'grp_id' => true,
			   'grp_name' => true ],
	     'portals' => [ 'por_id' => true,
			    'por_name' => true,
			    'por_description' => true ] ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($ugr1, $res[0]['ugr_id']);
    $this->assertEquals($usergroupName1, $res[0]['ugr_name']);
    $this->assertNotNull($res[0]['groups']);
    $this->assertNotNull($res[0]['portals']);

    $req = [ 'ugr_id' => true,
	     'ugr_name' => true,
	     'groups' => [ 'grp_id' => true,
			   'grp_name' => true ],
	     'portals' => [ 'por_id' => true,
			    'por_name' => true,
			    'por_description' => true ] ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($ugr1, $res[0]['ugr_id']);
    $this->assertEquals($usergroupName1, $res[0]['ugr_name']);
    $this->assertNotNull($res[0]['groups']);
    $this->assertNotNull($res[0]['portals']);

    $req = [ 'ugr_id' => true,
	     'ugr_name' => null,
	     'groups' => null,
	     'portals' => null ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($ugr1, $res[0]['ugr_id']);
    $this->assertNull($res[0]['ugr_name']);
    $this->assertNull($res[0]['groups']);
    $this->assertNull($res[0]['portals']);

    $req = [ 'ugr_id' => null,
	     'ugr_name' => true,
	     'groups' => null,
	     'portals' => null ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res[0]['ugr_id']);
    $this->assertEquals($usergroupName1, $res[0]['ugr_name']);
    $this->assertNull($res[0]['groups']);
    $this->assertNull($res[0]['portals']);

    $req = [ 'ugr_id' => null,
	     'ugr_name' => null,
	     'groups' => [ 'grp_id' => true,
			   'grp_name' => true ],	 
	     'portals' => null ];
    $json = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res[0]['ugr_id']);
    $this->assertNull($res[0]['ugr_name']);
    $this->assertNotNull($res[0]['groups']);
    $this->assertNull($res[0]['portals']);

    $req = [ 'ugr_id' => null,
	     'ugr_name' => null,
	     'groups' => null,
	     'portals' => [ 'por_id' => true,
			    'por_name' => true,
			    'por_description' => true ] ];
    $res = self::$base->login->usergroup_json($this->token, $ugr1, json_encode($req));
    $this->assertNull($res[0]->ugr_id);
    $this->assertNull($res[0]->ugr_name);
    $this->assertNull($res[0]->groups);
    $this->assertNotNull($res[0]->portals);
  }
}
?>
