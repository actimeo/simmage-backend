<?php
function import_groups($file, $base, $token, $delimiter = "\t", $topics_map, $organs_map) {
  $topics_slice = 7;
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $topics = null;

  $code_map = [];

  $xsets = [];

  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($topics === null) {
	$topics = array_slice($line, $topics_slice);
      }
      continue;
    }
    $code = $line[0];
    $organ_code = $line[1];
    $org_id = $organs_map[$organ_code];
    $name = $line[2];
    $description = $line[3];
    $mandatory = strtolower($line[4]) === 'x' ? true : false;
    $orientation = $line[5];
    $exclusive_set = $line[6];

    $id = $base->organ->group_add($token, $org_id, $name, $description, $mandatory, $orientation);

    if ($exclusive_set) {
      if (!isset($xsets[$exclusive_set])) {
	$xsets[$exclusive_set] = [];
      }
      array_push($xsets[$exclusive_set], $id);
    }

    $code_map[$code] = $id;
    $top_ids = [];
    $topics_use = array_slice($line, $topics_slice);
    for ($i=0; $i < count($topics); $i++) {
      if (strtolower($topics_use[$i]) == 'x') {
	$top_ids[] = $topics_map[$topics[$i]];
      }
    }
    if (count($top_ids)) {
      $base->organ->group_set_topics($token, $id, $top_ids);
    }
  }
  foreach ($xsets as $name => $grp_ids) {
    $set_id = $base->organ->group_exclusive_new($token, $name, $grp_ids);
  }
  fclose($f);
  return $code_map;
}
