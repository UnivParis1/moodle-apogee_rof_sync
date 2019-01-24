<?php
class apogee_rof_mapping {
	
	private $record;
	
	public function __construct($data=null){
		global $CFG;
		$this->record = $this->initializeRecord($data);
	}
	//Table de référence mdl_mapping_rofids(NUM_OBJ,COD1_ORI_OBJ,COD2_ORI_OBJ,ROFID)
	private function initializeRecord($data=null) {
		$record = new stdClass();
	    $record->num_obj 		= !empty($data['num_obj']) ? $data['num_obj'] : '';
	    $record->cod1_ori_obj		= !empty($data['cod1_ori_obj']) ? $data['cod1_ori_obj'] : '';
	    $record->cod2_ori_obj 		= !empty($data['cod2_ori_obj']) ? $data['cod2_ori_obj'] : '';
	    $record->rofid 		= !empty($data['rofid']) ? $data['rofid'] : '';
		return $record;
	}

	public function getAllDatas($field='up1rofid') {
		global $DB;
		$select = "	SELECT d.objectid, d.data, d.id
					FROM {custom_info_data} d
					JOIN {custom_info_field} f on (f.id=d.fieldid)
					WHERE f.shortname like ?
					AND d.data !=''";
		$obj = $DB->get_records_sql($select,array($field));
		return $obj;
	}
	
	public function getCourses(){
		global $DB;
		$select = "	SELECT DISTINCT d.objectid
					FROM {custom_info_data} d
					where objectname like ?";
		$obj = $DB->get_records_sql($select,array('course'));
		return $obj;
	}	

	public function getInfoDataByCourse($course) {
		global $DB;
		$select = "	SELECT f.shortname, d.data, d.id
					FROM {custom_info_data} d
					JOIN {custom_info_field} f on (f.id=d.fieldid)
					WHERE f.shortname IN ('up1rofpath','up1rofpathid')
					AND d.objectid = ?
					AND d.data !=''";
		$obj = $DB->get_records_sql($select,array($course));
		return $obj;
		
	}
	
        public function getMappingInfo($oldrofid,$names = false) {
        global $DB;
        $sqlchamps = ''; 
        $suffix = '';
        $newrofid = new StdClass();
        $oldrofid = str_replace('/', '', $oldrofid); // Néttoyage du rofid
        if (substr($oldrofid, -1) == ';') $suffix = ';'; 
        $oldrofid = str_replace(';', '', $oldrofid); // Néttoyage du rofid
        
        if ($names) $sqlchamps = ' ,name ';
                $select = "SELECT cod1_ori_obj,cod2_ori_obj from {mapping_rofids} where rofid like ? ";
                $obj = $DB->get_record_sql($select,array($oldrofid));
                if (empty($obj->cod1_ori_obj)) {
                        if ($names) return array($oldrofid.$suffix,'');
                        return $oldrofid.$suffix;    
                }   
                if (preg_match('#PROG#i',$oldrofid)) {
                        if (empty($obj->cod2_ori_obj)) { // c'est un semestre
                                $cod = 'ELP-'.$obj->cod1_ori_obj;
                        } else { // c'est un diplôme
                                $cod = $obj->cod1_ori_obj.'-'.$obj->cod2_ori_obj;
                        }   
                        $select = "select rofid $sqlchamps from {rof_program} where rofid  like '%$cod'";
                        $newrofidobject = $DB->get_records_sql($select,null,0,1);
                        foreach($newrofidobject as $key=>$value) {
                                if (isset($value->rofid)) $newrofid->rofid=$value->rofid;
                                if (isset($value->name)) $newrofid->name=$value->name; else $newrofid->name='';
                                $newrofid=$value;
                                continue;
                        }   
                        $retourrofid =  (empty($newrofid->rofid))?'':$newrofid->rofid; 
                        if ($names) return array($retourrofid.$suffix,$newrofid->name);
                        return $retourrofid.$suffix;
                }    
                if (preg_match('#UP1-C#i',$oldrofid)) { // c'est un cours
                        $cod = 'ELP-'.$obj->cod1_ori_obj;
                        $select = "select rofid $sqlchamps from {rof_course} where rofid  like '%$cod'";
                        $newrofidobject = $DB->get_records_sql($select,null,0,1);
                        foreach($newrofidobject as $key=>$value) { 
                                if (isset($value->rofid)) 	$newrofid->rofid = $value->rofid;
                                if (isset($value->name)) 	$newrofid->name = $value->name; else $newrofid->name = '';
                                continue;
                        }   
                        $retourrofid = (empty($newrofid->rofid))?'':$newrofid->rofid;
                        if ($names) return array($retourrofid.$suffix,$newrofid->name);
                        return $retourrofid.$suffix;
                }   
                if ($names) return array($oldrofid.$suffix,'');
                return $oldrofid.$suffix;
        }
	
	public function update($data,$id) {
		global $DB;
		$sqlupdate = "UPDATE {custom_info_data} SET data = ? WHERE id = ?";
		$params[] = $data;
		$params[] = $id;
		$DB->execute($sqlupdate, $params);
	}
	
	public function RemoveDoubleTuples(){
		global $DB;
		$select = "	SELECT *
			FROM {mapping_rofids} m";
		$obj = $DB->get_records_sql($select);
		foreach($obj as $i=>$tuple) {
			if ($DB->record_exists_select('mapping_rofids','rofid = ? and id != ?', array($tuple->rofid,$tuple->id))) {
				$DB->delete_records('mapping_rofids', array('id' => $tuple->id));
			}
		}
	}
	
	public function Removedoubleslashes() {
	
		global $DB;
		$select = "	SELECT d.*
			FROM {custom_info_data} d
					JOIN {custom_info_field} f on (f.id=d.fieldid)
					WHERE f.shortname like ? or f.shortname like ?
					AND d.data !=''";
		$obj = $DB->get_records_sql($select,array('up1rofpathid','up1rofpath'));
		foreach($obj as $i=>$datas) {
			$data = explode('/',$datas->data);
			$data_a_remodeler = '';
			for($i=0;$i<count($data);$i++) {
				if (!empty($data[$i])) $data_a_remodeler .='/'.$data[$i];
			}
			$sqlupdate = "UPDATE {custom_info_data} SET data = ? , sub = ? WHERE id = ?";
			$params[] = $data_a_remodeler;
			$params[] = $datas->data;
			$DB->execute($sqlupdate, $params);
		}
	}
	
}
?>
