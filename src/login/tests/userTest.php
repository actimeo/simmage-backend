<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class userTest extends PHPUnit_Framework_TestCase {
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
  }

  protected function assertPostConditions()
  {
    //    echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }

  public function testUserAdd() {    
    $parId = self::$base->organ->participant_add($this->token, 'Super', 'Admin');
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    $loginUser = 'a user';
    self::$base->login->user_add($this->token, $loginUser, array('users'), $parId, null);
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($user['usr_login'], $loginUser);
    $this->assertEquals($user['usr_rights'], array('users'));			      
    $tempPwd = self::$base->login->user_get_temporary_pwd($this->token, $loginUser);    

    $res = self::$base->login->user_login($loginUser, $tempPwd, array('users'));
    $this->assertGreaterThan(0, $this->token);
  }

  public function testUserList() {
    $parId = self::$base->organ->participant_add($this->token, 'Super', 'Admin');
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    

    $listBefore = self::$base->login->user_list($this->token, null);
    
    $loginUser = 'a user';
    self::$base->login->user_add($this->token, $loginUser, array('users'), $parId, null);
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($user['usr_login'], $loginUser);
    $this->assertEquals($user['usr_rights'], array('users'));			      
    $tempPwd = self::$base->login->user_get_temporary_pwd($this->token, $loginUser);    

    $res = self::$base->login->user_login($loginUser, $tempPwd, array('users'));
    $this->assertGreaterThan(0, $this->token);

    $list = self::$base->login->user_list($this->token, null);
    $this->assertEquals(count($listBefore) + 1, count($list));
  }

  public function testUserListDemo() {
    $list = self::$base->login->user_list_demo();
    $ugr = array_filter($list, function($x) { return $x['ugr_name'] === null; });
    $res = array_filter($ugr[0]['usr_login'], function($y) { return $y === 'variation'; });
    $this->assertEquals(1, count($res));
  }

  public function testUserListFilteredByUsergroup() {
    $parId1 = self::$base->organ->participant_add($this->token, 'Super', 'Admin');
    $parId2 = self::$base->organ->participant_add($this->token, 'Lower', 'Admin');

    $listBefore = self::$base->login->user_list($this->token, null);
    
    $loginUser1 = 'a user';
    self::$base->login->user_add($this->token, $loginUser1, array('users'), $parId1, null);

    $loginUser2 = 'another user';
    self::$base->login->user_add($this->token, $loginUser2, array('users'), $parId2, null);

    $usergroupName1 = 'A user group';
    $ugr1 = self::$base->login->usergroup_add($this->token, $usergroupName1, null, '{preadmission, admission, present, left}');

    $usergroupName2 = 'Another user group';
    $ugr2 = self::$base->login->usergroup_add($this->token, $usergroupName2, null, '{preadmission, admission, present, left}');
    
    self::$base->login->user_usergroup_set($this->token, $loginUser1, $ugr1);

    $list1 = self::$base->login->user_list($this->token, $ugr1);
    $this->assertEquals(1, count($list1));

    $list2 = self::$base->login->user_list($this->token, $ugr2);
    $this->assertEquals(0, count($list2));

    $listAll = self::$base->login->user_list($this->token, null);
    $this->assertEquals(count($listBefore) + 2, count($listAll));
  }

  public function testUserRightList() {
    $list = self::$base->login->user_right_list();
    $this->assertEquals('structure', $list[0]);
  }
}
?>
