<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class globalTest extends PHPUnit_Framework_TestCase {
  private $base;
  private $pgHost;
  private $pgUser;
  private $pgPass;
  private $pgPort;
  private $pgDatabase;

  private $procs;
  private $skip = array (array('login', '_token_assert'),
			 array('login', '_token_assert_any'),
			 array('login', '_token_assert_other_login'),
			 array('login', 'user_login'),
			 array('login', 'user_login_json'),
			 array('login', '_user_token_create'),
			 array('portal', 'mainview_element_type_list'),
			 array('portal', 'param_list'),
			 array('portal', 'personview_element_type_list'),
			 array('organ', '_dossier_link_get_inverted_relationship'),
			 array('events', 'event_category_list'),
			 array('login', 'user_list_demo'),
			 array('login', 'user_right_list'),
			 array('organ', 'dossier_status_value_list'),
			 array('login', 'usergroup_right_list'),
			 array('login', 'usergroup_topic_right_list'),
			 array('documents', 'document_status_list'),
			 array('events', 'event_duration_list'),
			 array('events', 'event_status_list'),
			 array('objectives', 'objective_status_list'),
			 array('organ', '_participant_dossier_referee_list'),
			 array('public', '_json_date_format'),
			 array('public', '_format_retrieve_ids'),
			 array('notes', '_retrieve_notes_participant'),
			 );
  
  public function __construct($name = NULL, array $data = array(), $dataName = '') {
    global $pg_host, $pg_user, $pg_pass, $pg_database, $pg_port;
    $this->pgHost = $pg_host;
    $this->pgUser = $pg_user;
    $this->pgPass = $pg_pass;
    $this->pgPort = $pg_port;
    $this->pgDatabase = $pg_database;
    
    // Create object
    $this->base = new PgProcedures ($this->pgHost, $this->pgUser, $this->pgPass, $this->pgDatabase, $this->pgPort, '.traces');

    $this->getProcs();
    parent::__construct($name, $data, $dataName);
  }
  /*
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
    self::$base = new PgProcedures (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);    

    //    self::getProcs();
  }
  */
  private function getProcs() {
    $q = "SELECT nspname, proname, pronargs
FROM pg_proc
INNER JOIN pg_namespace ON pg_namespace.oid = pronamespace
WHERE nspname NOT IN ('pg_catalog', 'information_schema', 'pgcrypto', 'pgprocedures', 'pgdoc')
order by nspname, proname";
    $this->procs = [];
    $res = $this->base->execute_sql($q);
    foreach ($res as $proc) {
      if ($this->skip_proc($proc))
	continue;
      $this->procs[] = [ $proc['nspname'], $proc['proname'], $proc['pronargs'] ];
    }    
  }

  private function skip_proc($proc) {
    foreach ($this->skip as list($s, $p)) {
      if ($proc['nspname'] == $s && $proc['proname'] == $p) {
	return true;
      }
    }
    return false;
  }
  
  public function proceduresList() {
    return $this->procs;
  }

  /**
   * @dataProvider proceduresList
   * @expectedException \actimeo\pgproc\PgProcException
   * @expectedExceptionMessage insufficient_privilege
   */
  public function testAssertTokenInProcedure($schema, $proc, $nargs) {
    $args = array_fill(0, $nargs, NULL);
    $args[0] = '123456'; // wrong token
    $this->base->$schema->__call($proc, $args);
  }

  
}
?>
