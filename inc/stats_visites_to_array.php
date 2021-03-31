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

include_spip('inc/statistiques');

/**
 * Retourne les statistiques globales ou d'un objet pour une durée donnée
 *
 * @param string $unite jour | mois | annee
 * @param ?int $duree Combien de jours | mois | annee on prend…
 * @param string $objet
 * @param string $id_objet
 * @return array [date => nb visites]
 */
function inc_stats_visites_to_array_dist($unite, ?int $duree = null, ?string $objet = null, ?int $id_objet = null) {
	$now = time();

	if (!in_array($unite, array('jour', 'mois', 'annee', 'day', 'month', 'year'))) {
		$unite = 'day';
	}
	if (in_array($unite, ['jour', 'day'])) {
		$format_sql = '%Y-%m-%d';
		$format = 'Y-m-d';
		$unite = 'day';
		$duration = 'D';
	} elseif (in_array($unite, ['mois', 'month'])) {
		$format_sql = '%Y-%m';
		$format = 'Y-m';
		$unite = 'month';
		$duration = 'M';
	} else {
		$format_sql = '%Y';
		$format = 'Y';
		$unite = 'year';
		$duration = 'Y';
	}
	if ($duree and $duree < 0) {
		$duree = null;
	}

	$serveur = '';
	$table = "spip_visites";
	$where = [];
	$order = "date";

	$currentDate = (new \DateTime())->format($format);
	$startDate = null;
	$endDate = $currentDate;


	if ($duree) {
		$where[] = sql_date_proche($order, -$duree, $unite, $serveur);
		// sql_date_proche utilise une comparaison stricte. On soustrait 1 jour...
		$startDate = (new \DateTime())->sub(new \DateInterval('P' . ($duree - 1) . $duration))->format($format);
	}

	if ($objet and $id_objet) {
		if ($objet === 'article') {
			$table = "spip_visites_articles";
			$where[] = "id_article=" . intval($id_objet);
		} else {
			// plugin stats objets ?
			$trouver_table = charger_fonction('trouver_table', 'base');
			if ($trouver_table('spip_visites_objets')) {
				$table = "spip_visites_objets";
				$where[] = "objet=" . table_objet($objet);
				$where[] = "id_objet=" . intval($id_objet);
			} else {
				throw new \Exception('Table spip_visisites_objets not found. You need a plugin for stats outside articles.');
			}
		}
	}


	$where = implode(" AND ", $where);

	$firstDateStat = sql_getfetsel("date", $table, $where, "", "date", "0,1");
	if ($firstDateStat) {
		$firstDate = (new \DateTime($firstDateStat))->format($format);
	} else {
		$firstDate = null;
	}

	$data = sql_allfetsel(
		"DATE_FORMAT($order,'$format_sql') AS formatted_date, SUM(visites) AS visites", 
		$table, $where, 
		"formatted_date",
		"formatted_date", 
		"", 
		'',
		$serveur
	);
	$data = array_map(function($d) {
		$d['date'] = $d['formatted_date'];
		unset($d['formatted_date']);
		return $d;
	}, $data);

	return [
		'meta' => [
			'unite' => $unite,
			'duree' => $duree,
			'objet' => $objet,
			'id_objet' => $id_objet,
			'format_date' => $format,
			'start_date' => $startDate ?? $firstDate,
			'end_date' => $endDate,
			'first_date' => $firstDate,
			'columns' => [
				'date',
				'visites',
			],
			'translations' => [
				'date' => _T('public:date'),
				'visites' => _L('Visites'),
				'moyenne' => spip_ucfirst(trim(_T('info_moyenne'), " :\t\n\r\0\x0B\xc2\xa0")), // "moyenne :" => "Moyenne". hum.
			],
		],
		'data' => array_values($data),
	];

	return array_values($data);
}
