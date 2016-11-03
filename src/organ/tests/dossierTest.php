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

  protected function assertPreConditions() {
    self::$base->startTransaction();
    $login = 'testdejfhcqcsdfkhn';
    $pwd = 'kfarinzaona';
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
			     ."VALUES ('Test', 'User')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
			     .$login."', pgcrypto.crypt('"
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization, users}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			  

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

  public function testDossierAssignment() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosId = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosId);
    
    $orgId = self::$base->organ->organization_add($this->token, 'org', 'desc org', true);
    $this->assertGreaterThan(0, $orgId);

    $grp_name1 = 'a group 1';
    $grp_desc1 = 'a group 1 desc';
    $grpId1 = self::$base->organ->group_add($this->token, $orgId, $grp_name1, $grp_desc1, false, 'organization');

    $grp_name2 = 'a group 2';
    $grp_desc2 = 'a group 2 desc';
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, $grp_name2, $grp_desc2, false, 'organization');

    $grp_name3 = 'a group 3';
    $grp_desc3 = 'a group 3 desc';
    $grpId3 = self::$base->organ->group_add($this->token, $orgId, $grp_name3, $grp_desc3, false, 'organization');

    self::$base->organ->dossier_assignment_add($this->token, $dosId, array($grpId1, $grpId3));
    $grps = self::$base->organ->dossier_assignment_list($this->token, $dosId);
    $this->assertEquals(array($grp_name1, $grp_name3), 
			array_map(function ($r) { return $r['grp_name']; }, $grps));
  }

  public function testDossierListRestricted() {
    $fname1 = 'firstname1';
    $lname1 = 'lastname1';
    $bdate1 = '03/10/2016';
    $dosId1 = self::$base->organ->dossier_add_individual($this->token, $fname1, $lname1, $bdate1, 'male', false);
    $this->assertGreaterThan(0, $dosId1);
    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '03/10/2016';
    $dosId2 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan($dosId1, $dosId2);
    $fname3 = 'firstname3';
    $lname3 = 'lastname3';
    $bdate3 = '03/10/2016';
    $dosId3 = self::$base->organ->dossier_add_individual($this->token, $fname3, $lname3, $bdate3, 'male', false);
    $this->assertGreaterThan($dosId2, $dosId3);
    $fname4 = 'firstname4';
    $lname4 = 'lastname4';
    $bdate4 = '03/10/2016';
    $dosId4 = self::$base->organ->dossier_add_individual($this->token, $fname4, $lname4, $bdate4, 'male', false);
    $this->assertGreaterThan($dosId3, $dosId4);
    $fname5 = 'firstname5';
    $lname5 = 'lastname5';
    $bdate5 = '03/10/2016';
    $dosId5 = self::$base->organ->dossier_add_individual($this->token, $fname5, $lname5, $bdate5, 'male', false);
    $this->assertGreaterThan($dosId4, $dosId5);


    $orgId = self::$base->organ->organization_add($this->token, 'org', 'desc org', true);
    $this->assertGreaterThan(0, $orgId);

    self::$base->organ->dossier_status_change($this->token, $dosId1, $orgId, 'preadmission', '01/09/2016');
    self::$base->organ->dossier_status_change($this->token, $dosId2, $orgId, 'admission', '05/09/2016');
    self::$base->organ->dossier_status_change($this->token, $dosId3, $orgId, 'admission', '10/09/2016');
    self::$base->organ->dossier_status_change($this->token, $dosId4, $orgId, 'present', '15/09/2016');
    self::$base->organ->dossier_status_change($this->token, $dosId5, $orgId, 'present', '20/09/2016');

    $grpId1 = self::$base->organ->group_add($this->token, $orgId, 'group 1', 'grp desc 1', false, 'organization');
    $grpId2 = self::$base->organ->group_add($this->token, $orgId, 'group 2', 'grp desc 2', false, 'organization');
    $this->assertGreaterThan(0, $grpId1);
    $this->assertGreaterThan($grpId1, $grpId2);

    $ugr1 = self::$base->login->usergroup_add($this->token, 'usergroup pread-ad', null, null, null, null, null,
					      '{preadmission, admission}');
    $ugr2 = self::$base->login->usergroup_add($this->token, 'usergroup ad-pres', null, null, null, null, null,
					      '{admission, present}');

    self::$base->login->usergroup_set_groups($this->token, $ugr1, array($grpId1));
    self::$base->login->usergroup_set_groups($this->token, $ugr2, array($grpId2));

    self::$base->organ->dossier_assignment_add($this->token, $dosId1, array($grpId1, $grpId2));
    self::$base->organ->dossier_assignment_add($this->token, $dosId2, array($grpId1, $grpId2));
    self::$base->organ->dossier_assignment_add($this->token, $dosId3, array($grpId1, $grpId2));
    self::$base->organ->dossier_assignment_add($this->token, $dosId4, array($grpId1, $grpId2));
    self::$base->organ->dossier_assignment_add($this->token, $dosId5, array($grpId1, $grpId2));


    self::$base->login->user_usergroup_set($this->token, 'testdejfhcqcsdfkhn', $ugr1);

    $login2 = 'zaertyuiopa';
    $pwd2 = 'blablabla';
    self::$base->execute_sql("INSERT INTO organ.participant (par_firstname, par_lastname) "
                             ."VALUES ('Test2', 'User2')");
    self::$base->execute_sql("insert into login.user (usr_login, usr_salt, usr_rights, par_id) values ('"
                             .$login2."', pgcrypto.crypt('"
                             .$pwd2."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
                             ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test2'));");

    $usr2 = self::$base->login->user_login($login2, $pwd2, null);
    $token2 = $usr2['usr_token'];

    self::$base->login->user_usergroup_set($this->token, $login2, $ugr2);

    $dosList1 = self::$base->organ->dossier_list($this->token, false, false, $grpId1);
    $this->assertEquals(3, count($dosList1));

    $dosList2 = self::$base->organ->dossier_list($token2, false, false, $grpId2);
    $this->assertEquals(4, count($dosList2));
  }
}
?>
