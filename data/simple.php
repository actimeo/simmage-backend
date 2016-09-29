#! /usr/bin/php
<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;

$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database);

$base->startTransaction();

$variationUser = $base->login->user_login('variation', 'variation', '{users,organization,structure}');

$token = $variationUser['usr_token'];

// Create topics
$tSocial = $base->organ->topic_add($token, 'Social', 'Social', 'support', '#F44336');
$tJustice = $base->organ->topic_add($token, 'Justice', 'Justice', 'justice', '#E91E63');
$tSport = $base->organ->topic_add($token, 'Sport', 'Sport', 'sport', '#9C27B0');
$tCulture = $base->organ->topic_add($token, 'Culture', 'Culture', 'culture', '#673AB7');
$tFinancer = $base->organ->topic_add($token, 'Financer', 'Financer', 'financer', '#3F51B5');
$tSupport = $base->organ->topic_add($token, 'Support', 'Support', 'support', '#2196F3');
$tPsycho = $base->organ->topic_add($token, 'Psychologist', 'Psychologist', 'psychology', '#009688');

// Create user foo1
$userLogin1 = 'foo1';
$stfId1 = $base->organ->participant_add($token, 'Pierre', 'MARTIN');
$base->login->user_add($token, $userLogin1, null, $stfId1);
$fooInfo1 = $base->login->user_info($token, $userLogin1);
$foo1 = $base->login->user_login($userLogin1, $fooInfo1['usr_temp_pwd'], null);
$base->login->user_change_password($foo1['usr_token'], 'bar');

// Create user foo2
$userLogin2 = 'foo2';
$stfId2 = $base->organ->participant_add($token, 'Paul', 'DURAND');
$base->login->user_add($token, $userLogin2, null, $stfId2);
$fooInfo2 = $base->login->user_info($token, $userLogin2);
$foo2 = $base->login->user_login($userLogin2, $fooInfo2['usr_temp_pwd'], null);
$base->login->user_change_password($foo2['usr_token'], 'bar');

// Create portals
$porId1 = $base->portal->portal_add($token, 'portal 1', 'desc 1');
$porId2 = $base->portal->portal_add($token, 'portal 2', 'desc 2');
$porId3 = $base->portal->portal_add($token, 'portal 3', 'desc 3');

$ug1 = $base->login->usergroup_add($token, 'Usergroup 1');
$ug2 = $base->login->usergroup_add($token, 'Usergroup 2');

// Give user1 access to 1 portal
$base->login->usergroup_set_portals($token, $ug1, array($porId3));
$base->login->user_usergroup_set($token, $userLogin1, $ug1);

// Give user2 access to 2 portals
$base->login->usergroup_set_portals($token, $ug2, array($porId3, $porId1));
$base->login->user_usergroup_set($token, $userLogin2, $ug2);

// Create organizations with groups
$orgI = $base->organ->organization_add($token, "Organization I", "First organization", true);
$orgII = $base->organ->organization_add($token, "Organization II", "Second organization", true);

$grpI1 = $base->organ->group_add($token, $orgI, 'Group 1 institution I', 'Group I 1');
$base->organ->group_set_topics($token, $grpI1, array($tSocial, $tJustice));

$grpI2 = $base->organ->group_add($token, $orgI, 'Group 2 institution I', 'Group I 2');
$base->organ->group_set_topics($token, $grpI2, array($tSocial, $tSport, $tCulture));

$grpII1 = $base->organ->group_add($token, $orgII, 'Group 1 institution II', 'Group II 1');
$base->organ->group_set_topics($token, $grpII1, array($tSport));

$grpII2 = $base->organ->group_add($token, $orgII, 'Group 2 institution II', 'Group II 2');
$base->organ->group_set_topics($token, $grpII2, array($tFinancer, $tSupport));

// set exclusive Institution I groups 1 and 2
$base->organ->group_exclusive_new($token, 'exclusive group', array($grpI1, $grpI2));

// add a mandatory group
$grpIMand = $base->organ->group_add($token, $orgI, 'Mandatory Group', 'Group mandarory on Org I');
$base->organ->group_set_topics($token, $grpIMand, array($tPsycho));

// update group orientation
$base->organ->group_set_orientation($token, $grpIMand, 'participant');

// Assign user1 to 1 group
$base->organ->participant_assignment_add($token, $grpI1, $stfId1);

// Assign user2 to 3 groups
$base->organ->participant_assignment_add($token, $grpI1, $stfId2);
$base->organ->participant_assignment_add($token, $grpI2, $stfId2);
$base->organ->participant_assignment_add($token, $grpII2, $stfId2);

// Give user1 access to 1 group
$base->login->usergroup_set_groups($token, $ug1, array($grpI1));

// Give user2 access to 2 groups
$base->login->usergroup_set_groups($token, $ug2, array($grpII1, $grpII2));

// BUILD PORTALS
$mseAI = $base->portal->mainsection_add($token, $porId1, 'Section AI');
$mseAII = $base->portal->mainsection_add($token, $porId1, 'Section AII');
$mseAIII = $base->portal->mainsection_add($token, $porId1, 'Section AIII');

$mseBI = $base->portal->mainsection_add($token, $porId2, 'Section BI');
$mseBII = $base->portal->mainsection_add($token, $porId2, 'Section BII');
$mseBIII = $base->portal->mainsection_add($token, $porId2, 'Section BIII');

$mseCI = $base->portal->mainsection_add($token, $porId3, 'Section CI');
$mseCII = $base->portal->mainsection_add($token, $porId3, 'Section CII');
$mseCIII = $base->portal->mainsection_add($token, $porId3, 'Section CIII');

$base->portal->mainmenu_add($token, $mseCI, "Menu CI1");

$base->portal->mainmenu_add($token, $mseCII, "Menu CII1");
$base->portal->mainmenu_add($token, $mseCII, "Menu CII2");

$base->portal->mainmenu_add($token, $mseCIII, "Menu CIII1");
$base->portal->mainmenu_add($token, $mseCIII, "Menu CIII2");
$base->portal->mainmenu_add($token, $mseCIII, "Menu CIII3");

$base->organ->dossier_add_individual($token, 'firstname', 'lastname', '01/09/2016', 'male', false);
$base->organ->dossier_add_individual($token, 'firstname2', 'lastname2', '01/09/2016', 'male', false);
$base->organ->dossier_add_individual($token, 'firstname3', 'lastname3', '01/09/2016', 'female', false);
$base->organ->dossier_add_individual($token, 'firstname4', 'lastname4', '01/09/2016', 'female', false);
$base->organ->dossier_add_individual($token, 'firstname5', 'lastname5', '01/09/2016', 'male', true);
$base->organ->dossier_add_individual($token, 'firstname6', 'lastname6', '01/09/2016', 'female', true);
$base->organ->dossier_add_individual($token, 'firstname7', 'lastname7', '01/09/2016', 'male', true);

$base->organ->dossier_add_grouped($token, 'groupname', false);
$base->organ->dossier_add_grouped($token, 'groupname2', false);
$base->organ->dossier_add_grouped($token, 'groupname3', false);
$base->organ->dossier_add_grouped($token, 'groupname4', true);
$base->organ->dossier_add_grouped($token, 'groupname5', true);

$base->commit ();
