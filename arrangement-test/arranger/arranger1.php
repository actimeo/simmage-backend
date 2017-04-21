<?php
function arranger($base, $token, $por_id, $por_name, $topics_map, $panes) {
  $panes = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $panes.'.json'));
  if ($panes === null) {
    exit;
  }

  $mse_id = $base->portal->mainsection_add($token, $por_id, "Vue d'ensemble");

  $pse_id = $base->portal->personsection_add($token, $por_id, "Vue d'ensemble");

  // Events
  $menus = [
	    'incident' => 'Incidents',
	    'expense' => 'Dépenses',
	    'meeting' => 'Rendez-vous',
	    'absence' => 'Absences'
	    ];

  $all_topics = array_values($topics_map);
  foreach ($menus as $code => $name) {
    $evv_id = $base->events->eventsview_add($token, $por_name . ' > '.$name, 
					  [ $code ], null, $all_topics);
    $base->portal->mainmenu_add($token, $mse_id, $name, 
				$name, 'event', 
				'events', $evv_id);

    $base->portal->personmenu_add($token, $pse_id, $name, 
				  $name, 'event', 
				  'events', $evv_id);
  }
  
  foreach ($panes as $pane) {
    $name = $pane->pane;
    $topics = array_map(function($t) use ($topics_map) {
	return $topics_map[$t];
      }, $pane->topics);
    $mse_id = $base->portal->mainsection_add($token, $por_id, $name);

    $pse_id = $base->portal->personsection_add($token, $por_id, $name);

    // Dossiers
    $liv_id = $base->lists->listsview_add($token, $por_name . ' > Dossiers : ' . $name);
    $base->portal->mainmenu_add($token, $mse_id, 'Aperçu', 'Aperçu '.$name, 'group', 'lists', $liv_id);

    // Events
    $evv_id = $base->events->eventsview_add($token, $por_name . ' > Événements : ' . $name, 
					    [ 'incident', 'expense', 'meeting', 'absence' ], null, $topics);
    $base->portal->mainmenu_add($token, $mse_id, 'Agenda', 
				'Agenda '.$name, 'event', 
				'events', $evv_id);
    $base->portal->personmenu_add($token, $pse_id, 'Agenda', 
				  'Agenda '.$name, 'event', 
				  'events', $evv_id);
    
    // Documents
    $dov_id = $base->documents->documentsview_add($token, $por_name . ' > Documents : ' . $name, null, $topics);
    $base->portal->mainmenu_add($token, $mse_id, 'Comptes-rendus et pièces jointes', 
				'Documents '.$name, 'attach_file', 
				'documents', $dov_id);

    $base->portal->personmenu_add($token, $pse_id, 'Comptes-rendus et pièces jointes', 
				  'Documents '.$name, 'attach_file', 
				  'documents', $dov_id);
    
    // Notes
    $nov_id = $base->notes->notesview_add($token, $por_name . ' > Notes : ' . $name, $topics);
    $base->portal->mainmenu_add($token, $mse_id, 'Notes', 'Notes '.$name, 'mail_outline', 'notes', $nov_id);

    $base->portal->personmenu_add($token, $pse_id, 'Notes', 'Notes '.$name, 'mail_outline', 'notes', $nov_id);

    // Objectives
    $obv_id = $base->objectives->objectivesview_add($token, $por_name . ' > Objectives : ' . $name, $topics);
    $base->portal->mainmenu_add($token, $mse_id, 'Objectives', 'Objectives '.$name, 'my_location', 'objectives', $obv_id);
  }
}
