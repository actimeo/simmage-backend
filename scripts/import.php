<?php
require './vendor/autoload.php';
require './import/iVariation.inc.php';
require './import/VariationMain.inc.php';

require_once './config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

date_default_timezone_set('Europe/Paris');

if ($argc < 2) {
  echo "Usage: $argv[0] directory\n";
  exit;
}  
$dir = $argv[1];
$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database, $pg_port);

$main = new VariationMain($dir, $base);
$main->collectObjects('import/objects/', [ '', 'data/' ]);
$main->start();
$main->callObjects();
$main->end();
