<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class dossierLinkTest extends PHPUnit_Framework_TestCase {
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
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase,
				    '5432', '.traces');
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");			  			 

    $res = self::$base->login->user_login($login, $pwd, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions() {
    self::$base->rollback();
  }

  public function testDossierLinkAdd() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosI = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosI);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dosII = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dosII);

    $rel = 'father';
    $dosLId = self::$base->organ->dossier_link_add($this->token, $dosI, $dosII, $rel);
    $this->assertGreaterThan(0, $dosLId);
  }

  public function testDossierLinkGroupedAndIndividual() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosI = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosI);

    $gname = 'groupname';
    $dosII = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(1, $dosII);

    $dosLId = self::$base->organ->dossier_link_add($this->token, $dosI, $dosII, null);
    $this->assertGreaterThan(0, $dosLId);
  }

  /**
   * Try to link a dossier to itself
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierLinkToSelf() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos);

    $link = self::$base->organ->dossier_link_add($this->token, $dos, $dos, 'father');
  }

  /**
   * Trying to link two individual dossiers with a null relationship
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierLinkBothIndividualNullRelationship() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dosI = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dosI);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dosII = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dosII);

    $dosLId = self::$base->organ->dossier_link_add($this->token, $dosI, $dosII, null);
  }

  /**
   * Trying to link two grouped dossier together
   * @expectedException \actimeo\pgproc\PgProcException
   */ 
  public function testDossierLinkGroupedBoth() {
    $gname = 'groupname';
    $dosI = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(0, $dosI);
    
    $gname2 = 'groupname2';
    $dosII = self::$base->organ->dossier_add_grouped($this->token, $gname2, false);
    $this->assertGreaterThan(1, $dosII);

    $dosLId = self::$base->organ->dossier_link_add($this->token, $dosI, $dosII, 'father');
  }

  public function testDossierLinkGet() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos1 = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos1);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dos2 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dos2);

    $dosLId = self::$base->organ->dossier_link_add($this->token, $dos1, $dos2, 'father');
    $this->assertGreaterThan(0, $dosLId);

    $link = self::$base->organ->dossier_link_get($this->token, $dosLId);
    $this->assertEquals($dos1, $link['dos_id']);
    $this->assertEquals($dos2, $link['dos_id_related']);
    $this->assertEquals('father', $link['dol_relationship']);
  }

  public function testDossierLinkInvertedRelationShips() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    // Father
    $dos1 = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos1);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    // Son 1
    $dos2 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dos2);

    $fname3 = 'firstname3';
    $lname3 = 'lastname3';
    $bdate3 = '01/09/2016';
    // Son 2
    $dos3 = self::$base->organ->dossier_add_individual($this->token, $fname3, $lname3, $bdate3, 'male', false); 
    $this->assertGreaterThan(2, $dos3);

    $fname4 = 'firstname4';
    $lname4 = 'lastname4';
    $bdate4 = '01/09/2016';
    // Mother
    $dos4 = self::$base->organ->dossier_add_individual($this->token, $fname4, $lname4, $bdate4, 'female', false); 
    $this->assertGreaterThan(3, $dos4);

    $fname5 = 'firstname5';
    $lname5 = 'lastname5';
    $bdate5 = '01/09/2016';
    // Daughter 1
    $dos5 = self::$base->organ->dossier_add_individual($this->token, $fname5, $lname5, $bdate5, 'female', false); 
    $this->assertGreaterThan(4, $dos5);

    $fname6 = 'firstname6';
    $lname6 = 'lastname6';
    $bdate6 = '01/09/2016';
    // Daughter 2
    $dos6 = self::$base->organ->dossier_add_individual($this->token, $fname6, $lname6, $bdate6, 'female', false); 
    $this->assertGreaterThan(5, $dos6);

    $dosLBB = self::$base->organ->dossier_link_add($this->token, $dos2, $dos3, 'brother'); // Brother-Brother
    $this->assertGreaterThan(0, $dosLBB);
    $dosLBS = self::$base->organ->dossier_link_add($this->token, $dos2, $dos5, 'brother'); // Brother-Sister
    $this->assertGreaterThan(2, $dosLBS);
    $dosLSS = self::$base->organ->dossier_link_add($this->token, $dos5, $dos6, 'sister'); // Sister-Sister
    $this->assertGreaterThan(4, $dosLSS);
    $dosLFM = self::$base->organ->dossier_link_add($this->token, $dos1, $dos4, 'husband'); // Husband-Wife
    $this->assertGreaterThan(6, $dosLFM);
    $dosLFS = self::$base->organ->dossier_link_add($this->token, $dos1, $dos2, 'father'); // Father-Son
    $this->assertGreaterThan(8, $dosLFS);
    $dosLFD = self::$base->organ->dossier_link_add($this->token, $dos1, $dos5, 'father'); // Father-Daughter
    $this->assertGreaterThan(10, $dosLFD);
    $dosLMS = self::$base->organ->dossier_link_add($this->token, $dos4, $dos2, 'mother'); // Mother-Son
    $this->assertGreaterThan(12, $dosLMS);
    $dosLMD = self::$base->organ->dossier_link_add($this->token, $dos4, $dos5, 'mother'); // Mother-Daughter
    $this->assertGreaterThan(14, $dosLMD);

    $dolBB = self::$base->organ->dossier_link_get($this->token, $dosLBB+1);
    $this->assertEquals('brother', $dolBB['dol_relationship']);
    $dolSB = self::$base->organ->dossier_link_get($this->token, $dosLBS+1);
    $this->assertEquals('sister', $dolSB['dol_relationship']);
    $dolSS = self::$base->organ->dossier_link_get($this->token, $dosLSS+1);
    $this->assertEquals('sister', $dolSS['dol_relationship']);
    $dolMF = self::$base->organ->dossier_link_get($this->token, $dosLFM+1);
    $this->assertEquals('wife', $dolMF['dol_relationship']);
    $dolSF = self::$base->organ->dossier_link_get($this->token, $dosLFS+1);
    $this->assertEquals('son', $dolSF['dol_relationship']);
    $dolDF = self::$base->organ->dossier_link_get($this->token, $dosLFD+1);
    $this->assertEquals('daughter', $dolDF['dol_relationship']);
    $dolSM = self::$base->organ->dossier_link_get($this->token, $dosLMS+1);
    $this->assertEquals('son', $dolSM['dol_relationship']);
    $dolDM = self::$base->organ->dossier_link_get($this->token, $dosLMD+1);
    $this->assertEquals('daughter', $dolDM['dol_relationship']);
  }

  public function testDossierLinkList() {
    $fname = 'fisrtname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos1 = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos1);

    $gname = 'groupname';
    $dos2 = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(1, $dos2);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dos3 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(2, $dos3);

    $fname3 = 'firstname3';
    $lname3 = 'lastname3';
    $bdate3 = '01/09/2016';
    $dos4 = self::$base->organ->dossier_add_individual($this->token, $fname3, $lname3, $bdate3, 'female', false);
    $this->assertGreaterThan(3, $dos4);

    $dosL12 = self::$base->organ->dossier_link_add($this->token, $dos1, $dos2, null);
    $this->assertGreaterThan(0, $dosL12);
    $dosL23 = self::$base->organ->dossier_link_add($this->token, $dos2, $dos3, null);
    $this->assertGreaterThan(1, $dosL23);
    $dosL31 = self::$base->organ->dossier_link_add($this->token, $dos3, $dos1, 'father');
    $this->assertGreaterThan(2, $dosL31);
    $dosL24 = self::$base->organ->dossier_link_add($this->token, $dos2, $dos4, null);

    $list = self::$base->organ->dossier_link_list($this->token, $dos1);
    $this->assertEquals(2, count($list));

    $list2 = self::$base->organ->dossier_link_list($this->token, $dos2);
    $this->assertEquals(3, count($list2));
  }

  /**
   * Try to SET a null link between two individual dossiers
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierLinkNullBetweenIndividual() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos);

    $fname2 = 'fristname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dos2 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dos2);

    $dol = self::$base->organ->dossier_link_add($this->token, $dos, $dos2, 'father');
    $this->assertGreaterThan(0, $dol);

    self::$base->organ->dossier_link_set($this->token, $dol, null);
  }

  /**
   * Try to set a non null relationship if there's a grouped dossier
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierLinkNotNullWithGrouped() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos);

    $gname = 'firstname';
    $dos2 = self::$base->organ->dossier_add_grouped($this->token, $gname, false);
    $this->assertGreaterThan(1, $dos2);

    $dol = self::$base->organ->dossier_link_add($this->token, $dos, $dos2, null);
    $this->assertGreaterThan(0, $dol);

    self::$base->organ->dossier_link_set($this->token, $dol, 'father');
  }

  /**
   * Try to set a wrong relationship (gender depending)
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testDossierLinkSetWrongRelationship() {
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';
    $dos = self::$base->organ->dossier_add_individual($this->token, $fname, $lname, $bdate, 'male', false);
    $this->assertGreaterThan(0, $dos);

    $fname2 = 'firstname2';
    $lname2 = 'lastname2';
    $bdate2 = '01/09/2016';
    $dos2 = self::$base->organ->dossier_add_individual($this->token, $fname2, $lname2, $bdate2, 'male', false);
    $this->assertGreaterThan(1, $dos2);

    $dol = self::$base->organ->dossier_link_add($this->token, $dos, $dos2, 'father');
    $this->assertGreaterThan(0, $dol);

    self::$base->organ->dossier_link_set($this->token, $dol, 'mother');
  }
}
?>
