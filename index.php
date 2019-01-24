<?php
require_once("../../config.php");
require_once('../../lib/accesslib.php');
require_once('apogee_rof_sync.class.php');
require_once('locallib.php');
require_login();



@ini_set('display_errors', '1'); // NOT FOR PRODUCTION SERVERS!
$CFG->debug = 38911;  // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
$CFG->debugdisplay = true;   // NOT FOR PRODUCTION SERVERS!
$data = array();
$url = new moodle_url('/local/up1reportepiufr/index.php');
$PAGE->set_url($url);
$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('ajax.js'), true);
//$PAGE->navbar->add(get_string('analysis', 'feedback'));

		$heading_page_libelle = 'Synchronisation de l\'offre de formation avec APOGEE';
		$title_page_libelle = 'Synchronisation de l\'offre de formation avec APOGEE';
		$heading_output_libelle = 'Synchronisation de l\'offre de formation avec APOGEE';

/**
 * vérification que l'utilisateur est un administrateur
 */


if (is_siteadmin()) {
	
	$liste_tables = array (
							'rof_component' 	=>	'Composantes - (table apogee_rof_component)',
							'rof_program' 		=>	'Diplômes et Semestres - (table apogee_rof_program)',
							'rof_course' 		=>	'Cours et UEs - (table apogee_rof_course)',
							'rof_person' 		=>	'Reponsables pédagogiques - (table apogee_rof_person)',
							'rof_constant' 		=>	'Constantes - (table apogee_rof_constant)',
							'mapping_rofids'	=>	'Mapping des anciens rofids (uniquement pour l\'année 2014-2015'
						);
	$objApogeeRofSync = new apogee_rof_sync();
   	$initializeHtml=  '<span id="span_initialize_--"><a href="javascript:call_Synchro(\'initialize\',\'--\');">Initialiser</a></span>';
	$table = new html_table();
	
   	$table->head = array('Table à synchroniser','Réinitialiser', 'Date de l\'initialisation');
   	
   	foreach ($liste_tables as $nomtable =>$libelletable ) {
	   	$dates_history = $objApogeeRofSync->getHistorySync($nomtable); // Recherche des dates de mise à jour
	   	if (empty($dates_history)) {
	   		$date_initialize = '--';
	   	} else {
	   		$date_initialize= $dates_history;
	   	}
		$data[] = array(	$libelletable,
							str_replace('--',$nomtable,$initializeHtml),
							(!empty($dates_history))?formaterDate($date_initialize):'--'
						);
   	}
   	
   	$table->data = $data;
						
}
$PAGE->set_heading($heading_page_libelle);
$PAGE->set_title($title_page_libelle);
$PAGE->set_pagelayout('report');
echo $OUTPUT->header();
echo $OUTPUT->heading($heading_output_libelle);
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo '<div><a href="index.php">Retour</a></div>';
if (!empty($table)) echo html_writer::table($table);
echo $OUTPUT->box_end();
echo $OUTPUT->footer(); 