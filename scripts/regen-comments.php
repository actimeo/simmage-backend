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

$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database);

$schemas = $base->pgdoc->list_schemas(array('pg%', 'information_schema', 'public'));

foreach ($schemas as $schema) {
  $f = fopen ($dir.'/'.$schema.'/sql/comments.sql', 'w');
  if (!$f)
    continue;
  $desc = $base->pgdoc->schema_description($schema);
  fwrite($f, "COMMENT ON SCHEMA $schema IS '".pg_escape_string($desc)."';\n");

  $types = $base->pgdoc->schema_list_types($schema);
  if (count($types)) {
    foreach ($types as $type) {
      $typdesc = $base->pgdoc->type_description($schema, $type);
      fwrite ($f, "\nCOMMENT ON TYPE $schema.$type IS '".pg_escape_string($typdesc)."';\n");
    }
  }

  $tables = $base->pgdoc->schema_list_tables($schema);
  foreach ($tables as $table) {
    $tdesc = $base->pgdoc->table_description($schema, $table);
    fwrite ($f, "\nCOMMENT ON TABLE $schema.$table IS '".pg_escape_string($tdesc)."';\n");

    $cols = $base->pgdoc->table_columns($schema, $table);
    $descs = array_map(function ($x) use ($schema, $table) { return array('schema' => $schema, 'table' => $table, 'column' => $x['colname'], 'desc' => $x['description']); }, $cols);
    foreach ($descs as $desc) {
      fwrite ($f, "COMMENT ON COLUMN ".$desc['schema'].".".$desc['table'].".".$desc['column']." IS '".pg_escape_string($desc['desc'])."';\n");
    }
  }


  /*  if (count($fcts)) {
    foreach ($fcts as $fct) {
      $details = $base->pgdoc->function_details($schema, $fct);      
      if ($details['rettype_schema'] == 'pg_catalog')
	continue;
      display_info($details['rettype_schema'], $details['rettype_name']);
    }
    }*/
}
exit;


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