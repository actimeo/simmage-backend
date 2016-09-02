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
$tHygiene = $base->organ->topic_add($token, 'Hygiène', 'Hygiène corporelle');
$tBudget = $base->organ->topic_add($token, 'Budget', 'Argent de poche et gestion du budget');
$tPlacement = $base->organ->topic_add($token, 'Placement', 'Mesures placement (ASE, PJJ, TE)');
$tEtatCivil = $base->organ->topic_add($token, 'Droit de séjour', 'État civil et droits de séjour');
$tEducation = $base->organ->topic_add($token, 'Éducation', 'Accompagnement éducatif');
$tStage = $base->organ->topic_add($token, 'Emploi', 'Stages et apprentissage');
$tEntretien = $base->organ->topic_add($token, 'Entretien', 'Entretien et participation aux services');
$tVeture = $base->organ->topic_add($token, 'Vêture', 'Vêture et autres fournitures');
$tFamille = $base->organ->topic_add($token, 'Famille', 'Famille, tuteur et parrainage');
$tLogement = $base->organ->topic_add($token, 'Logement', 'Logement collectif ou individuel');
$tScolarite = $base->organ->topic_add($token, 'Scolarité', 'Scolarité');
$tPriseEnCharge = $base->organ->topic_add($token, 'Prises en charge', 'Prises en charge, admission et autorisations');
$tProjet = $base->organ->topic_add($token, 'Projet individuel', 'Projet individuel');
$tPsy = $base->organ->topic_add($token, 'Psychologie', 'Accompagnement psychologique');
$tRestauration = $base->organ->topic_add($token, 'Restauration', 'Restauration et alimentation');
$tSante = $base->organ->topic_add($token, 'Santé', 'Suivi médical');
$tSocial = $base->organ->topic_add($token, 'Loisirs', 'Vie sociale, loisirs et séjours');
$tTransport = $base->organ->topic_add($token, 'Transport', 'Transport');


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

// Create portals
$pEncadrement = $base->portal->portal_add($token, 'Encadrement');
$pEducateur = $base->portal->portal_add($token, 'Éducateur');

// Create user groups
$ugEncadrement = $base->login->usergroup_add($token, 'Encadrement');
$ugEducateur = $base->login->usergroup_add($token, 'Éducateur');

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

// Create MECS Sauvegarde organization with groups
$oMecs = $base->organ->organization_add($token, "MECS Sauvegarde", 'Description...', true);

// Groups Pavillon 1, 2
$topicsPavillons = array($tLogement, $tRestauration, $tEducation, $tProjet, $tVeture, $tEntretien, $tBudget, $tTransport);
$gPavillon1 = $base->organ->group_add($token, $oMecs, 'Pavillon 1', 'Pavillon Nord');
$gPavillon2 = $base->organ->group_add($token, $oMecs, 'Pavillon 2', 'Pavillon Sud');
$base->organ->group_set_topics($token, $gPavillon1, $topicsPavillons);
$base->organ->group_set_topics($token, $gPavillon2, $topicsPavillons);
$base->organ->group_exclusive_new($token, 'Pavillons', array($gPavillon1, $gPavillon2));

// Group Suivi psychologique
$gPsy = $base->organ->group_add($token, $oMecs, 'Suivi psychologique', 'Suivi par Mme PSY');
$base->organ->group_set_mandatory($token, $gPsy, true);
$base->organ->group_set_topics($token, $gPsy, array($tPsy, $tProjet));
$base->organ->group_set_orientation($token, $gPsy, 'participant');

// Group Suivi administratif
$gAdmin = $base->organ->group_add($token, $oMecs, 'Suivi administratif', 'Suivi par Mlle ADMIN'); 
$base->organ->group_set_mandatory($token, $gAdmin, true);
$base->organ->group_set_topics($token, $gAdmin, array($tPriseEnCharge));

// Create Ecole Georges Brassens with classes
$oEcole = $base->organ->organization_add($token, "École Georges Brassens", 'Gare au Gorille !', false);
$topicsEcole = array($tEducation);

$gCp = $base->organ->group_add($token, $oEcole, 'CP', '1e année primaire');
$gCe1 = $base->organ->group_add($token, $oEcole, 'CE1', '2e année primaire');
$gCe2 = $base->organ->group_add($token, $oEcole, 'CE2', '3e année primaire');
$gCm1 = $base->organ->group_add($token, $oEcole, 'CM1', '4e année primaire');
$gCm2 = $base->organ->group_add($token, $oEcole, 'CM2', '5e année primaire');
$base->organ->group_exclusive_new($token, 'Classes', array($gCp, $gCe1, $gCe2, $gCm1, $gCm2));

foreach (array($gCp, $gCe1, $gCe2, $gCm1, $gCm2) as $group) { 
  $base->organ->group_set_topics($token, $group, $topicsEcole);
}

$oTribunalBordeaux = $base->organ->organization_add($token, "Tribunal pour enfants de Bordeaux", "Tribunal ...", false);
$gTribunalBordeaux = $base->organ->group_add($token, $oTribunalBordeaux, "Assistance éducative", 'desc ...');
$base->organ->group_set_topics($token, $gTribunalBordeaux, array($tPlacement));

$oTribunalLangon = $base->organ->organization_add($token, "Tribunal pour enfants de Langon", "Rue du Tribunal ...", false);
$gTribunalLangon = $base->organ->group_add($token, $oTribunalLangon, "Assistance éducative", "Porte 4");
$base->organ->group_set_topics($token, $gTribunalLangon, array($tPlacement));

$oAse = $base->organ->organization_add($token, "ASE 33", "", false);
$gAse1 = $base->organ->group_add($token, $oAse, "Accueil provisoire", "");
$base->organ->group_set_topics($token, $gAse1, array($tPlacement));
$gAse2 = $base->organ->group_add($token, $oAse, "Accueil d'urgence", "");
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

$base->commit ();

function create_user($base, $token, $login, $pwd, $firstname, $lastname) {
  $base->login->user_add($token, $login, null, null);
  $userInfo = $base->login->user_info($token, $login);
  $parId = $base->organ->participant_add($token, $firstname, $lastname);
  $base->login->user_participant_set($token, $login, $parId);
  $user = $base->login->user_login($login, $userInfo['usr_temp_pwd'], null);
  $base->login->user_change_password($user['usr_token'], $pwd);
  return $parId;
}
