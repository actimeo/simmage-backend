<?php
function import_notes($file, $base, $token, $delimiter = "\t", $dossiers_map, $topics_map) {
  $topics_slice = 5;
  
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
    $text = $line[0];
    $creation_date = $line[1];
    $event_date = $line[2];
    $object = $line[3];
    $dossiers = $line[4];
    
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

    $base->notes->note_add($token, $text, $event_date, $object, $top_ids, $dos_ids);    
  }
  fclose($f);
  return $code_map;
}
