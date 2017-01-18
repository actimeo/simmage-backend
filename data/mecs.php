#! /usr/bin/php
<?php
require 'vendor/autoload.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;

$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database, $pg_port);

$base->startTransaction();

$variationUser = $base->login->user_login('variation', 'variation', '{users,organization,structure}', null);

$token = $variationUser['usr_token'];

// Create topics
$tHygiene = $base->organ->topic_add($token, 'Hygiène', 'Hygiène corporelle', 'medical_care', '#F44336');
$tBudget = $base->organ->topic_add($token, 'Budget', 'Argent de poche et gestion du budget', 'financial_assistance', '#F44336');
$tPlacement = $base->organ->topic_add($token, 'Placement', 'Mesures placement (ASE, PJJ, TE)', 'decisionmaker', '#E91E63');
$tEtatCivil = $base->organ->topic_add($token, 'Droit de séjour', 'État civil et droits de séjour', 'residence_right', '#9C27B0');
$tEducation = $base->organ->topic_add($token, 'Éducation', 'Accompagnement éducatif', 'pedagogy', '#673AB7');
$tStage = $base->organ->topic_add($token, 'Emploi', 'Stages et apprentissage', 'employment', '#3F51B5');
$tEntretien = $base->organ->topic_add($token, 'Entretien', 'Entretien et participation aux services', 'housekeeping', '#2196F3');
$tVeture = $base->organ->topic_add($token, 'Vêture', 'Vêture et autres fournitures', 'equipment', '#03A9F4');
$tFamille = $base->organ->topic_add($token, 'Famille', 'Famille, tuteur et parrainage', 'family', '#00BCD4');
$tLogement = $base->organ->topic_add($token, 'Logement', 'Logement collectif ou individuel', 'lodging', '#009688');
$tScolarite = $base->organ->topic_add($token, 'Scolarité', 'Scolarité', 'education', '#4CAF50');
$tPriseEnCharge = $base->organ->topic_add($token, 
					  'Prises en charge', 'Prises en charge, admission et autorisations', 'support', '#8BC34A');
$tProjet = $base->organ->topic_add($token, 'Projet individuel', 'Projet individuel', 'project', '#CDDC39');
$tPsy = $base->organ->topic_add($token, 'Psychologie', 'Accompagnement psychologique', 'psychology', '#FFEB3B');
$tRestauration = $base->organ->topic_add($token, 'Restauration', 'Restauration et alimentation', 'catering', '#FFC107');
$tSante = $base->organ->topic_add($token, 'Santé', 'Suivi médical', 'health', '#FF9800');
$tSocial = $base->organ->topic_add($token, 'Loisirs', 'Vie sociale, loisirs et séjours', 'entertainment', '#FF5722');
$tTransport = $base->organ->topic_add($token, 'Transport', 'Transport', 'transport', '#795548');


// Create user marie, Secrétaire
$loginMarie = 'marie';
$uMarie = create_user($base, $token, $loginMarie, 'marie', 'Marie', 'SECRET');

// Create user jeanne, Psychologue
$loginJeanne = 'jeanne';
$uJeanne = create_user($base, $token, $loginJeanne, 'jeanne', 'Jeanne', 'PSYCHO');

// Create paul, jean, pierre, sophie, Éducateurs
$loginPaul = 'paul';
$loginJean = 'jean';
$loginPierre = 'pierre';
$loginSophie = 'sophie';
$uPaul = create_user($base, $token, $loginPaul, 'paul', 'Paul', 'ÉDUC');
$uJean = create_user($base, $token, $loginJean, 'jean', 'Jean', 'ÉDUC');
$uPierre = create_user($base, $token, $loginPierre, 'pierre', 'Pierre', 'ÉDUC');
$uSophie = create_user($base, $token, $loginSophie, 'sophie', 'Sophie', 'ÉDUC');

// Create MECS Sauvegarde organization with groups
$oMecs = $base->organ->organization_add($token, "MECS Sauvegarde", 'Description...', true);

// 
// Events Types
//
$etyAbs1 = $base->events->event_type_add($token, 'absence', 'Absentéisme', false);
$base->events->event_type_set_organizations($token, $etyAbs1, array($oMecs));
$base->events->event_type_set_topics($token, $etyAbs1, array($tStage));

$etyAbs2 = $base->events->event_type_add($token, 'absence', 'Accident du travail', false);
$base->events->event_type_set_organizations($token, $etyAbs2, array($oMecs));
$base->events->event_type_set_topics($token, $etyAbs2, array($tStage));

