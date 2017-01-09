<?php
function import_events($file, $base, $token, $delimiter = "\t", $dossiers_map, $topics_map, $etys_map) {
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $topics_slice = 10;
  $topics = null;

  $code_map = [];

  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($topics === null) {
	$topics = array_slice($line, $topics_slice);
      }
      continue;
    }
    $title = $line[0];
    $type = $line[1];
    $ety_id = $etys_map[$type];
    $duration_type = $line[2];
    $start_time = $line[3];
    $end_time = $line[4];
    //    echo $start_time.' '.$end_time;
    $place = $line[5];
    $cost = $line[6];
    $description = $line[7];
    $sumup = $line[8];
    $dossiers = $line[9];
    
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

    $base->events->event_add($token, $title, $ety_id, $duration_type, 
			     $start_time, $end_time, $place, $cost, 
			     $description, $sumup,
			     false, null, null, null, 0,
			     $top_ids, $dos_ids);    
  }
  fclose($f);
  return $code_map;
}
