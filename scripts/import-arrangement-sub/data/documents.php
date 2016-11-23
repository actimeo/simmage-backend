<?php
function import_documents($file, $base, $token, $delimiter = "\t", $dossiers_map, $topics_map, $dtys_map) {
  $topics_slice = 10;
  
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
    $responsible = $line[0];
    $type = $line[1];
    $dty_id = $dtys_map[$type];
    $title = $line[2];
    $description = $line[3];
    $status = $line[4];
    $obtain_date = $line[5];
    $exec_date = $line[6];
    $valid_date = $line[7];
    $file = $line[8];
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

    $base->documents->document_add($token, null, $dty_id, $title, $description, $status, 
				   $obtain_date, $exec_date, $valid_date, $file, $top_ids, $dos_ids);    
  }
  fclose($f);
  return $code_map;
}
