<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class topicTest extends PHPUnit_Framework_TestCase {
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
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     ."(SELECT par_id FROM organ.participant ORDER BY par_id LIMIT 1));");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testTopicAdd() {
    $name = 'topic 1';
    $desc = 'topic 1 description';
    $id = self::$base->organ->topic_add($this->token, $name, $desc);
    $this->assertGreaterThan(0, $id);
  }  

  /**
   * Add two portals with same name
   * @expectedException \actimeo\pgproc\PgProcException
   */  
  public function testTopicAddSameName() {
    $name = 'topic 1';
    $desc1 = 'topic 1 desc';
    $desc2 = 'topic 2 desc';
    $id = self::$base->organ->topic_add($this->token, $name, $desc1);
    $this->assertGreaterThan(0, $id);
    self::$base->organ->topic_add($this->token, $name, $desc2);
  }  

  public function testTopicList() {
    $name1 = 'topic 1';
    $desc1 = 'topic 1 description';
    $id1 = self::$base->organ->topic_add($this->token, $name1, $desc1);
    $this->assertGreaterThan(0, $id1);

    $name2 = 'topic 2';
    $desc2 = 'topic 2 description';
    $id2 = self::$base->organ->topic_add($this->token, $name2, $desc2);
    $this->assertGreaterThan($id1, $id2);

    $topics = self::$base->organ->topics_list($this->token);
    foreach ($topics as $topic) {
      if ($topic['top_id'] == $id1) {
	$this->assertEquals($name1, $topic['top_name']);
	$this->assertEquals($desc1, $topic['top_description']);
      }
    }
  }  

  public function testTopicGet() {
    $name1 = 'topic 1';
    $desc1 = 'topic 1 description';
    $id1 = self::$base->organ->topic_add($this->token, $name1, $desc1);
    $this->assertGreaterThan(0, $id1);

    $name2 = 'topic 2';
    $desc2 = 'topic 2 description';
    $id2 = self::$base->organ->topic_add($this->token, $name2, $desc2);
    $this->assertGreaterThan($id1, $id2);

    $topic = self::$base->organ->topic_get($this->token, $id1);
    $this->assertEquals($id1, $topic['top_id']);
    $this->assertEquals($name1, $topic['top_name']);
    $this->assertEquals($desc1, $topic['top_description']);
  }  

  public function testTopicUpdate() {
    $name1 = 'topic 1';
    $desc1 = 'topic 1 description';
    $id1 = self::$base->organ->topic_add($this->token, $name1, $desc1);
    $this->assertGreaterThan(0, $id1);

    $name2 = 'topic 2';
    $desc2 = 'topic 2 description';
    self::$base->organ->topic_update($this->token, $id1, $name2, $desc2);
    
    $topic = self::$base->organ->topic_get($this->token, $id1);
    $this->assertEquals($id1, $topic['top_id']);
    $this->assertEquals($name2, $topic['top_name']);
    $this->assertEquals($desc2, $topic['top_description']);
  }  

}