$etyAbs3 = $base->events->event_type_add($token, 'absence', 'Arrêt maladie', false);
$base->events->event_type_set_organizations($token, $etyAbs3, array($oMecs));
$base->events->event_type_set_topics($token, $etyAbs3, array($tStage));

$etyAbs4 = $base->events->event_type_add($token, 'absence', 'Autre', true);
$base->events->event_type_set_organizations($token, $etyAbs4, array($oMecs));
$base->events->event_type_set_topics($token, $etyAbs4, array($tStage));

$etyDep1 = $base->events->event_type_add($token, 'expense', 'Ameublement et décoration', false);
$base->events->event_type_set_organizations($token, $etyDep1, array($oMecs));
$base->events->event_type_set_topics($token, $etyDep1, array($tVeture, $tLogement));

$etyDep2 = $base->events->event_type_add($token, 'expense', 'Coiffeur', false);
$base->events->event_type_set_organizations($token, $etyDep2, array($oMecs));
$base->events->event_type_set_topics($token, $etyDep2, array($tHygiene));

$etyDep3 = $base->events->event_type_add($token, 'expense', 'Autre', true);
$base->events->event_type_set_organizations($token, $etyDep3, array($oMecs));
$base->events->event_type_set_topics($token, $etyDep3, array($tBudget));

// Documents types
$dty1 = $base->documents->document_type_add($token, 'Assurance responsabilité civile', false);
$base->documents->document_type_set_organizations($token, $dty1, array($oMecs));
$base->documents->document_type_set_topics($token, $dty1, array($tLogement, $tPriseEnCharge, $tVeture));

$dty2 = $base->documents->document_type_add($token, 'Attestation de sécurité sociale', false);
$base->documents->document_type_set_organizations($token, $dty2, array($oMecs));
$base->documents->document_type_set_topics($token, $dty2, array($tPriseEnCharge, $tSante));

// Events views
$evv1 = $base->events->eventsview_add($token, 'Absences, Emploi', array('absence'), $etyAbs1, array($tStage));

// Documents views
$dov1 = $base->documents->documentsview_add($token, 'Docs Attestation', $dty2, array($tPriseEnCharge));

// Lists views
$liv1 = $base->lists->listsview_add($token, 'A list of dossiers');
$liv2 = $base->lists->listsview_add($token, 'Another list of dossiers');

// Create portals
$pEncadrement = $base->portal->portal_add($token, 'Portail Encadrement', 'Portail pour l\'encadrement');
$pEducateur = $base->portal->portal_add($token, 'Portail Éducateur', 'Portail pour les éducateurs');

$mseEncadrement = $base->portal->mainsection_add($token, $pEncadrement, 'Section 1');
$base->portal->mainmenu_add($token, $mseEncadrement, "Events", "Événements...", 'event', 'events', $evv1);
$base->portal->mainmenu_add($token, $mseEncadrement, "Docs", "Documents ...", 'attach_file', 'documents', $dov1);
$base->portal->mainmenu_add($token, $mseEncadrement, "Dossiers", "List ...", 'group', 'lists', $liv1);

$mseEducateur = $base->portal->mainsection_add($token, $pEducateur, 'Section 1');
$base->portal->mainmenu_add($token, $mseEducateur, "Events", "Événements", 'event', 'events', $evv1);
$base->portal->mainmenu_add($token, $mseEducateur, "Docs", "Documents", 'attach_file', 'documents', $dov1);
$base->portal->mainmenu_add($token, $mseEducateur, "Dossiers", "List ...", 'group', 'lists', $liv1);

// Create user groups
$ugEncadrement = $base->login->usergroup_add($token, 'Groupe d\'utilisateurs Encadrement', '{internal_dossier_add, external_dossier_add}', '{preadmission, admission, present, left}');
$ugEducateur = $base->login->usergroup_add($token, 'Groupe d\'utilisateurs Éducateur', '{external_dossier_add}', '{present}');

$base->login->usergroup_set_topics($token, $ugEncadrement, array($tBudget, $tPlacement, $tEtatCivil, $tVeture, $tPriseEnCharge, $tProjet));
$base->login->usergroup_set_topics($token, $ugEducateur, array($tHygiene, $tEducation, $tStage, $tLogement, $tScolarite, $tRestauration));

