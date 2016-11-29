<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class ObjectivesviewTest extends PHPUnit_Framework_TestCase {
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


  public function testObjectivesViewAdd() {
    $name = 'a objectives view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->objectives->objectivesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);
  }  

  public function testObjectivesViewGet() {
    $name = 'a objectives view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->objectives->objectivesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    $evv = self::$base->objectives->objectivesview_get($this->token, $id);
    $this->assertEquals($name, $evv['obv_name']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }  

  public function testObjectivesViewList() {
    $name = 'an objectives view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->objectives->objectivesview_add($this->token, $name, [ $top1, $top2 ]);

    $evvs = self::$base->objectives->objectivesview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($name, $evv['obv_name']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }

  public function testObjectivesViewUpdate() {
    $name = 'a objectives view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->objectives->objectivesview_add($this->token, $name, [ $top1, $top2 ]);

    $nameAfter = 'another objectives view';
    $top1After = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 1', 'health', '#000000');
    $top2After = self::$base->organ->topic_add($this->token, 'topic 4', 'desc 2', 'health', '#000000');
    self::$base->objectives->objectivesview_update($this->token, $id, $nameAfter, [ $top1After, $top2After ]);

    $evvs = self::$base->objectives->objectivesview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($nameAfter, $evv['obv_name']);
    $this->assertEquals([$top1After, $top2After], $evv['top_ids']);
  }

  public function testObjectivesViewDelete() {
    $name = 'a objectives view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->objectives->objectivesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    self::$base->objectives->objectivesview_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $evv = self::$base->objectives->objectivesview_get($this->token, $id);
  }  
}
