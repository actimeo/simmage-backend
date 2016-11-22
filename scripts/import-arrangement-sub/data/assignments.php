<?php
function import_assignments($file, $base, $token, $delimiter = "\t", $dossiers_map, $groups_map) {

  if (!file_exists($file))
    return -1;
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;

  $groups_slice = 1;
  $groups = null;

  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($groups === null) {
	$groups = array_slice($line, $groups_slice);
      }
      continue;
    }
    $dos_code = $line[0];
    $dos_id = $dossiers_map[$dos_code];

    $grp_ids = [];
    $groups_use = array_slice($line, $groups_slice);
    for ($i=0; $i < count($groups); $i++) {
      if (strtolower($groups_use[$i]) == 'x') {
	$grp_ids[] = $groups_map[$groups[$i]];
      }
    }

    $base->organ->dossier_assignment_add($token, $dos_id, $grp_ids);
  }
  fclose($f);
}
