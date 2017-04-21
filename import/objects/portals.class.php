<?php
class VariationPortals extends iVariation {

  public function getDependencies() { return [ 'topics' ]; }

  public function headers($line) {}

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $name = $line[1];
    $desc = $line[2];
    $id = $base->portal->portal_add($token, $name, $desc);
    if (isset ($line[3]) && $line[3]) {
      $arranger = $line[3];
      $panes = $line[4];
      include_once($dir . DIRECTORY_SEPARATOR . 'arranger' . DIRECTORY_SEPARATOR . $arranger . '.php');
      arranger($base, $token, $id, $name, $deps['topics'], $panes);
    }
    return [ $code, $id ];
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
