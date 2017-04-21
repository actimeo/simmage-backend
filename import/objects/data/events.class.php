<?php
class VariationEvents extends iVariation {

  private $headers;
  
  public function getDependencies() { return [ 'data/dossiers', 'topics', 'eventstypes' ]; }

  public function headers($line) {
    $this->headers = $line;
  }
  
  public function import($base, $token, $line, $deps, $dir) {

    $title = $line[0];
    $type = $line[1];
    $ety_id = $deps['eventstypes'][$type];
    $duration_type = $line[2];
    $start_time = $line[3];
    $end_time = $line[4];
    $place = $line[5];
    $cost = $line[6];
    $description = $line[7];
    $sumup = $line[8];
    $dossiers = $line[9];

    $topics_selected = array_slice($line, 10, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 10, count($deps['topics']));
    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);
    
    $dos_ids = [];
    $dossiers_use = explode(';', $dossiers);
    foreach ($dossiers_use as $k => $d) {
      $dos_ids[] = $deps['data/dossiers'][$d];
    }

    $base->events->event_add($token, $title, $ety_id, $duration_type, 
			     $start_time, $end_time, $place, $cost, 
			     $description, $sumup,
			     false, null, null, null, 0,
			     $top_ids, $dos_ids, [], []);
    return null;
  }

  public function beforeImport($base, $token) {}
  public function afterImport($base, $token) {}
}
