<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class EventJsonTest extends PHPUnit_Framework_TestCase {
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
    //	  echo "\n".'*** pre conditions'."\n";
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksdfjgsfdyubg';
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{structure,organization,users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    //	  echo "\n".'*** post conditions'."\n";
    self::$base->rollback();
  }

  public function testEventTypeTopicJson() {
    $eventType = self::$base->events->event_type_add($this->token, 'expense', 'event 1', true);
    $topName1 = 'topic 1';
    $topDesc1 = 'description 1';
    $topIcon1 = 'health';
    $topColor1 = '#123456';
    $topName2 = 'topic 2';
    $topDesc2 = 'description 2';
    $topIcon2 = 'money';
    $topColor2 = '#987654';
    $topId1 = self::$base->organ->topic_add($this->token, $topName1, $topDesc1, $topIcon1, $topColor1);
    $topId2 = self::$base->organ->topic_add($this->token, $topName2, $topDesc2, $topIcon2, $topColor2);

    self::$base->events->event_type_set_topics($this->token, $eventType, array($topId1, $topId2));

    $req = [ 'top_id' => true,
	     'top_name' => true,
	     'top_description' => true,
	     'top_icon' => true,
	     'top_color' => true ];
    $res = self::$base->events->event_type_topic_json($this->token, $eventType, json_encode($req));
    
    $top1 = self::$base->organ->topic_get($this->token, $topId1);
    $top2 = self::$base->organ->topic_get($this->token, $topId2);
    $this->assertEquals($top1['top_id'], $res[0]->top_id);
    $this->assertEquals($top1['top_name'], $res[0]->top_name);
    $this->assertEquals($top1['top_description'], $res[0]->top_description);
    $this->assertEquals($top1['top_icon'], $res[0]->top_icon);
    $this->assertEquals($top1['top_color'], $res[0]->top_color);    
    $this->assertEquals($top2['top_id'], $res[1]->top_id);
    $this->assertEquals($top2['top_name'], $res[1]->top_name);
    $this->assertEquals($top2['top_description'], $res[1]->top_description);
    $this->assertEquals($top2['top_icon'], $res[1]->top_icon);
    $this->assertEquals($top2['top_color'], $res[1]->top_color);
  }

  public function testEventTypeOrganizationJson() {
    $eventType = self::$base->events->event_type_add($this->token, 'expense', 'event 1', true);
    $orgId1 = self::$base->organ->organization_add($this->token, 'organ 1', 'desc 1', true);
    $orgId2 = self::$base->organ->organization_add($this->token, 'organ 2', 'desc 2', true);

    self::$base->events->event_type_set_organizations($this->token, $eventType, array($orgId1, $orgId2));

    $req = [ 'org_id' => true,
	     'org_name' => true,
	     'org_description' => true ];
    $res = self::$base->events->event_type_organization_json($this->token, $eventType, json_encode($req));

    $org1 = self::$base->organ->organization_get($this->token, $orgId1);
    $org2 = self::$base->organ->organization_get($this->token, $orgId2);
    $this->assertEquals($org1['org_id'], $res[0]->org_id);
    $this->assertEquals($org1['org_name'], $res[0]->org_name);
    $this->assertEquals($org1['org_description'], $res[0]->org_description);
    $this->assertEquals($org2['org_id'], $res[1]->org_id);
    $this->assertEquals($org2['org_name'], $res[1]->org_name);
    $this->assertEquals($org2['org_description'], $res[1]->org_description);
  }

  public function testEventTypeJson() {
    $evt1 = self::$base->events->event_type_add($this->token, 'expense', 'event 1', true);
    $evt2 = self::$base->events->event_type_add($this->token, 'expense', 'event 2', true);

    $req = [ 'ety_id' => true,
	     'ety_name' => true,
	     'ety_category' => true,
	     'ety_individual_name' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true,
			   'top_description' => true,
			   'top_icon' => true,
			   'top_color' => true ],
	     'organizations' => [ 'org_id' => true,
				  'org_name' => true,
				  'org_description' => true ] ];
    $json = self::$base->events->event_type_json($this->token, $evt1, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($res['ety_name'], 'event 1');

    $json = self::$base->events->event_type_json($this->token, NULL, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals(2, count($res));
    $this->assertEquals($res[0]['ety_name'], 'event 1');
    $this->assertEquals($res[1]['ety_name'], 'event 2');
  }

  public function testEventTypeReqJson() {
    $eventType = self::$base->events->event_type_add($this->token, 'expense', 'event 1', true);
    $topName1 = 'topic 1';
    $topDesc1 = 'description 1';
    $topIcon1 = 'health';
    $topColor1 = '#123456';    
    $topName2 = 'topic 2';
    $topDesc2 = 'description 2';
    $topIcon2 = 'money';
    $topColor2 = '#978654';
    $topId1 = self::$base->organ->topic_add($this->token, $topName1, $topDesc1, $topIcon1, $topColor1);
    $topId2 = self::$base->organ->topic_add($this->token, $topName2, $topDesc2, $topIcon2, $topColor2);

    self::$base->events->event_type_set_topics($this->token, $eventType, array($topId1, $topId2));

    $orgId1 = self::$base->organ->organization_add($this->token, 'organ 1', 'desc 1', true);
    $orgId2 = self::$base->organ->organization_add($this->token, 'organ 2', 'desc 2', true);

    self::$base->events->event_type_set_organizations($this->token, $eventType, array($orgId1, $orgId2));

    $req = [ 'ety_id' => true,
	     'ety_name' => true,
	     'ety_category' => true,
	     'ety_individual_name' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true,
			   'top_description' => true,
			   'top_icon' => true,
			   'top_color' => true ],
	     'organizations' => [ 'org_id' => true,
				  'org_name' => true,
				  'org_description' => true ] ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($eventType, $res['ety_id']);
    $this->assertEquals('event 1', $res['ety_name']);
    $this->assertEquals('expense', $res['ety_category']);
    $this->assertEquals(true, $res['ety_individual_name']);
    $this->assertNotNull($res['topics']);
    $this->assertNotNull($res['organizations']);

    $req = [ 'ety_id' => true,
	     'ety_name' => null,
	     'ety_category' => null,
	     'ety_individual_name' => null,
	     'topics' => null,
	     'organizations' => null ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertEquals($eventType, $res['ety_id']);
    $this->assertNull($res['ety_name']);
    $this->assertNull($res['ety_category']);
    $this->assertNull($res['ety_individual_name']);
    $this->assertNull($res['topics']);
    $this->assertNull($res['organizations']);

    $req = [ 'ety_id' => null,
	     'ety_name' => true,
	     'ety_category' => null,
	     'ety_individual_name' => null,
	     'topics' => null,
	     'organizations' => null ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res['ety_id']);
    $this->assertEquals('event 1', $res['ety_name']);
    $this->assertNull($res['ety_category']);
    $this->assertNull($res['ety_individual_name']);
    $this->assertNull($res['topics']);
    $this->assertNull($res['organizations']);

    $req = [ 'ety_id' => null,
	     'ety_name' => null,
	     'ety_category' => true,
	     'ety_individual_name' => null,
	     'topics' => null,
	     'organizations' => null ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res['ety_id']);
    $this->assertNull($res['ety_name']);
    $this->assertEquals('expense', $res['ety_category']);
    $this->assertNull($res['ety_individual_name']);
    $this->assertNull($res['topics']);
    $this->assertNull($res['organizations']);

    $req = [ 'ety_id' => null,
	     'ety_name' => null,
	     'ety_category' => null,
	     'ety_individual_name' => true,
	     'topics' => null,
	     'organizations' => null ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res['ety_id']);
    $this->assertNull($res['ety_name']);
    $this->assertNull($res['ety_category']);
    $this->assertEquals(true, $res['ety_individual_name']);
    $this->assertNull($res['topics']);
    $this->assertNull($res['organizations']);

    $req = [ 'ety_id' => null,
	     'ety_name' => null,
	     'ety_category' => null,
	     'ety_individual_name' => null,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true,
			   'top_description' => true,
			   'top_icon' => true,
			   'top_color' => true ],
	     'organizations' => null ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res['ety_id']);
    $this->assertNull($res['ety_name']);
    $this->assertNull($res['ety_category']);
    $this->assertNull($res['ety_individual_name']);
    $this->assertNotNull($res['topics']);
    $this->assertNull($res['organizations']);

    $req = [ 'ety_id' => null,
	     'ety_name' => null,
	     'ety_category' => null,
	     'ety_individual_name' => null,
	     'topics' => null,
	     'organizations' => [ 'org_id' => true,
				  'org_name' => true,
				  'org_description' => true ] ];
    $json = self::$base->events->event_type_json($this->token, $eventType, json_encode($req));
    $res = json_decode(json_encode($json), true);
    $this->assertNull($res['ety_id']);
    $this->assertNull($res['ety_name']);
    $this->assertNull($res['ety_category']);
    $this->assertNull($res['ety_individual_name']);
    $this->assertNull($res['topics']);
    $this->assertNotNull($res['organizations']);
  }
}

?>
