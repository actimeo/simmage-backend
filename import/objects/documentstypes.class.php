<?php
class VariationDocumentstypes extends iVariation {

  private $headers;

  public function getDependencies() { return [ 'topics', 'organs' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function beforeImport($base, $token) {
  }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $name = $line[1];
    $individual = strtolower($line[2]) == 'x' ? true : false;
    $topics_selected = array_slice($line, 3, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 3, count($deps['topics']));

    $organs_selected = array_slice($line, 3 + count($deps['topics']), count($deps['organs']));
    $organs_headers = array_slice($this->headers, 3 + count($deps['topics']), count($deps['organs']));

    $id = $base->documents->document_type_add($token, $name, $individual);

    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);
    if (count($top_ids)) {
      $base->documents->document_type_set_topics($token, $id, $top_ids);
    }

    $org_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($organs_headers, $organs_selected), 
				     $deps['organs']);
    if (count($org_ids)) {
      $base->documents->document_type_set_organizations($token, $id, $org_ids);
    }
    return [ $code, $id ];
  }

  public function afterImport($base, $token) {}

}
