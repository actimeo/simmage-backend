<?php
class VariationReferee extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'users', 'groups', 'data/dossiers', 'data/assignments', 'data/usersassignments' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $grp_code = $line[0];
    $grp_id = $deps['groups'][$grp_code];
    $dos_code = $line[1];
    $dos_id = $deps['data/dossiers'][$dos_code];
    $login = $line[2];
    $par_id = $deps['users'][$login];
    $function = $line[3];
    $base->organ->referee_add($token, $grp_id, $dos_id, $par_id, $function);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
