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
  private $pgDatabase;
  
  private $procs;
  private $skip = array (array('login', '_token_assert'),
			 array('login', '_token_assert_any'),
			 array('login', '_token_assert_other_login'),
			 array('login', '_user_token_create'),
			 array('organ', '_dossier_link_get_inverted_relationship'),
			 array('organ', '_participant_dossier_referee_list'),
			 );
  
  public function __construct($name = NULL, array $data = array(), $dataName = '') {
    global $pg_host, $pg_user, $pg_pass, $pg_database;
    $this->pgHost = $pg_host;
    $this->pgUser = $pg_user;
    $this->pgPass = $pg_pass;
    $this->pgDatabase = $pg_database;
    
    // Create object
    $this->base = new PgProcedures ($this->pgHost, $this->pgUser, $this->pgPass, $this->pgDatabase);

    $this->getProcs();
    parent::__construct($name, $data, $dataName);
  }

  private function getProcs() {
    $q = "SELECT nspname, proname
FROM pg_proc
INNER JOIN pg_namespace ON pg_namespace.oid = pronamespace
WHERE nspname NOT IN ('pg_catalog', 'information_schema', 'pgcrypto', 'pgprocedures', 'pgdoc')
order by nspname, proname";
    $this->procs = [];
    $res = $this->base->execute_sql($q);
    foreach ($res as $proc) {
      if ($this->skip_proc($proc))
	continue;
      $this->procs[] = [ $proc['nspname'], $proc['proname'] ];
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
   */
  public function testAssertTokenInProcedure($schema, $proc) {
    $path = getcwd() . DIRECTORY_SEPARATOR . '.traces' . DIRECTORY_SEPARATOR . $schema . DIRECTORY_SEPARATOR . $proc;
    $this->assertTrue(file_exists($path), 'procedure '. $schema.'->'.$proc.' should have been tested');
  }

  
}
?>
