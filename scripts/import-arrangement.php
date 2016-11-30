#! /usr/bin/php
<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

require 'import-arrangement-sub/topics.php';
require 'import-arrangement-sub/portals.php';
require 'import-arrangement-sub/organs.php';
require 'import-arrangement-sub/events-types.php';
require 'import-arrangement-sub/documents-types.php';
require 'import-arrangement-sub/groups.php';
require 'import-arrangement-sub/usergroups.php';
require 'import-arrangement-sub/users.php';

require 'import-arrangement-sub/data/dossiers.php';
require 'import-arrangement-sub/data/assignments.php';
require 'import-arrangement-sub/data/statuses.php';
require 'import-arrangement-sub/data/documents.php';
require 'import-arrangement-sub/data/events.php';
require 'import-arrangement-sub/data/notes.php';
require 'import-arrangement-sub/data/resources.php';
require 'import-arrangement-sub/data/objectives.php';

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

$topics_map = import_topics($dir . DIRECTORY_SEPARATOR . 'topics.csv', 
			    $base, $token, CSV_SEPARATOR);
$portals_map = import_portals($dir . DIRECTORY_SEPARATOR . 'portals.csv', 
			      $base, $token, CSV_SEPARATOR, $topics_map,
			      $dir . DIRECTORY_SEPARATOR . 'arranger');
$organs_map = import_organs($dir . DIRECTORY_SEPARATOR . 'organs.csv', 
			    $base, $token, CSV_SEPARATOR);
$etys_map = import_events_types($dir . DIRECTORY_SEPARATOR . 'events-types.csv', 
		    $base, $token, CSV_SEPARATOR, $topics_map);
$dtys_map = import_documents_types($dir . DIRECTORY_SEPARATOR . 'documents-types.csv', 
				   $base, $token, CSV_SEPARATOR, $topics_map);
$groups_map = import_groups($dir . DIRECTORY_SEPARATOR . 'groups.csv', 
			    $base, $token, CSV_SEPARATOR, $topics_map, $organs_map);
$usergroups_map = import_usergroups($dir . DIRECTORY_SEPARATOR . 'usergroups.csv', 
			    $base, $token, CSV_SEPARATOR, $portals_map, $groups_map);
import_users($dir . DIRECTORY_SEPARATOR . 'users.csv', 
	     $base, $token, CSV_SEPARATOR, $usergroups_map);

$dossiers_map = import_dossiers($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'dossiers.csv',
				$base, $token, CSV_SEPARATOR);
import_assignments($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'assignments.csv',
		   $base, $token, CSV_SEPARATOR, $dossiers_map, $groups_map);
import_statuses($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'statuses.csv',
		$base, $token, CSV_SEPARATOR, $dossiers_map, $organs_map);
import_documents($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'documents.csv',
		 $base, $token, CSV_SEPARATOR, $dossiers_map, $topics_map, $dtys_map);
import_events($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'events.csv',
	      $base, $token, CSV_SEPARATOR, $dossiers_map, $topics_map, $etys_map);
import_notes($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'notes.csv',
	     $base, $token, CSV_SEPARATOR, $dossiers_map, $topics_map);
import_resources($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'resources.csv',
		 $base, $token, CSV_SEPARATOR, $topics_map);
import_objectives($dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'objectives.csv',
		  $base, $token, CSV_SEPARATOR, $dossiers_map, $topics_map);

$base->commit ();
