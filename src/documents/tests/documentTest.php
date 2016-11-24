<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class DocumentTest extends PHPUnit_Framework_TestCase {
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

  public function testDocumentAdd() {
    $name = 'a document type';
    $indiv = true;
    $dty_id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $par_id_responsible = null;
    $title = 'a document title';
    $desc = "";
    $status = 'done';

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

    $id = self::$base->documents->document_add($this->token, $par_id_responsible, 
					       $dty_id, $title, $desc, $status, 
					       null, null, null, // dates
					       null, // file
					       [ $top_id1, $top_id2 ], [ $dosId ]
					       );
    $this->assertGreaterThan(0, $id);
  }  

  public function testDocumentGet() {
    $name = 'a document type';
    $indiv = true;
    $dty_id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $par_id_responsible = null;
    $title = 'a document title';
    $desc = "";
    $status = 'done';

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

    $id = self::$base->documents->document_add($this->token, $par_id_responsible, 
					       $dty_id, $title, $desc, $status, 
					       null, null, null, // dates
					       null, // file
					       [ $top_id1, $top_id2 ], [ $dosId ]
					       );
    $doc = self::$base->documents->document_get($this->token, $id);
    $this->assertEquals($doc['doc_id'], $id);
    $this->assertEquals($doc['par_id_responsible'], $par_id_responsible);
    $this->assertEquals($doc['dty_id'], $dty_id);
    $this->assertEquals($doc['doc_title'], $title);
    $this->assertEquals($doc['doc_description'], $desc);
    $this->assertEquals($doc['doc_status'], $status);

    $topics = self::$base->documents->document_topic_list($this->token, $id);
    $this->assertEquals([ $top_id1, $top_id2 ], array_map(function ($t) { return $t['top_id']; }, $topics));

    $dossiers = self::$base->documents->document_dossier_list($this->token, $id);
    $this->assertEquals([ $dosId ], array_map(function ($d) { return $d['dos_id']; }, $dossiers));
  }  

  public function testDocumentGetUnknown() {
    $name = 'a document type';
    $indiv = true;
    $dty_id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $par_id_responsible = null;
    $title = 'a document title';
    $desc = "";
    $status = 'done';

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

    $id = self::$base->documents->document_add($this->token, $par_id_responsible, 
					       $dty_id, $title, $desc, $status, 
					       null, null, null, // dates
					       null, // file
					       [ $top_id1, $top_id2 ], [ $dosId ]
					       );
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $doc = self::$base->documents->document_get($this->token, $id + 1);
  }

  public function testDocumentJson() {
    $name = 'a document type';
    $indiv = true;
    $dty_id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    $par_id_responsible = null;
    $title = 'a document title';
    $desc = "";
    $status = 'done';

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

    $id = self::$base->documents->document_add($this->token, $par_id_responsible, 
					       $dty_id, $title, $desc, $status, 
					       null, null, null, // dates
					       null, // file
					       [ $top_id1, $top_id2 ], [ $dosId ]
					       );
    $req = [ 'doc_id' => true,
	     'doc_title' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $docs_json = self::$base->documents->document_json($this->token, [ $id ], json_encode($req));
    $this->assertEquals(1, count($docs_json));
    $json = $docs_json[0];
    $this->assertEquals($id, $json->doc_id);
    $this->assertEquals($title, $json->doc_title);
    $this->assertEquals(2, count($json->topics));
    $this->assertEquals(1, count($json->dossiers));
  }

  public function testDocumentInDocumentsView() {
    $name = 'a documents view';
    $top1 = self::$base->organ->topic_add($this->token, 'topic 1', 'desc 1', 'health', '#000000');
    $top2 = self::$base->organ->topic_add($this->token, 'topic 2', 'desc 2', 'health', '#000000');
    $indivDty = true;
    $dov_id = self::$base->documents->documentsview_add($this->token, $name, null, [ $top1, $top2 ]);

    $name = 'a document type';
    $indiv = true;
    $dty_id = self::$base->documents->document_type_add($this->token, $name, $indiv);
    
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);

    $par_id_responsible1 = null;
    $title1 = 'a document title';
    $desc1 = "";
    $status1 = 'done';
    $doc_id1 = self::$base->documents->document_add($this->token, $par_id_responsible1, 
						    $dty_id, $title1, $desc1, $status1, 
						    null, null, null, // dates
						    null, // file
						    [ $top1 ], [ $dosId ]
						    );

    $par_id_responsible2 = null;
    $title2 = 'a document title';
    $desc2 = "";
    $status2 = 'done';
    $doc_id2 = self::$base->documents->document_add($this->token, $par_id_responsible2, 
						    $dty_id, $title2, $desc2, $status2, 
						    null, null, null, // dates
						    null, // file
						    [ $top1, $top2 ], [ $dosId ]
						    );

    $req = [ 'doc_id' => true,
	     'doc_title' => true,
	     'topics' => [ 'top_id' => true,
			   'top_name' => true ],
	     'dossiers' => [ 'dos_id' => true,
			     'dos_firstname' => true,
			     'dos_lastname' => true ] ];
    $ret = self::$base->documents->document_in_view_list($this->token, $dov_id, json_encode($req));
    print_r($ret);
  }  
}
