<?php
class VariationNotes extends iVariation {

  private $headers;
  
  public function getDependencies() { return [ 'data/dossiers', 'topics', 'users' ]; }

  public function headers($line) {
    $this->headers = $line;
  }
  
  public function import($base, $token, $line, $deps, $dir) {
    $text = $line[0];
    $creation_date = $line[1];
    $event_date = $line[2];
    $object = $line[3];
    $dossiers = $line[4];
    $rcpt_info = $line[5];
    $rcpt_action = $line[6];

    $topics_selected = array_slice($line, 7, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 7, count($deps['topics']));
    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);

    $dos_ids = [];
    $dossiers_use = explode(';', $dossiers);
    foreach ($dossiers_use as $k => $d) {
      $dos_ids[] = $deps['data/dossiers'][$d];
    }

    $rcpti_ids = [];
    $rcpti_use = $rcpt_info ? explode(';', $rcpt_info) : [];
    foreach ($rcpti_use as $k => $r) {
      $rcpti_ids[] = $deps['users'][$r];
    }

    $rcpta_ids = [];
    $rcpta_use = $rcpt_action ? explode(';', $rcpt_action) : [];
    foreach ($rcpta_use as $k => $r) {
      $rcpta_ids[] = $deps['users'][$r];
    }
    $base->notes->note_add($token, $text, $event_date, $object, 
			   $top_ids, $dos_ids,
			   $rcpti_ids, $rcpta_ids);
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
