<?php
function import_topics($file, $base, $token, $delimiter = "\t") {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  $code_map = [];
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $code = $line[0];
    $id = $base->organ->topic_add($token, $line[1], $line[2], $line[3], $line[4]);
    $code_map[$code] = $id;
  }
  fclose($f);
  return $code_map;
}
