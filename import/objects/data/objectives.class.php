<?php
class VariationObjectives extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'data/dossiers', 'topics' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {

    $name = $line[0];
    $status = $line[1];
    $start_date = $line[2];
    $end_date = $line[3];
    $dossier = $line[4];

    $topics_selected = array_slice($line, 5, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 5, count($deps['topics']));
    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);
    
    $dos_id = $deps['data/dossiers'][$dossier];

    $base->objectives->objective_add($token, $name, $status, $start_date, $end_date, $top_ids, $dos_id);
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
