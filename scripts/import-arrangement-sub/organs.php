<?php
function import_organs($file, $base, $token, $delimiter = "\t") {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $code_map = [];  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $code = $line[0];
    $id = $base->organ->organization_add($token, $line[1], $line[2], strtolower($line[3]) === 'x' ? true : false);
    $code_map[$code] = $id;
  }

  fclose($f);
  return $code_map;
}
