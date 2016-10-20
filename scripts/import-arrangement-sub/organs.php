<?php
function import_organs($file, $base, $token, $delimiter = "\t") {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $base->organ->organization_add($token, $line[0], $line[1], strtolower($line[2]) === 'x' ? true : false);
  }

  fclose($f);
}
