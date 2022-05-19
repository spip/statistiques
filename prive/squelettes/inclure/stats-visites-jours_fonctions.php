<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('prive/squelettes/inclure/stats-visites-data_fonctions');

/**
 * L'url d'accès aux données json ou csv.
 *
 * Nécessite une autorisation avec l'auteur connecté sur l'url cible.
 */
function statistiques_url_data(array $Pile, string $output = 'json', string $export = 'visites', string $unite = 'jour', ?int $duree = null, ?bool $public = null): string {
	include_spip('inc/acces');

	$args = [
		'export' => $export,
		'unite' => $unite,
	];

	if ($duree and $duree > 0) {
		$args['duree'] = $duree;
	}
	$objet = $Pile[0]['objet'] ?? null;
	$id_objet = $Pile[0]['id_objet'] ?? null;
	if ($objet) {
		$args['objet'] = $objet;
	}
	if ($id_objet) {
		$args['id_objet'] = $id_objet;
	}

	$args = http_build_query($args);
	$url = generer_url_api_low_sec('transmettre', $output, 'statistiques.' . $output, '', $args, false, $public);
	// Ne pas nécessiter une redirection d’url
	$url = str_replace(
		'transmettre.api/',
		'?action=api_transmettre&arg=',
		str_replace('?', '&', $url)
	);
	return $url;
}

/**
 * L'url d'accès aux données json ou csv (pour cet auteur, même non connecté).
 *
 * Ajoute un hash pour un auteur donné, de sorte qu'il puisse accéder aux statistiques même non connecté
 * Possiblement utilisé pour télécharger périodiquement ses statistiques depuis un cron
 */
function statistiques_url_data_auteur(array $Pile, string $output = 'json', string $export = 'visites', string $unite = 'jour', ?int $duree = null): string {
	$url = statistiques_url_data($Pile, $output, $export, $unite, $duree, true);
	return $url;
}