$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tBudget, [ 'event_add', 'event_read' ]);
$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tPlacement, [ 'event_add' ]);
$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tEtatCivil, [ 'event_read' ]);
$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tVeture, [ 'event_update' ]);
$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tPriseEnCharge, [ 'event_add', 'event_read' ]);
$base->login->usergroup_topic_set_rights($token, $ugEncadrement, $tProjet, [ 'event_read' ]);

$base->login->usergroup_set_portals($token, $ugEncadrement, array($pEncadrement));
$base->login->usergroup_set_portals($token, $ugEducateur, array($pEducateur));

// Place marie and jeanne in the Encadrement user group
foreach (array($loginMarie, $loginJeanne) as $login) {
  $base->login->user_usergroup_set($token, $login, $ugEncadrement);
}

// Place paul, jean, pierre and sophie in the Éducateur user group
foreach (array($loginPaul, $loginJean, $loginPierre, $loginSophie) as $login) {
  $base->login->user_usergroup_set($token, $login, $ugEducateur);
}

// Groups Pavillon 1, 2
$topicsPavillons = array($tLogement, $tRestauration, $tEducation, 
			 $tProjet, $tVeture, $tEntretien, 
			 $tBudget, $tTransport);
$gPavillon1 = $base->organ->group_add($token, $oMecs, 'Pavillon 1', 'Pavillon Nord', false, 'organization');
$gPavillon2 = $base->organ->group_add($token, $oMecs, 'Pavillon 2', 'Pavillon Sud', false, 'organization');
$gAppart1 = $base->organ->group_add($token, $oMecs, 'Appartement 1', 'Appartement 1 pour famille', false, 'organization');
$gAppart2 = $base->organ->group_add($token, $oMecs, 'Appartement 2', 'Appartement 2 pour famille', false, 'organization');

$base->organ->group_set_topics($token, $gPavillon1, $topicsPavillons);
$base->organ->group_set_topics($token, $gPavillon2, $topicsPavillons);
$base->organ->group_exclusive_new($token, 'Pavillons', array($gPavillon1, $gPavillon2));

// TODO : add some topics to usergroups

$base->login->usergroup_set_groups($token, $ugEncadrement, array($gPavillon1, $gPavillon2));
$base->login->usergroup_set_groups($token, $ugEducateur, array($gPavillon1, $gPavillon2));

// Group Suivi psychologique
$gPsy = $base->organ->group_add($token, $oMecs, 'Suivi psychologique', 'Suivi par Mme PSY', false, 'organization');
$base->organ->group_set_mandatory($token, $gPsy, true);
$base->organ->group_set_topics($token, $gPsy, array($tPsy, $tProjet));
$base->organ->group_set_orientation($token, $gPsy, 'participant');

// Group Suivi administratif
$gAdmin = $base->organ->group_add($token, $oMecs, 'Suivi administratif', 'Suivi par Mlle ADMIN', false, 'organization'); 
$base->organ->group_set_mandatory($token, $gAdmin, true);
$base->organ->group_set_topics($token, $gAdmin, array($tPriseEnCharge));

$base->login->usergroup_set_groups($token, $ugEncadrement, array($gAdmin));

// Create Ecole Georges Brassens with classes
$oEcole = $base->organ->organization_add($token, "École Georges Brassens", 'Gare au Gorille !', false);
$topicsEcole = array($tEducation);

$gCp = $base->organ->group_add($token, $oEcole, 'CP', '1e année primaire', false, 'organization');
$gCe1 = $base->organ->group_add($token, $oEcole, 'CE1', '2e année primaire', false, 'organization');
$gCe2 = $base->organ->group_add($token, $oEcole, 'CE2', '3e année primaire', false, 'organization');
$gCm1 = $base->organ->group_add($token, $oEcole, 'CM1', '4e année primaire', false, 'organization');
$gCm2 = $base->organ->group_add($token, $oEcole, 'CM2', '5e année primaire', false, 'organization');
$base->organ->group_exclusive_new($token, 'Classes', array($gCp, $gCe1, $gCe2, $gCm1, $gCm2));

foreach (array($gCp, $gCe1, $gCe2, $gCm1, $gCm2) as $group) { 
  $base->organ->group_set_topics($token, $group, $topicsEcole);
}

$base->login->usergroup_set_groups($token, $ugEducateur, array($gPavillon1, $gPavillon2));

