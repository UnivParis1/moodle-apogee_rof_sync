<?php
class apogee_rof_person {
	
	private $record;
	
	public function __construct($data=null){
		global $CFG;
		$this->record = $this->initializeRecord($data);
	}
	
	private function initializeRecord($data=null) {
		$record = new stdClass();
	    $record->rofid 		= !empty($data['rofid']) ? $data['rofid'] : '';
	    $record->givenname 	= !empty($data['givenname']) ? $data['givenname'] : '';
	    $record->familyname	= !empty($data['familyname']) ? $data['familyname'] : '';
	    $record->title 		= !empty($data['title']) ? $data['title'] : '';
	    $record->role 		= !empty($data['role']) ? $data['role'] : '';
	    $record->email 		= !empty($data['email']) ? $data['email'] : '';
	    $record->oneparent 	= !empty($data['oneparent']) ? $data['oneparent'] : '';
		$record->timesync = time();
		return $record;
	}

	public function insert($level = 0,$data = null) {
		global $DB;
		if (!empty($data)) {
			$this->record = $this->initializeRecord($data);
		}
        if (!empty($this->record->rofid)) {
        	if (!$DB->record_exists_select('rof_person','rofid = ?', array($this->record->rofid))) {
        		 $DB->insert_record('rof_person', $this->record , false);
        	}
        }
	}
	
	public function addRefPersonToCourse($rofidperson,$rofidcourse) {
		global $DB;
		$sqlupdate = "UPDATE {rof_course} SET refperson = ? WHERE rofid like ?";
		$params[] = $rofidperson;
		$params[] = $rofidcourse;
		$DB->execute($sqlupdate, $params);
		
	}
	
	public function addRefPersonToProgram($rofidperson,$rofidprogram) {
		global $DB;
		$sqlupdate = "UPDATE {rof_program} SET refperson = ? WHERE rofid like ?";
		$params[] = $rofidperson;
		$params[] = $rofidprogram;
		$DB->execute($sqlupdate, $params);
		
	}
}
?>
