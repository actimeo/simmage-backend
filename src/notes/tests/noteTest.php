<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class NoteTest extends PHPUnit_Framework_TestCase {
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

  public function testNoteAdd() {
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
    
    $id = self::$base->notes->note_add($this->token, 'a note', 
				       '29/01/2016', 'an object', 
				       [ $top_id1, $top_id2 ], [ $dosId ], 
				       null, null
				       );
    $this->assertGreaterThan(0, $id);
  }  

  public function testNoteGet() {
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

    $id = self::$base->notes->note_add($this->token, 'a note', 
				       '29/01/2016', 'an object', 
				       [ $top_id1, $top_id2 ], [ $dosId ],
				       null, null
				       );
    $doc = self::$base->notes->note_get($this->token, $id);
    $this->assertEquals($doc['not_id'], $id);

    $topics = self::$base->notes->note_topic_list($this->token, $id);
    $this->assertEquals([ $top_id1, $top_id2 ], array_map(function ($t) { return $t['top_id']; }, $topics));

    $dossiers = self::$base->notes->note_dossier_list($this->token, $id);
    $this->assertEquals([ $dosId ], array_map(function ($d) { return $d['dos_id']; }, $dossiers));
  }  

  public function testNoteGetUnknown() {
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

    $id = self::$base->notes->note_add($this->token, 'a note', 
				       '29/01/2016', 'an object', 
				       [ $top_id1, $top_id2 ], [ $dosId ],
				       null, null
				       );
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $doc = self::$base->notes->note_get($this->token, $id + 1);
  }

  public function testNoteJson() {
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

    // Recipients
    $parId1 = self::$base->organ->participant_add($this->token, 'Pierre', 'Paris');
    $parId2 = self::$base->organ->participant_add($this->token, 'Margo', 'Paris');
    $parId3 = self::$base->organ->participant_add($this->token, 'Lila', 'Paris');
    $parId4 = self::$base->organ->participant_add($this->token, 'Mélina', 'Martin');
    $parId5 = self::$base->organ->participant_add($this->token, 'Elsa', 'Martin');
    
    $id = self::$base->notes->note_add($this->token, 'a note', 
				       '29/01/2016', 'an object', 
				       [ $top_id1, $top_id2 ], [ $dosId ],
				       [ $parId1, $parId2, $parId3 ], [ $parId4, $parId5 ]
				       );
    $req = [ 'not_id' => true,
	     'not_text' => true,
	     'author' => [ 'par_id' => true,
			   'par_firstname' => true,
			   'par_lastname' => true ],
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ],
	     'recipients' => [ 'par_id' => true,
			       'par_firstname' => true,
			       'par_lastname' => true,
			       'nor_for_action' => true ]
	     ];
    $nots_json = self::$base->notes->note_json($this->token, [ $id ], json_encode($req));
    $this->assertEquals(1, count($nots_json));
    $json = $nots_json[0];
    $this->assertEquals($id, $json->not_id);
    $this->assertEquals('a note', $json->not_text);
    $this->assertEquals(2, count($json->topics));
    $this->assertEquals(1, count($json->dossiers));
    $this->assertEquals('Test', $json->author->par_firstname);
    $this->assertEquals('User', $json->author->par_lastname);

    $this->assertEquals(5, count($json->recipients));
    $recipients_info_only = array_filter($json->recipients, function($r) { return !$r->nor_for_action; });
    $this->assertEquals(3, count($recipients_info_only));
  }

  public function testNoteInNotesView() {
    $name = 'a notes view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $nov_id = self::$base->notes->notesview_add($this->token, $name, [ $top1, $top2 ]);

    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $doc_id1 = self::$base->notes->note_add($this->token, 'a note', 
					    '29/01/2016', 'an object', 
					    [ $top1 ], [ $dosId ],
				       null, null
					    );

    $doc_id2 = self::$base->notes->note_add($this->token, 'another note', 
					    '29/03/2016', 'an object', 
					    [ $top1, $top2 ], [ $dosId ],
				       null, null
					    );

    $req = [ 'doc_id' => true,
	     'not_text' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $ret = self::$base->notes->note_in_view_list($this->token, $nov_id, NULL, json_encode($req));
  } 

  public function testNoteRecipients() {
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

    $par1 = self::$base->organ->participant_add($this->token, 'Pierre', 'Dupont');
    $par2 = self::$base->organ->participant_add($this->token, 'Jacques', 'Martin');
    $par3 = self::$base->organ->participant_add($this->token, 'Marie', 'Poppins');
    
    $notId = self::$base->notes->note_add($this->token, 'a note', 
				       '29/01/2016', 'an object', 
				       [ $top_id1, $top_id2 ], [ $dosId ], 
				       [ $par1, $par2 ], [ $par3 ]
				       );
    $this->assertGreaterThan(0, $notId);

    $info = self::$base->notes->note_get_recipients($this->token, $notId, false);
    $this->assertEquals(2, count($info));
    $action = self::$base->notes->note_get_recipients($this->token, $notId, true);
    $this->assertEquals(1, count($action));
  }

  public function testNoteParticipantList() {
    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');

    $dos_id = self::$base->organ->dossier_add_individual($this->token, 'First', 'Lastname', '21/12/1963', 'male', false);

    $par = self::$base->execute_sql("SELECT par_id FROM login.user WHERE usr_token = ".$this->token);
    $par1 = self::$base->organ->participant_add($this->token, 'Pierre', 'Dupont');
    $par2 = self::$base->organ->participant_add($this->token, 'Jacques', 'Martin');
    $par3 = self::$base->organ->participant_add($this->token, 'Marie', 'Poppins');

    $login1 = 'flebeleb';
    $pwd1 = 'gladabaadaleda';

    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login1."', pgcrypto.crypt('"
			     .$pwd1."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     .$par1.");");

    $res = self::$base->login->user_login($login1, $pwd1, null, null);
    $token1 = $res['usr_token'];

    self::$base->notes->note_add($this->token, 'a note',
				  '21/12/2016 14:00:00', 'an object',
					[ $top_id1, $top_id2 ], [ $dos_id ],
					[ $par1, $par2 ], [ $par3 ]);

    self::$base->notes->note_add($token1, 'a note',
				  '25/12/2016 15:00:00', 'an object',
					[ $top_id1, $top_id2 ], [ $dos_id ],
					[ $par1 ], [ $par3, $par ]);

    self::$base->notes->note_add($token1, 'a note',
				  '22/12/2016 14:00:00', 'an object',
					[ $top_id1, $top_id2 ], [ $dos_id ],
					[ ], [ ]);

    self::$base->notes->note_add($token1, 'a note',
				  '16/12/2016 14:00:00', 'an object',
					[ $top_id1 ], [ $dos_id ],
					[ $par ], [ ]);

    self::$base->notes->note_add($this->token, 'a note',
				  '19/12/2016 14:00:00', 'an object',
					[ $top_id1, $top_id2 ], [ $dos_id ],
					[ $par3 ], [ $par ]);

    $req = [ 'not_id' => true,
	     'not_text' => true,
	     'not_creation_date' => true,
	     'not_event_date' => true,
	     'author' => [ 'par_id' => true,
			   'par_firstname' => true,
			   'par_lastname' => true ],
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ],
	     'recipients' => [ 'par_id' => true,
			       'par_firstname' => true,
			       'par_lastname' => true,
			       'nor_for_action' => true ]
	     ];

    $list = self::$base->notes->note_participant_list($token1, 'not_event_date', true, json_encode($req));

    $this->assertEquals(4, count($list));
  }

  public function testNoteAcknowledgeReceipt() {
    $top_id1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top_id2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');

    $dosId = self::$base->organ->dossier_add_individual($this->token, 'Firstname', 'Lastname', '21/12/1963', 'male', false);

    $par = self::$base->execute_sql('SELECT par_id FROM login.user WHERE usr_token = '.$this->token);
    $par1 = self::$base->organ->participant_add($this->token, 'Pierre', 'Dupont');

    $login1 = 'flebeleb';
    $pwd1 = 'glaglagzlgazfadaa';

    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
	    .$login1."', pgcrypto.crypt('"
	    .$pwd1."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
	    .$par1.");");

    $res = self::$base->login->user_login($login1, $pwd1, null, null);
    $token1 = $res['usr_token'];

    self::$base->notes->note_add($this->token, 'a note',
				  '21/12/2016 14:00:00', 'an object',
				  [ $top_id1, $top_id2 ], [ $dosId ],
				  [ $par1 ], [ ]);

    $req = ['not_id' => true,
	    'not_text' => true,
	    'recipients' => [ 'par_id' => true,
			      'nor_acknowledge_receipt' => true ]
          ];

    $note = self::$base->notes->note_participant_list($token1, 'not_event_date', true, json_encode($req))[0];

    $this->assertEquals(false, $note->recipients[0]->nor_acknowledge_receipt);

    self::$base->notes->note_user_acknowledge_receipt($token1, $note->not_id);

    $noteAck = self::$base->notes->note_participant_list($token1, 'not_event_date', true, json_encode($req))[0];

    $this->assertEquals(true, $noteAck->recipients[0]->nor_acknowledge_receipt);
  }

}
