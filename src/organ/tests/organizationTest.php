<?php
require_once 'pgproc/php/pgprocedures.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures2;
use \actimeo\pgproc\PgProcException;

class organizationTest extends PHPUnit_Framework_TestCase {
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
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('".$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}');");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testOrganizationAdd() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $this->assertGreaterThan(0, $id);
  }  

  public function testOrganizationGet() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $this->assertGreaterThan(0, $id);
    $org = self::$base->organ->organization_get($this->token, $id);
    $this->assertEquals($name, $org['org_name']);
    $this->assertEquals($desc, $org['org_description']);
  }  
  
  /**
   * Add two portals with same name
   * @expectedException \actimeo\pgproc\PgProcException
   */  
  public function testOrganizationAddSameName() {
    $name = 'an organization';
    $desc1 = 'an organization desc';
    $desc2 = 'another organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc1, true);
    $this->assertGreaterThan(0, $id);
    $id = self::$base->organ->organization_add($this->token, $name, $desc2, true);
  }  

  public function testOrganizationList() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $orgs = self::$base->organ->organization_list($this->token);
    $this->assertGreaterThan(0, count($orgs));
    $found = false;
    foreach ($orgs as $org) {
      if ($name == $org['org_name']) {
	$found = true;
	break;
      }
    }
    $this->assertTrue($found);
  }

  public function testOrganizationRename() {
    $name1 = 'an organization';
    $name2 = 'another organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name1, $desc, true);
    self::$base->organ->organization_rename($this->token, $id, $name2);
    $orgs = self::$base->organ->organization_list($this->token);
    $this->assertGreaterThan(0, count($orgs));
    $org = $orgs[0];
    $found = false;
    foreach ($orgs as $org) {
      if ($name2 == $org['org_name']) {
	$found = true;
	break;
      }
    }
    $this->assertTrue($found);
  }

  /**
   * Trying to rename an inexistant portal raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testOrganizationRenameUnknown() {
    $name1 = 'a portal';
    $desc = 'an organization desc';
    $name2 = 'another portal';
    $id = self::$base->organ->organization_add($this->token, $name1, $desc, true);
    self::$base->organ->organization_rename($this->token, $id+1, $name2);
  }

  public function testOrganizationDelete() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    $orgs = self::$base->organ->organization_list($this->token);
    $nAfterAdd = count($orgs);
    $this->assertGreaterThan(0, count($orgs));
    self::$base->organ->organization_delete($this->token, $id);
    $orgs = self::$base->organ->organization_list($this->token);
    $this->assertEquals($nAfterAdd-1, count($orgs));
  }

  /**
   * Trying to delete an inexistant portal raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testOrganizationDeleteUnknown() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    self::$base->organ->organization_delete($this->token, $id+1);
  }

  /**
   * Trying to delete a portal with null value as por_id raises an exception
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testOrganizationDeleteNull() {
    $name = 'an organization';
    $desc = 'an organization desc';
    $id = self::$base->organ->organization_add($this->token, $name, $desc, true);
    self::$base->organ->organization_delete($this->token, null);
  }

}
