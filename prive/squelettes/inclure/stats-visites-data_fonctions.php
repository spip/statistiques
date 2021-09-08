<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/acces');
include_spip('inc/statistiques');



function stats_total($serveur = '') {
	$row = sql_fetsel('SUM(visites) AS total_absolu', 'spip_visites', '', '', '', '', '', $serveur);

	return $row ? $row['total_absolu'] : 0;
}
