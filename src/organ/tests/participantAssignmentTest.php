<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class participantAssignmentTest extends PHPUnit_Framework_TestCase {
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
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization,users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			 
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];

    // Create a user
    $this->userLogin = 'a user';
    $this->parId = self::$base->organ->participant_add($this->token, 'Pierre', 'MARTIN');
    self::$base->login->user_add($this->token, $this->userLogin, null, $this->parId, null);

    // Create an organization, service and group
    $org = self::$base->organ->organization_add($this->token, "An organization", "a desc", true);
    $this->grpId = self::$base->organ->group_add($this->token, $org, 'A group', 'a desc', false, 'organization');
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testSgaAdd() {
    $id = self::$base->organ->participant_assignment_add($this->token, $this->grpId, $this->parId);
    $this->assertGreaterThan(0, $id);
  }

  public function testSgaLists() {
    $id = self::$base->organ->participant_assignment_add($this->token, $this->grpId, $this->parId);
    $grps = self::$base->organ->participant_assignment_list_groups($this->token, $this->parId);    
    $this->assertEquals(1, count($grps));
    $this->assertEquals('A group', $grps[0]['grp_name']);

    $pars = self::$base->organ->participant_assignment_list_participants($this->token, $this->grpId);
    $this->assertEquals(1, count($pars));
    $this->assertEquals('Pierre', $pars[0]['par_firstname']);
    $this->assertEquals('MARTIN', $pars[0]['par_lastname']);
  }

  public function testSgaDelete() {
    $id = self::$base->organ->participant_assignment_add($this->token, $this->grpId, $this->parId);
    $grps = self::$base->organ->participant_assignment_list_groups($this->token, $this->parId);    
    $this->assertEquals(1, count($grps));
    self::$base->organ->participant_assignment_delete($this->token, $this->grpId, $this->parId);
    $grps = self::$base->organ->participant_assignment_list_groups($this->token, $this->parId);    
    $this->assertNull($grps);    
  }

  /**
   * Trying to delete an inexistant assignment raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testSgaDeleteUnknown() {
    $id = self::$base->organ->participant_assignment_add($this->token, $this->grpId, $this->parId);
    $grps = self::$base->organ->participant_assignment_list_groups($this->token, $this->parId);    
    self::$base->organ->participant_assignment_delete($this->token, $this->grpId+1, $this->parId+1);
  }
}
