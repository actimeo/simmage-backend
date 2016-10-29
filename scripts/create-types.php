#! /usr/bin/php
<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcException;

if ($argc < 2) {
  echo "Usage: $argv[0] directory\n";
  exit;
}  

$dir = $argv[1];

$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database, $pg_port);

$pg2tsType = array ('int4' => 'number',
		    'text' => 'string',
		    'date' => 'string',
		    'bool' => 'boolean');

function pg2tsType($s) {
  global $pg2tsType;
  if (isset($pg2tsType[$s]))
    return $pg2tsType[$s];
  else
    return 'string';
}
$res = array();

$schemas = $base->pgdoc->list_schemas(array('pg%', 'information_schema', 'public'));

foreach ($schemas as $schema) {
  $fcts = $base->pgdoc->schema_list_functions($schema);
  if (count($fcts)) {
    foreach ($fcts as $fct) {
      $details = $base->pgdoc->function_details($schema, $fct);      
      if ($details['rettype_schema'] == 'pg_catalog')
	continue;
      display_info($details['rettype_schema'], $details['rettype_name']);
    }
  }
}

write_files($dir, $res);

function display_info($schema, $name) {
  global $base, $pg2tsType, $res;
  $ccName = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
  $cols = $base->pgdoc->type_columns($schema, $name);
  if (count ($cols)) {
    if (isset($res[$schema][$ccName])) {
      return;
    }
    $res[$schema][$ccName] = array();
    foreach ($cols as $col) {
      $type = $col['typname'];
      $subtype = $type;
      if (substr($type, 0, 1) == '_') {
	$subtype = substr($subtype, 1);
      }
      $subtype = pg2tsType($subtype);
      if (substr($type, 0, 1) == '_') {
	$type = $subtype.'[]';
      } else {
	$type = $subtype;
      }
      $colname = $col['colname'];
      $res[$schema][$ccName][$colname] = $type;
    }
  }
}

function write_files($dir, $res) {
  foreach ($res as $schema => $interfaces) {
    $path = $dir.'/'.$schema.'.ts';
    $f = fopen($path, 'w');
    foreach ($interfaces as $intname => $cols) {
      fwrite ($f, 'export interface Db'.$intname." {\n");
      foreach ($cols as $colname => $coltype) {
	fwrite ($f, '  '.$colname.': '.$coltype.";\n");
      }
      fwrite ($f, "}\n\n");      
    }
    fclose ($f);
  }
}