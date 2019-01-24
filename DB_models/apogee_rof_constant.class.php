<?php

class apogee_rof_constant{
	
	private $record;
	
	public function __construct($data=null){
		global $CFG;
		$this->record = $this->initializeRecord($data);
	}
	
	private function initializeRecord($data=null) {
		$record = new stdClass();
	    $record->id 			= !empty($data['id']) ? $data['id'] : '';
	    $record->element		= !empty($data['element']) ? $data['element'] : '';
	    $record->elementtype 	= !empty($data['elementtype']) ? $data['elementtype'] : '';
	    $record->dataid 		= !empty($data['dataid']) ? $data['dataid'] : '';
	    $record->dataimport 	= !empty($data['dataimport']) ? $data['dataimport'] : '';
	    $record->dataoai 		= !empty($data['dataoai']) ? $data['dataoai'] : 0;
	    $record->value 			= !empty($data['value']) ? $data['value'] : '';
		$record->timesync = time();
		return $record;
	}
	private function clearRecord() {
		$this->record = null;
	}

	/**
	 * Installation des constantes de la table rof_component
 	 *
	 *  element 		-->		composante
	 *  dataid 			-->		rofcomponent.import
	 *  dataimport		-->		rofcomponent.import
	 *  dataoai 		-->		rofcomponent.import // on n'utilise plus 
	 *  value 			-->		rofcomponent.name
	 */

	public function insertComponentConstant($component) {
		global $DB;
		$data['element'] = 'composante';
		$data['dataid'] = $component->import;
		$data['dataimport'] = $component->import;
		$data['dataoai'] = $component->import;
		$data['value'] = $component->name;
		$this->record = $this->initializeRecord($data);
		$DB->insert_record('rof_constant', $this->record , false);
	}
	


	/**
	 * Installation des constantes du domainediplome de la table rof_program
 	 * Le libéllé est inséré dans la table rof_program donc on duplique juste ce libéllé dans tous 
 	 * les champs de la tabvle rof_constant pour s'assurer du bon fonctionnement du reste de l'application
	 *  element 		-->		domaineDiplome
	 *  dataid 			-->		rof_program.domainediplome 
	 *  dataimport		-->		rof_program.domainediplome 
	 *  dataoai 		-->		rof_program.domainediplome // on n'utilise plus 
	 *  value 			-->		rof_program.domainediplome 
	 */

	public function insertDomaineDiplome($domainediplome) {
		global $DB;
		$data['element'] = 'domaineDiplome';
		$data['dataid'] = $domainediplome;
		$data['dataimport'] = $domainediplome;
		$data['dataoai'] = $domainediplome;
		$data['value'] = $domainediplome;
		$this->record = $this->initializeRecord($data);
		if (!$DB->record_exists_select('rof_constant','element = ? and value = ?', array('domaineDiplome',$domainediplome))) 
			$DB->insert_record('rof_constant', $this->record , false);
	}
	/**
	 * Installation des constantes du rythmediplome de la table rof_program
 	 * Le libéllé est inséré dans la table rof_program donc on duplique juste ce libéllé dans tous 
 	 * les champs de la tabvle rof_constant pour s'assurer du bon fonctionnement du reste de l'application
	 *  element 		-->		publicDiplome
	 *  dataid 			-->		rof_program.rythmediplome 
	 *  dataimport		-->		rof_program.rythmediplome 
	 *  dataoai 		-->		rof_program.rythmediplome // on n'utilise plus 
	 *  value 			-->		rof_program.rythmediplome 
	 */

	public function insertRythmeDiplome($publicDiplome) {
		global $DB;
		$data['element'] = 'publicDiplome';
		$data['dataid'] = $publicDiplome;
		$data['dataimport'] = $publicDiplome;
		$data['dataoai'] = $publicDiplome;
		$data['value'] = $publicDiplome;
		$this->record = $this->initializeRecord($data);
		if (!$DB->record_exists_select('rof_constant','element = ? and value = ?', array('publicDiplome',$publicDiplome))) 
			$DB->insert_record('rof_constant', $this->record , false);
	}
	
	/**
	 * Intialisation des constantes des cycles de diplome de la table rof_program
	 * 
	 */
	public function insertCycleDiplome(){
		global $DB;
		$clevaleur = array(
				'1' => 'Premier cycle',
				'2' => 'Second cycle',
				'3' => 'Troisième cycle',
				'4' => 'Inter cycle'
		);
		foreach($clevaleur as $cle=>$valeur) {
			$data = array(
				'element'		=> 	'cycleDiplome',
				'dataid'		=> 	$cle,
				'dataimport'	=> 	$valeur,
				'dataoai'		=> 	$cle,
				'value'			=> 	$valeur,
			);
			$this->record = $this->initializeRecord($data);
			$DB->insert_record('rof_constant', $this->record , false);
			$this->clearRecord();
			unset($data);
		}
		
	}
	

	public function insert($data){
		global $DB;
		$this->record = $this->initializeRecord($data);
		$DB->insert_record('rof_constant', $this->record , false);
	}
	
	
}
?>
