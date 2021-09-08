<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_configurer_compteur_charger_dist() {

	$valeurs = [];

	$valeurs['activer_statistiques'] = $GLOBALS['meta']['activer_statistiques'];
	$valeurs['activer_referers'] = $GLOBALS['meta']['activer_referers'];
	$valeurs['activer_captures_referers'] = $GLOBALS['meta']['activer_captures_referers'];

	return $valeurs;
}

function formulaires_configurer_compteur_verifier_dist() {
	$erreurs = [];

	// les checkbox
	foreach (['activer_statistiques', 'activer_referers', 'activer_captures_referers'] as $champ) {
		if (_request($champ) != 'oui') {
			set_request($champ, 'non');
		}
	}

	return $erreurs;
}

function formulaires_configurer_compteur_traiter_dist() {
	include_spip('inc/config');
	appliquer_modifs_config();

	return ['message_ok' => _T('config_info_enregistree')];
}
