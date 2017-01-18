<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class eventsviewTest extends PHPUnit_Framework_TestCase {
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


  public function testEventsViewAdd() {
    $name = 'an events view';
    $cats = [ 'incident', 'absence' ];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);
  }  

  public function testEventsViewGet() {
    $name = 'an events view';
    $cats = [ 'incident', 'absence' ];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    $evv = self::$base->events->eventsview_get($this->token, $id);
    $this->assertEquals($name, $evv['evv_name']);
    $this->assertEquals($cats, $evv['evv_categories']);
    $this->assertEquals($idEty, $evv['ety_id']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }  

  public function testEventsViewList() {
    $name = 'an events view';
    $cats = [ 'incident', 'absence' ];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [ $top1, $top2 ]);

    $evvs = self::$base->events->eventsview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($name, $evv['evv_name']);
    $this->assertEquals($cats, $evv['evv_categories']);
    $this->assertEquals($idEty, $evv['ety_id']);
    $this->assertEquals([$top1, $top2], $evv['top_ids']);
  }

  public function testEventsViewUpdate() {
    $name = 'an events view';
    $cats = [ 'incident', 'absence' ];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [ $top1, $top2 ]);

    $nameAfter = 'another events view';
    $catsAfter = [ 'incident' ];
    $top1After = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 1', 'health', '#000000');
    $top2After = self::$base->organ->topic_add($this->token, 'topic 4', 'desc 2', 'health', '#000000');
    $catEtyAfter = 'incident';
    $nameEtyAfter = 'another event type';
    $indivEtyAfter = true;
    $idEtyAfter = self::$base->events->event_type_add($this->token, $catEtyAfter, $nameEtyAfter, $indivEtyAfter);
    self::$base->events->eventsview_update($this->token, $id, $nameAfter, $catsAfter, $idEtyAfter, [ $top1After, $top2After ]);

    $evvs = self::$base->events->eventsview_list($this->token);
    $this->assertEquals(1, count($evvs));
    $evv = $evvs[0];
    $this->assertEquals($nameAfter, $evv['evv_name']);
    $this->assertEquals($catsAfter, $evv['evv_categories']);
    $this->assertEquals($idEtyAfter, $evv['ety_id']);
    $this->assertEquals([$top1After, $top2After], $evv['top_ids']);
  }

  public function testEventsViewDelete() {
    $name = 'an events view';
    $cats = [ 'incident', 'absence' ];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [ $top1, $top2 ]);
    $this->assertGreaterThan(0, $id);

    self::$base->events->eventsview_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $evv = self::$base->events->eventsview_get($this->token, $id);
  }

  public function testEventsViewGetTopics() {
    $name = 'an events view';
    $cats = ['incident', 'absence'];
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $catEty = 'incident';
    $nameEty = 'an event type';
    $indivEty = true;
    $idEty = self::$base->events->event_type_add($this->token, $catEty, $nameEty, $indivEty);

    $id = self::$base->events->eventsview_add($this->token, $name, $cats, $idEty, [$top1, $top2]);
    $this->assertGreaterThan(0, $id);

    $tops = self::$base->events->eventsview_get_topics($this->token, $id);
    $this->assertEquals(2, count($tops));
  }
}
