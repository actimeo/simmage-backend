<?php
require_once '../../pgproc/php/pgprocedures.php';
require_once '../../config.inc.php';

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

  public function testTopicAdd() {
    $name = 'topic 1';
    $desc = 'topic 1 description';
    $id = self::$base->organ->topic_add($this->token, $name, $desc);
    $this->assertGreaterThan(0, $id);
  }  

  /**
   * Add two portals with same name
   * @expectedException PgProcException
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

}
