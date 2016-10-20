<?php
function import_portals($file, $base, $token, $delimiter = "\t") {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $base->portal->portal_add($token, $line[0], $line[1]);
  }

  fclose($f);
}
