<?php
function import_documents_types($file, $base, $token, $delimiter = "\t", $topics_map) {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $topics = null;

  $orgs = $base->organ->organization_list($token, true);
  $org_ids = array_map(function($o) { return $o['org_id']; }, $orgs);

  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($topics === null) {
	$topics = array_slice($line, 2);
      }
      continue;
    }
    $name = $line[0];
    $individual = strtolower($line[1]) == 'x' ? true : false;
    $id = $base->documents->document_type_add($token, $name, $individual);
    $top_ids = [];
    $topics_use = array_slice($line, 2);
    for ($i=0; $i < count($topics); $i++) {
      if (strtolower($topics_use[$i]) == 'x') {
	$top_ids[] = $topics_map[$topics[$i]];
      }
    }
    if (count($top_ids)) {
      $base->documents->document_type_set_topics($token, $id, $top_ids);
    }
    if (count($org_ids)) {
      $base->documents->document_type_set_organizations($token, $id, $org_ids);
    }
  }
  fclose($f);
}
