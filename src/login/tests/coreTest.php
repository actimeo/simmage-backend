<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class coreTest extends PHPUnit_Framework_TestCase {
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
  }

  protected function assertPostConditions()
  {
    //    echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }

  /**
   * Valid authentication 
   */
  public function testUserLoginOk() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), NULL, "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->assertGreaterThan(0, $res['usr_token']);
    $this->assertFalse($res['usr_temp_pwd']);
    $this->assertNull($res['usr_rights']);
  }
  
  /**
   * Login exception with wrong password
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testUserLoginExceptionWrongPwd() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user(usr_login, usr_salt, usr_rights) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), NULL);");

    $res = self::$base->login->user_login($login, $pwd."X", null);
  }

  /**
   * Login exception with insufficient/no rights
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testUserLoginExceptionNoRight() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user(usr_login, usr_salt, usr_rights) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), null);");

    $res = self::$base->login->user_login($login, $pwd, '{structure}');
  }

  /**
   * Login exception with insufficient/wrong rights
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testUserLoginExceptionWrongRight() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user(usr_login, usr_salt, usr_rights) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), '{users}');");

    $res = self::$base->login->user_login($login, $pwd, '{structure}');
  }

  /**
   * Test user logout
   */
  public function testUserLogoutOk() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");

    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), NULL, "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			     

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->assertGreaterThan(0, $res['usr_token']);

    self::$base->login->user_logout($res['usr_token']);

    // Token should be invalid now
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->login->user_logout($res['usr_token']);
  }

  /**
   * Test password change
   */
  public function testUserChangePassword() {
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    $newpwd = 'sdfjkgh';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), NULL, "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->assertGreaterThan(0, $res['usr_token']);

    self::$base->login->user_change_password($res['usr_token'], $newpwd);
    self::$base->login->user_logout($res['usr_token']);

    $res = self::$base->login->user_login($login, $newpwd, null);
    $this->assertGreaterThan(0, $res['usr_token']);
    $this->assertFalse($res['usr_temp_pwd']);
    
    self::$base->login->user_logout($res['usr_token']);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $res = self::$base->login->user_login($login, 'wrong_pwd', null);
  }

  /**
   * Test password regenerate
   */
  public function testUserRegeneratePassword() {
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    $loginLost = 'toto';
    $pwdLost = 'tata';

    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$loginAdmin."', pgcrypto.crypt('"
			     .$pwdAdmin."', pgcrypto.gen_salt('bf', 8)), '{users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  


    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test2', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$loginLost."', pgcrypto.crypt('".$pwdLost."', pgcrypto.gen_salt('bf', 8)), NULL, "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test2'));");			   


    $admin = self::$base->login->user_login($loginAdmin, $pwdAdmin, null);
    $this->assertGreaterThan(0, $admin['usr_token']);

    $toto = self::$base->login->user_login($loginLost, $pwdLost, null);
    $this->assertGreaterThan(0, $toto['usr_token']);
    self::$base->login->user_logout($toto['usr_token']);
    
    $tmppwd = self::$base->login->user_regenerate_password($admin['usr_token'], $loginLost);

    $toto2 = self::$base->login->user_login($loginLost, $tmppwd, null);
    $this->assertGreaterThan(0, $toto2['usr_token']);
    $this->assertTrue($toto2['usr_temp_pwd']);
    self::$base->login->user_logout($toto2['usr_token']);
    
  }

  /**
   * Test password regenerate on same user
   */
  public function testUserRegenerateMyPassword() {
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$loginAdmin."', pgcrypto.crypt('"
			     .$pwdAdmin."', pgcrypto.gen_salt('bf', 8)), NULL, "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			   


    $admin = self::$base->login->user_login($loginAdmin, $pwdAdmin, null);
    $this->assertGreaterThan(0, $admin['usr_token']);
    
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $tmppwd = self::$base->login->user_regenerate_password($admin['usr_token'], $loginAdmin);    
  }

  public function testUserAdd() {
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$loginAdmin."', pgcrypto.crypt('"
			     .$pwdAdmin."', pgcrypto.gen_salt('bf', 8)), '{users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			

    $admin = self::$base->login->user_login($loginAdmin, $pwdAdmin, array('users'));

    $loginUser = 'a user';
    $parFirstname = 'Paul';
    $parLastname = 'Napoléon';
    $parId = self::$base->organ->participant_add($admin['usr_token'], $parFirstname, $parLastname);
    self::$base->login->user_add($admin['usr_token'], $loginUser, array('users'), $parId);
    $user = self::$base->login->user_info($admin['usr_token'], $loginUser);
    $this->assertEquals($user['usr_login'], $loginUser);
    $this->assertEquals($user['usr_rights'], array('users'));			      

    $res = self::$base->login->user_login($loginUser, $user['usr_temp_pwd'], array('users'));
    $this->assertGreaterThan(0, $res['usr_token']);
  }

  public function testUserParticipantSet() {
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$loginAdmin."', pgcrypto.crypt('"
			     .$pwdAdmin."', pgcrypto.gen_salt('bf', 8)), '{users, organization}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));"); 			  


    $admin = self::$base->login->user_login($loginAdmin, $pwdAdmin, array('users', 'organization'));

    $loginUser = 'user';
    $parFirstname = 'Paul';
    $parLastname = 'Napoléon';
    $parId = self::$base->organ->participant_add($admin['usr_token'], $parFirstname, $parLastname);
    self::$base->login->user_add($admin['usr_token'], $loginUser, null, $parId);
    $user = self::$base->login->user_info($admin['usr_token'], $loginUser);
    $this->assertEquals($user['par_id'], $parId);
  }

}
?>
