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
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}');");
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
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);
  }

  public function testDossierGetIndividual() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016'; 
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertEquals($dos['dos_firstname'], $fname);
    $this->assertEquals($dos['dos_lastname'], $lname);
    $this->assertEquals($dos['dos_birthdate'], $bdate);
  }

  public function testDossierIndividualGroupedDefaultIsFalse() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertFalse($dos['dos_grouped']);
  }

  public function testDossierAddIndividualExternal() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', true);
    $this->assertGreaterThan(0, $dosId);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertTrue($dos['dos_external']);
  }

  public function testDossierIndividualUpdateFields() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '10/09/2016';
    $gender = 'female';
    self::$base->organ->dossier_set_individual_fields($this->token, $dosId, $fname2, $lname2, $bdate2, $gender);

    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertEquals('firstname2', $dos['dos_firstname']);
    $this->assertEquals('lastname2', $dos['dos_lastname']);
    $this->assertEquals('10/09/2016', $dos['dos_birthdate']);
    $this->assertEquals('female', $dos['dos_gender']);
  }

  /**
   * Set a groupname for an individual dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierIndividualSetGroupname() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
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

  /**
   * Trying to set a firstname for a grouped dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierGroupedSetFirstname() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    $fname = 'firstname';
    self::$base->organ->dossier_set_individual_fields($this->token, $dosId, $fname, null, null, null);
  }

  /**
   * Trying to set a lastname for a grouped dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierGroupedSetLastname() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    $lname = 'lastname';
    self::$base->organ->dossier_set_individual_fields($this->token, $dosId, null, $lname, null, null);
  }

  /**
   * Trying to set a birthdate for a grouped dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierGroupedSetBirthdate() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    $bdate = '01/09/2016';
    self::$base->organ->dossier_set_individual_fields($this->token, $dosId, null, null, $bdate, null);
  }

  /**
   * Trying to set a gender for a grouped dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierGroupedSetGender() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    $gender = 'female';
    self::$base->organ->dossier_set_individual_fields($this->token, $dosId, null, null, null, $gender);
  }

  public function testDossierSetExternalTrue() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);

    $gname = 'groupname';
    $dosId2 = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(1, $dosId2);

    self::$base->organ->dossier_set_external($this->token, $dosId, true);
    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertTrue($dos['dos_external']);

    self::$base->organ->dossier_set_external($this->token, $dosId2, true);
    $dos2 = self::$base->organ->dossier_get($this->token, $dosId2);
    $this->assertTrue($dos['dos_external']);
  }

  public function testDossierSetExternalBackToFalse() {
    $gname = 'groupname';
    $dosId = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosId);

    self::$base->organ->dossier_set_external($this->token, $dosId, true);
    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertTrue($dos['dos_external']);

    self::$base->organ->dossier_set_external($this->token, $dosId, false);
    $dos = self::$base->organ->dossier_get($this->token, $dosId);
    $this->assertFalse($dos['dos_external']);
  }
}
?>
