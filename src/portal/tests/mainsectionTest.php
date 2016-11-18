<?php
/*
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

class mainsectionTest extends PHPUnit_Framework_TestCase {
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

  public function testMainsectionAdd() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $mse_name = 'a main section';
    $id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    $this->assertGreaterThan(0, $id);
  }

  public function testMainsectionAddTwice() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name1 = 'a first section';
    $mse_name2 = 'a second section';
    $id1 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name1);
    $id2 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name2);
    $this->assertGreaterThan($id1, $id2);
  }

  /**
   * Add two mainsections with same name in same portal
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testMainsectionAddSameName() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name = 'a first section';
    $id1 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    $id2 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
  }  

  /**
   * Add two mainsections with same name in different portals
   */
  public function testMainsectionAddSameNameOtherPortal() {
    $por_name1 = 'a first portal';
    $por_desc1 = 'a first portal desc';
    $por_id1 = self::$base->portal->portal_add($this->token, $por_name1, $por_desc1);
    $por_name2 = 'a second portal';
    $por_desc2 = 'a second portal desc';
    $por_id2 = self::$base->portal->portal_add($this->token, $por_name2, $por_desc2);
    
    $mse_name = 'a section';
    $id1 = self::$base->portal->mainsection_add($this->token, $por_id1, $mse_name);
    $id2 = self::$base->portal->mainsection_add($this->token, $por_id2, $mse_name);
    $this->assertGreaterThan($id1, $id2);
  }  

  public function testMainsectionAddAndList() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);

    $mse_name = 'a main section';
    $id = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(1, count($mainsections));
  }

  public function testMainsectionAddTwiceAndList() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name1 = 'a first section';
    $mse_name2 = 'a second section';
    $id1 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name1);
    $id2 = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name2);
    
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(2, count($mainsections));    
  }

  public function testMainsectionAddDifferentPortalsAndList() {
    $por_name1 = 'a first portal';
    $por_desc1 = 'a first portal desc';
    $por_id1 = self::$base->portal->portal_add($this->token, $por_name1, $por_desc1);
    $por_name2 = 'a second portal';
    $por_desc2 = 'a second portal desc';
    $por_id2 = self::$base->portal->portal_add($this->token, $por_name2, $por_desc2);
    
    $mse_name = 'a section';
    $id1 = self::$base->portal->mainsection_add($this->token, $por_id1, $mse_name);
    $id2 = self::$base->portal->mainsection_add($this->token, $por_id2, $mse_name);

    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id1);
    $this->assertEquals(1, count($mainsections));

    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id2);
    $this->assertEquals(1, count($mainsections));
  }

  public function testMainsectionAddAndCheckOrder() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name[0] = 'a first section';
    $mse_name[1] = 'a second section';
    $mse_name[2] = 'a third section';
    $mse_name[3] = 'a fourth section';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name[$i]);
    
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(4, count($mainsections));
    for ($i=0; $i<4; $i++) {
      $this->assertEquals($i+1, $mainsections[$i]['mse_order']);
      $this->assertEquals($mse_name[$i], $mainsections[$i]['mse_name']);
    }
  }

  public function testMainsectionRename() {
    $name1 = 'a section';
    $name2 = 'another section';
    $por_desc = 'a portal desc';
    $por = self::$base->portal->portal_add($this->token, 'a portal', $por_desc);
    
    $id = self::$base->portal->mainsection_add($this->token, $por, $name1);
    self::$base->portal->mainsection_rename($this->token, $id, $name2);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por);
    $this->assertEquals(1, count($mainsections));
    $mainsection = $mainsections[0];
    $this->assertEquals($name2, $mainsection['mse_name']);
    
  }

 /**
   * Trying to rename an inexistant mainsection raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
   public function testMainsectionRenameUnknown() {
    $name1 = 'a section';
    $name2 = 'another section';
    $por_desc = 'a portal desc';
    $por = self::$base->portal->portal_add($this->token, 'a portal', $por_desc);
    
    $id = self::$base->portal->mainsection_add($this->token, $por, $name1);
    self::$base->portal->mainsection_rename($this->token, $id+1, $name2);
  }

  public function testMainsectionDelete() {
    $por = self::$base->portal->portal_add($this->token, 'a portal', 'a desc');
    $id = self::$base->portal->mainsection_add($this->token, $por, 'a section');
    $mainsections = self::$base->portal->mainsection_list($this->token, $por);
    $this->assertEquals(1, count($mainsections));
    self::$base->portal->mainsection_delete($this->token, $id);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por);
    $this->assertNull($mainsections);
  }

  /**
   * Trying to delete an inexistant portal raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testMainsectionDeleteUnknown() {
    $name = 'a portal';
    $desc = 'a portal desc';
    $por = self::$base->portal->portal_add($this->token, $name, $desc);
    $id = self::$base->portal->mainsection_add($this->token, $por, 'a section');
    self::$base->portal->mainsection_delete($this->token, $id+1);
  }

  public function testMainsectionAddAndMoveToMiddle() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name[0] = '1';
    $mse_name[1] = '2';
    $mse_name[2] = '3';
    $mse_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name[$i]);
    
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(4, count($mainsections));

    self::$base->portal->mainsection_move_before_position($this->token, $id[2], 1);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(array('3', '1', '2', '4'), $this->getMseNames($mainsections));
  }

  public function testMainsectionAddAndMoveToStart() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name[0] = '1';
    $mse_name[1] = '2';
    $mse_name[2] = '3';
    $mse_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name[$i]);
    
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(4, count($mainsections));

    self::$base->portal->mainsection_move_before_position($this->token, $id[3], 1);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(array('4', '1', '2', '3'), $this->getMseNames($mainsections));
  }

  public function testMainsectionAddAndMoveToEnd() {
    $por_name = 'a portal';
    $por_desc = 'a portal desc';
    $por_id = self::$base->portal->portal_add($this->token, $por_name, $por_desc);
    
    $mse_name[0] = '1';
    $mse_name[1] = '2';
    $mse_name[2] = '3';
    $mse_name[3] = '4';
    for ($i=0; $i<4; $i++)
      $id[$i] = self::$base->portal->mainsection_add($this->token, $por_id, $mse_name[$i]);
    
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(4, count($mainsections));

    self::$base->portal->mainsection_move_before_position($this->token, $id[0], 5);
    $mainsections = self::$base->portal->mainsection_list($this->token, $por_id);
    $this->assertEquals(array('2', '3', '4', '1'), $this->getMseNames($mainsections));
  }
  
  private function getMseNames($a) {
    $ret = array();
    foreach($a as $v) {
      $ret[] = $v['mse_name'];
    }
    return $ret;
  }

}
