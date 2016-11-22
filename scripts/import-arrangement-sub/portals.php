<?php
function import_portals($file, $base, $token, $delimiter = "\t", $topics_map, $arranger_path) {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $code_map = [];  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $code = $line[0];
    $name = $line[1];
    $desc = $line[2];
    $id = $base->portal->portal_add($token, $name, $desc);
    $code_map[$code] = $id;
    if (isset ($line[3]) && $line[3]) {
      $arranger = $line[3];
      include_once($arranger_path . DIRECTORY_SEPARATOR . $arranger . '.php');
      arranger($base, $token, $id, $name, $topics_map);
    }
  }

  fclose($f);
  return $code_map;
}
