#! /usr/bin/php
<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

require 'import-arrangement-sub/topics.php';
require 'import-arrangement-sub/portals.php';
require 'import-arrangement-sub/organs.php';
require 'import-arrangement-sub/events-types.php';
require 'import-arrangement-sub/documents-types.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

define ('CSV_SEPARATOR', ",");

if ($argc < 2) {
  echo "Usage: $argv[0] directory\n";
  exit;
}  

$dir = $argv[1];

$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database, $pg_port);

$base->startTransaction();

$variationUser = $base->login->user_login('variation', 'variation', '{users,organization,structure}');

$token = $variationUser['usr_token'];

$topics_map = import_topics($dir . DIRECTORY_SEPARATOR . 'topics.csv', $base, $token, CSV_SEPARATOR);
import_portals($dir . DIRECTORY_SEPARATOR . 'portals.csv', $base, $token, CSV_SEPARATOR);
import_organs($dir . DIRECTORY_SEPARATOR . 'organs.csv', $base, $token, CSV_SEPARATOR);
import_events_types($dir . DIRECTORY_SEPARATOR . 'events-types.csv', $base, $token, CSV_SEPARATOR, $topics_map);
import_documents_types($dir . DIRECTORY_SEPARATOR . 'documents-types.csv', $base, $token, CSV_SEPARATOR, $topics_map);
$base->commit ();
