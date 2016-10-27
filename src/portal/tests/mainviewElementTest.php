<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class mainviewElementTest extends PHPUnit_Framework_TestCase {
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
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase,
				    '5432', '.traces');
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

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];


  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testMainviewElementAdd() {
    $mve_type1 = 'sample1';
    $mve_type2 = 'sample2';

    $listAllBefore = self::$base->portal->mainview_element_list($this->token, null);
    $list1Before = self::$base->portal->mainview_element_list($this->token, $mve_type1);
    $list2Before = self::$base->portal->mainview_element_list($this->token, $mve_type2);
    
    $mve_name1 = 'element 1';
    self::$base->portal->mainview_element_add($this->token, $mve_type1, $mve_name1);

    $mve_name2 = 'element 2';
    self::$base->portal->mainview_element_add($this->token, $mve_type2, $mve_name2);

    $listAll = self::$base->portal->mainview_element_list($this->token, null);
    $this->assertEquals(count($listAll), 2 + count($listAllBefore));

    $list1 = self::$base->portal->mainview_element_list($this->token, $mve_type1);
    $this->assertEquals(count($list1), 1 + count($list1Before));

    $list2 = self::$base->portal->mainview_element_list($this->token, $mve_type2);
    $this->assertEquals(count($list2), 1 + count($list2Before));
  }

  public function testMainviewElementDelete() {
    $listAllBefore = self::$base->portal->mainview_element_list($this->token, null);

    $mve_name1 = 'element 1';
    $mve_type1 = 'sample1';
    $mve_id1 = self::$base->portal->mainview_element_add($this->token, $mve_type1, $mve_name1);
    
    $mve_name2 = 'element 2';
    $mve_type2 = 'sample2';
    $mve_id2 = self::$base->portal->mainview_element_add($this->token, $mve_type2, $mve_name2);

    $listAll = self::$base->portal->mainview_element_list($this->token, null);
    $this->assertEquals(count($listAll), 2 + count($listAllBefore));

    self::$base->portal->mainview_element_delete($this->token, $mve_id1);
    $listAll = self::$base->portal->mainview_element_list($this->token, null);
    $this->assertEquals(count($listAll), 1 + count($listAllBefore));
  }

  public function testMainviewElementRename() {
    $mve_name1 = 'element 1';
    $mve_type1 = 'sample1';
    $mve_id1 = self::$base->portal->mainview_element_add($this->token, $mve_type1, $mve_name1);
    
    $mve_name2 = 'element 2';
    self::$base->portal->mainview_element_rename($this->token, $mve_id1, $mve_name2);

    $mves = self::$base->portal->mainview_element_list($this->token, null);
    $found = false;
    foreach ($mves as $mve) {
      if ($mve['mve_id'] == $mve_id1) {
	$this->assertEquals($mve['mve_name'], $mve_name2);
	$found = true;
	break;
      }
    }
    $this->assertTrue($found);
  }

}
