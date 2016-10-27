<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class mainviewTest extends PHPUnit_Framework_TestCase {
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

    $por_name = 'a portal';
    $por_desc = 'another desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name = 'a person menu';
    $this->pmeId = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name);

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name = 'a main menu';
    $this->mmeId = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name);

    $mve_type1 = 'sample1';
    $mve_name1 = 'element 1';
    $this->mve_id1 = self::$base->portal->mainview_element_add($this->token, $mve_type1, $mve_name1);

    $mve_type2 = 'sample2';
    $mve_name2 = 'element 2';
    $this->mve_id2 = self::$base->portal->mainview_element_add($this->token, $mve_type2, $mve_name2);

    $pve_type1 = 'personsample1';
    $pve_name1 = 'person element 1';
    $entities1 = array('staff', 'contact');
    $this->pve_id1 = self::$base->portal->personview_element_add($this->token, $pve_type1, $pve_name1, $entities1);
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testMainviewAdd() {
    $title = 'a title';
    $icon = 'an icon';
    
    self::$base->portal->mainview_set($this->token, $this->mmeId, $title, $icon, $this->mve_id1, null);
    $mvi = self::$base->portal->mainview_get($this->token, $this->mmeId);
    $this->assertEquals($mvi, array('mme_id' => $this->mmeId, 'mvi_title' => $title, 
				    'mvi_icon' => $icon, 'mve_id' => $this->mve_id1, 
				    'pme_id_associated' => null));
  }

  public function testMainviewAddWithPme() {
    $title = 'a title';
    $icon = 'an icon';
    $type = 'sample1';

    $ptitle = 'a person title';
    $picon = 'an person icon';
    $ptype = 'personsample1';
    self::$base->portal->personview_set($this->token, $this->pmeId, $ptitle, $picon, $this->pve_id1);
    $pvi = self::$base->portal->personview_get($this->token, $this->pmeId);

    self::$base->portal->mainview_set($this->token, $this->mmeId, $title, $icon, $this->mve_id1, $this->pmeId);
    $mvi = self::$base->portal->mainview_get($this->token, $this->mmeId);
    $this->assertEquals($mvi, array('mme_id' => $this->mmeId, 'mvi_title' => $title, 
				    'mvi_icon' => $icon, 'mve_id' => $this->mve_id1, 
				    'pme_id_associated' => $this->pmeId));
  }

  public function testMainviewDelete() {
    $title = 'a title';
    $icon = 'an icon';
    $type = 'sample1';

    self::$base->portal->mainview_set($this->token, $this->mmeId, $title, $icon, $this->mve_id1, null);
    self::$base->portal->mainview_delete($this->token, $this->mmeId);
    $this->setExpectedException('\actimeo\pgproc\PgProcException');
    $mvi = self::$base->portal->mainview_get($this->token, $this->mmeId);
  }

  public function testMainviewSet() {
    $title = 'a title';
    $icon = 'an icon';
    $type = 'sample1';
    $title2 = 'a second title';
    $icon2 = 'an second icon';
    $type2 = 'sample2';
    self::$base->portal->mainview_set($this->token, $this->mmeId, $title, $icon, $this->mve_id1, null);
    $mvi = self::$base->portal->mainview_get($this->token, $this->mmeId);
    $this->assertEquals($mvi, array('mme_id' => $this->mmeId, 'mvi_title' => $title, 
				    'mvi_icon' => $icon, 'mve_id' => $this->mve_id1, 
				    'pme_id_associated' => null));
    self::$base->portal->mainview_set($this->token, $this->mmeId, $title2, $icon2, $this->mve_id2, null);
    $mvi2 = self::$base->portal->mainview_get($this->token, $this->mmeId);
    $this->assertEquals($mvi2, array('mme_id' => $this->mmeId, 'mvi_title' => $title2, 
				     'mvi_icon' => $icon2, 'mve_id' => $this->mve_id2, 
				     'pme_id_associated' => null));
  }
  
  public function testMainviewTypeList() {
    $types = self::$base->portal->mainview_element_type_list();
    $this->assertInternalType('array', $types);
  }
}
