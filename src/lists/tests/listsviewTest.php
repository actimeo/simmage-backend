<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class ListsviewTest extends PHPUnit_Framework_TestCase {
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


  public function testListsViewAdd() {
    $name = 'a lists view';
    $id = self::$base->lists->listsview_add($this->token, $name);
    $this->assertGreaterThan(0, $id);
  }  

  public function testListsViewGet() {
    $name = 'an lists view';
    $id = self::$base->lists->listsview_add($this->token, $name);
    $this->assertGreaterThan(0, $id);

    $evv = self::$base->lists->listsview_get($this->token, $id);
    $this->assertEquals($name, $evv['liv_name']);
  }  

  public function testListsViewList() {
    $name = 'an lists view';
    $id = self::$base->lists->listsview_add($this->token, $name);

    $evvs = self::$base->lists->listsview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($name, $evv['liv_name']);
  }

  public function testListsViewUpdate() {
    $name = 'an lists view';
    $id = self::$base->lists->listsview_add($this->token, $name);

    $nameAfter = 'another lists view';
    self::$base->lists->listsview_update($this->token, $id, $nameAfter);

    $evvs = self::$base->lists->listsview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($nameAfter, $evv['liv_name']);
  }

  public function testListsViewDelete() {
    $name = 'an lists view';
    $id = self::$base->lists->listsview_add($this->token, $name);
    $this->assertGreaterThan(0, $id);

    self::$base->lists->listsview_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $evv = self::$base->lists->listsview_get($this->token, $id);
  }  

}
