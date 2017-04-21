<?php
class VariationUsersAssignments extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'users', 'groups' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $login = $line[0];
    $par_id = $deps['users'][$login];
    $grp_code = $line[1];
    $grp_id = $deps['groups'][$grp_code];
    $base->organ->participant_assignment_add($token, $grp_id, $par_id);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
