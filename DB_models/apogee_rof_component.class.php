<?php
class apogee_rof_component {
	
	private $record;
	
	public function __construct($data=null){
		global $CFG;
		$this->record = $this->initializeRecord($data);
	}
	
	/**
	 * 
	 * Initialisation des champs de données
	 * @param array() $data
	 */
	private function initializeRecord($data=null) {
		$record = new stdClass();
	    $record->rofid 		= !empty($data['rofid']) ? $data['rofid'] : '';
	    $record->import		= !empty($data['import']) ? $data['import'] : '';
	    $record->oai 		= !empty($data['oai']) ? $data['oai'] : '';
	    $record->name 		= !empty($data['name']) ? $data['name'] : '';
	    $record->number 	= !empty($data['number']) ? $data['number'] : '';
	    $record->subnb 		= !empty($data['subnb']) ? $data['subnb'] : 0;
	    $record->sub 	= !empty($data['sub']) ? $data['sub'] : '';
		$record->timesync = time();
		$record->timemodified = time();
		return $record;
	}

	/**
	 * 
	 * Insertion d'une ligne Composante dans la table rof_component
	 * @param array() $data
	 */
	public function insert($data = null) {
		global $DB;
		if (!empty($data)) {
			$this->record = $this->initializeRecord($data);
		}
		 if (!empty($this->record)) $DB->insert_record('rof_component', $this->record , false);
	}

	/**
	 * 
	 * Liste toutes les composantes et toutes les informations associées
	 * @param Cette fonction ne reçoit aucun paramêtres
	 * @return stdClass Objet multidimensionné contenant la liste
	 */
	public function getAllComponents() {
		global $DB;
		return  $DB->get_records_select('rof_component','',null,'','import');
	}
	

	/**
	 * 
	 * Liste toutes les composantes et toutes les informations associées
	 * @param Cette fonction ne reçoit aucun paramêtres
	 * @return stdClass Objet multidimensionné contenant la liste
	 */
	public function getAllComponentsRecords() {
		global $DB;
		return  $DB->get_records_select('rof_component', '');
	}
	
	/**
	 * 
	 * Retourne l'identifiant d'une ligne Composante par rapprt au $name fournit
	 * Le $name correspond au numéro de composante d'Apogée (01, 02,...)
	 * @param string $name
	 * @return int Identifiant
	 */
	public function getIdComponentByName($name) {
		global $DB;
		$select = "SELECT id FROM {rof_component} WHERE name LIKE ?";
		$obj = $DB->get_record_sql($select,array($name));
		return $obj->id;
	}

	/**
	 * 
	 * Retourne les fils (diplômes) d'une composante en fontion du $name fournit
	 * Le $name correspond au numéro de composante d'Apogée (01, 02,...)
	 * @param string $name
	 * @return string Fils de la composante 
	 */	
	public function getSubsByName($name) {
		global $DB;
		$select = "SELECT sub FROM {rof_component} WHERE import LIKE ?";
		$obj = $DB->get_record_sql($select,array($name));
		return (empty($obj->sub))?'':$obj->sub;
	}
	
	/**
	 * 
	 * Ajoute un fils rofid à la composante de numéro $name
	 * @param string $component Numéro de composante d'Apogée (01, 02,...)
	 * @param string $rofid Rofid du fils à ajouter
	 */
	public function addSub($component,$rofid) {
		global $DB;
		$sub = $this->getSubsByName($component);
		if (empty($sub)) $sub = $rofid; else $sub .=','.$rofid;
		$sqlupdate = "UPDATE {rof_component} SET subnb = subnb + 1 , sub = ? WHERE import LIKE ?";
		$params[] = $sub;
		$params[] = $component;
		$DB->execute($sqlupdate, $params);
	}
	
	
}
?>
