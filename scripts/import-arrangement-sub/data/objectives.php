<?php
function import_objectives($file, $base, $token, $delimiter = "\t", $dossiers_map, $topics_map) {
  $topics_slice = 4;
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $topics = null;

  $code_map = [];

  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($topics === null) {
	$topics = array_slice($line, $topics_slice);
      }
      continue;
    }
    $name = $line[0];
    $deadline = $line[1];
    $open = $line[2];
    $dossiers = $line[3];
    
    $top_ids = [];
    $topics_use = array_slice($line, $topics_slice);
    for ($i=0; $i < count($topics); $i++) {
      if (strtolower($topics_use[$i]) == 'x') {
	$top_ids[] = $topics_map[$topics[$i]];
      }
    }
    
    $dos_ids = [];
    $dossiers_use = explode(';', $dossiers);
    foreach ($dossiers_use as $k => $d) {
      $dos_ids[] = $dossiers_map[$d];
    }

    $base->objectives->objective_add($token, $name, $open, $deadline, $top_ids, $dos_ids);    
  }
  fclose($f);
  return $code_map;
}
