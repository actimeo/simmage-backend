<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class organizationTest extends PHPUnit_Framework_TestCase {
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
    $orgs = self::$base->organ->organization_list($this->token, null);
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

  public function testOrganizationListFiltered() {
    $nameI = 'an internal organization';
    $descI = 'an internal organization desc';
    $idI = self::$base->organ->organization_add($this->token, $nameI, $descI, true);

    $nameE = 'an external organization';
    $descE = 'an external organization desc';
    $idE = self::$base->organ->organization_add($this->token, $nameE, $descE, false);

    $orgsAll = self::$base->organ->organization_list($this->token, null);
    $orgsInt = self::$base->organ->organization_list($this->token, true); 
    $orgsExt = self::$base->organ->organization_list($this->token, false); 
   
    $filter = function($f) use($idI, $idE) { 
      return in_array($f['org_id'], array($idI, $idE)); 
    };

    $getNameMap = function($f) {
      return $f['org_name'];
    };
    
    $nAll = array_filter($orgsAll, $filter);
    $this->assertEquals(2, count($nAll));

    $listInt = array_filter($orgsInt, $filter);
    $this->assertEquals(1, count($listInt));
    $this->assertEquals(array($nameI), array_map($getNameMap, $listInt));

    $listExt = array_filter($orgsExt, $filter);
    $this->assertEquals(1, count($listExt));
    $this->assertEquals(array($nameE), array_map($getNameMap, $listExt));
  }

  public function testOrganizationSet() {
    $name1 = 'an organization';
    $name2 = 'another organization';
    $desc1 = 'an organization desc';
    $desc2 = 'another organization desc';
    $internal1 = true;
    $internal2 = false;
    $id = self::$base->organ->organization_add($this->token, $name1, $desc2, $internal1);

    self::$base->organ->organization_set($this->token, $id, $name2, $desc2, $internal2);

    $orgs = self::$base->organ->organization_list($this->token, null);
    $this->assertGreaterThan(0, count($orgs));
    $org = $orgs[0];
    $found = false;
    foreach ($orgs as $org) {
      if ($name2 == $org['org_name']
	  && $desc2 == $org['org_description']
	  && $internal2 == $org['org_internal']) {
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
  public function testOrganizationSetUnknown() {
    $name1 = 'a portal';
    $desc = 'an organization desc';
    $name2 = 'another portal';
    $id = self::$base->organ->organization_add($this->token, $name1, $desc, true);
    self::$base->organ->organization_set($this->token, $id+1, $name2, $desc, true);
  }

  public function testOrganizationDelete() {
    $name1 = 'an organization';
    $desc1 = 'an organization desc';
    $id1 = self::$base->organ->organization_add($this->token, $name1, $desc1, true);
    $name2 = 'another organization';
    $desc2 = 'another organization desc';
    $id2 = self::$base->organ->organization_add($this->token, $name2, $desc2, true);
    $orgs = self::$base->organ->organization_list($this->token, null);
    $nAfterAdd = count($orgs);
    $this->assertGreaterThan(0, count($orgs));
    self::$base->organ->organization_delete($this->token, $id1);
    $orgs = self::$base->organ->organization_list($this->token, null);
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
