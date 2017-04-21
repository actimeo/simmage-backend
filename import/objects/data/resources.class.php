<?php
class VariationResources extends iVariation {

  private $headers;
  
  public function getDependencies() { return [ 'topics' ]; }

  public function headers($line) {
    $this->headers = $line;
  }
  
  public function import($base, $token, $line, $deps, $dir) {

    $name = $line[0];

    $topics_selected = array_slice($line, 1, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 1, count($deps['topics']));
    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);

    $base->resources->resource_add($token, $name, $top_ids);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}

}
