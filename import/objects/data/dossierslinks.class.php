<?php
class VariationDossiersLinks extends iVariation {

  public function getDependencies() { return [ 'data/dossiers' ]; }

  public function headers($line) {}

  public function import($base, $token, $line, $deps, $dir) {
    $code_dossier = $line[0];
    $dos_id = $deps['data/dossiers'][$code_dossier];
    $code_related = $line[1];
    $dos_id_related = $deps['data/dossiers'][$code_related];
    $relationship = $line[2];
    $base->organ->dossier_link_add($token, $dos_id, $dos_id_related, $relationship);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
