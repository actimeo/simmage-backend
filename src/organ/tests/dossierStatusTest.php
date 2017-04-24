<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class dossierStatusTest extends PHPUnit_Framework_TestCase {
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
			     .$pwd."', pgcrypto.gen_salt('bf', 8)), '{organization}', "
			     ."(SELECT par_id FROM organ.participant WHERE par_firstname='Test'));");

    $res = self::$base->login->user_login($login, $pwd, null, null);
    $this->token = $res['usr_token'];
  }

  protected function assertPostConditions() {
    self::$base->rollback();
  }

  public function testDossierStatusChangeAndGet() {
    $orgId = self::$base->organ->organization_add($this->token, 'an org', 'a desc', true);
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, 'first', 'last', '01/01/2000', 'male', false);
    $statusSet = 'present';
    $when = '01/01/2016';
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, $statusSet, $when);
    $status = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, $when);
    $this->assertEquals($statusSet, $status);
    $statusPrev = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, '31/12/1999');
    $this->assertEquals(NULL, $statusPrev);
  }

  public function testDossierStatusChangeMultiple() {
    $orgId = self::$base->organ->organization_add($this->token, 'an org', 'a desc', true);
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, 'first', 'last', '01/01/2000', 'male', false);
    $status1 = 'preadmission';
    $status2 = 'admission';
    $status3 = 'present';
    $status4 = 'left';
    $when1 = '01/01/2016';
    $whenAfter1 = '15/01/2016';
    $when2 = '01/02/2016';
    $whenAfter2 = '15/02/2016';
    $when3 = '01/04/2016';
    $whenAfter3 = '15/04/2016';
    $when4 = '01/08/2016';
    $whenAfter4 = '15/08/2016';
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, $status1, $when1);
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, $status2, $when2);
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, $status3, $when3);
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId, $status4, $when4);
    $status = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, $whenAfter1);
    $this->assertEquals($status1, $status);
    $status = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, $whenAfter2);
    $this->assertEquals($status2, $status);
    $status = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, $whenAfter3);
    $this->assertEquals($status3, $status);
    $status = self::$base->organ->dossier_status_get($this->token, $dosId, $orgId, $whenAfter4);
    $this->assertEquals($status4, $status);
  }

  public function testDossierStatusList() {
    $orgId1 = self::$base->organ->organization_add($this->token, 'an org 1', 'a desc 1', true);
    $orgId2 = self::$base->organ->organization_add($this->token, 'an org 2', 'a desc 2', true);
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, 'first', 'last', '01/01/2000', 'male', false);    
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId1, 'preadmission', '01/12/2015');
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId1, 'admission', '01/01/2016');
    self::$base->organ->dossier_status_change($this->token, $dosId, $orgId2, 'present', '01/02/2016');
    $req = [
	    'org_id' => true,
	    'dst_value' => true
	    ];
    $list = self::$base->organ->dossier_status_list_json($this->token, $dosId, null, json_encode($req));
    $list2 = array_reduce($list, function($pot, $item) { 
	$pot[$item->org_id] = $item->dst_value;
	return $pot;
	}, []);
    ksort($list2);
    $this->assertEquals(array($orgId1 => 'admission', $orgId2 => 'present'), $list2);
  }

  public function testDossierStatusValueList() {
    $list = self::$base->organ->dossier_status_value_list();
    $this->assertEquals($list[0], 'preadmission');
  }

  public function testDossierStatusHistory() {
    $orgId1 = self::$base->organ->organization_add($this->token, 'an org 1', 'a desc 1', true);
    $orgId2 = self::$base->organ->organization_add($this->token, 'an org 2', 'a desc 2', true);
    $fname = 'firstname';
    $lname = 'lastname';
    $bdate = '01/09/2016';    
    $dosId = self::$base->organ->dossier_add_individual($this->token, 'first', 'last', '01/01/2000', 'male', false);    
    $dst1 = self::$base->organ->dossier_status_change($this->token, $dosId, $orgId1, 'preadmission', '01/12/2015');
    $dst2 = self::$base->organ->dossier_status_change($this->token, $dosId, $orgId1, 'admission', '01/01/2016');
    $dst3 = self::$base->organ->dossier_status_change($this->token, $dosId, $orgId2, 'present', '01/02/2016');
    $list = self::$base->organ->dossier_status_history($this->token, $dosId, null, null);
    $ids = array_map(function($a) { return $a['dst_id']; }, $list);
    $this->assertEquals([$dst3, $dst2, $dst1], $ids);

    $list = self::$base->organ->dossier_status_history($this->token, $dosId, $orgId1, null);
    $ids = array_map(function($a) { return $a['dst_id']; }, $list);
    $this->assertEquals([$dst2, $dst1], $ids);

    $list = self::$base->organ->dossier_status_history($this->token, $dosId, null, 'present');
    $ids = array_map(function($a) { return $a['dst_id']; }, $list);
    $this->assertEquals([$dst3], $ids);
  }
}
?>
