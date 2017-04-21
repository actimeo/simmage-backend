<?php
class VariationAssignments extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'data/dossiers', 'groups' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $dos_code = $line[0];
    $dos_id = $deps['data/dossiers'][$dos_code];
    $groups_selected = array_slice($line, 1);
    $groups_headers = array_slice($this->headers, 1);

    $grp_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($groups_headers, $groups_selected), 
				     $deps['groups']);
    $base->organ->dossier_assignment_add($token, $dos_id, $grp_ids);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
