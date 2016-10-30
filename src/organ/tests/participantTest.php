<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class participantTest extends PHPUnit_Framework_TestCase {
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			 
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testParticipantAdd() {
    $firstname = 'Pierre';
    $lastname = 'Dupont';
    $id = self::$base->organ->participant_add($this->token, $firstname, $lastname);
    $this->assertGreaterThan(0, $id);
  }  

  public function testParticipantList() {
    $firstname = 'Pierre';
    $lastname = 'Dupont';
    $id = self::$base->organ->participant_add($this->token, $firstname, $lastname);
    $this->assertGreaterThan(0, $id);
    $list = self::$base->organ->participant_list($this->token);
    $back = array_filter($list, function($x) use($id) { return $x['par_id'] == $id; });
    $this->assertEquals(1, count($back));
  }  

  public function testParticipantJson() {
    $firstname = 'Pierre';
    $lastname = 'Dupont';
    $id = self::$base->organ->participant_add($this->token, $firstname, $lastname);
    $this->assertGreaterThan(0, $id);
    $req = [ 'par_id' => true ];
    $json = self::$base->organ->participant_json($this->token, $id, json_encode($req));
    $this->assertNotNull($json->par_id);
    $this->assertNull($json->par_firstname);
    $this->assertNull($json->par_lastname);

    $req = [ 'par_firstname' => true, 'par_lastname' => true, 'par_email' => true ];
    $json = self::$base->organ->participant_json($this->token, $id, json_encode($req));
    $this->assertNull($json->par_id);
    $this->assertNotNull($json->par_firstname);
    $this->assertNotNull($json->par_lastname);
  }  
}
