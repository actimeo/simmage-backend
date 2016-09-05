<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class dossierTest extends PHPUnit_Framework_TestCase {
  private static $base;
  private static $pgHost;
  private static $pgUser;
  private static $pgPass;
  private static $pgDatabase;

  public static function setUpBeforeClass() {

    // Get connecion params
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
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);
  }

  protected function assertPreConditions() {
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'kfarinzaona';
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('".$login."', pgcrypto.crypt('".$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}');");
    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions() {
    self::$base->rollback();
  }

  public function testDossierAddIndividual() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, date('Ymd', strtotime($bdate)), false);
    $this->assertGreaterThan(0, $dosId);
  }

  public function testDossierGetIndividual() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016'; 
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, false);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    //$this->assertEquals(array('dos_id' => $dosId, 'dos_firstname' => 'firstname', 'dos_lastname' => 'lastname', 'dos_birthdate' => '01/09/2016', 'dos_grouped' => false, 'dos_external' => false, 'dos_groupname' => null), $dos);
    $this->assertEquals($dos['dos_firstname'], $fname);
    $this->assertEquals($dos['dos_lastname'], $lname);
    $this->assertEquals($dos['dos_birthdate'], $bdate);
  }

  public function testDossierIndividualGroupedDefaultIsFalse() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, false);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertFalse($dos['dos_grouped']);
  }

  public function testDossierAddIndividualExternal() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, true);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertTrue($dos['dos_external']);
  }

  /**
   * Set a groupname for an individual dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierIndividualSetGroupname() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, false);
    $this->assertGreaterThan(0, $dosId);
    
    $gname = 'groupname';
    self::$base->organ->dossier_set_groupname($this->token, $dosId, $gname);
  }

  public function testDossierAddGrouped() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);
  }

  public function testDossierGetGrouped() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertEquals($dos['dos_groupname'], $gname);
    $this->assertTrue($dos['dos_grouped']);
  }

  public function testDossierAddGroupedExternal() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, true);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertTrue($dos['dos_grouped']);
    $this->assertTrue($dos['dos_external']);
  }
}
?>
