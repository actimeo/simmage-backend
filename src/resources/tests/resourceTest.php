<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class ResourceTest extends PHPUnit_Framework_TestCase {
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

  public function testResourceAdd() {
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

    $id = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $this->assertGreaterThan(0, $id);
  }  

  public function testResourceGet() {
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

    $id = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $res = self::$base->resources->resource_get($this->token, $id);
    $this->assertEquals($res['res_id'], $id);

    $topics = self::$base->resources->resource_topic_list($this->token, $id);
    $this->assertEquals([ $top_id1, $top_id2 ], array_map(function ($t) { return $t['top_id']; }, $topics));
  }  

  public function testResourceGetUnknown() {
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

    $id = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $res = self::$base->resources->resource_get($this->token, $id + 1);
  }

  public function testResourceJson() {
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

    $id = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $req = [ 'res_id' => true,
	     'res_name' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $ress_json = self::$base->resources->resource_json($this->token, [ $id ], json_encode($req));
    $this->assertEquals(1, count($ress_json));
    $json = $ress_json[0];
    $this->assertEquals($id, $json->res_id);
    $this->assertEquals('a resource', $json->res_name);
    $this->assertEquals(2, count($json->topics));
  }

  public function testResourceInResourcesView() {
    $name = 'a resources view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $res_id = self::$base->resources->resourcesview_add($this->token, $name, [ $top1, $top2 ]);

    $doc_id1 = self::$base->resources->resource_add($this->token, 'a resource', [ $top1 ]);

    $doc_id2 = self::$base->resources->resource_add($this->token, 'another resource', [ $top1, $top2 ]);

    $req = [ 'res_id' => true,
	     'res_name' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     ];
    $ret = self::$base->resources->resource_in_view_list($this->token, $res_id, json_encode($req));
  }

  public function testResourceUpdate() {
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

    $id = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);

    self::$base->resources->resource_update($this->token, $id, 'an edited resource', [ $top_id1, $top_id2 ]);

    $res = self::$base->resources->resource_get($this->token, $id);

    $this->assertEquals('an edited resource', $res['res_name']);
  }

  function testResourcesParticipantList() {
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

    $par_id = self::$base->execute_sql("SELECT par_id FROM login.user WHERE usr_token = ".$this->token)[0]['par_id'];
    $par_id1 = self::$base->organ->participant_add($this->token, 'Pierre', 'Dupont');
    $par_id2 = self::$base->organ->participant_add($this->token, 'Marc', 'Antoine');

    $resId1 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $resId2 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $resId3 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $resId4 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $resId5 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);
    $resId6 = self::$base->resources->resource_add($this->token, 'a resource', [ $top_id1, $top_id2 ]);

    $ide1 = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 'confirmed',
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ], [ $par_id, $par_id2 ], [ $resId1 ]
					 );

    $ide2 = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 'confirmed',
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ], [ $par_id1, $par_id ], [ $resId3, $resId4 ]
					 );

    self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 'confirmed',
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ], [ $par_id1, $par_id2 ], [ $resId5, $resId6 ]
					 );

    self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 'confirmed',
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ], [ $par_id1, $par_id, $par_id2 ], [ $resId1, $resId4, $resId5 ]
					 );

    $ide5 = self::$base->events->event_add($this->token, 'a title', $ety_id, 'standard', 'confirmed',
					 '01/01/2016 00:00:00', '31/12/2016 23:59:59',
					 null, null, null, null,
					 false, null, null, null, 0,
					 [ $top_id1, $top_id2 ], [ $dosId ], [ $par_id1, $par_id2 ], [ $resId1, $resId6 ]
					 );
    $req = ['res_id' => true,
	    'res_name' => true];

    $list = self::$base->resources->resource_participant_list($this->token, json_encode($req));

    $this->assertEquals(4, count($list));
  }
}
