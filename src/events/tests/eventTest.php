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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			     
    $res = self::$base->login->user_login($login, $pwd, null);
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
					      [ $top_id1, $top_id2 ], [ $dosId ]
					      );

    $eve_id2 = self::$base->events->event_add($this->token, 'a title 2', $ety_id, 'standard', 
					      '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					      null, null, null, null,
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
    print_r($ret);
    }
}
