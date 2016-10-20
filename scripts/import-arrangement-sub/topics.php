<?php
function import_topics($file, $base, $token, $delimiter = "\t") {
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;
  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#')
      continue;
    $base->organ->topic_add($token, $line[0], $line[1], $line[2], $line[3]);
  }

  fclose($f);
}
