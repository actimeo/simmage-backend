<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class usergroupTest extends PHPUnit_Framework_TestCase {
  private static $base;
  private static $pgHost;
  private static $pgUser;
  private static $pgPass;
  private static $pgDatabase;

  public static function setUpBeforeClass() {
    
    // Get connection params
    global $pg_host, $pg_user, $pg_pass, $pg_database;
    self::$pgHost = $pg_host;
    self::$pgUser = $pg_user;
    self::$pgPass = $pg_pass;
    self::$pgDatabase = $pg_database;
    self::assertNotNull(self::$pgHost);
    self::assertNotNull(self::$pgUser);
    self::assertNotNull(self::$pgPass);
    self::assertNotNull(self::$pgDatabase);
    
    // Create object
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);    
  }

  protected function assertPreConditions()
  {
    //    echo "\n".'*** pre conditions'."\n";
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization,structure,users}', "
			     ."(SELECT par_id FROM organ.participant ORDER BY par_id LIMIT 1));");

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    //    echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }


  public function testUsergroupAdd() {
    $name1 = 'Usergroup 1';
    $name2 = 'Usergroup 2';
    $ugr1 = self::$base->login->usergroup_add($this->token, $name1);
    $this->assertGreaterThan(0, $ugr1);    
    $ugr2 = self::$base->login->usergroup_add($this->token, $name2);
    $this->assertGreaterThan($ugr1, $ugr2);    
  }

  public function testUsergroupList() {
    $name1 = 'Usergroup 1';
    $name2 = 'Usergroup 2';
    $ugr1 = self::$base->login->usergroup_add($this->token, $name1);
    $ugr2 = self::$base->login->usergroup_add($this->token, $name2);
    $ugrs = self::$base->login->usergroup_list($this->token);
    $found = 0;
    foreach ($ugrs as $ugr) {
      if ($ugr['ugr_id'] == $ugr1) {
	$this->assertEquals($name1, $ugr['ugr_name']);
	$found++;
      } else if ($ugr['ugr_id'] == $ugr2) {
	$this->assertEquals($name2, $ugr['ugr_name']);
	$found++;
      }
    }
    $this->assertEquals(2, $found);
  }
  
  public function testUserUsergroupSet() {
    $ugrName = 'Usergroup name';
    $ugr = self::$base->login->usergroup_add($this->token, $ugrName);

    $loginUser = 'user';
    $parFirstname = 'Paul';
    $parLastname = 'Napoléon';
    $parId = self::$base->organ->participant_add($this->token, $parFirstname, $parLastname);
    self::$base->login->user_add($this->token, $loginUser, null, $parId);

    self::$base->login->user_usergroup_set($this->token, $loginUser, $ugr);
    
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($ugr, $user['ugr_id']);
  }
  
  public function testUsergroupPortalSet() {

    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3);
    $this->assertGreaterThan($porId1, $porId2);
    $this->assertGreaterThan($porId2, $porId3);

    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName);
    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId2, $porId1));

    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1), 
			       array('por_id'=>$porId2, 'por_name'=>$porName2)), 
			$porIds);
    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId3, $porId1, $porId2));

    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1), 
			       array('por_id'=>$porId2, 'por_name'=>$porName2), 
			       array('por_id'=>$porId3, 'por_name'=>$porName3)), 
			$porIds);

    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId3, $porId1));
    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1), 
			       array('por_id'=>$porId3, 'por_name'=>$porName3)), 
			$porIds);

    self::$base->login->usergroup_set_portals($this->token, $ugr, array());
    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(null, $porIds);

    self::$base->login->usergroup_set_portals($this->token, $ugr, null);
    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(null, $porIds);

  }

  public function testUsergroupGroupSet() {
    $orgNameA = 'Organization A';
    $orgNameB = 'Organization B';
    $orgIdA = self::$base->organ->organization_add($this->token, $orgNameA, 'desc A', true);
    $orgIdB = self::$base->organ->organization_add($this->token, $orgNameB, 'desc B', false);
    $grpNameA1 = 'Group A1';
    $grpNameA2 = 'Group A2';
    $grpNameB1 = 'Group B1';
    $grpDescA1 = 'desc A1';
    $grpDescA2 = 'desc A2';
    $grpDescB1 = 'desc B1';
    $grpIdA1 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA1, $grpDescA1);
    $grpIdA2 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA2, $grpDescA2);
    $grpIdB1 = self::$base->organ->group_add($this->token, $orgIdB, $grpNameB1, $grpDescB1);
    $this->assertGreaterThan($grpIdA1, $grpIdA2);
    $this->assertGreaterThan($grpIdA2, $grpIdB1);
    
    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName);
    
    self::$base->login->usergroup_set_groups($this->token, $ugr, array($grpIdA2, $grpIdA1));
    $grpIds = self::$base->login->usergroup_group_list($this->token, $ugr);
    $this->assertEquals(array (array('grp_id'=>$grpIdA1, 'org_id' => $orgIdA, 
				     'grp_name'=>$grpNameA1, 'grp_description' => $grpDescA1, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization'), 
			       array('grp_id'=>$grpIdA2, 'org_id' => $orgIdA, 
				     'grp_name'=>$grpNameA2, 'grp_description' => $grpDescA2, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization')), 
			$grpIds);
    
    self::$base->login->usergroup_set_groups($this->token, $ugr, array($grpIdB1, $grpIdA1, $grpIdA2));
    $grpIds = self::$base->login->usergroup_group_list($this->token, $ugr);
    $this->assertEquals(array (array('grp_id'=>$grpIdA1, 'org_id' => $orgIdA, 
				     'grp_name'=>$grpNameA1, 'grp_description' => $grpDescA1, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization'), 
			       array('grp_id'=>$grpIdA2, 'org_id' => $orgIdA, 
				     'grp_name'=>$grpNameA2, 'grp_description' => $grpDescA2, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization'),
			       array('grp_id'=>$grpIdB1, 'org_id' => $orgIdB, 
				     'grp_name'=>$grpNameB1, 'grp_description' => $grpDescB1, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization')),
			$grpIds);
    
    self::$base->login->usergroup_set_groups($this->token, $ugr, array($grpIdA1));
    $grpIds = self::$base->login->usergroup_group_list($this->token, $ugr);
    $this->assertEquals(array (array('grp_id'=>$grpIdA1, 'org_id' => $orgIdA, 
				     'grp_name'=>$grpNameA1, 'grp_description' => $grpDescA1, 
				     'grp_mandatory' => false, 'grp_orientation' => 'organization')),
			$grpIds);
    
    self::$base->login->usergroup_set_groups($this->token, $ugr, array());
    $grpIds = self::$base->login->usergroup_group_list($this->token, $ugr);
    $this->assertEquals(null, $grpIds);

    self::$base->login->usergroup_set_groups($this->token, $ugr, null);
    $grpIds = self::$base->login->usergroup_group_list($this->token, $ugr);
    $this->assertEquals(null, $grpIds);
  }

}
?>
