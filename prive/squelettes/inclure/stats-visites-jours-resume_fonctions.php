<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Calcule visites totales, aujourd'hui, hier pour le site ou objet/id_objet
 */
function statistiques_stats_generales(array $Pile): array {

	$objet = ($Pile[0]['objet'] ?? null) ?: null;
	$id_objet = ($Pile[0]['id_objet'] ?? null) ?: null;
	$table = 'spip_visites';
	$where = [];
	if ($objet === 'article') {
		$table = 'spip_visites_articles';
		$where[] = 'id_article = ' . sql_quote($id_objet);
	} elseif ($objet) {
		$table = 'spip_visites_objets';
		$where[] = 'objet = ' . sql_quote($objet);
		$where[] = 'id_objet = ' . sql_quote($id_objet);
	}
	$res = [];
	$res['max'] = sql_getfetsel('MAX(visites) as max', $table, $where);
	$stats_visites_to_array = charger_fonction('stats_visites_to_array', 'inc');
	// on demande 2 jours de stats, à partir d'aujourd'hui
	$stats = $stats_visites_to_array('day', 2, $objet, $id_objet);
	$data = array_column($stats['data'], 'visites', 'date');
	// les lignes ne sortent que s'il y a des entrées...
	$res['today'] = $data[$stats['meta']['end_date']] ?? 0;
	$res['yesterday'] = $data[$stats['meta']['start_date']] ?? 0;
	return $res;
}
