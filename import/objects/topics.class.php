<?php
class VariationTopics extends iVariation {

  public function getDependencies() { return []; }

  public function headers($line) { }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $id = $base->organ->topic_add($token, $line[1], $line[2], $line[3], $line[4]);
    return [ $code, $id ];
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
