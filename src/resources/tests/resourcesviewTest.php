<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class ResourcesviewTest extends PHPUnit_Framework_TestCase {
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
    $res = self::$base->login->user_login($login, $pwd, null, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }


  public function testResourcesViewAdd() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);
  }  

  public function testResourcesViewGet() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    $evv = self::$base->resources->resourcesview_get($this->token, $id);
    $this->assertEquals($name, $evv['rev_name']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }  

  public function testResourcesViewList() {
    $name = 'an resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);

    $evvs = self::$base->resources->resourcesview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($name, $evv['rev_name']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }

  public function testResourcesViewUpdate() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);

    $nameAfter = 'another resources view';
    $top1After = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 1', 'health', '#000000');
    $top2After = self::$base->organ->topic_add($this->token, 'topic 4', 'desc 2', 'health', '#000000');
    self::$base->resources->resourcesview_update($this->token, $id, $nameAfter, [ $top1After, $top2After ]);

    $evvs = self::$base->resources->resourcesview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($nameAfter, $evv['rev_name']);
    $this->assertEquals([$top1After, $top2After], $evv['top_ids']);
  }

  public function testResourcesViewDelete() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    self::$base->resources->resourcesview_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $evv = self::$base->resources->resourcesview_get($this->token, $id);
  }  

  public function testResourcesViewGetTopics() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $id = self::$base->resources->resourcesview_add($this->token, $name, [$top1, $top2]);
    $this->assertGreaterThan(0, $id);

    $tops = self::$base->resources->resourcesview_get_topics($this->token, $id);
    $this->assertEquals(2, count($tops));
  }
}
