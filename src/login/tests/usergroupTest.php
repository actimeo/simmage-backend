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
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization,structure,users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");						 

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
    $ugr1 = self::$base->login->usergroup_add($this->token, $name1, null, '{preadmission, admission, present, left}');
    $this->assertGreaterThan(0, $ugr1);
    $ugr2 = self::$base->login->usergroup_add($this->token, $name2, null, '{preadmission, admission, present, left}');
    $this->assertGreaterThan($ugr1, $ugr2);    
  }

  public function testUsergroupList() {
    $name1 = 'Usergroup 1';
    $name2 = 'Usergroup 2';
    $ugr1 = self::$base->login->usergroup_add($this->token, $name1, null, '{preadmission, admission, present, left}');
    $ugr2 = self::$base->login->usergroup_add($this->token, $name2, null, '{preadmission, admission, present, left}');
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

  public function testUsergroupGet() {
    $name = 'usergroup';
    $ugrId = self::$base->login->usergroup_add($this->token, $name, null, '{preadmission, admission, present, left}');

    $this->assertGreaterThan(0, $ugrId);

    $ugr = self::$base->login->usergroup_get($this->token, $ugrId);

    $this->assertEquals('usergroup', $ugr['ugr_name']);
  }

  public function testUsergroupUpdate() {
    $name = 'Usergroup';
    $ugrId = self::$base->login->usergroup_add($this->token, $name, null, '{preadmission, admission, present, left}');

    $this->assertGreaterThan(0, $ugrId);

    self::$base->login->usergroup_update($this->token, $ugrId, 'Renamed usergroup', null, '{preadmission, admission, present, left}');

    $ugr = self::$base->login->usergroup_get($this->token, $ugrId);

    $this->assertEquals('Renamed usergroup', $ugr['ugr_name']);
  }
  
  public function testUserUsergroupSet() {
    $ugrName = 'Usergroup name';
    $ugr = self::$base->login->usergroup_add($this->token, $ugrName, null, '{preadmission, admission, present, left}');

    $loginUser = 'user';
    $parFirstname = 'Paul';
    $parLastname = 'Napoléon';
    $parId = self::$base->organ->participant_add($this->token, $parFirstname, $parLastname);
    self::$base->login->user_add($this->token, $loginUser, null, $parId, null);

    self::$base->login->user_usergroup_set($this->token, $loginUser, $ugr);
    
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($ugr, $user['ugr_id']);
  }
  
  public function testUsergroupPortalSet() {

    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porDesc1 = 'portal desc 1';
    $porDesc2 = 'portal desc 2';
    $porDesc3 = 'portal desc 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1, $porDesc1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2, $porDesc2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3, $porDesc3);
    $this->assertGreaterThan($porId1, $porId2);
    $this->assertGreaterThan($porId2, $porId3);

    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');
    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId2, $porId1));

    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1, 'por_description' => $porDesc1), 
			       array('por_id'=>$porId2, 'por_name'=>$porName2, 'por_description' => $porDesc2)), 
			$porIds);
    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId3, $porId1, $porId2));

    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1, 'por_description' => $porDesc1), 
			       array('por_id'=>$porId2, 'por_name'=>$porName2, 'por_description' => $porDesc2), 
			       array('por_id'=>$porId3, 'por_name'=>$porName3, 'por_description' => $porDesc3)),
			$porIds);

    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId3, $porId1));
    $porIds = self::$base->login->usergroup_portal_list($this->token, $ugr);
    $this->assertEquals(array (array('por_id'=>$porId1, 'por_name'=>$porName1, 'por_description' => $porDesc1), 
			       array('por_id'=>$porId3, 'por_name'=>$porName3, 'por_description' => $porDesc3)), 
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
    $orgIdB = self::$base->organ->organization_add($this->token, $orgNameB, 'desc B', true);
    $grpNameA1 = 'Group A1';
    $grpNameA2 = 'Group A2';
    $grpNameB1 = 'Group B1';
    $grpDescA1 = 'desc A1';
    $grpDescA2 = 'desc A2';
    $grpDescB1 = 'desc B1';
    $grpIdA1 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA1, $grpDescA1, false, 'organization');
    $grpIdA2 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA2, $grpDescA2, false, 'organization');
    $grpIdB1 = self::$base->organ->group_add($this->token, $orgIdB, $grpNameB1, $grpDescB1, false, 'organization');
    $this->assertGreaterThan($grpIdA1, $grpIdA2);
    $this->assertGreaterThan($grpIdA2, $grpIdB1);
    
    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');
    
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

  public function testUsergroupTopicSet() {
    $topName1 = 'topic 1';
    $topDesc1 = 'description 1';
    $icon1 = 'health';
    $color1 = '#ffffff';
    $topId1 = self::$base->organ->topic_add($this->token, $topName1, $topDesc1, $icon1, $color1);
    $topName2 = 'topic 2';
    $topDesc2 = 'description 2';
    $icon2 = 'health';
    $color2 = '#ffffff';
    $topId2 = self::$base->organ->topic_add($this->token, $topName2, $topDesc2, $icon2, $color2);
    $topName3 = 'topic 3';
    $topDesc3 = 'description 3';
    $icon3 = 'health';
    $color3 = '#ffffff';
    $topId3 = self::$base->organ->topic_add($this->token, $topName3, $topDesc3, $icon3, $color3);
    $this->assertGreaterThan($topId1, $topId2);
    $this->assertGreaterThan($topId2, $topId3);

    $usergroupName = 'An usergroup';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');
    self::$base->login->usergroup_set_topics($this->token, $ugr, array($topId2, $topId1), null);

    $topIds = self::$base->login->usergroup_topic_list($this->token, $ugr);
    $this->assertEquals(array (array('top_id'=>$topId1, 'top_name'=>$topName1, 'top_description'=>$topDesc1, 'top_icon'=>$icon1, 'top_color'=>$color1),
			       array('top_id'=>$topId2, 'top_name'=>$topName2, 'top_description'=>$topDesc2, 'top_icon'=>$icon2, 'top_color'=>$color2)),
			$topIds);

    self::$base->login->usergroup_set_topics($this->token, $ugr, array($topId2, $topId3, $topId1), null);
    $topIds = self::$base->login->usergroup_topic_list($this->token, $ugr);
    $this->assertEquals(array (array('top_id'=>$topId1, 'top_name'=>$topName1, 'top_description'=>$topDesc1, 'top_icon'=>$icon1, 'top_color'=>$color1),
                               array('top_id'=>$topId2, 'top_name'=>$topName2, 'top_description'=>$topDesc2, 'top_icon'=>$icon2, 'top_color'=>$color2),
			       array('top_id'=>$topId3, 'top_name'=>$topName3, 'top_description'=>$topDesc3, 'top_icon'=>$icon3, 'top_color'=>$color3)),
                        $topIds);

    self::$base->login->usergroup_set_topics($this->token, $ugr, array($topId3, $topId1), null);
    $topIds = self::$base->login->usergroup_topic_list($this->token, $ugr);
    $this->assertEquals(array (array('top_id'=>$topId1, 'top_name'=>$topName1, 'top_description'=>$topDesc1, 'top_icon'=>$icon1, 'top_color'=>$color1),
                               array('top_id'=>$topId3, 'top_name'=>$topName3, 'top_description'=>$topDesc3, 'top_icon'=>$icon3, 'top_color'=>$color3)),
                        $topIds);

    self::$base->login->usergroup_set_topics($this->token, $ugr, array(), null);
    $topIds = self::$base->login->usergroup_topic_list($this->token, $ugr);
    $this->assertEquals(null, $topIds);

    self::$base->login->usergroup_set_topics($this->token, $ugr, null, null);
    $topIds = self::$base->login->usergroup_topic_list($this->token, $ugr);
    $this->assertEquals(null, $topIds);
  }

  public function testUsergroupExternalGroupSet() {
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
    $grpIdA1 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA1, $grpDescA1, false, 'organization');
    $grpIdA2 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA2, $grpDescA2, false, 'organization');
    $grpIdB1 = self::$base->organ->group_add($this->token, $orgIdB, $grpNameB1, $grpDescB1, false, 'organization');

    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');
    
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->login->usergroup_set_groups($this->token, $ugr, 
					     array($grpIdA2, $grpIdA1, $grpIdB1));
  }

  public function testUsergroupDelete() {
    $porName1 = 'portal 1';
    $porName2 = 'portal 2';
    $porName3 = 'portal 3';
    $porDesc1 = 'portal desc 1';
    $porDesc2 = 'portal desc 2';
    $porDesc3 = 'portal desc 3';
    $porId1 = self::$base->portal->portal_add($this->token, $porName1, $porDesc1);
    $porId2 = self::$base->portal->portal_add($this->token, $porName2, $porDesc2);
    $porId3 = self::$base->portal->portal_add($this->token, $porName3, $porDesc3);
    $this->assertGreaterThan($porId1, $porId2);
    $this->assertGreaterThan($porId2, $porId3);

    $orgNameA = 'Organization A';
    $orgNameB = 'Organization B';
    $orgIdA = self::$base->organ->organization_add($this->token, $orgNameA, 'desc A', true);
    $orgIdB = self::$base->organ->organization_add($this->token, $orgNameB, 'desc B', true);
    $grpNameA1 = 'Group A1';
    $grpNameA2 = 'Group A2';
    $grpNameB1 = 'Group B1';
    $grpDescA1 = 'desc A1';
    $grpDescA2 = 'desc A2';
    $grpDescB1 = 'desc B1';
    $grpIdA1 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA1, $grpDescA1, false, 'organization');
    $grpIdA2 = self::$base->organ->group_add($this->token, $orgIdA, $grpNameA2, $grpDescA2, false, 'organization');
    $grpIdB1 = self::$base->organ->group_add($this->token, $orgIdB, $grpNameB1, $grpDescB1, false, 'organization');
    $this->assertGreaterThan($grpIdA1, $grpIdA2);
    $this->assertGreaterThan($grpIdA2, $grpIdB1);

    $topName1 = 'topic 1';
    $topDesc1 = 'description 1';
    $icon1 = 'health';
    $color1 = '#ffffff';
    $topId1 = self::$base->organ->topic_add($this->token, $topName1, $topDesc1, $icon1, $color1);
    $topName2 = 'topic 2';
    $topDesc2 = 'description 2';
    $icon2 = 'health';
    $color2 = '#ffffff';
    $topId2 = self::$base->organ->topic_add($this->token, $topName2, $topDesc2, $icon2, $color2);
    $this->assertGreaterThan(0, $topId1);
    $this->assertGreaterThan($topId1, $topId2);

    $usergroupName = 'A user group';
    $ugr = self::$base->login->usergroup_add($this->token, $usergroupName, null, '{preadmission, admission, present, left}');

    self::$base->login->usergroup_set_groups($this->token, $ugr, array($grpIdB1, $grpIdA1, $grpIdA2));

    self::$base->login->usergroup_set_portals($this->token, $ugr, array($porId3, $porId1, $porId2));

    self::$base->login->usergroup_set_topics($this->token, $ugr, array($topId1, $topId2), null);

    self::$base->login->usergroup_delete($this->token, $ugr);
  }


}
?>
