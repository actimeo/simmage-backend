<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class eventtypeTest extends PHPUnit_Framework_TestCase {
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

  public function testEventCategoryList() {
    $vals = self::$base->events->event_category_list();
    $this->assertGreaterThan(0, count($vals));
  }

  public function testEventTypeAdd() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);
  }  

  public function testEventTypeGet() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $ety = self::$base->events->event_type_get($this->token, $id);
    $this->assertEquals($cat, $ety['ety_category']);
    $this->assertEquals($name, $ety['ety_name']);
    $this->assertEquals($indiv, $ety['ety_individual_name']);
  }  

  public function testEventTypeGetUnknown() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $ety = self::$base->events->event_type_get($this->token, $id + 1);
  }  

  public function testEventTypeUpdate() {
    $cat1 = 'incident';
    $name1 = 'an event type';
    $indiv1 = true;
    $id = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);

    $cat2 = 'expense';
    $name2 = 'another event type';
    $indiv2 = false;

    self::$base->events->event_type_update($this->token, $id, $cat2, $name2, $indiv2);
    $ety = self::$base->events->event_type_get($this->token, $id);
    $this->assertEquals($cat2, $ety['ety_category']);
    $this->assertEquals($name2, $ety['ety_name']);
    $this->assertEquals($indiv2, $ety['ety_individual_name']);
  }  

  public function testEventTypeUpdateUnknown() {
    $cat1 = 'incident';
    $name1 = 'an event type';
    $indiv1 = true;
    $id = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);

    $cat2 = 'expense';
    $name2 = 'another event type';
    $indiv2 = false;

    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->events->event_type_update($this->token, $id + 1, $cat2, $name2, $indiv2);
  }  

  public function testEventTypeDelete() {
    $cat1 = 'incident';
    $name1 = 'an event type';
    $indiv1 = true;
    $id = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);
    $this->assertGreaterThan(0, $id);
    self::$base->events->event_type_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->events->event_type_get($this->token, $id);
  }  

  public function testEventTypeDeleteUnknown() {
    $cat1 = 'incident';
    $name1 = 'an event type';
    $indiv1 = true;
    $id = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);
    $this->assertGreaterThan(0, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->events->event_type_delete($this->token, $id + 1);
  }  
  

  public function testEventTypeList() {
    $cat1 = 'incident';
    $name1 = 'event type 1';
    $indiv1 = true;
    $id1 = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);
    $cat2 = 'expense';
    $name2 = 'event type 2';
    $indiv2 = false;
    $id2 = self::$base->events->event_type_add($this->token, $cat2, $name2, $indiv2);

    $etys = self::$base->events->event_type_list($this->token, NULL);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['ety_id']; }, $etys));

    $etys = self::$base->events->event_type_list($this->token, 'expense');
    $this->assertEquals(array($id2), array_map(function($x) { return $x['ety_id']; }, $etys));

    $etys = self::$base->events->event_type_list($this->token, 'incident');
    $this->assertEquals(array($id1), array_map(function($x) { return $x['ety_id']; }, $etys));
  }  

  public function testEventTypeFilter() {
    $cat1 = 'incident';
    $name1 = 'event type 1';
    $indiv1 = true;
    $id1 = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);
    $cat2 = 'expense';
    $name2 = 'event type 2';
    $indiv2 = false;
    $id2 = self::$base->events->event_type_add($this->token, $cat2, $name2, $indiv2);

    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->events->event_type_set_topics($this->token, $id1, [ $top1, $top2 ]);
    self::$base->events->event_type_set_topics($this->token, $id2, [ $top1, $top3 ]);

    $etys = self::$base->events->event_type_filter($this->token, NULL, NULL);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['ety_id']; }, $etys));

    $etys = self::$base->events->event_type_filter($this->token, ['expense'], NULL);
    $this->assertEquals(array($id2), array_map(function($x) { return $x['ety_id']; }, $etys));

    $etys = self::$base->events->event_type_filter($this->token, NULL, [$top2]);
    $this->assertEquals(array($id1), array_map(function($x) { return $x['ety_id']; }, $etys));

    $etys = self::$base->events->event_type_filter($this->token, NULL, [$top1]);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['ety_id']; }, $etys));
  }  


  public function testEventTypeSetTopics() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->events->event_type_set_topics($this->token, $id, array($top1, $top3));
  }  

  public function testEventTypeTopicsList() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->events->event_type_set_topics($this->token, $id, array($top1, $top3));
    $tops = self::$base->events->event_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top3), 
			array_map(function ($m) { return $m['top_id']; }, $tops));
  }  

  public function testEventTypeSetOrganizations() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    self::$base->events->event_type_set_organizations($this->token, $id, array($org1, $org3));
  }  

  public function testEventTypeOrganizationsList() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);

    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    self::$base->events->event_type_set_organizations($this->token, $id, array($org1, $org3));
    $orgs = self::$base->events->event_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org3), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));
  }  

  public function testEventTypeSetOrganizationsExternal() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $id = self::$base->events->event_type_add($this->token, $cat, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', false);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->events->event_type_set_organizations($this->token, $id, array($org1, $org3));
  }  

  public function testEventTypeAddDetails() {
    $cat = 'incident';
    $name = 'an event type';
    $indiv = true;
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    $id = self::$base->events->event_type_add_details($this->token, $cat, $name, $indiv, array($top1, $top3), array($org1, $org2));
    $this->assertGreaterThan(0, $id);

    $ety = self::$base->events->event_type_get($this->token, $id);
    $this->assertEquals($cat, $ety['ety_category']);
    $this->assertEquals($name, $ety['ety_name']);
    $this->assertEquals($indiv, $ety['ety_individual_name']);

    $tops = self::$base->events->event_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top3), 
			array_map(function ($m) { return $m['top_id']; }, $tops));

    $orgs = self::$base->events->event_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org2), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));    
  }

  public function testEventTypeUpdateDetails() {
    $cat1 = 'incident';
    $name1 = 'an event type';
    $indiv1 = true;
    $id = self::$base->events->event_type_add($this->token, $cat1, $name1, $indiv1);
    $tops = self::$base->events->event_type_topics_list($this->token, $id);
    $this->assertNull($tops);
    $orgs = self::$base->events->event_type_organizations_list($this->token, $id);
    $this->assertNull($orgs);
    $cat2 = 'expense';
    $name2 = 'another event type';
    $indiv2 = false;

    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);

    self::$base->events->event_type_update_details($this->token, $id, $cat2, $name2, $indiv2, array($top1, $top2), array($org1, $org3));

    $ety = self::$base->events->event_type_get($this->token, $id);
    $this->assertEquals($cat2, $ety['ety_category']);
    $this->assertEquals($name2, $ety['ety_name']);
    $this->assertEquals($indiv2, $ety['ety_individual_name']);

    $tops = self::$base->events->event_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top2), 
			array_map(function ($m) { return $m['top_id']; }, $tops));

    $orgs = self::$base->events->event_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org3), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));    
  }  

  public function testEventDurationList() {
    $list = self::$base->events->event_duration_list();
    $this->assertEquals($list[0], 'standard');
  }
}
