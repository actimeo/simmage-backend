<?php
require_once '../../pgproc/php/pgprocedures.php';
require_once '../../config.inc.php';

class userTest extends PHPUnit_Framework_TestCase {
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
    //    echo "\n".'*** pre conditions'."\n";
    self::$base->startTransaction();
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('".$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization,users}');");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    //    echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }

  public function testUserAdd() {
    $loginAdmin = 'admin';
    $pwdAdmin = 'ksfdjgsfdyubg';    
    
    $loginUser = 'a user';
    self::$base->login->user_add($this->token, $loginUser, array('users'), null);
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($user['usr_login'], $loginUser);
    $this->assertEquals($user['usr_rights'], array('users'));			      

    $res = self::$base->login->user_login($loginUser, $user['usr_temp_pwd'], array('users'));
    $this->assertGreaterThan(0, $this->token);
  }

  public function testUserParticipantSet() {
    $loginUser = 'user';
    $parFirstname = 'Paul';
    $parLastname = 'Napoléon';
    self::$base->login->user_add($this->token, $loginUser, null, null);
    $parId = self::$base->organ->participant_add($this->token, $parFirstname, $parLastname);
    self::$base->login->user_participant_set($this->token, $loginUser, $parId);
    $user = self::$base->login->user_info($this->token, $loginUser);
    $this->assertEquals($user['par_id'], $parId);
  }

}
?>
