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
 * Recuperer la liste des moteurs de recherche depuis un fichier txt
 * Adaptees du code des "Visiteurs",
 * par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)
 *
 * https://code.spip.net/@stats_load_engines
 *
 * @return array
 */
function stats_load_engines() {
	$moteurs = null;
	$arr_engines = [];
	lire_fichier(find_in_path('engines-list.txt'), $moteurs);
	foreach (array_filter(preg_split("/([\r\n]|#.*)+/", $moteurs)) as $ligne) {
		$ligne = trim($ligne);
		if (preg_match(',^\[([^][]*)\]$,S', $ligne, $regs)) {
			$moteur = $regs[1];
			$query = '';
		} else {
			if (preg_match(',=$,', $ligne, $regs)) {
				$query = $ligne;
			} else {
				$arr_engines[] = [$moteur, $query, $ligne];
			}
		}
	}

	return $arr_engines;
}

/**
 * Retrouver les mots cles de recherche dans une url de referer
 *
 * Adaptees du code des "Visiteurs",
 * par Jean-Paul Dezelus (http://www.phpinfo.net/applis/visiteurs/)
 *
 * https://code.spip.net/@stats_show_keywords
 *
 * @param string $kw_referer
 * @return array
 */
function stats_show_keywords($kw_referer) {
	$buffer = [];
	static $arr_engines = '';
	static $url_site;

	if (!is_array($arr_engines)) {
		// Charger les moteurs de recherche
		$arr_engines = stats_load_engines();

		// initialiser la recherche interne
		$url_site = $GLOBALS['meta']['adresse_site'];
		$url_site = preg_replace(',^((https?|ftp):?/?/?)?(www\.)?,', '', strtolower($url_site));
	}

	if ($url = @parse_url($kw_referer)) {
		$query = $url['query'] ?? '';
		$host = isset($url['host']) ? strtolower($url['host']) : '';
		$path =  $url['path'] ?? '';
		$scheme = $url['scheme'] ?? '';
	} else {
		$scheme = $query = $host = $path = '';
	}

	// construire un array des variables directement depuis la query-string
	parse_str($query, $Tquery);

	$keywords = '';
	$found = false;

	if (!empty($url_site)) {
		if (strpos('-' . $kw_referer, (string) $url_site) !== false) {
			if (preg_match(',(s|search|r|recherche)=([^&]+),i', $kw_referer, $regs)) {
				$keywords = urldecode($regs[2]);
			} else {
				return ['host' => ''];
			}
		} else {
			for ($cnt = 0; $cnt < sizeof($arr_engines) && !$found; $cnt++) {
				if (
					$found = preg_match(',' . $arr_engines[$cnt][2] . ',', $host)
					or $found = preg_match(',' . $arr_engines[$cnt][2] . ',', $path)
				) {
					$kw_referer_host = $arr_engines[$cnt][0];

					if (strpos($arr_engines[$cnt][1], '=') !== false) {
						// Fonctionnement simple: la variable existe dans l'array
						$v = str_replace('=', '', $arr_engines[$cnt][1]);
						$keywords = $Tquery[$v] ?? '';

						// Si on a defini le nom de la variable en expression reguliere, chercher la bonne variable
						if (!strlen($keywords) > 0) {
							if (preg_match(',' . $arr_engines[$cnt][1] . '([^\&]*),', $query, $vals)) {
								$keywords = urldecode($vals[2]);
							}
						}
					} else {
						$keywords = '';
					}

					if (
						($kw_referer_host == 'Google')
						|| ($kw_referer_host == 'AOL' && strpos($query, 'enc=iso') === false)
						|| ($kw_referer_host == 'MSN')
					) {
						include_spip('inc/charsets');
						if (!isset($ie) or !$cset = $ie) {
							$cset = 'utf-8';
						}
						$keywords = importer_charset($keywords, $cset);
					}
					$buffer['hostname'] = $kw_referer_host;
				}
			}
		}
	}

	$buffer['host'] = $host;
	$buffer['scheme'] = $scheme;
	if (!isset($buffer['hostname']) or !$buffer['hostname']) {
		$buffer['hostname'] = $host;
	}

	$buffer['path'] = substr($path, 1, strlen($path));
	$buffer['query'] = $query;

	if ($keywords != '') {
		if (strlen($keywords) > 150) {
			$keywords = spip_substr($keywords, 0, 148);
			// supprimer l'eventuelle entite finale mal coupee
			$keywords = preg_replace('/&#?[a-z0-9]*$/', '', $keywords);
		}
		$buffer['keywords'] = trim(entites_html(urldecode(stripslashes($keywords))));
	}

	return $buffer;
}


/**
 * Recherche des objets pointés par un referer
 *
 * @param string       $referermd5
 * @param string       $serveur
 * @param array|string $objets
 * @return string
 */
function referes(string $referermd5, $objets = null, string $serveur = ''): string {

	include_spip('base/objets'); // au cas où
	$trouver_table = charger_fonction('trouver_table', 'base');

	// plugin stats objets ?
	$stats_objets = $trouver_table('spip_visites_objets');

	// si aucun type d'objet n'est donné en paramètre,
	// on va chercher tous les types d'objets référencés dans les tables des referers
	if (!$objets) {
		$objets = [];
		if ($stats_objets) {
			if ($objets = sql_fetsel('DISTINCT objet', 'spip_referers_objets')) {
				$objets = array_values($objets);
			}
		}
		if (sql_countsel('spip_visites_articles')) {
			$objets[] = 'article';
		}
	}
	// sinon on s'assure d'avoir un array
	elseif (is_string($objets)) {
		$objets = [$objets];
	}

	$retours = [];
	$res_objets = [];
	foreach ($objets as $objet) {
		$table_objet_sql = table_objet_sql($objet); // spip_articles
		$id_table_objet = id_table_objet($objet); // id_article
		$desc = $trouver_table($table_objet_sql);
		$champ_titre = $desc['titre'] ?? 'titre'; // titre, nom...
		if ($objet == 'article') {
			$table_referers = 'spip_referers_articles';
		} else {
			$table_referers = 'spip_referers_objets';
		}
		$on = ($objet == 'article' ?
			'J1.id_article = J2.id_article' :
			'(J1.objet=' . sql_quote($objet) . " AND J1.id_objet = J2.$id_table_objet)");
		if (
			$res = sql_allfetsel(
				"J2.$id_table_objet, J2.$champ_titre",
				"$table_referers AS J1 LEFT JOIN $table_objet_sql AS J2 ON $on",
				"(referer_md5='$referermd5' AND J1.maj>=DATE_SUB(" . sql_quote(date('Y-m-d H:i:s')) . ', INTERVAL 2 DAY))',
				'',
				'titre',
				'',
				'',
				$serveur
			)
		) {
			$res_objets[$objet] = $res;
		}
	}

	foreach ($res_objets as $objet => $res) {
		$id_table_objet = id_table_objet($objet);
		foreach ($res as $k => $ligne) {
			$titre = typo($ligne['titre']);
			$url = generer_url_entite($ligne[$id_table_objet], $objet, '', '', true);
			$retours[$k] = "<a href='$url'><i>$titre</i></a>";
		}
	}

	if (count($retours) > 1) {
		return '&rarr; ' . join(',<br />&rarr; ', $retours);
	}
	if (count($retours) == 1) {
		return '&rarr; ' . array_shift($retours);
	}

	return '';
}
