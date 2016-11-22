<?php
function import_dossiers($file, $base, $token, $delimiter = "\t") {
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;

  $code_map = [];  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      continue;
    }
    $birthdate = $line[0];
    $gender = $line[1] == '0' ? 'male' : 'female';
    $firstname = $line[2];
    $lastname = $line[3];
    $code = $line[4];
    $dos_id = $base->organ->dossier_add_individual($token, $firstname, $lastname, $birthdate, $gender, false);
    $code_map[$code] = $dos_id;
  }
  fclose($f);
  return $code_map;
}
