<?php

function transmettre_statistiques_json(string $export = 'visites', string $unite = 'jour', ?int $duree = null, ?string $objet = null, ?int $id_objet = null) {
	$visites = charger_fonction('stats_' . $export . '_to_array', 'inc');
	$stats = $visites($unite, $duree, $objet, $id_objet);
	return json_encode($stats, JSON_PRETTY_PRINT);
}
