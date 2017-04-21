<?php
class VariationUsergroups extends iVariation {

  private $statuses_count;
  private $portals_count;
  private $topics_count;
  private $headers;

  public function getDependencies() { return [ 'portals', 'groups', 'topics' ]; }

  public function headers($line) {
    $this->headers = $line;
    $this->statuses_headers = array_slice($this->headers, 2, 
					  $this->statuses_count);

    $this->portals_headers = array_slice($this->headers, 2 + $this->statuses_count, 
					 $this->portals_count);

    $this->topics_headers = array_slice($this->headers, 2 + $this->statuses_count + $this->portals_count, 
					$this->topics_count);

    $this->groups_headers = array_slice($this->headers, 2 + $this->statuses_count + $this->portals_count + $this->topics_count);
  }

  public function beforeImport($base, $token) {
    $db_statuses = $base->organ->dossier_status_value_list();
    $this->statuses_count = count($db_statuses);
    $db_portals = $base->portal->portal_list($token);
    $this->portals_count = count($db_portals);
    $db_topics = $base->organ->topics_list($token);
    $this->topics_count = count($db_topics);

  }

  private function getTopicsIdsFromHeaders($headers, $selected) {
    return array_map(function($id) use ($headers) { return $headers[$id]; }, 
		     array_filter(array_keys($headers), 
				  function ($x) use ($selected) { 
				    return $selected[$x] != null; 
				  }));    
  }

  private function getTopicsRightsFromHeaders($headers, $selected) {
    return array_map(function($id) use ($selected) { return $selected[$id]; }, 
		     array_filter(array_keys($headers), 
				  function ($x) use ($selected) { 
				    return $selected[$x] != null; 
				  }));    
  }

  public function import($base, $token, $line, $deps, $dir) {
    $code = $line[0];
    $name = $line[1];
    $statuses_selected = array_slice($line, 2, 
				     $this->statuses_count);
    $portals_selected = array_slice($line, 2 + $this->statuses_count, 
				    $this->portals_count);
    $topics_selected = array_slice($line, 2 + $this->statuses_count + $this->portals_count, 
				   $this->topics_count);
    $groups_selected = array_slice($line, 2 + $this->statuses_count + $this->portals_count + $this->topics_count);

    $statuses = $this->getCodesFromHeaders($this->statuses_headers, $statuses_selected);
    $id = $base->login->usergroup_add($token, $name, null, $statuses);

    $portal_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($this->portals_headers, $portals_selected), 
					$deps['portals']);
    if (count($portal_ids)) {
      $base->login->usergroup_set_portals($token, $id, $portal_ids);
    }

    $topicsIds = $this->getTopicsIdsFromHeaders($this->topics_headers, $topics_selected);
    $topicsRights = $this->getTopicsRightsFromHeaders($this->topics_headers, $topics_selected);
    $topic_ids = $this->getIdsFromDeps($topicsIds, $deps['topics']);
    if (count($topic_ids)) {
      $base->login->usergroup_set_topics($token, $id, $topic_ids);
      foreach ($topicsIds as $k => $top) {
	$top_id = $deps['topics'][$top];
	if (strtolower($topicsRights[$k]) != 'x') {      
	  $base->login->usergroup_topic_set_rights($token, $id, $top_id, explode(';', $topicsRights[$k]));
	}
      }
    }

    $group_ids = $this->getIdsFromDeps($this->getCodesFromHeaders($this->groups_headers, $groups_selected), 
				       $deps['groups']);
    if (count($group_ids)) {
      $base->login->usergroup_set_group_dossiers($token, $id, $group_ids);
    }
    return [ $code, $id ];
  }

  public function afterImport($base, $token) {}
}
