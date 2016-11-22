<?php
function import_statuses($file, $base, $token, $delimiter = "\t", $dossiers_map, $organs_map) {

  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      continue;
    }
    $dos_code = $line[0];
    $dos_id = $dossiers_map[$dos_code];

    $org_code = $line[1];
    $org_id = $organs_map[$org_code];
    $status = $line[2];
    $start = $line[3];
    $base->organ->dossier_status_change($token, $dos_id, $org_id, $status, $start);
  }
  fclose($f);
}
