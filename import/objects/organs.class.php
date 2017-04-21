<?php
class VariationOrgans extends iVariation {

  public function getDependencies() { return [ ]; }

  public function headers($line) { }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $id = $base->organ->organization_add($token, $line[1], $line[2], strtolower($line[3]) === 'x' ? true : false);
    return [ $code, $id ];
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
