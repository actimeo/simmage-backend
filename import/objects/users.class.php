<?php
class VariationUsers extends iVariation {

  public function getDependencies() { return [ 'usergroups' ]; }
  
  public function headers($line) {}

  public function import($base, $token, $line, $deps, $dir) {
    $login = $line[0];
    $usergroup_code = $line[1];
    $firstname = $line[2];
    $lastname = $line[3];

    $par_id = $base->organ->participant_add($token, $firstname, $lastname);
    $base->login->user_add($token, $login, null, $par_id, $deps['usergroups'][$usergroup_code]);
    $temp_pwd = $base->login->user_get_temporary_pwd($token, $login);
    // change password to Aa#login
    $user_info = $base->login->user_login($login, $temp_pwd, null, null);
    $base->login->user_change_password($user_info['usr_token'], 'Aa#'.$login);
    $base->login->user_logout($user_info['usr_token']);
    return [ $login, $par_id ];
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
