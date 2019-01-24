<?php
class apogee_rof_program {

    private $record;

    public function __construct($level = 1,$data=null){
        global $CFG;
    	$this->record = $this->initializeRecord($level,$data);
    }

    private function initializeRecord($level = 1,$data=null){
	    $record = new stdClass();
	    $record->level = $level;	    
	    $record->rofid 		= !empty($data['rofid']) ? $data['rofid'] : '';
	    $record->name 		= !empty($data['name']) ? $data['name'] : '';
	    $record->components	= !empty($data['components']) ? $data['components'] : '';
	    $record->typedip 	= !empty($data['typedip']) ? $data['typedip'] : '';
	    $record->domainedip = !empty($data['domainedip']) ? $data['domainedip'] : '';
	    $record->naturedip 	= !empty($data['naturedip']) ? $data['naturedip'] : '';
	    $record->cycledip 	= !empty($data['cycledip']) ? $data['cycledip'] : '';
	    $record->rythmedip 	= !empty($data['rythmedip']) ? $data['rythmedip'] : '';
	    $record->rythmedip 	= !empty($data['rythmedip']) ? $data['rythmedip'] : '';
	    $record->languedip 	= !empty($data['languedip']) ? $data['languedip'] : '';
	    $record->acronyme 	= !empty($data['acronyme']) ? $data['acronyme'] : '';
	    $record->mention 	= !empty($data['mention']) ? $data['mention'] : '';
	    $record->specialite	= !empty($data['specialite']) ? $data['specialite'] : '';
	    $record->subnb		= !empty($data['subnb']) ? $data['subnb'] : 0;
	    $record->sub		= !empty($data['sub']) ? $data['sub'] : '';
	    $record->coursesnb	= !empty($data['coursesnb']) ? $data['coursesnb'] : 0;
	    $record->courses	= !empty($data['courses']) ? $data['courses'] : '';
	    $record->parents	= !empty($data['parents']) ? $data['parents'] : '';
	    $record->parentsnb	= !empty($data['parentsnb']) ? $data['parentsnb'] : 0;
	    $record->oneparent	= !empty($data['oneparent']) ? $data['oneparent'] : '';
	    $record->refperson	= !empty($data['refperson']) ? $data['refperson'] : '';
	    $record->timesync = time();
	    $record->timemodified = time();
	    return $record;
    }

	public function insert($level = 0,$data = null) {
		global $DB;
		if (!empty($level)) {
			if (!empty($data)) $this->record = $this->initializeRecord($level,$data);
            if (!empty($this->record->rofid)) {
            	if ($DB->record_exists_select('rof_program','rofid = ?', array($this->record->rofid))) {
            		$program = $this->getProgramByRofId($this->record->rofid);
            		if (!empty($program->parent)) $parent = $program->parent; else $parent = '';
                	$this->addParent($this->record->rofid,$parent);
                } else {
               		$DB->insert_record('rof_program', $this->record , false);
                }
            }
		}
	}

	public function getParentsById($id) {
    	global $DB;
		$select = "SELECT parents from {rof_program} where id like ?";
		$obj = $DB->get_record_sql($select,array($id));
		if (!empty($obj->parents)) return $obj->parents;
		return '';
	}

	public function getParentsByRofd($rofid) {
    	global $DB;
		$select = "SELECT parents from {rof_program} where rofid like ?";
		$obj = $DB->get_record_sql($select,array($rofid));
		return (empty($obj->parents))?'':$obj->parents;
	}

	public function addParent($rofid,$parent) {
		global $DB;
		$parents = $this->getParentsByRofd($rofid);
		if (empty($parents)) $parents = $parent; else $parents .= ','.$parent;
		$sqlupdate = "UPDATE {rof_program} SET parentsnb = parentsnb + 1 , parents = ? WHERE rofid like ?";
		$params[] = $parents;
		$params[] = $rofid;
		$DB->execute($sqlupdate, $params);
	}

	public function getSubsById($id) {
		global $DB;
		$select = "SELECT sub from {rof_program} where id like ?";
		$obj = $DB->get_record_sql($select,array(id));
		return $obj->sub;
	}

	public function getSubsByRofid($rofid) {
		global $DB;
		$select = "SELECT sub from {rof_program} where rofid like ?";
		$obj = $DB->get_record_sql($select,array($rofid));
		return (empty($obj->sub))?'':$obj->sub;
	}

	public function getCoursesByRofid($rofid) {
		global $DB;
		$select = "SELECT courses from {rof_program} where rofid like ?";
		$obj = $DB->get_record_sql($select,array($rofid));
		return (empty($obj->courses))?'':$obj->courses;
	}

	public function addSub($rofidpere,$rofidfils) {
		global $DB;
		$sub = $this->getSubsByRofid($rofidpere);
		if (empty($sub)) $sub = $rofidfils; else $sub .=','.$rofidfils;
		$sqlupdate = "UPDATE {rof_program} SET subnb = subnb + 1 , sub = ? WHERE rofid like ?";
		$params[] = $sub;
		$params[] = $rofidpere;
		$DB->execute($sqlupdate, $params);
	}

	public function addCourse($rofidpere,$rofidfils) {
		global $DB;
		$sub = $this->getCoursesByRofid($rofidpere);
		if (empty($sub)) $sub = $rofidfils; else $sub .=','.$rofidfils;
		$sqlupdate = "UPDATE {rof_program} SET coursesnb = coursesnb + 1 , courses = ? WHERE rofid like ?";
		$params[] = $sub;
		$params[] = $rofidpere;
		$DB->execute($sqlupdate, $params);
	}
	
	public function getProgramByRofId($rofid) {
		global $DB;
		$select = "select count(id) as nb from {rof_program} where rofid  like '$rofid'";
		$program = $DB->get_record_sql($select);
		return $program->nb;
	}
	
	public function getRofidByCod($cod) {
		global $DB;
		$select = "select rofid as nb from {rof_program} where rofid  like '%$cod'";
		$rofid = $DB->get_record_sql($select);
		return $rofid->rofid;
	}
	
	public function getListCodElp() {
		global $DB;
		$retour = array();
		$select = "SELECT rofid FROM {rof_program} where level=2";
		$obj = $DB->get_records_sql($select);
		foreach($obj as $i=>$rof) {
			$rofArray = explode('-',$rof->rofid);
			// A ce niveau le rofid est de type UP1-PROG-ELP-<Cod_elp>
			if (isset($rofArray[3])) $retour[] = $rofArray[3];
		}
		return $retour;
	}
	
	public function getAllDomaineDiplome() {
		global $DB;
		return  $DB->get_records_select('rof_program','',null,'','distinct domainedip');
	}
	
	public function getAllRythmeDiplome() {
		global $DB;
		return  $DB->get_records_select('rof_program','',null,'','distinct rythmedip');
	}
	
}
?>
