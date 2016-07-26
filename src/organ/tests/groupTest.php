<?php
require_once '../../pgproc/php/pgprocedures.php';
require_once '../../config.inc.php';

class groupTest extends PHPUnit_Framework_TestCase {
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
    self::$base = new PgProcedures2 (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);    
  }

  protected function assertPreConditions()
  {
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('".$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}');");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testGroupAdd() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $this->assertGreaterThan(0, $orgId);

    $grp_name = 'a group';
    $grp_desc = 'a group desc';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grp_name, $grp_desc);
    $this->assertGreaterThan(0, $grpId);
  }  

  public function testGroupGet() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $this->assertGreaterThan(0, $orgId);

    $grp_name = 'a group';
    $grp_desc = 'a group desc';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grp_name, $grp_desc);
    $this->assertGreaterThan(0, $grpId);

    $grp = self::$base->organ->group_get($this->token, $grpId);
    $this->assertEquals($grp_name, $grp['grp_name']);
  }  

  public function testGroupAddSameNameDifferentOrganization() {
    $name1 = 'organization 1';
    $desc1 = 'an organization 1 desc';
    $orgId1 = self::$base->organ->organization_add($this->token, $name1, $desc1, true);
    $name2 = 'organization 2';
    $desc2 = 'an organization 2 desc';
    $orgId2 = self::$base->organ->organization_add($this->token, $name2, $desc2, true);

    $this->assertGreaterThan(0, $orgId1);
    $this->assertGreaterThan(0, $orgId2);

    $grpName = 'a same group name';
    $grp_desc = 'a same group desc';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId1, $grpName, $grp_desc);
    $grpId2 = self::$base->organ->group_add($this->token, $orgId2, $grpName, $grp_desc);
  }  

  /**
   * Add two groups with same name to the same organization
   * @expectedException PgProcException
   */  
  public function testGroupAddSameNameSameOrganization() {
    $name = 'organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grpName = 'a same group';
    $grp_desc = 'a same group desc';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId, $grpName, $grp_desc);
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, $grpName, $grp_desc);
  } 

  public function testGroupSet() {
    $name = 'organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);
    
    $grpName = 'a group';
    $grp_desc = 'a group desc';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grpName, $grp_desc);
    $this->assertGreaterThan(0, $grpId);
    
    $grpNotes = 'a note';
    self::$base->organ->group_set($this->token, $grpId, $grpNotes);
    $grp = self::$base->organ->group_get($this->token, $grpId);
  }
  
  public function testGroupList() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grp_name1 = 'group 1';
    $grp_desc1 = 'a group 1 desc';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId, $grp_name1, $grp_desc1);
    $this->assertGreaterThan(0, $grpId1);
    
    $grp_name2 = 'group 2';
    $grp_desc2 = 'a group 2 desc';
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, $grp_name2, $grp_desc2);
    $this->assertGreaterThan(0, $grpId2);
    
    $grps = self::$base->organ->group_list($this->token, $orgId);
    $this->assertEquals(2, count($grps));
  }
  
  public function testGroupDelete() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grp_name = 'a group';
    $grp_desc = 'a group desc';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grp_name, $grp_desc);

    $grps = self::$base->organ->group_list($this->token, $orgId);
    $this->assertEquals(1, count($grps));
    
    self::$base->organ->group_delete($this->token, $grpId);
    $grpsAfter = self::$base->organ->group_list($this->token, $orgId);
    $this->assertEquals(0, count($grpsAfter));
  }

  public function testGroupRename() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grp_name1 = 'a group';
    $grp_desc = 'a group desc';
    $grp_name2 = 'another group';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grp_name1, $grp_desc);
    self::$base->organ->group_rename($this->token, $grpId, $grp_name2);
    $grp = self::$base->organ->group_get($this->token, $grpId);
    $this->assertEquals($grp_name2, $grp['grp_name']);
  }

  /**
   * Trying to rename an inexistant portal raises an exception
   * @expectedException PgProcException
   */
  public function testGroupRenameUnknown() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $orgId = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grp_name1 = 'a group';
    $grp_desc = 'a group desc';
    $grp_name2 = 'another group';
    $grpId = self::$base->organ->group_add($this->token, $orgId, $grp_name1, $grp_desc);
    self::$base->organ->group_rename($this->token, $grpId+1, $grp_name2);
  }

  /**
   * Trying to delete an inexistant portal raises an exception
   * @expectedException PgProcException
   */
  public function testGroupDeleteUnknown() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);

    $grp_name = 'a service group';
    $grp_desc = 'a group desc';
    $grpId = self::$base->organ->group_add($this->token, $id, $grp_name, $grp_desc);

    self::$base->organ->group_delete($this->token, $grpId+1);
  }

  /**
   * Trying to delete a portal with null value as por_id raises an exception
   * @expectedException PgProcException
   */
  public function testServiceDeleteNull() {
    self::$base->organ->group_delete($this->token, null);
  }
}
