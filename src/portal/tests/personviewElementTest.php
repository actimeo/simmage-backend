<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class personviewElementTest extends PHPUnit_Framework_TestCase {
  private static $base;
  private static $pgHost;
  private static $pgUser;
  private static $pgPass;
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
    self::assertNotNull(self::$pgPort);
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
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];

    $pves = self::$base->portal->personview_element_list($this->token, null, null);
    foreach ($pves as $pve) {
      self::$base->portal->personview_element_delete($this->token, $pve['pve_id']);
    }
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testPersonviewElementAdd() {
    $pve_name1 = 'element 1';
    $pve_type1 = 'personsample1';
    $entities1 = array('staff', 'contact');
    self::$base->portal->personview_element_add($this->token, $pve_type1, $pve_name1, $entities1);

    $pve_name2 = 'element 2';
    $pve_type2 = 'personsample2';
    $entities2 = array('patient');
    self::$base->portal->personview_element_add($this->token, $pve_type2, $pve_name2, $entities2);

    $listAll = self::$base->portal->personview_element_list($this->token, null, null);
    $this->assertEquals(count($listAll), 2);

    $list1 = self::$base->portal->personview_element_list($this->token, $pve_type1, null);
    $this->assertEquals(count($list1), 1);

    $list2 = self::$base->portal->personview_element_list($this->token, $pve_type2, null);
    $this->assertEquals(count($list2), 1);
  }

  public function testPersonviewElementListByEntity() {
    $pve_type = 'personsample1';

    $pve_name1 = 'element 1';
    $entities1 = array('staff', 'contact');
    self::$base->portal->personview_element_add($this->token, $pve_type, $pve_name1, $entities1);

    $pve_name2 = 'element 2';
    $entities2 = array('patient');
    self::$base->portal->personview_element_add($this->token, $pve_type, $pve_name2, $entities2);

    $pve_name3 = 'element 3';
    $entities3 = array('patient', 'contact');
    self::$base->portal->personview_element_add($this->token, $pve_type, $pve_name3, $entities3);

    $listAll = self::$base->portal->personview_element_list($this->token, null, null);
    $this->assertEquals(count($listAll), 3);

    $listPatient = self::$base->portal->personview_element_list($this->token, null, 'patient');
    $this->assertEquals(count($listPatient), 2);

    $listStaff = self::$base->portal->personview_element_list($this->token, null, 'staff');
    $this->assertEquals(count($listStaff), 1);

    $listContact = self::$base->portal->personview_element_list($this->token, null, 'contact');
    $this->assertEquals(count($listContact), 2);
  }

  public function testPersonviewElementSetNullEntities() {
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $pve_type = 'personsample1';
    $pve_name4 = 'element 4';
    $entities4 = null;
    self::$base->portal->personview_element_add($this->token, $pve_type, $pve_name4, $entities4);
  }

  public function testPersonviewElementSetEmptyEntities() {
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $pve_type = 'personsample1';
    $pve_name5 = 'element 5';
    $entities5 = array ();
    self::$base->portal->personview_element_add($this->token, $pve_type, $pve_name5, $entities5);

  }

  public function testPersonviewElementDelete() {
    $pve_name1 = 'element 1';
    $pve_type1 = 'personsample1';
    $entities1 = array('staff', 'contact');
    $pve_id1 = self::$base->portal->personview_element_add($this->token, $pve_type1, $pve_name1, $entities1);
    
    $pve_name2 = 'element 2';
    $pve_type2 = 'personsample2';
    $entities2 = array('patient');
    $pve_id2 = self::$base->portal->personview_element_add($this->token, $pve_type2, $pve_name2, $entities2);

    $listAll = self::$base->portal->personview_element_list($this->token, null, null);
    $this->assertEquals(count($listAll), 2);

    self::$base->portal->personview_element_delete($this->token, $pve_id1);
    $listAll = self::$base->portal->personview_element_list($this->token, null, null);
    $this->assertEquals(count($listAll), 1);
  }

  public function testPersonviewElementRename() {
    $pve_name1 = 'element 1';
    $pve_type1 = 'personsample1';
    $entities1 = array('staff', 'contact');
    $pve_id1 = self::$base->portal->personview_element_add($this->token, $pve_type1, $pve_name1, $entities1);
    
    $pve_name2 = 'element 2';
    self::$base->portal->personview_element_rename($this->token, $pve_id1, $pve_name2);

    $listAll = self::$base->portal->personview_element_list($this->token, null, null);
    $this->assertEquals(count($listAll), 1);
    $this->assertEquals($listAll[0]['pve_name'], $pve_name2);
  }

}
