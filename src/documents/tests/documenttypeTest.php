<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class documenttypeTest extends PHPUnit_Framework_TestCase {
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
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			     
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testDocumentTypeAdd() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);
  }  

  public function testDocumentTypeGet() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $dty = self::$base->documents->document_type_get($this->token, $id);
    $this->assertEquals($name, $dty['dty_name']);
    $this->assertEquals($indiv, $dty['dty_individual_name']);
  }  

  public function testDocumentTypeGetUnknown() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $dty = self::$base->documents->document_type_get($this->token, $id + 1);
  }  

  public function testDocumentTypeUpdate() {
    $name1 = 'a document type';
    $indiv1 = true;
    $id = self::$base->documents->document_type_add($this->token, $name1, $indiv1);

    $name2 = 'another document type';
    $indiv2 = false;

    self::$base->documents->document_type_update($this->token, $id, $name2, $indiv2);
    $dty = self::$base->documents->document_type_get($this->token, $id);
    $this->assertEquals($name2, $dty['dty_name']);
    $this->assertEquals($indiv2, $dty['dty_individual_name']);
  }  

  public function testDocumentTypeUpdateUnknown() {
    $name1 = 'a document type';
    $indiv1 = true;
    $id = self::$base->documents->document_type_add($this->token, $name1, $indiv1);

    $name2 = 'another document type';
    $indiv2 = false;

    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->documents->document_type_update($this->token, $id + 1, $name2, $indiv2);
  }  

  public function testDocumentTypeDelete() {
    $name1 = 'a document type';
    $indiv1 = true;
    $id = self::$base->documents->document_type_add($this->token, $name1, $indiv1);
    $this->assertGreaterThan(0, $id);
    self::$base->documents->document_type_delete($this->token, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->documents->document_type_get($this->token, $id);
  }  

  public function testDocumentTypeDeleteUnknown() {
    $name1 = 'a document type';
    $indiv1 = true;
    $id = self::$base->documents->document_type_add($this->token, $name1, $indiv1);
    $this->assertGreaterThan(0, $id);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->documents->document_type_delete($this->token, $id + 1);
  }  
  

  public function testDocumentTypeList() {
    $name1 = 'document type 1';
    $indiv1 = true;
    $id1 = self::$base->documents->document_type_add($this->token, $name1, $indiv1);
    $name2 = 'document type 2';
    $indiv2 = false;
    $id2 = self::$base->documents->document_type_add($this->token, $name2, $indiv2);

    $dtys = self::$base->documents->document_type_list($this->token);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['dty_id']; }, $dtys));
  }  

  public function testDocumentTypeFilter() {
    $name1 = 'document type 1';
    $indiv1 = true;
    $id1 = self::$base->documents->document_type_add($this->token, $name1, $indiv1);
    $name2 = 'document type 2';
    $indiv2 = false;
    $id2 = self::$base->documents->document_type_add($this->token, $name2, $indiv2);

    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->documents->document_type_set_topics($this->token, $id1, [ $top1, $top2 ]);
    self::$base->documents->document_type_set_topics($this->token, $id2, [ $top1, $top3 ]);

    $dtys = self::$base->documents->document_type_filter($this->token, NULL);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['dty_id']; }, $dtys));

    $dtys = self::$base->documents->document_type_filter($this->token, [$top2]);
    $this->assertEquals(array($id1), array_map(function($x) { return $x['dty_id']; }, $dtys));

    $dtys = self::$base->documents->document_type_filter($this->token, [$top1]);
    $this->assertEquals(array($id1, $id2), array_map(function($x) { return $x['dty_id']; }, $dtys));
  }  

  public function testDocumentTypeSetTopics() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->documents->document_type_set_topics($this->token, $id, array($top1, $top3));
  }  

  public function testDocumentTypeTopicsList() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    self::$base->documents->document_type_set_topics($this->token, $id, array($top1, $top3));
    $tops = self::$base->documents->document_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top3), 
			array_map(function ($m) { return $m['top_id']; }, $tops));
  }  

  public function testDocumentTypeSetOrganizations() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    self::$base->documents->document_type_set_organizations($this->token, $id, array($org1, $org3));
  }  

  public function testDocumentTypeOrganizationsList() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);

    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    self::$base->documents->document_type_set_organizations($this->token, $id, array($org1, $org3));
    $orgs = self::$base->documents->document_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org3), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));
  }  

  public function testDocumentTypeSetOrganizationsExternal() {
    $name = 'a document type';
    $indiv = true;
    $id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $this->assertGreaterThan(0, $id);
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', false);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    self::$base->documents->document_type_set_organizations($this->token, $id, array($org1, $org3));
  }  

  public function testDocumentTypeAddDetails() {
    $name = 'a document type';
    $indiv = true;
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);
    $id = self::$base->documents->document_type_add_details($this->token, $name, $indiv, array($top1, $top3), array($org1, $org2));
    $this->assertGreaterThan(0, $id);

    $dty = self::$base->documents->document_type_get($this->token, $id);
    $this->assertEquals($name, $dty['dty_name']);
    $this->assertEquals($indiv, $dty['dty_individual_name']);

    $tops = self::$base->documents->document_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top3), 
			array_map(function ($m) { return $m['top_id']; }, $tops));

    $orgs = self::$base->documents->document_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org2), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));    
  }

  public function testDocumentTypeUpdateDetails() {
    $name1 = 'a document type';
    $indiv1 = true;
    $id = self::$base->documents->document_type_add($this->token, $name1, $indiv1);
    $tops = self::$base->documents->document_type_topics_list($this->token, $id);
    $this->assertEquals(0, count($tops));
    $orgs = self::$base->documents->document_type_organizations_list($this->token, $id);
    $this->assertEquals(0, count($orgs));
    $name2 = 'another document type';
    $indiv2 = false;

    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $top3 = self::$base->organ->topic_add($this->token, 'topic 3', 'desc 3', 'health', '#000000');
    $org1 = self::$base->organ->organization_add($this->token, 'org 1', 'desc 1', true);
    $org2 = self::$base->organ->organization_add($this->token, 'org 2', 'desc 2', true);
    $org3 = self::$base->organ->organization_add($this->token, 'org 3', 'desc 3', true);

    self::$base->documents->document_type_update_details($this->token, $id, $name2, $indiv2, array($top1, $top2), array($org1, $org3));

    $dty = self::$base->documents->document_type_get($this->token, $id);
    $this->assertEquals($name2, $dty['dty_name']);
    $this->assertEquals($indiv2, $dty['dty_individual_name']);

    $tops = self::$base->documents->document_type_topics_list($this->token, $id);
    $this->assertEquals(array($top1, $top2), 
			array_map(function ($m) { return $m['top_id']; }, $tops));

    $orgs = self::$base->documents->document_type_organizations_list($this->token, $id);
    $this->assertEquals(array($org1, $org3), 
			array_map(function ($m) { return $m['org_id']; }, $orgs));    
  }  

}
