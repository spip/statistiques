<?php

/**
 * Déclaration d'autorisations
 *
 * @plugin Statistiques pour SPIP
 * @license GNU/GPL
 * @package SPIP\Stats\Pipelines
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 *
 * @pipeline autoriser
 */
function stats_autoriser() {
}

/**
 * Autoriser l'affichage du menu de statistiques
 *
 * @uses autoriser_voirstats_dist()
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_statistiques_menu_dist($faire, $type = '', $id = 0, $qui = null, $opt = null) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}

/**
 * Autoriser l'affichage des statistiques
 *
 * Nécessite :
 * - les statistiques sont actives dans la configuration
 * - d'être administrateur
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_voirstats_dist($faire, $type, $id, $qui, $opt) {
	return (($GLOBALS['meta']['activer_statistiques'] != 'non')
		and ($qui['statut'] == '0minirezo'));
}

/**
 * Autoriser l'affichage de l'onglet visites dans les statistiques
 *
 * @uses autoriser_voirstats_dist()
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_statsvisites_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}

/**
 * Autoriser l'affichage de l'onglet répartition par secteur dans les statistiques
 *
 * @uses autoriser_voirstats_dist()
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_statsrepartition_onglet_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voirstats', $type, $id, $qui, $opt);
}


/**
 * Autoriser l'affichage de l'onglet répartition par langue dans les statistiques
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_statslang_onglet_dist($faire, $type, $id, $qui, $opt) {
	$objets = explode(',', $GLOBALS['meta']['multi_objets'] ?? '');

	return (in_array('spip_articles', $objets)
		or in_array('spip_rubriques', $objets))
	and autoriser('voirstats', $type, $id, $qui, $opt);
}

/**
 * Autoriser l'affichage de l'onglet référers dans les statistiques
 *
 * @param  string $faire Action demandée
 * @param  string $type Type d'objet sur lequel appliquer l'action
 * @param  int $id Identifiant de l'objet
 * @param  array $qui Description de l'auteur demandant l'autorisation
 * @param  array $opt Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_statsreferers_onglet_dist($faire, $type, $id, $qui, $opt) {
	return (!isset($GLOBALS['meta']['activer_referers']) or $GLOBALS['meta']['activer_referers'] == 'oui') && autoriser('voirstats', $type, $id, $qui, $opt);
}


/**
 * Autoriser l'acces aux statistiques json transmettre meme sans lowsec
 * @param $faire
 * @param $type
 * @param $id
 * @param $qui
 * @param $opt
 * @return bool
 */
function autoriser_jsonstatistiquesjson_transmettre_dist($faire, $type, $id, $qui, $opt) {

	// si on est là c'est que la cle lowsec est invalide mais on a une session
	if (autoriser('voirstats', '', 0, $qui, $opt)
	  or autoriser('ecrire', '', 0, $qui, $opt)) {
		return true;
	}

	return false;
}

/**
 * Autoriser l'acces aux statistiques csv transmettre meme sans lowsec
 * @param $faire
 * @param $type
 * @param $id
 * @param $qui
 * @param $opt
 * @return bool
 */
function autoriser_csvstatistiquescsv_transmettre_dist($faire, $type, $id, $qui, $opt) {

	// si on est là c'est que la cle lowsec est invalide mais on a une session
	if (autoriser('voirstats', '', 0, $qui, $opt)
	  or autoriser('ecrire', '', 0, $qui, $opt)) {
		return true;
	}

	return false;
}
