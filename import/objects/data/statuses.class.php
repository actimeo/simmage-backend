<?php
class VariationStatuses extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'data/dossiers', 'organs' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $dos_code = $line[0];
    $dos_id = $deps['data/dossiers'][$dos_code];
    $org_code = $line[1];
    $org_id = $deps['organs'][$org_code];
    $status = $line[2];
    $start = $line[3];
    $base->organ->dossier_status_change($token, $dos_id, $org_id, $status, $start);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
