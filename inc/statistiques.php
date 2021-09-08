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

/**
 * Construire un tableau par popularite
 *   classemnt => id_truc
 *
 * @param string $type
 * @param string $serveur
 * @return array
 */
function classement_populaires($type, $serveur = '') {
	static $classement = [];
	if (isset($classement[$type])) {
		return $classement[$type];
	}
	$_id = id_table_objet($type, $serveur);
	$classement[$type] = sql_allfetsel(
		$_id,
		table_objet_sql($type, $serveur),
		"statut='publie' AND popularite > 0",
		'',
		'popularite DESC',
		'',
		'',
		$serveur
	);
	$classement[$type] = array_column($classement[$type], $_id);

	return $classement[$type];
}
