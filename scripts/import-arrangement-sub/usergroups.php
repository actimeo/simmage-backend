<?php
function import_usergroups($file, $base, $token, $delimiter = "\t", $portals_map, $groups_map) {
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;

  $statuses_slice = 2;
  $db_statuses = $base->organ->dossier_status_value_list();
  $statuses = null;

  $portals_slice = $statuses_slice + count($db_statuses);
  $db_portals = $base->portal->portal_list($token);
  $portals = null;

  $groups_slice = $portals_slice + count($db_portals);
  $groups = null;

  $code_map = [];  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      if ($statuses === null) {
	$statuses = array_slice($line, $statuses_slice, count($db_statuses));
	if ($statuses != $db_statuses) {
	  echo 'Error statuses'."\n";
	  exit;
	}
	$portals = array_slice($line, $portals_slice, count($db_portals));	
	$groups = array_slice($line, $groups_slice);	
      }
      continue;
    }
    $code = $line[0];
    $name = $line[1];
    $usergroup_statuses = [];
    foreach ($statuses as $k => $st) {
      if (strtolower($line[$k+2]) == 'x') {
	$usergroup_statuses[] = $st;
      }
    }
    
    $usergroup_portal_ids = [];
    $portals_use = array_slice($line, $portals_slice, count($db_portals));
    for ($i=0; $i < count($portals_use); $i++) {
      if (strtolower($portals_use[$i]) == 'x') {
	$usergroup_portal_ids[] = $portals_map[$portals[$i]];
      }
    }

    $usergroup_group_ids = [];
    $groups_use = array_slice($line, $groups_slice);
    for ($i=0; $i < count($groups_use); $i++) {
      if (strtolower($groups_use[$i]) == 'x') {
	$usergroup_group_ids[] = $groups_map[$groups[$i]];
      }
    }
    $id = $base->login->usergroup_add($token, $name, null, $usergroup_statuses);

    if (count($usergroup_portal_ids)) {
      $base->login->usergroup_set_portals($token, $id, $usergroup_portal_ids);
    }
    if (count($usergroup_group_ids)) {
      $base->login->usergroup_set_groups($token, $id, $usergroup_group_ids);
    }

    $code_map[$code] = $id;
  }

  fclose($f);
  return $code_map;
}
