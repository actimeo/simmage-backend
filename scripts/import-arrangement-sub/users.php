<?php
function import_users($file, $base, $token, $delimiter = "\t", $usergroups_map) {
  
  $f = fopen($file, 'r');
  if ($f === FALSE)
    return -1;

  $code_map = [];  
  while ( ($line = fgetcsv($f, 0, $delimiter)) !== FALSE) {
    if ($line[0][0] == '#') {
      continue;
    }
    $login = $line[0];
    $usergroup_code = $line[1];
    $firstname = $line[2];
    $lastname = $line[3];

    $par_id = $base->organ->participant_add($token, $firstname, $lastname);
    $base->login->user_add($token, $login, null, $par_id, $usergroups_map[$usergroup_code]);
    $temp_pwd = $base->login->user_get_temporary_pwd($token, $login);
    // change password to login
    $user_info = $base->login->user_login($login, $temp_pwd, null);
    $base->login->user_change_password($user_info['usr_token'], $login);
    $base->login->user_logout($user_info['usr_token']);
  }
  fclose($f);
}
