<?php
class VariationGroups extends iVariation {
  
  private $xsets;
  private $headers;

  public function getDependencies() { return [ 'topics', 'organs' ]; }

  public function headers($line) {
    $this->headers = $line;
  }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $organ_code = $line[1];
    $org_id = $deps['organs'][$organ_code];
    $name = $line[2];
    $description = $line[3];
    $mandatory = strtolower($line[4]) === 'x' ? true : false;
    $orientation = $line[5];
    $exclusive_set = $line[6];
    $topics_selected = array_slice($line, 7, count($deps['topics']));
    $topics_headers = array_slice($this->headers, 7, count($deps['topics']));

    $id = $base->organ->group_add($token, $org_id, $name, $description, $mandatory, $orientation);

    if ($exclusive_set) {
      if (!isset($this->xsets[$exclusive_set])) {
	$this->xsets[$exclusive_set] = [];
      }
      array_push($this->xsets[$exclusive_set], $id);
    }
    
    $top_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($topics_headers, $topics_selected), 
				     $deps['topics']);

    if (count($top_ids)) {
      $base->organ->group_set_topics($token, $id, $top_ids);
    }
    return [ $code, $id ];
  }

  public function beforeImport($base, $token) {
    $this->xsets = [];
  }
  public function afterImport($base, $token) {
    foreach ($this->xsets as $name => $grp_ids) {
      $set_id = $base->organ->group_exclusive_new($token, $name, $grp_ids);
    }
  }
}
