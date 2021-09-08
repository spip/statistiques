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
function statistiques_url_data(array $Pile, string $output = 'json', string $export = 'visites', string $unite = 'jour', ?int $duree = null): string {
	$url = generer_url_public("statistiques.$output");
	$url = parametre_url($url, 'export', $export);
	$url = parametre_url($url, 'unite', $unite);
	if ($duree and $duree > 0) {
		$url = parametre_url($url, 'duree', $duree);
	}
	$objet = $Pile[0]['objet'] ?? null;
	$id_objet = $Pile[0]['id_objet'] ?? null;
	if ($objet) {
		$url = parametre_url($url, 'objet', $objet);
	}
	if ($id_objet) {
		$url = parametre_url($url, 'id_objet', $id_objet);
	}
	return $url;
}

/**
 * L'url d'accès aux données json ou csv (pour cet auteur, même non connecté).
 *
 * Ajoute un hash pour un auteur donné, de sorte qu'il puisse accéder aux statistiques même non connecté
 * Possiblement utilisé pour télécharger périodiquement ses statistiques depuis un cron
 */
function statistiques_url_data_auteur(array $Pile, string $output = 'json', string $export = 'visites', string $unite = 'jour', ?int $duree = null): string {
	$url = statistiques_url_data($Pile, $output, $export, $unite, $duree);
	$params = param_low_sec('statistiques', ['hash' => md5($url)], '', 'voirstats');
	// pas besoin de l'arg hash. Il sert juste à calculer une clé unique pour l'auteur.
	return parametre_url($url . '&' . $params, 'hash', '');
}
