<?php
class VariationDossiers extends iVariation {

  private $headers;

  public function getDependencies() { return [ ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $individual = strtolower($line[1]) == 'x';
    $external = strtolower($line[2]) == 'x';
    if ($individual) {
      $birthdate = $line[3];
      $gender = $line[4] == '0' ? 'male' : 'female';
      $firstname = $line[5];
      $lastname = $line[6];
      $id = $base->organ->dossier_add_individual($token, $firstname, $lastname, $birthdate, $gender, $external);
    } else {
      $groupname = $line[7];
      $id = $base->organ->dossier_add_grouped($token, $groupname, $external);
    }
    return [ $code, $id ];
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
