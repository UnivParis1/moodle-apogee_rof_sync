<?php 

function formaterDate($date_a_formater) {
	$tab_month = array(1=>"Janvier", "Fevrier", "Mars", "Avril", "Mai", "Juin", "Juillet", "Aout", "Septembre", "Octobre", "Novembre", "Decembre");
	$tab_day = array("Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi");
	$prefix = "";
	$suffix = " à";
	$tab_date = explode(' ', $date_a_formater);
	$date_hour = explode(':', $tab_date[1]);
	$tab_dmy = explode('-', $tab_date[0]);
	$day = date("w", mktime(0, 0, 0, $tab_dmy[1], $tab_dmy[2], $tab_dmy[0]));
	$date_formatee = $prefix . "$tab_day[$day] " . "$tab_dmy[2] ";
	settype($tab_dmy[1], 'integer');
	$date_formatee .= $tab_month[$tab_dmy[1]] . " $tab_dmy[0]" . $suffix . " $date_hour[0]h " . "$date_hour[1]min";
	return $date_formatee;
}