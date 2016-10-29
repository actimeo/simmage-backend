<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class personmenuTest extends PHPUnit_Framework_TestCase {
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)),  '{structure}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testPersonMenuAdd() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name = 'a person menu';
    $pme_id = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name);
    $this->assertGreaterThan(0, $pme_id);
  }

  public function testPersonMenuAddTwice() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name1 = 'a first menu';
    $pme_name2 = 'a second menu';
    $id1 = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name1);
    $id2 = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name2);
    $this->assertGreaterThan($id1, $id2);
  }

  /**
   * Add two personmenus with same name in same personsection
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testPersonMenuAddSameName() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name = 'a first menu';
    $id1 = self::$base->portal->personmenu_add($this->token, $pse_id, $pse_name);
    $id2 = self::$base->portal->personmenu_add($this->token, $pse_id, $pse_name);
  }  

  /**
   * Add two personmenus with same name in different personsections
   */
  public function testPersonMenuAddSameNameOtherPersonsection() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name1 = 'a first section';
    $pse_id1 = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name1);
    $pse_name2 = 'a second section';
    $pse_id2 = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name2);
    
    $pme_name = 'a section';
    $id1 = self::$base->portal->personmenu_add($this->token, $pse_id1, $pme_name);
    $id2 = self::$base->portal->personmenu_add($this->token, $pse_id2, $pme_name);
    $this->assertGreaterThan($id1, $id2);
  }  

  public function testPersonMenuAddAndList() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name = 'a person menu';
    $pme_id = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(1, count($personmenus));
  }

  public function testPersonMenuAddTwiceAndList() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name1 = 'a first menu';
    $pme_name2 = 'a second menu';
    $id1 = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name1);
    $id2 = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name2);
   
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(2, count($personmenus));
  }

  public function testPersonMenuAddDifferentPersonsectionsAndList() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name1 = 'a first section';
    $pse_id1 = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name1);
    $pse_name2 = 'a second section';
    $pse_id2 = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name2);
    
    $pme_name = 'a menu';
    $id1 = self::$base->portal->personmenu_add($this->token, $pse_id1, $pme_name);
    $id2 = self::$base->portal->personmenu_add($this->token, $pse_id2, $pme_name);

    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id1);
    $this->assertEquals(1, count($personmenus));

    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id2);
    $this->assertEquals(1, count($personmenus));
  }

  public function testPersonMenuAddAndCheckOrder() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name[0] = 'a first menu';
    $pme_name[1] = 'a second menu';
    $pme_name[2] = 'a third menu';
    $pme_name[3] = 'a fourth menu';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name[$i]);
    
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(4, count($personmenus));
    for ($i=0; $i<4; $i++) {
      $this->assertEquals($i+1, $personmenus[$i]['pme_order']);
      $this->assertEquals($pme_name[$i], $personmenus[$i]['pme_name']);
    }
  }

  public function testPersonMenuRename() {
    $por_id = self::$base->portal->portal_add($this->token, 'a portal', 'a desc');

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $name1 = 'a menu';
    $name2 = 'another menu';
    
    $id = self::$base->portal->personmenu_add($this->token, $pse_id, $name1);
    self::$base->portal->personmenu_rename($this->token, $id, $name2);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(1, count($personmenus));
    $personmenu = $personmenus[0];
    $this->assertEquals($name2, $personmenu['pme_name']);
    
  }

 /**
   * Trying to rename an inexistant personmenu raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
   public function testPersonMenuRenameUnknown() {
    $name1 = 'a section';
    $name2 = 'another section';
    $por_id = self::$base->portal->portal_add($this->token, 'a portal', 'a desc');

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);
    
    $id = self::$base->portal->personmenu_add($this->token, $pse_id, $name1);
    self::$base->portal->personmenu_rename($this->token, $id+1, $name2);
  }

  public function testPersonMenuDelete() {
    $por_id = self::$base->portal->portal_add($this->token, 'a portal', 'a desc');

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $id = self::$base->portal->personmenu_add($this->token, $pse_id, 'a menu');
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(1, count($personmenus));
    self::$base->portal->personmenu_delete($this->token, $id);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(0, count($personmenus));
  }

  /**
   * Trying to delete an inexistant portal raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testPersonMenuDeleteUnknown() {
    $name = 'a portal';
    $desc = 'a desc';
    $por = self::$base->portal->portal_add($this->token, $name, $desc);

    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por, 'patient', $pse_name);

    $id = self::$base->portal->personmenu_add($this->token, $pse_id, 'a menu');
    self::$base->portal->personmenu_delete($this->token, $id+1);
  }

  public function testPersonMenuAddAndMoveToMiddle() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);

    $pme_name[0] = '1';
    $pme_name[1] = '2';
    $pme_name[2] = '3';
    $pme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name[$i]);
    
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(4, count($personmenus));

    self::$base->portal->personmenu_move_before_position($this->token, $id[2], 1);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(array('3', '1', '2', '4'), $this->getPmeNames($personmenus));
  }

  public function testPersonMenuAddAndMoveToStart() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);
    
    $pme_name[0] = '1';
    $pme_name[1] = '2';
    $pme_name[2] = '3';
    $pme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name[$i]);
    
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(4, count($personmenus));

    self::$base->portal->personmenu_move_before_position($this->token, $id[3], 1);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(array('4', '1', '2', '3'), $this->getPmeNames($personmenus));
  }

  public function testPersonMenuAddAndMoveToEnd() {
    $por_name = 'a portal';
    $por_desc = 'a desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $pse_name = 'a person section';
    $pse_id = self::$base->portal->personsection_add($this->token, $por_id, 'patient', $pse_name);
    
    $pme_name[0] = '1';
    $pme_name[1] = '2';
    $pme_name[2] = '3';
    $pme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->personmenu_add($this->token, $pse_id, $pme_name[$i]);
    
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(4, count($personmenus));

    self::$base->portal->personmenu_move_before_position($this->token, $id[0], 5);
    $personmenus = self::$base->portal->personmenu_list($this->token, $pse_id);
    $this->assertEquals(array('2', '3', '4', '1'), $this->getPmeNames($personmenus));
  }
  
  private function getPmeNames($a) {
    $ret = array();
    foreach($a as $v) {
      $ret[] = $v['pme_name'];
    }
    return $ret;
  }

}
