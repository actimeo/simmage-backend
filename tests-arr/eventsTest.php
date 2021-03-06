<?php
require 'vendor/autoload.php';
require './import/iVariation.inc.php';
require './import/VariationMain.inc.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

class EventsTest extends PHPUnit_Framework_TestCase {
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

    $main = new VariationMain('./arrangement-test', self::$base);
    $main->collectObjects('import/objects/', [ '', 'data/' ]);
    $main->start();
    $main->callObjects();
    //$main->end(); DO NOT CALL so ROLLBACK is DONE
  }
  
  protected function assertPreConditions()
  {
    self::$base->startTransaction();
  }

  protected function assertPostConditions()
  {
    self::$base->rollback();
  }

  public function testOk() {
    $this->assertTrue(true);
  }
  
}
?>
