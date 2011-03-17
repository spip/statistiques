<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@enfants
function enfants($id_parent, $critere, &$nombre_vis, &$nombre_abs){
	$result = sql_select("id_rubrique", "spip_rubriques", "id_parent=".intval($id_parent));

	$nombre = 0;

	while($row = sql_fetch($result)) {
		$id_rubrique = $row['id_rubrique'];

		$visites = intval(sql_getfetsel("SUM(".$critere.")", "spip_articles", "id_rubrique=".intval($id_rubrique)));
		$nombre_abs[$id_rubrique] = $visites;
		$nombre_vis[$id_rubrique] = $visites;
		$nombre += $visites + enfants($id_rubrique, $critere, $nombre_vis, $nombre_abs);
	}
	if (!isset($nombre_vis[$id_parent])) $nombre_vis[$id_parent]=0;
	$nombre_vis[$id_parent] += $nombre;
	return $nombre;
}


// http://doc.spip.org/@enfants_aff
function enfants_aff($id_parent,$decalage, $taille, $critere, $gauche=0) {
	global $spip_lang_right, $spip_lang_left;
	static $abs_total=null;
	static $niveau=0;
	static $nombre_vis;
	static $nombre_abs;
	if (is_null($abs_total)){
		$nombre_vis = array();
		$nombre_abs = array();
		$abs_total = enfants(0, $critere, $nombre_vis, $nombre_abs);
		if ($abs_total<1) $abs_total=1;
		$nombre_vis[0] = 0;
	}
	$visites_abs = 0;
	$out = "";

	$result = sql_select("id_rubrique, titre, descriptif", "spip_rubriques", "id_parent=$id_parent",'', '0+titre,titre');

	while($row = sql_fetch($result)){
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);
		$descriptif = attribut_html(couper(typo($row['descriptif']),80));

		if ($nombre_vis[$id_rubrique]>0 OR $nombre_abs[$id_rubrique]>0){
			$largeur_rouge = floor(($nombre_vis[$id_rubrique] - $nombre_abs[$id_rubrique]) * $taille / $abs_total);
			$largeur_vert = floor($nombre_abs[$id_rubrique] * $taille / $abs_total);
			
			if ($largeur_rouge+$largeur_vert>0){
					
				if ($niveau == 0) {
					$couleur="#cccccc";
				}

				else if ($niveau == 1) {
					$couleur="#eeeeee";
				}
				else {
					$couleur="white";
				}
				$out .= "<table cellpadding='2' cellspacing='0' border='0' width='100%'>";
				$out .= "\n<tr style='background-color: $couleur'>";
				$out .= "\n<td style='border-bottom: 1px solid #aaaaaa; padding-$spip_lang_left: ".($niveau*20+5)."px;'>";

				
				if ( $largeur_rouge > 2) 
					$out .= bouton_block_depliable("<a href='" . generer_url_entite($id_rubrique,'rubrique') . "' style='color: black;' title=\"$descriptif\">$titre</a>","incertain", "stats$id_rubrique");
				else
					$out .= "<div class='verdana1' style='padding-left: 18px; padding-top: 4px; padding-bottom: 3px;'>"
						. "<a href='" . generer_url_entite($id_rubrique,'rubrique') . "' style='color: black;' title=\"$descriptif\">$titre</a>"
						. "</div>";
				$out .= "</td>";
				
				
				if ($niveau==0 OR 1==1){
					$pourcent = round($nombre_vis[$id_rubrique]/$abs_total*1000)/10;
					$out .= "\n<td class='verdana1' style='text-align: $spip_lang_right; width: 40px; border-bottom: 1px solid #aaaaaa;'>$pourcent%</td>";
				}
				else {
					$out .= "<td style='width: 10px; border-bottom: 1px solid #aaaaaa;'></td>";
				}
				
				
				$out .= "\n<td align='right' style='border-bottom: 1px solid #aaaaaa; width:" . ($taille+5) ."px'>";
				
				
				$out .= "\n<table cellpadding='0' cellspacing='0' border='0' width='".($decalage+1+$gauche)."'>";
				$out .= "\n<tr>";
				if ($gauche > 0) $out .= "<td style='width: " .$gauche."px'></td>";
				$out .= "\n<td style='border: 0px; white-space: nowrap;'>";
				$out .= "<div style='border: 1px solid #999999; background-color: #dddddd; height: 12px; padding: 0px; margin: 0px;'>";
				if ($visites_abs > 0) $out .= "<img src='" . chemin_image('rien.gif') . "' style='vertical-align: top; height: 12px; border: 0px; width: ".$visites_abs."px;' alt= ' '/>";
				if ($largeur_rouge>0) $out .= "<img src='" . chemin_image('rien.gif') . "' class='couleur_cumul' style='vertical-align: top; height: 12px; border: 0px; width: " . $largeur_rouge . "px;' alt=' ' />";
				if ($largeur_vert>0) $out .= "<img src='" . chemin_image('rien.gif') . "' class='couleur_nombre' style='vertical-align: top; width: " . $largeur_vert ."px; height: 12px; border: 0px' alt=' ' />";
				$out .= "</div>";
				$out .= "</td></tr></table>\n";
				$out .= "</td></tr></table>";
			}	
		}
		
		if (isset($largeur_rouge) && ($largeur_rouge > 0)) {
			$niveau++;
			$out .= debut_block_depliable(false,"stats$id_rubrique");
			$out .= enfants_aff($id_rubrique,$largeur_rouge, $taille, $critere, $visites_abs+$gauche);
			$out .= fin_block();
			$niveau--;
		}
		$visites_abs = $visites_abs + round($nombre_vis[$id_rubrique]/$abs_total*$taille);
	}
	return $out;
}

