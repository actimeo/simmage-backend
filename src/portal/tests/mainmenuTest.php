<?php
require_once 'pgproc/php/pgprocedures.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures2;
use \actimeo\pgproc\PgProcException;

class mainmenuTest extends PHPUnit_Framework_TestCase {
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
    self::$base = new PgProcedures2 (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);    
  }

  protected function assertPreConditions()
  {
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'ksfdjgsfdyubg';    
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('".$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)),  '{structure}');");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testMainmenuAdd() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name = 'a main menu';
    $mme_id = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name);
    $this->assertGreaterThan(0, $mme_id);
  }

  public function testMainmenuAddTwice() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name1 = 'a first menu';
    $mme_name2 = 'a second menu';
    $id1 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name1);
    $id2 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name2);
    $this->assertGreaterThan($id1, $id2);
  }

  /**
   * Add two mainmenus with same name in same mainsection
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testMainmenuAddSameName() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name = 'a first menu';
    $id1 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mse_name);
    $id2 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mse_name);
  }  

  /**
   * Add two mainmenus with same name in different mainsections
   */
  public function testMainmenuAddSameNameOtherMainsection() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name1 = 'a first section';
    $mse_id1 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name1);
    $mse_name2 = 'a second section';
    $mse_id2 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name2);
    
    $mme_name = 'a section';
    $id1 = self::$base->portal->mainmenu_add($this->token, $mse_id1, $mme_name);
    $id2 = self::$base->portal->mainmenu_add($this->token, $mse_id2, $mme_name);
    $this->assertGreaterThan($id1, $id2);
  }  

  public function testMainmenuAddAndList() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name = 'a main menu';
    $mme_id = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(1, count($mainmenus));
  }

  public function testMainmenuAddTwiceAndList() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name1 = 'a first menu';
    $mme_name2 = 'a second menu';
    $id1 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name1);
    $id2 = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name2);
   
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(2, count($mainmenus));
  }

  public function testMainmenuAddDifferentMainsectionsAndList() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name1 = 'a first section';
    $mse_id1 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name1);
    $mse_name2 = 'a second section';
    $mse_id2 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name2);
    
    $mme_name = 'a menu';
    $id1 = self::$base->portal->mainmenu_add($this->token, $mse_id1, $mme_name);
    $id2 = self::$base->portal->mainmenu_add($this->token, $mse_id2, $mme_name);

    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id1);
    $this->assertEquals(1, count($mainmenus));

    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id2);
    $this->assertEquals(1, count($mainmenus));
  }

  public function testMainmenuAddAndCheckOrder() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name[0] = 'a first menu';
    $mme_name[1] = 'a second menu';
    $mme_name[2] = 'a third menu';
    $mme_name[3] = 'a fourth menu';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name[$i]);
    
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(4, count($mainmenus));
    for ($i=0; $i<4; $i++) {
      $this->assertEquals($i+1, $mainmenus[$i]['mme_order']);
      $this->assertEquals($mme_name[$i], $mainmenus[$i]['mme_name']);
    }
  }

  public function testMainmenuRename() {
    $por_id = self::$base->portal->portal_add($this->token, 'a portal');

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $name1 = 'a menu';
    $name2 = 'another menu';
    
    $id = self::$base->portal->mainmenu_add($this->token, $mse_id, $name1);
    self::$base->portal->mainmenu_rename($this->token, $id, $name2);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(1, count($mainmenus));
    $mainmenu = $mainmenus[0];
    $this->assertEquals($name2, $mainmenu['mme_name']);
    
  }

 /**
   * Trying to rename an inexistant mainmenu raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
   public function testMainmenuRenameUnknown() {
    $name1 = 'a section';
    $name2 = 'another section';
    $por_id = self::$base->portal->portal_add($this->token, 'a portal');

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    
    $id = self::$base->portal->mainmenu_add($this->token, $mse_id, $name1);
    self::$base->portal->mainmenu_rename($this->token, $id+1, $name2);
  }

  public function testMainmenuDelete() {
    $por_id = self::$base->portal->portal_add($this->token, 'a portal');

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $id = self::$base->portal->mainmenu_add($this->token, $mse_id, 'a menu');
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(1, count($mainmenus));
    self::$base->portal->mainmenu_delete($this->token, $id);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(0, count($mainmenus));
  }

  /**
   * Trying to delete an inexistant portal raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testMainmenuDeleteUnknown() {
    $name = 'a portal';
    $por = self::$base->portal->portal_add($this->token, $name);

    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por, $mse_name);

    $id = self::$base->portal->mainmenu_add($this->token, $mse_id, 'a menu');
    self::$base->portal->mainmenu_delete($this->token, $id+1);
  }

  public function testMainmenuAddAndMoveToMiddle() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);

    $mme_name[0] = '1';
    $mme_name[1] = '2';
    $mme_name[2] = '3';
    $mme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name[$i]);
    
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(4, count($mainmenus));

    self::$base->portal->mainmenu_move_before_position($this->token, $id[2], 1);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(array('3', '1', '2', '4'), $this->getMmeNames($mainmenus));
  }

  public function testMainmenuAddAndMoveToStart() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    
    $mme_name[0] = '1';
    $mme_name[1] = '2';
    $mme_name[2] = '3';
    $mme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name[$i]);
    
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(4, count($mainmenus));

    self::$base->portal->mainmenu_move_before_position($this->token, $id[3], 1);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(array('4', '1', '2', '3'), $this->getMmeNames($mainmenus));
  }

  public function testMainmenuAddAndMoveToEnd() {
    $por_name = 'a portal';
    $por_id = self::$base->portal->portal_add($this->token, $por_name);
    
    $mse_name = 'a main section';
    $mse_id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    
    $mme_name[0] = '1';
    $mme_name[1] = '2';
    $mme_name[2] = '3';
    $mme_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainmenu_add($this->token, $mse_id, $mme_name[$i]);
    
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(4, count($mainmenus));

    self::$base->portal->mainmenu_move_before_position($this->token, $id[0], 5);
    $mainmenus = self::$base->portal->mainmenu_list($this->token, $mse_id);
    $this->assertEquals(array('2', '3', '4', '1'), $this->getMmeNames($mainmenus));
  }
  
  private function getMmeNames($a) {
    $ret = array();
    foreach($a as $v) {
      $ret[] = $v['mme_name'];
    }
    return $ret;
  }

}
