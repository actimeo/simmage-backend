<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class EventTest extends PHPUnit_Framework_TestCase {
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization, users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			     
    $res = self::$base->login->user_login($login, $pwd, null, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testEventAdd() {
    $name = 'a event type';
    $indiv = true;
    $ety_id = self::$base->events->event_type_add($this->token, 'incident', $name, $indiv);

    $top_name1 = 'topic 1';
    $top_desc1 = 'topic 1 description';
    $top_icon1 = 'health';
    $top_color1 = '#000000';
    $top_id1 = self::$base->organ->topic_add($this->token, $top_name1, $top_desc1, $top_icon1, $top_color1);

    $top_name2 = 'topic 2';
    $top_desc2 = 'topic 2 description';
    $top_icon2 = 'health';
    $top_color2 = '#000000';
    $top_id2 = self::$base->organ->topic_add($this->token, $top_name2, $top_desc2, $top_icon2, $top_color2);

    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $id = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ]
					 );
    $this->assertGreaterThan(0, $id);
  }  

  public function testEventGet() {
    $name = 'a event type';
    $indiv = true;
    $ety_id = self::$base->events->event_type_add($this->token, 'incident', $name, $indiv);

    $top_name1 = 'topic 1';
    $top_desc1 = 'topic 1 description';
    $top_icon1 = 'health';
    $top_color1 = '#000000';
    $top_id1 = self::$base->organ->topic_add($this->token, $top_name1, $top_desc1, $top_icon1, $top_color1);

    $top_name2 = 'topic 2';
    $top_desc2 = 'topic 2 description';
    $top_icon2 = 'health';
    $top_color2 = '#000000';
    $top_id2 = self::$base->organ->topic_add($this->token, $top_name2, $top_desc2, $top_icon2, $top_color2);

    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $id = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ]
					 );
    $eve = self::$base->events->event_get($this->token, $id);
    $this->assertEquals($eve['eve_id'], $id);
    $this->assertEquals($eve['ety_id'], $ety_id);
    $this->assertEquals($eve['eve_title'], 'a title');

    $topics = self::$base->events->event_topic_list($this->token, $id);
    $this->assertEquals([ $top_id1, $top_id2 ], array_map(function ($t) { return $t['top_id']; }, $topics));

    $dossiers = self::$base->events->event_dossier_list($this->token, $id);
    $this->assertEquals([ $dosId ], array_map(function ($d) { return $d['dos_id']; }, $dossiers));
  }  

  public function testEventGetUnknown() {
    $name = 'a event type';
    $indiv = true;
    $ety_id = self::$base->events->event_type_add($this->token, 'incident', $name, $indiv);

    $top_name1 = 'topic 1';
    $top_desc1 = 'topic 1 description';
    $top_icon1 = 'health';
    $top_color1 = '#000000';
    $top_id1 = self::$base->organ->topic_add($this->token, $top_name1, $top_desc1, $top_icon1, $top_color1);

    $top_name2 = 'topic 2';
    $top_desc2 = 'topic 2 description';
    $top_icon2 = 'health';
    $top_color2 = '#000000';
    $top_id2 = self::$base->organ->topic_add($this->token, $top_name2, $top_desc2, $top_icon2, $top_color2);

    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $id = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ]
					 );
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $eve = self::$base->events->event_get($this->token, $id + 1);
  }

  public function testEventJson() {
    $name = 'an event type';
    $indiv = true;
    $ety_id = self::$base->events->event_type_add($this->token, 'incident', $name, $indiv);

    $top_name1 = 'topic 1';
    $top_desc1 = 'topic 1 description';
    $top_icon1 = 'health';
    $top_color1 = '#000000';
    $top_id1 = self::$base->organ->topic_add($this->token, $top_name1, $top_desc1, $top_icon1, $top_color1);

    $top_name2 = 'topic 2';
    $top_desc2 = 'topic 2 description';
    $top_icon2 = 'health';
    $top_color2 = '#000000';
    $top_id2 = self::$base->organ->topic_add($this->token, $top_name2, $top_desc2, $top_icon2, $top_color2);

    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $id = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ]
					 );
    $req = [ 'eve_id' => true,
	     'eve_title' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $eves_json = self::$base->events->event_json($this->token, [ $id ], json_encode($req));
    $this->assertEquals(1, count($eves_json));
    $json = $eves_json[0];
    $this->assertEquals($id, $json->eve_id);
    $this->assertEquals('a title', $json->eve_title);
    $this->assertEquals(2, count($json->topics));
    $this->assertEquals(1, count($json->dossiers));
  }

  public function testEventInEventsView() {
    $name = 'an avents view';
    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $evv_id = self::$base->events->eventsview_add($this->token, $name, [ 'incident' ], null, [ $top_id1, $top_id2 ]);

    $name = 'an event type';
    $indiv = true;
    $ety_id = self::$base->events->event_type_add($this->token, 'incident', $name, $indiv);
    
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $eve_id1 = self::$base->events->event_add($this->token, 'a title 1', $ety_id, 'standard', 
					      '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					      null, null, null, null,
					      false, null, null, null, 0,
					      [ $top_id1, $top_id2 ], [ $dosId ]
					      );

    $eve_id2 = self::$base->events->event_add($this->token, 'a title 2', $ety_id, 'standard', 
					      '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					      null, null, null, null,
					      false, null, null, null, 0,
					      [ $top_id1, $top_id2 ], [ $dosId ]
					      );

    $req = [ 'eve_id' => true,
	     'eve_title' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $ret = self::$base->events->event_in_view_list($this->token, $evv_id, NULL, json_encode($req));
    //print_r($ret);
  }

  public function testEventAddRecurentDaily() {
      
    $dosId = self::$base->organ->dossier_add_individual($this->token, 'firstname', 'lastname', '01/09/2016', 'male', false);

    $orgId = self::$base->organ->organization_add($this->token, 'org', 'desc org', true);

    $ety_id = self::$base->events->event_type_add($this->token, 'incident', 'an event type', true);

    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'description 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'description 2', 'health', '#000000');

    $viewId = self::$base->events->eventsview_add($this->token, 'an events view', ['incident'], $ety_id, [ $top_id1, $top_id2 ]);

    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, 'preadmission', '01/09/2016');

    $grpId1 = self::$base->organ->group_add($this->token, $orgId, 'group 1', 'grp desc 1', false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, 'group 2', 'grp desc 2', false, 'organization');

    $ugr1 = self::$base->login->usergroup_add($this->token, 'usergroup pread-ad', null, '{preadmission, admission}');
    $ugr2 = self::$base->login->usergroup_add($this->token, 'usergroup ad-pres', null, '{admission, present}');

    self::$base->login->usergroup_set_groups($this->token, $ugr1, array($grpId1));
    self::$base->login->usergroup_set_groups($this->token, $ugr2, array($grpId2));

    self::$base->organ->dossier_assignment_add($this->token, $dosId, array($grpId1, $grpId2));

    self::$base->login->user_usergroup_set($this->token, 'testdejfhcqcsdfkhn', $ugr1);

    $firstId = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard',
					      '01/01/2016 00:00:00', '31/12/2016 00:00:00',
					      null, null, null, null,
					      true, 'daily', 3, null, 5,
					      [ $top_id1, $top_id2 ], [ $dosId ]
				      );

    $secondId = $firstId + 1;
    $thirdId = $secondId + 1;
    $fourthId = $thirdId + 1;
    $fifthId = $fourthId + 1;

    $this->assertGreaterThan(0, $firstId);

    $auth= self::$base->organ->dossiers_authorized_for_user($this->token);

    $req = [  'eve_id' => true,
	      'eve_title' => true,
	      'ety_id' => true,
	      'ety_name' => true,
	      'eve_duration' => true,
	      'eve_start_time' => true,
	      'eve_end_time' => true,
	      'eve_place' => true,
	      'eve_cost' => true,
	      'eve_description' => true,
	      'eve_sumup' => true,
	      'dossiers' => [ 'dos_id' => true ] ];

    $eves_json = self::$base->events->event_json($this->token, [ $firstId, $secondId, $thirdId, $fourthId, $fifthId ], json_encode($req));
    
    $list = self::$base->events->event_in_view_list($this->token, $viewId, [], json_encode($req));
    //print_r($eves_json);
  }

  public function testEventAddRecurentMonthlySameDay() {

    $dosId = self::$base->organ->dossier_add_individual($this->token, 'firstname', 'lastname', '01/09/2016', 'male', false);

    $orgId = self::$base->organ->organization_add($this->token, 'org', 'desc org', true);

    $ety_id = self::$base->events->event_type_add($this->token, 'incident', 'an event type', true);

    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'description 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'description 2', 'health', '#000000');

    $viewId = self::$base->events->eventsview_add($this->token, 'an events view', ['incident'], $ety_id, [ $top_id1, $top_id2 ]);

    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, 'preadmission', '01/09/2016');

    $grpId1 = self::$base->organ->group_add($this->token, $orgId, 'group 1', 'grp desc 1', false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, 'group 2', 'grp desc 2', false, 'organization');

    $ugr1 = self::$base->login->usergroup_add($this->token, 'usergroup pread-ad', null, '{preadmission, admission}');
    $ugr2 = self::$base->login->usergroup_add($this->token, 'usergroup ad-pres', null, '{admission, present}');

    self::$base->login->usergroup_set_groups($this->token, $ugr1, array($grpId1));
    self::$base->login->usergroup_set_groups($this->token, $ugr2, array($grpId2));

    self::$base->organ->dossier_assignment_add($this->token, $dosId, array($grpId1, $grpId2));

    self::$base->login->user_usergroup_set($this->token, 'testdejfhcqcsdfkhn', $ugr1);

    $firstId = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard',
					      '16/01/2016 00:00:00', '31/12/2016 00:00:00',
					      null, null, null, null,
					      true, 'monthly', null, 'day', 5,
					      [ $top_id1, $top_id2 ], [ $dosId ]
				      );

    $secondId = $firstId + 1;
    $thirdId = $secondId + 1;
    $fourthId = $thirdId + 1;
    $fifthId = $fourthId + 1;

    $this->assertGreaterThan(0, $firstId);

    $auth= self::$base->organ->dossiers_authorized_for_user($this->token);

    $req = [  'eve_id' => true,
	      'eve_title' => true,
	      'ety_id' => true,
	      'ety_name' => true,
	      'eve_duration' => true,
	      'eve_start_time' => true,
	      'eve_end_time' => true,
	      'eve_place' => true,
	      'eve_cost' => true,
	      'eve_description' => true,
	      'eve_sumup' => true,
	      'dossiers' => [ 'dos_id' => true ] ];

    $eves_json = self::$base->events->event_json($this->token, [ $firstId, $secondId, $thirdId, $fourthId, $fifthId ], json_encode($req));
    //print_r($eves_json);
  }

  public function testEventAddRecurentMonthlySameWeekday() {

    $dosId = self::$base->organ->dossier_add_individual($this->token, 'firstname', 'lastname', '01/09/2016', 'male', false);

    $orgId = self::$base->organ->organization_add($this->token, 'org', 'desc org', true);

    $ety_id = self::$base->events->event_type_add($this->token, 'incident', 'an event type', true);

    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'description 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'description 2', 'health', '#000000');

    $viewId = self::$base->events->eventsview_add($this->token, 'an events view', ['incident'], $ety_id, [ $top_id1, $top_id2 ]);

    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, 'preadmission', '01/09/2016');

    $grpId1 = self::$base->organ->group_add($this->token, $orgId, 'group 1', 'grp desc 1', false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, 'group 2', 'grp desc 2', false, 'organization');

    $ugr1 = self::$base->login->usergroup_add($this->token, 'usergroup pread-ad', null, '{preadmission, admission}');
    $ugr2 = self::$base->login->usergroup_add($this->token, 'usergroup ad-pres', null, '{admission, present}');

    self::$base->login->usergroup_set_groups($this->token, $ugr1, array($grpId1));
    self::$base->login->usergroup_set_groups($this->token, $ugr2, array($grpId2));

    self::$base->organ->dossier_assignment_add($this->token, $dosId, array($grpId1, $grpId2));

    self::$base->login->user_usergroup_set($this->token, 'testdejfhcqcsdfkhn', $ugr1);

    $firstId = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard',
					      '16/01/2016 00:00:00', '31/12/2016 00:00:00',
					      null, null, null, null,
					      true, 'monthly', null, 'weekday', 5,
					      [ $top_id1, $top_id2 ], [ $dosId ]
				      );

    $secondId = $firstId + 1;
    $thirdId = $secondId + 1;
    $fourthId = $thirdId + 1;
    $fifthId = $fourthId + 1;

    $this->assertGreaterThan(0, $firstId);

    $auth= self::$base->organ->dossiers_authorized_for_user($this->token);

    $req = [  'eve_id' => true,
	      'eve_title' => true,
	      'ety_id' => true,
	      'ety_name' => true,
	      'eve_duration' => true,
	      'eve_start_time' => true,
	      'eve_end_time' => true,
	      'eve_place' => true,
	      'eve_cost' => true,
	      'eve_description' => true,
	      'eve_sumup' => true ];

    $eves_json = self::$base->events->event_json($this->token, [ $firstId, $secondId, $thirdId, $fourthId, $fifthId ], json_encode($req));
   // print_r($eves_json);
  }
}