// http://doc.spip.org/@exec_statistiques_repartition_dist
function exec_statistiques_repartition_dist()
{

	global  $abs_total, $nombre_vis, $taille, $spip_ecran;

	if (!autoriser('voirstats')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {

	$taille = _request('taille');
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_statistiques'), "statistiques_visites", "repartition");
	
	echo debut_grand_cadre(true);
	echo gros_titre(_T('titre_page_statistiques'),'',false);
	
	echo barre_onglets("statistiques", "statistiques_repartition");

	if ($spip_ecran == "large") { 
	 	                $largeur_table = 974; 
	 	                $taille = 550; 
	 	        } else { 
	 	                $largeur_table = 750; 
	 	                $taille = 400; 
	 	        } 
	 	 
	echo "\n<br /><br /><table width='$largeur_table'><tr><td class='verdana2' style='text-align: center;  width: $largeur_table" . "px;'>"; 
	$critere = _request('critere');
	
	if ($critere == "debut") {
		$critere = "visites";
		echo "<a href='".generer_url_ecrire('statistiques_repartition')."'>"._T('icone_repartition_actuelle').'</a>';
		echo " | <strong>"._T('onglet_repartition_debut').'</strong>';
	}
	else {
		$critere = "popularite";
		echo "<strong>"._T('icone_repartition_actuelle').'</strong>';
		echo " | <a href='".generer_url_ecrire('statistiques_repartition','critere=debut')."'>"._T('onglet_repartition_debut').'</a>';
	}

	echo debut_cadre_relief("statistique-24.png",true);
	echo "<div style='border: 1px solid #aaaaaa; border-bottom: 0px;'>";
	echo enfants_aff(0,$taille, $taille, $critere);
	echo "</div><br />",
	  "<div class='verdana3' style='text-align: left;'>",
	  _T('texte_signification'),
	  "</div>";
	echo fin_cadre_relief(true);
	echo "</td></tr></table>"; 
	echo fin_grand_cadre(true),fin_page();
	}
}
?>
