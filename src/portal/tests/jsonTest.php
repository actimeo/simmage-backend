<?php
/*
** portal
 - add 
 - rename
 - delete
 - clean 
 - list

 ** mainsection
 - add
 - remove
 - reorder
 - unique order
*/
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class portalJsonTest extends PHPUnit_Framework_TestCase {
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{structure}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			     
    $res = self::$base->login->user_login($login, $pwd, null, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testPortalJson() {
    $por_name1 = '2. a portal';
    $por_desc1 = 'a portal desc';
    $por_id1 = self::$base->portal->portal_add($this->token, $por_name1, $por_desc1);

    $por_name2 = '1. portal 2';
    $por_desc2 = 'portal desc 2';
    $por_id2 = self::$base->portal->portal_add($this->token, $por_name2, $por_desc2);

    $mse_nameA = 'main section A';
    $mse_idA = self::$base->portal->mainsection_add($this->token, $por_id1, $mse_nameA);

    $mme_nameA1 = 'main menu A.1';
    $mme_idA1 = self::$base->portal->mainmenu_add($this->token, $mse_idA, $mme_nameA1, 
						  'title', 'group', null, null);

    $mme_nameA2 = 'main menu A.2';
    $mme_idA2 = self::$base->portal->mainmenu_add($this->token, $mse_idA, $mme_nameA2, 
						  'title', 'group', null, null);

    $req = [ 'mme_id' => true ];
    $json = self::$base->portal->mainmenu_json($this->token, $mse_idA, json_encode($req));
    $this->assertEquals(2, count($json));
    $this->assertNotNull($json[0]->mme_id);
    $this->assertNull($json[0]->mme_name);
    
    $req = [ 'mme_id' => true,
	     'mme_name' => true ];
    $json = self::$base->portal->mainmenu_json($this->token, $mse_idA, json_encode($req));
    $this->assertEquals(2, count($json));
    $this->assertNotNull($json[0]->mme_id);
    $this->assertNotNull($json[0]->mme_name);

    $req = [ 'mse_id' => true ];
    $json = self::$base->portal->mainsection_json($this->token, $por_id1, json_encode($req));
    $this->assertEquals(1, count($json));
    $this->assertNotNull($json[0]->mse_id);
    $this->assertNull($json[0]->mse_name);
    $this->assertNull($json[0]->mainmenus);

    $req = [ 'mse_id' => true,
	     'mse_name' => true ];
    $json = self::$base->portal->mainsection_json($this->token, $por_id1, json_encode($req));
    $this->assertEquals(1, count($json));
    $this->assertNotNull($json[0]->mse_id);
    $this->assertNotNull($json[0]->mse_name);
    $this->assertNull($json[0]->mainmenus);

    $req = [ 'mse_id' => true,
	     'mse_name' => true,
	     'mainmenus' => [
			     'mme_id' => true ]
	     ];
    $json = self::$base->portal->mainsection_json($this->token, $por_id1, json_encode($req));
    $this->assertEquals(1, count($json));
    $this->assertNotNull($json[0]->mse_id);
    $this->assertNotNull($json[0]->mse_name);
    $this->assertNotNull($json[0]->mainmenus);
    $this->assertEquals(2, count($json[0]->mainmenus));
    $this->assertNotNull($json[0]->mainmenus[0]->mme_id);
    $this->assertNull($json[0]->mainmenus[0]->mme_name);

    $req = [ 'mse_id' => true,
	     'mse_name' => true,
	     'mainmenus' => [
			     'mme_id' => true,
			     'mme_name' => true ]
	     ];
    $json = self::$base->portal->mainsection_json($this->token, $por_id1, json_encode($req));
    $this->assertEquals(1, count($json));
    $this->assertNotNull($json[0]->mse_id);
    $this->assertNotNull($json[0]->mse_name);
    $this->assertNotNull($json[0]->mainmenus);
    $this->assertEquals(2, count($json[0]->mainmenus));
    $this->assertNotNull($json[0]->mainmenus[0]->mme_id);
    $this->assertNotNull($json[0]->mainmenus[0]->mme_name);

    $req = [ 'por_id' => true ];
    $json = self::$base->portal->portal_json($this->token, null, json_encode($req));
    $this->assertEquals(2, count($json));
    $this->assertEquals($por_id2, $json[0]->por_id);
    $this->assertEquals($por_id1, $json[1]->por_id);
    $this->assertNull($json[0]->por_name);

    $req = [ 'por_id' => true,
	     'por_name' => true,
	     'mainsections' => [ 'mse_id' => true,
				 'mse_name' => true,
				 'mainmenus' => [ 'mme_id' => true,
						  'mme_name' => true ]
				 ]
	     ];
    $json = self::$base->portal->portal_json($this->token, null, json_encode($req));
    $this->assertEquals(2, count($json));
    $this->assertEquals($por_id2, $json[0]->por_id);
    $this->assertEquals($por_id1, $json[1]->por_id);
    $this->assertNotNull($json[1]->mainsections);
    $this->assertEquals(1, count($json[1]->mainsections));
    $this->assertNotNull($json[1]->mainsections[0]->mainmenus);
  }
}