$oTribunalBordeaux = $base->organ->organization_add($token, 
						    "Tribunal pour enfants de Bordeaux", "Tribunal ...", 
						    false);
$gTribunalBordeaux = $base->organ->group_add($token, $oTribunalBordeaux, "Assistance éducative", 'desc ...', false, 'organization');
$base->organ->group_set_topics($token, $gTribunalBordeaux, array($tPlacement));

$oTribunalLangon = $base->organ->organization_add($token, 
						  "Tribunal pour enfants de Langon", "Rue du Tribunal ...", 
						  false);
$gTribunalLangon = $base->organ->group_add($token, $oTribunalLangon, "Assistance éducative", "Porte 4", false, 'organization');
$base->organ->group_set_topics($token, $gTribunalLangon, array($tPlacement));

$oAse = $base->organ->organization_add($token, "ASE 33", "", false);
$gAse1 = $base->organ->group_add($token, $oAse, "Accueil provisoire", "", false, 'organization');
$base->organ->group_set_topics($token, $gAse1, array($tPlacement));
$gAse2 = $base->organ->group_add($token, $oAse, "Accueil d'urgence", "", false, 'organization');
$base->organ->group_set_topics($token, $gAse2, array($tPlacement));


// Assign users to groups
$assigns = array ($uMarie => array($gAdmin),
		  $uJeanne => array($gPsy),
		  $uPaul => array($gPavillon1),
		  $uPierre => array($gPavillon1),
		  $uJean => array($gPavillon2),
		  $uSophie => array($gPavillon2));
foreach ($assigns as $user => $groups) {
  foreach ($groups as $group) {
    $base->organ->participant_assignment_add($token, $group, $user);
  }
}

// Create dossiers and affect to groups
$dos1 = $base->organ->dossier_add_individual($token, 'Nom 1', 'prénom 1', '01/09/1998', 'male', false);
$base->organ->dossier_assignment_add($token, $dos1, array ($gPavillon1, $gPsy, $gAdmin, $gCe2));
$base->organ->dossier_status_change($token, $dos1, $oMecs, 'present', '01/01/2016');

$dos2 = $base->organ->dossier_add_individual($token, 'Nom 2', 'prénom 2', '02/09/1998', 'male', false);
$base->organ->dossier_assignment_add($token, $dos2, array ($gPavillon2, $gPsy, $gAdmin, $gCe1));
$base->organ->dossier_status_change($token, $dos2, $oMecs, 'present', '01/02/2016');

$dos3 = $base->organ->dossier_add_individual($token, 'Nom 3', 'prénom 3', '03/09/1998', 'female', false);
$base->organ->dossier_assignment_add($token, $dos3, array ($gPavillon1, $gPsy, $gAdmin, $gCp));
$base->organ->dossier_status_change($token, $dos3, $oMecs, 'preadmission', '01/03/2016');

$dosExt1 = $base->organ->dossier_add_individual($token, 'NomExt 1', 'prénom 1', '01/09/1998', 'male', true);

$dosExt2 = $base->organ->dossier_add_individual($token, 'NomExt 2', 'prénom 2', '02/09/1998', 'male', true);

$dosFam1 = $base->organ->dossier_add_grouped($token, 'Famille 1', false);
$base->organ->dossier_assignment_add($token, $dosFam1, array ($gAppart1, $gAdmin));
$base->organ->dossier_status_change($token, $dosFam1, $oMecs, 'admission', '01/03/2016');

$dosFam2 = $base->organ->dossier_add_grouped($token, 'Famille 2', false);
$base->organ->dossier_assignment_add($token, $dosFam2, array ($gAppart2, $gAdmin));
$base->organ->dossier_status_change($token, $dosFam2, $oMecs, 'preadmission', '01/03/2016');

$dosFamExt1 = $base->organ->dossier_add_grouped($token, 'Famille de Pierre', true);

$dosFamExt2 = $base->organ->dossier_add_grouped($token, 'Famille de Paul', true);

$base->commit ();

function create_user($base, $token, $login, $pwd, $firstname, $lastname) {
  $parId = $base->organ->participant_add($token, $firstname, $lastname);
  $base->login->user_add($token, $login, null, $parId, null);
  $userInfo = $base->login->user_info($token, $login);
  $userTempPwd = $base->login->user_get_temporary_pwd($token, $login);
  $user = $base->login->user_login($login, $userTempPwd, null, null);
  $base->login->user_change_password($user['usr_token'], $pwd);
  return $parId;
}
