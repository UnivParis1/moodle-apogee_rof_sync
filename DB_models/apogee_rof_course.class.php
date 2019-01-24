<?php
class apogee_rof_course {

    private $record;

    public function __construct($level = 1,$data=null){
        global $CFG;
    	$this->record = $this->initializeRecord($level,$data);
    }

    
	/**
	 * 
	 * Initialisation des champs de données
	 * @param int $level
	 * @param array() $data
	 */
    private function initializeRecord($level = 1,$data=null){
	    $record = new stdClass();
	    $record->level = $level;	    
	    $record->rofid 			= !empty($data['rofid']) ? $data['rofid'] : '';
	    $record->name 			= !empty($data['name']) ? $data['name'] : '';
	    $record->code			= !empty($data['code']) ? $data['code'] : '';
	    $record->composition	= !empty($data['composition']) ? $data['composition'] : '';
	    $record->subnb			= !empty($data['subnb']) ? $data['subnb'] : 0;
	    $record->sub			= !empty($data['sub']) ? $data['sub'] : '';
	    $record->parents		= !empty($data['parents']) ? $data['parents'] : '';
	    $record->parentsnb		= !empty($data['parentsnb']) ? $data['parentsnb'] : 0;
	    $record->oneparent		= !empty($data['oneparent']) ? $data['oneparent'] : '';
	    $record->refperson		= !empty($data['refperson']) ? $data['refperson'] : '';
	    $record->timesync = time();
	    $record->timemodified = time();
	    return $record;
    }

	public function insert($level = 0,$data = null) {
		global $DB;
		if (!empty($level)) {
			if (!empty($data)) $this->record = $this->initializeRecord($level,$data);
            if (!empty($this->record->rofid)) {
            	if ($DB->record_exists_select('rof_course','rofid = ?', array($this->record->rofid))) {
            		$course = $this->getCourseByRofId($this->record->rofid);
            		if (!empty($this->record->parents)) {
            			if (!preg_match('/\b'.$this->record->parents.'\b/i',$course->parents)) {
            				$this->addParent($this->record->rofid,$course->parents);
            			}
            		} 
                } else {
               		$DB->insert_record('rof_course', $this->record , false);
                }
       		}
		/**
		* @todo Voir si un élément éxiste déjà en comptant le nombre d'élément ayant le même name et le même rofid
		* @todo Si c'est le cas ajouter un parents, et incrémenter le nbparent à l'enregistrement existant
		* @todo Sinon on insère
		*/
		}
	}

	public function getParentsById($id) {
    	global $DB;
		$select = "SELECT parents from {rof_course} where id like ?";
		$obj = $DB->get_record_sql($select,array($id));
		return (empty($obj->parents))?'':$obj->parents;
	}

	public function getParentsByRofd($rofid) {
    	global $DB;
		$select = "SELECT parents from {rof_course} where rofid like ?";
		$obj = $DB->get_record_sql($select,array($rofid));
		return (empty($obj->parents))?'':$obj->parents;
	}

	public function addParent($rofid,$parent) {
		global $DB;
		if (!$DB->record_exists_select('rof_course',"rofid = ? and parents like ?", array($rofid,'%'.$parent.'%'))) {
			$parents = $this->getParentsByRofd($rofid);
			if (empty($parents)) $parents = $parent; else $parents .= ','.$parent;
			$sqlupdate = "UPDATE {rof_course} SET parentsnb = parentsnb + 1 , parents = ? WHERE rofid like ?";
			$params[] = $parents;
			$params[] = $rofid;
			$DB->execute($sqlupdate, $params);		
		}
	}

	public function getSubsById($id) {
		global $DB;
		$select = "SELECT sub from {rof_course} where id like ?";
		$obj = $DB->get_record_sql($select,array(id));
		return (empty($obj->sub))?'':$obj->sub;
	}

	public function getSubsByRofid($rofid) {
		global $DB;
		$select = "SELECT sub from {rof_course} where rofid like ?";
		$obj = $DB->get_record_sql($select,array($rofid));
		return (empty($obj->sub))?'':$obj->sub;
	}

	public function addSub($rofidpere,$rofidfils) {
		global $DB;
		$sub = $this->getSubsByRofid($rofidpere);
		if (empty($sub)) $sub = $rofidfils; else $sub .=','.$rofidfils;
		$sqlupdate = "UPDATE {rof_course} SET subnb = subnb + 1 , sub = ? WHERE rofid like ?";
		$params[] = $sub;
		$params[] = $rofidpere;
		$DB->execute($sqlupdate, $params);
	}
	
	public function getCourseByRofId($rofid) {
		global $DB;
		$select = "select * from {rof_course} where rofid  like '$rofid'";
		$course = $DB->get_record_sql($select);
		return $course;
	}

	
	public function getListCodElp($level=0) {
		global $DB;
		$retour = array();
		if ($level) {
			$select = "SELECT rofid FROM {rof_course} where level=?";
			$obj = $DB->get_records_sql($select,array($level));
		} else {
			$select = "SELECT rofid FROM {rof_course}";
			$obj = $DB->get_records_sql($select);
		}
		foreach($obj as $i=>$rof) {
			$rofArray = explode('-',$rof->rofid);
			// A ce niveau le rofid est de type UP1-C-<Cod_elp>
			if (isset($rofArray[3])) $retour[] = $rofArray[3];
		}
		return $retour;
	}
}
?>
