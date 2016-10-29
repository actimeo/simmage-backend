<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class personviewTest extends PHPUnit_Framework_TestCase {
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
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];

    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name = 'a person menu';
    $this->pmeId = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name);

    $pve_type1 = 'personsample1';
    $pve_name1 = 'person element 1';
    $entities1 = array ('staff', 'contact');
    $this->pve_id1 = self::$base->portal->personview_element_add($this->token, $pve_type1, $pve_name1, $entities1);

    $pve_type2 = 'personsample2';
    $pve_name2 = 'person element 2';
    $entities2 = array ('patient');
    $this->pve_id2 = self::$base->portal->personview_element_add($this->token, $pve_type2, $pve_name2, $entities2);
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }
  
  public function testPersonviewAdd() {
    $title = 'a title';
    $icon = 'an icon';

    self::$base->portal->personview_set($this->token, $this->pmeId, $title, $icon, $this->pve_id1);
    $pvi = self::$base->portal->personview_get($this->token, $this->pmeId);
    $this->assertEquals($pvi, array('pme_id' => $this->pmeId, 'pvi_title' => $title, 
				    'pvi_icon' => $icon, 'pve_id' => $this->pve_id1));
  }

  public function testPersonviewDelete() {
    $title = 'a title';
    $icon = 'an icon';
    $type = 'personsample1';

    self::$base->portal->personview_set($this->token, $this->pmeId, $title, $icon, $this->pve_id1);
    self::$base->portal->personview_delete($this->token, $this->pmeId);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $pvi = self::$base->portal->personview_get($this->token, $this->pmeId);
  }

  public function testPersonviewSet() {
    $title = 'a title';
    $icon = 'an icon';
    $type = 'personsample1';

    $title2 = 'a second title';
    $icon2 = 'an second icon';
    $type2 = 'personsample1';
    self::$base->portal->personview_set($this->token, $this->pmeId, $title, $icon, $this->pve_id1);
    $pvi = self::$base->portal->personview_get($this->token, $this->pmeId);
    $this->assertEquals($pvi, array('pme_id' => $this->pmeId, 'pvi_title' => $title, 
				    'pve_id' => $this->pve_id1, 'pvi_icon' => $icon));
    self::$base->portal->personview_set($this->token, $this->pmeId, $title2, $icon2, $this->pve_id2);
    $pvi2 = self::$base->portal->personview_get($this->token, $this->pmeId);
    $this->assertEquals($pvi2, array('pme_id' => $this->pmeId, 'pvi_title' => $title2, 
				     'pve_id' => $this->pve_id2, 'pvi_icon' => $icon2));
  }
  
  public function testPersonviewTypeList() {
    $types = self::$base->portal->personview_element_type_list();
    $this->assertInternalType('array', $types);
  }
}
