<?php 
/**
 * @author El-Miqui CHEMLAL
 * @version 1.0
 * 
 */
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_constant.class.php');
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_component.class.php');
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_course.class.php');
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_mapping.class.php');
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_program.class.php');
require_once($CFG->dirroot.'/local/apogee_rof_sync/DB_models/apogee_rof_person.class.php');

class apogee_rof_sync {
	private $array_liste_tables = array(	'rof_component',
										'rof_constant',
										'rof_course',
										'rof_person',
										'rof_program');
	private $user_oracle;
	private $passwd_oracle;
	private $base_oracle; // de type HOST:PORT/NOM_BDD
	
	public function __construct() {
		$this->getConfigOracle();
	}
	
/**
 * 
 * Récupération des paramêtres de connexion à APOGEE
 */
	public function getConfigOracle() {
		global $CFG;
		$this->user_oracle = $CFG->user_oracle;
		$this->passwd_oracle = $CFG->passwd_oracle;
		$this->base_oracle = $CFG->base_oracle;
	}

    /**
     *
     * Retourne l'année universitaire actuelle
     */
    private function getAnneeUniversitaire() {
        $conn = $this->OpenOracleConn() ;
        $SELECT_ANNEE = "select * from ANNEE_UNI where ETA_ANU_IAE = 'O'";
        $cursor = OCIParse($conn, $SELECT_ANNEE);
        $result = OCIExecute($cursor);
        $values = oci_fetch_assoc($cursor) ;
        oci_close($conn);
        return $values['COD_ANU'];

    }

	/**
	 * 
	 * Ouvre a connexion Oracle pour accéder à la BD APOGEE
	 */
	private function OpenOracleConn() {
		if (empty($this->user_oracle)||empty($this->passwd_oracle)||empty($this->base_oracle))
			$this->getConfigOracle();
		$cnxoracle = oci_connect($this->user_oracle, $this->passwd_oracle,$this->base_oracle,"AL32UTF8"); //"AMERICAN_AMERICA.WE8ISO8859P9");
		$cnxoracle = ocilogon($this->user_oracle, $this->passwd_oracle,$this->base_oracle,"AL32UTF8");
	    if ($cnxoracle == false) die("Connexion $base_oracle impossible ".OCIError($cnxoracle)."\n");
	    else return $cnxoracle; 
	}
	
  
	/**
	 * 
	 * Récupére les dates de synchronisation pour une table
	 * @param string $table
	 */
	public function getHistorySync($table) {
		global $DB;
		$select = "SELECT * FROM {rof_history} where table_libellee like ?";
		$obj =  $DB->get_record_sql($select,array($table));
		if (!empty($obj)) {
			return $obj->date_initialisation;
		}
		return null;
	}
	
	/**
	 * 
	 * Suppresion des enregistrements de la table $table
	 * @param string $table
	 */
	private function clearTable($table) {
		global $DB;
		if (in_array($table, $this->array_liste_tables)) $DB->delete_records($table);
	}
	
	/**
	 * 
	 * Mettre à jour la date de synchronisation de la table $table en fonction du type de synchro $type (initialize ou update)
	 * @param string $table
	 * @param string $type
	 */
	public function FillHistory($table, $type) {
		global $DB;
		$datesHistory = $this->getHistorySync($table);
		//$record = new stdClass();			
		if (empty($datesHistory)) {
			$sqlinsert = "INSERT INTO {rof_history} (table_libellee,date_initialisation,date_mise_a_jour)  
						 VALUES ('$table',NOW(),NOW())";
			$DB->execute($sqlinsert);
			
		} else {
			$sqlupdate='UPDATE {rof_history} SET ';
			$params = array();
			if ($type == 'initialize') {
				$sqlupdate .= ' date_initialisation=now()';
			} elseif ($type == 'update')  {
				$sqlupdate .= ' date_mise_a_jour=now()';
			}
			$sqlupdate .= ' WHERE table_libellee=?';
			$params[] = $table;
			$DB->execute($sqlupdate, $params);
		}
	}
	
	/**
	 * 
	 * Synchronisation des Composantes/UFR
	 * Il existe 2 mode Intialisation et Mise à jour. 
	 * L'initialisation éfface tous les éléments de la table puis importe les éléments pour les organiser en BDD
	 * La mise à jour est incrémentale
	 * @param string $typeSynchro
	 */
	public function SynchronizeComponent($typeSynchro = 'update') {
		$this->FillHistory('rof_component', $typeSynchro);
		if ($typeSynchro == 'initialize') {
			$this->clearTable('rof_component');
			$conn = $this->OpenOracleConn() ;
			$cursor = OCIParse($conn, "SELECT COD_CMP,LIB_CMP FROM apogee.composante WHERE (  UPPER(COD_TPC) like 'DPT' OR   UPPER(COD_TPC) like 'UFR' OR UPPER(COD_TPC) like 'INS'  OR UPPER(COD_TPC) like 'SC'   OR UPPER(COD_TPC) like 'EPR' )  order by cod_cmp");
			$result = OCIExecute($cursor); 
			while ($values = oci_fetch_assoc($cursor)) {
				$data = array();
				$data['rofid'] = '';
				$data['import'] 	= $values['COD_CMP'];
				$data['name'] 	= $values['LIB_CMP'];
				$data['number'] 	= $values['COD_CMP'];
				$DB_Apogee_Rof_Component = new apogee_rof_component($data);
				$DB_Apogee_Rof_Component->insert();
				unset($DB_Apogee_Rof_Component);
				unset($data);
			}
			oci_close($conn);
		} elseif ($typeSynchro == 'update') {
			
		}
	}
	
	/**
	 * 
	 * Synchronisation des Constantes
	 * Il existe 2 mode Intialisation et Mise à jour. 
	 * L'initialisation éfface tous les éléments de la table puis importe les éléments pour les organiser en BDD
	 * La mise à jour est incrémentale
	 * L'utilisation de ces constantes sont hérités de l'ancienne version de rof_sunc faite par silecs.
	 * Les constantes doivent être recréés pour que d'autres fonctionnalités fonctionnent
	 * @param string $typeSynchro
	 */
	public function SynchronizeConstant($typeSynchro = 'update') {
		$this->FillHistory('rof_constant', $typeSynchro);
		if ($typeSynchro == 'initialize') {
			$conn = $this->OpenOracleConn() ;
			$this->clearTable('rof_constant');
			$DB_Apogee_Rof_Constant = new apogee_rof_constant();
			//Dans la table component Recheche des nom de composantes
			$DB_Apogee_Rof_Component = new apogee_rof_component();
			$liste_components = $DB_Apogee_Rof_Component->getAllComponentsRecords();
			foreach($liste_components as $id=>$component) {
				$DB_Apogee_Rof_Constant->insertComponentConstant($component);
			}
			
			//CycleDiplome
			$DB_Apogee_Rof_Constant->insertCycleDiplome();
			
			//domaineDiplome
			$DB_Apogee_Rof_Program = new apogee_rof_program();
			$liste_domaine_diplome = $DB_Apogee_Rof_Program->getAllDomaineDiplome();
			foreach($liste_domaine_diplome as $id=>$domaine_diplome) {
				$DB_Apogee_Rof_Constant->insertDomaineDiplome($id);
			}
			
			// LangueDiplome
			// 2015-05-20 : Les langues ne sont pas renseignées à ce jour dans APOGEE
			
			//natureDiplome
			$SELECT_NATURE_DIPLOME = "SELECT COD_NIF, LIB_NIF FROM NIVEAU_FORMATION WHERE TEM_EN_SVE_NIF='O'";
			$cursor = OCIParse($conn, $SELECT_NATURE_DIPLOME);
			$result = OCIExecute($cursor); 
			while ($values = oci_fetch_assoc($cursor)) {
				$data = array(
				'element'		=> 'natureDiplome',
				'dataid'		=> $values['COD_NIF'],
				'dataimport'	=> $values['COD_NIF'],
				'dataoai'		=> $values['COD_NIF'],
				'value'			=> $values['LIB_NIF'],
				);
				$DB_Apogee_Rof_Constant->insert($data);
				unset($data);
			}
			
			//publicDiplome
			$liste_rythme_diplome = $DB_Apogee_Rof_Program->getAllRythmeDiplome();
			foreach($liste_rythme_diplome as $id=>$rythme_diplomes) {
				$DB_Apogee_Rof_Constant->insertRythmeDiplome($id);
			}

			//TypeDiplome
			$SELECT_TYP_DIPLOME = "SELECT COD_TPD_ETB, LIB_TPD FROM TYP_DIPLOME WHERE TEM_EN_SVE_TPD='O'";
			$cursor = OCIParse($conn, $SELECT_TYP_DIPLOME);
			$result = OCIExecute($cursor); 
			while ($values = oci_fetch_assoc($cursor)) {
				$data = array(
				'element'		=> 'typeDiplome',
				'dataid'		=> $values['COD_TPD_ETB'],
				'dataimport'	=> $values['COD_TPD_ETB'],
				'dataoai'		=> $values['COD_TPD_ETB'],
				'value'			=> $values['LIB_TPD'],
				);
				$DB_Apogee_Rof_Constant->insert($data);
				unset($data);
			}
			oci_close($conn);
		} elseif ($typeSynchro == 'update') {
		}
	}
	
	
	/**
	 * 
	 * Synchronisation des UE (level 1) et des sous niveaux (de 2 à 7)
	 * Il existe 2 mode Intialisation et Mise à jour. 
	 * L'initialisation éfface tous les éléments de la table puis importe les éléments pour les organiser en BDD
	 * La mise à jour est incrémentale
	 * @param string $typeSynchro
	 */
	public function SynchronizeCourse($typeSynchro = 'update') {
		$this->FillHistory('rof_course', $typeSynchro);
		if ($typeSynchro == 'initialize') {
			$this->clearTable('rof_course');
			$DB_Apogee_Rof_Program = new apogee_rof_program();
			$DB_Apogee_Rof_Course= new apogee_rof_course();
			for($level=1;$level<=7;$level++) {
				if ($level==1) $listCodeElp= $DB_Apogee_Rof_Program->getListCodElp(); else  $listCodeElp= $DB_Apogee_Rof_Course->getListCodElp($level-1);
				foreach($listCodeElp as $i=>$cod_elp) {
					$this->_synchronizeFilsElp($cod_elp, $level);
				}
			}
		} elseif ($typeSynchro == 'update') {
			
		}
	}
	
	
	/**
	 * 
	 * Recherche tous les fils d'un élément pédagogique et les insères dans la table rof_course
	 * @param string $cod_elp
	 * @param integer $level
	 */
	private function _synchronizeFilsElp($cod_elp,$level,$pere = null) {
		$DB_Apogee_Rof_Program = new apogee_rof_program();
		$conn = $this->OpenOracleConn() ;
		$SELECT_COURSES = "	SELECT  distinct ERE.COD_ELP_FILS AS CODE, ELP.LIB_ELP AS NAME,TYH.LIB_TYP_HEU AS COMPOSITION, ELP.COD_NEL
					 FROM APOGEE.elp_regroupe_elp ERE 
					 INNER JOIN ELEMENT_PEDAGOGI ELP on (ELP.COD_ELP= ERE.COD_ELP_FILS)
					 LEFT JOIN ELP_CHG_TYP_HEU ECT on (ELP.COD_ELP = ECT.COD_ELP)
					 LEFT JOIN TYPE_HEURE TYH on (ECT.COD_TYP_HEU = TYH.COD_TYP_HEU)
					 where ERE.DATE_FERMETURE_LIEN is null
					 and ELP.TEM_SUS_ELP  = 'N'
					 and ERE.COD_ELP_PERE='$cod_elp'
					 ";	
		$cursor = OCIParse($conn, $SELECT_COURSES);
		$result = OCIExecute($cursor);
		$cod_nel_a_prendre = array(
								'CHOI',
								'MATI',
								'MEM',
								'PAR',
								'PRJ',
								'SEM',
								'STAG',
								'UE97'
							);
		if ($level==1) $prefix_rof_parent = 'UP1-PROG-ELP-'; else $prefix_rof_parent = 'UP1-C-ELP-'; 
		while ($values = oci_fetch_array($cursor) ) {
			//if ( $values['COD_NEL']!='MACR' && $values['COD_NEL']!='SP1'&& $values['COD_NEL']!='SP1' ) {
			if ( in_array( $values['COD_NEL'], $cod_nel_a_prendre) ) {
				if ($pere) {
					$oneparent		=	$prefix_rof_parent.$pere;
	                $parents 		= 	$prefix_rof_parent.$pere;
				} else {
					$oneparent		=	$prefix_rof_parent.$cod_elp;
	                $parents 		= 	$prefix_rof_parent.$cod_elp;
				}
				$data = array(
					'rofid' 		=>	'UP1-C-ELP-'.$values['CODE'],
					'code' 			=>	$values['CODE'],
					'name'			=> 	$values['NAME'],
					'composition'	=> 	$values['COMPOSITION'],
					'oneparent'		=>	$oneparent,
	                'parents' 		=> 	$oneparent,
	            	'parentsnb'		=> 	1
				);
				$DB_Apogee_Rof_Course = new apogee_rof_course($level,$data);
				$DB_Apogee_Rof_Course->insert($level,$data);
				if ($level==1) 
					$DB_Apogee_Rof_Program->addCourse($oneparent, 'UP1-C-ELP-'.$values['CODE']);
				else
					$DB_Apogee_Rof_Course->addSub($oneparent, 'UP1-C-ELP-'.$values['CODE']);
				unset($DB_Apogee_Rof_Course);
				unset($data);
			} else {
				if (!$pere) $pere = $cod_elp;
				$this->_synchronizeFilsElp($values['CODE'],$level,$pere);
			}
			
		}
	 	oci_close($conn);
	}
	
	/**
	 * 
	 * Synchronisation des respondables pédagogiques
	 * Il existe 2 mode Intialisation et Mise à jour. 
	 * L'initialisation éfface tous les éléments de la table puis importe les éléments pour les organiser en BDD
	 * La mise à jour est incrémentale
	 * @param string $typeSynchro
	 */
	public function SynchronizePerson($typeSynchro = 'update') {
		$this->FillHistory('rof_person', $typeSynchro);
		if ($typeSynchro == 'initialize') {
			$this->clearTable('rof_person');
			// get List Cod_ELP by semestre to determine the Person in charge
			$conn = $this->OpenOracleConn() ;
			$DB_Apogee_Rof_Course = new apogee_rof_course();
			$listCodeElp= $DB_Apogee_Rof_Course->getListCodElp();
			foreach($listCodeElp as $i=>$cod_elp) {
				$SELECT_RESPONSABLES = "	SELECT 	PER.num_dos_har_per as num_harpege, 
													PER.lib_nom_pat_per as nom, 
													PER.lib_pr1_per as prenom, 
                         							up1.get_email_personnel(PER.cod_PER) as email
											FROM per_elp PLP
											INNER JOIN personnel PER ON PER.cod_per = PLP.cod_per
                     						WHERE PER.num_dos_har_per is not null
											AND PLP.cod_elp='$cod_elp'";
				$cursor = OCIParse($conn, $SELECT_RESPONSABLES);
				$result = OCIExecute($cursor);
				while ($values = oci_fetch_array($cursor)) {
					//
					$data = array(
								'rofid' 		=>	'UP1-PERS-'.$values['NUM_HARPEGE'],
								'givenname' 	=>	$values['PRENOM'],
								'familyname'	=> 	$values['NOM'],
								'email'			=> 	$values['EMAIL'],
								'oneparent'		=>	'UP1-C-ELP-'.$cod_elp
					);
					$DB_Apogee_Rof_Person = new apogee_rof_person($data);
					$DB_Apogee_Rof_Person->insert();
					$DB_Apogee_Rof_Person->addRefPersonToCourse('UP1-PERS-'.$values['NUM_HARPEGE'], 'UP1-C-ELP-'.$cod_elp);
					unset($DB_Apogee_Rof_Person);
					unset($data);
				}
			}
			unset($listCodeElp);
			$DB_Apogee_Rof_Program = new apogee_rof_program();
			$listCodeElp= $DB_Apogee_Rof_Program->getListCodElp();
			foreach($listCodeElp as $i=>$cod_elp) {
				// Ajout d'une requetes pour éviter les warning du à la function up1.get_email_personnel() avec des paramêtres vides
				$SELECT_NB_RESPONSABLES = "SELECT 	count(*) AS NUM_ROWS
											FROM per_elp PLP
											INNER JOIN personnel PER ON PER.cod_per = PLP.cod_per
                     						WHERE PER.num_dos_har_per is not null
											AND PLP.cod_elp='$cod_elp'";
				$cursornb = OCIParse($conn, $SELECT_NB_RESPONSABLES);
				OCIExecute($cursornb);
				$resultnb = oci_fetch_assoc($cursornb);
				if (!empty($resultnb['NUM_ROWS'])) {
					$SELECT_RESPONSABLES = "	SELECT 	PER.num_dos_har_per as num_harpege, 
														PER.lib_nom_pat_per as nom, 
														PER.lib_pr1_per as prenom, 
	                         							up1.get_email_personnel(PER.cod_PER) as email
												FROM per_elp PLP
												INNER JOIN personnel PER ON PER.cod_per = PLP.cod_per
	                     						WHERE PER.num_dos_har_per is not null
												AND PLP.cod_elp='$cod_elp'";
					$cursor = OCIParse($conn, $SELECT_RESPONSABLES);
					$result = OCIExecute($cursor);
					while ($values = oci_fetch_array($cursor)) {
						//
						$data = array(
									'rofid' 		=>	'UP1-PERS-'.$value['NUM_HARPEGE'],
									'givenname' 	=>	$value['PRENOM'],
									'familyname'	=> 	$value['NOM'],
									'email'			=> 	$value['EMAIL'],
									'oneparent'		=>	'UP1-PROG-ELP-'.$cod_elp
						);
						$DB_Apogee_Rof_Person = new apogee_rof_person($data);
						$DB_Apogee_Rof_Person->insert();
						$DB_Apogee_Rof_Person->addRefPersonToProgram('UP1-PERS-'.$value['NUM_HARPEGE'], 'UP1-PROG-ELP-'.$cod_elp);
						unset($DB_Apogee_Rof_Person);
						unset($data);
					}
				}
			}
		oci_close($conn);
		} elseif ($typeSynchro == 'update') {
			
		}
	}
	
	
	/**
	 * 
	 * Synchronisation des Diplômes (level 1) et Semestres (level2) 
	 * Il existe 2 mode Intialisation et Mise à jour. 
	 * L'initialisation éfface tous les éléments de la table puis importe les éléments pour les organiser en BDD
	 * La mise à jour est incrémentale
	 * @param string $typeSynchro
	 */
	public function SynchronizeProgram($typeSynchro = 'update') {
		$this->FillHistory('rof_program', $typeSynchro);
        $annee =$this->getAnneeUniversitaire();
		if ($typeSynchro == 'initialize') {
			$this->clearTable('rof_program');
						// Recherche au level 1 --> diplômes par composante
			$DB_Apogee_Rof_Component = new apogee_rof_component();
			$listeComponents = $DB_Apogee_Rof_Component->getAllComponents();
			$conn = $this->OpenOracleConn() ;
			foreach ($listeComponents as $id=>$component) {
				$SELECT_DIPLOMES = " 
  					SELECT DISTINCT  VDE.cod_dip ,VDE.cod_vrs_vdi ,
                             		LISTAGG(VDE.cod_etp,',') WITHIN GROUP (ORDER BY VDE.cod_etp) as ETPS,
                             		DIP.lib_dip as NAME , 
                            		SDS.LIB_SDS as DOMAINEDIP , 
                            		DIP.COD_TPD_ETB as TYPEDIP , 
                            		TPD.COD_NIF as NATUREDIP , 
                            		DIP.COD_CYC as CYCLEDIP , 
                            		LISTAGG(RGI.lic_rgi,',')  WITHIN GROUP (ORDER BY RGI.lib_rgi) RYTHMEDIP , 
                            		SVD.LIB_SVD as SPECIALITE , 
                            		MEV.lib_mev as MENTION 
					FROM vdi_fractionner_vet VDE
					INNER JOIN version_etape VET ON VET.cod_etp = VDE.cod_etp and VET.cod_vrs_vet = VDE.cod_vrs_vet
					INNER JOIN version_diplome VDI ON VDI.cod_dip = VDE.cod_dip and VDI.cod_vrs_vdi = VDE.cod_vrs_vdi
					INNER JOIN diplome DIP ON VDI.cod_dip = DIP.cod_dip
					INNER JOIN SEC_DIS_SIS SDS ON SDS.COD_SDS=DIP.COD_SDS
					LEFT JOIN RGI_AUTORISER_VET RVE ON VET.cod_etp  = RVE.cod_etp and VET.cod_vrs_vet = RVE.cod_vrs_vet
					LEFT JOIN  REGIME_INS RGI ON RGI.COD_RGI = RVE.COD_RGI
					LEFT JOIN  MENTION_VDI MEV ON VDI.COD_MEV = MEV.COD_MEV
					LEFT JOIN  SPECIALITE_VDI SVD ON SVD.COD_SVD=VDI.COD_SVD
					LEFT JOIN  TYP_DIPLOME TPD ON TPD.COD_TPD_ETB=DIP.COD_TPD_ETB
					WHERE '$annee' BETWEEN VDE.daa_deb_rct_vet and VDE.daa_fin_val_vet
                 		 	and RVE.COD_RGI NOT IN (3,6) 
					and VET.cod_cmp='$id'
					group by (VDE.cod_dip ,VDE.cod_vrs_vdi,DIP.lib_dip ,SDS.LIB_SDS,DIP.COD_TPD_ETB,TPD.COD_NIF,DIP.COD_CYC,SVD.LIB_SVD,MEV.lib_mev)";
				/**
				 * @todo Cette requête ne contient pas la languedip (Information non donnée dans Apogée)
				 */

				$cursor = OCIParse($conn, $SELECT_DIPLOMES);
				$result = OCIExecute($cursor);
				while ($values = oci_fetch_array($cursor)) {
					// creation d'un rofid => concaténation de UP1-PROG-<Code compoosante>-<Code du diplôme>-<Version du diplôme>
					// Ainsi pour retrouver les code de diplome et de version de diplôme
					// On "explodera" la chaîne (nécéssaire pour le niveau 2)
					$rofid = 'UP1-PROG-'.$id.'-'.$values['COD_DIP'].'-'.$values['COD_VRS_VDI'];
					echo $rofid.'<br />';
					$data = array(
								'rofid' 		=> $rofid,
								'name' 			=> $values['NAME'],
								'components' 	=> $id,
								'typedip' 		=> $values['TYPEDIP'],
								'domainedip' 	=> $values['DOMAINEDIP'],
								'naturedip' 	=> $values['NATUREDIP'],
								'cycledip' 		=> $values['CYCLEDIP'],
								'rythmedip' 	=> $values['RYTHMEDIP'],
								'specialite' 	=> $values['SPECIALITE'],
								'mention' 		=> $values['MENTION'],
								'parentsnb'		=> 1,
								'subnb'			=> 0,
								'sub'			=> '',
								'parents' 		=> $id,
								'oneparent' 	=> $id
					);
					
					$DB_Apogee_Rof_Program = new apogee_rof_program(1,$data);
					$DB_Apogee_Rof_Program->insert(1);
					$DB_Apogee_Rof_Component->addSub($id, $rofid);
					unset($DB_Apogee_Rof_Program);
					unset($data);
					// recherche au level 2 Semestres par diplômes	

                    foreach (explode(',', $values['ETPS']) as $COD_ETP) {
                    $COD_VRS_VDI = $values['COD_VRS_VDI'];
					$SELECT_LSE =
                        "SELECT VRL.cod_lse FROM vet_regroupe_lse VRL
                         WHERE VRL.cod_etp = '$COD_ETP' AND VRL.cod_vrs_vet = '$COD_VRS_VDI'
                         AND (VRL.dat_frm_rel_lse_vet > sysdate or VRL.dat_frm_rel_lse_vet is null)";


                    $cursor_LSE = OCIParse($conn, $SELECT_LSE);
                    OCIExecute($cursor_LSE);
                    while ($values_LSE = oci_fetch_array($cursor_LSE)) {
                        $COD_LSE = $values_LSE['COD_LSE'];
                        //echo "SUB $COD_ETP-$COD_VRS_VDI $COD_LSE\n";
                    
                    $todo = array("cod_lse = '$COD_LSE'");
                    $added = array();
                    
                    $SELECT_ELP_BASE =
                        "SELECT ERE.cod_elp_fils, ELP.cod_nel, ELP.cod_elp, ELP.lib_elp as NAME
                         FROM elp_regroupe_elp ERE
                         INNER JOIN element_pedagogi ELP ON ERE.cod_elp_fils = ELP.cod_elp
                         WHERE ELP.tem_sus_elp = 'N' AND ELP.eta_elp = 'O' 
                         AND ERE.date_fermeture_lien IS NULL
                         AND ERE.";
                    while ($todo) {
                        $one = array_shift($todo);
                        //echo "$COD_ETP: searching child $one\n";
                        $cursor2 = OCIParse($conn, "$SELECT_ELP_BASE$one");
                        $result = OCIExecute($cursor2);

					while ($values2 = oci_fetch_array($cursor2)) {
                        if ($values2['COD_NEL'] !== 'SEM') {
                            $cstrt = "cod_elp_pere = '" . $values2['COD_ELP'] . "'";
                            if (!isset($added[$cstrt])) {
                                //echo "$COD_ETP: got " . $values2['COD_ELP'] . " " . $values2['COD_NEL'] . ", will search for children for semester\n";
                                $added[$cstrt] = true;
                                array_push($todo, $cstrt);
                            }
                            continue;
                        }
					// creation d'un rofid => concaténation de UP1-PROG-ELP-<Code ELP>
					// Ainsi pour retrouver les code ELP
					// On "explodera" la chaîne (nécéssaire pour Synchroniser les cours)
                        //echo "$COD_ETP: found semester " . $values2['COD_ELP'] . "\n";
                        //continue;
						$rofid2 = 'UP1-PROG-ELP-'.$values2['COD_ELP'];
						$data = array(
									'rofid' 		=> $rofid2,
									'name' 			=> $values2['NAME'],
									'components' 	=> $id,
									'typedip' 		=> $values['TYPEDIP'],
									'domainedip' 	=> $values['DOMAINEDIP'],
									'naturedip' 	=> $values['NATUREDIP'],
									'cycledip' 		=> $values['CYCLEDIP'],
									'rythmedip' 	=> $values['RYTHMEDIP'],
									'specialite' 	=> $values['SPECIALITE'],
									'mention' 		=> $values['MENTION'],
									'parentsnb'		=> 1,
									'subnb'			=> 0,
									'sub'			=> '',
									'parents' 		=> $rofid,
									'oneparent' 	=> $rofid
						);
						
						$DB_Apogee_Rof_Program = new apogee_rof_program(2,$data);
						$DB_Apogee_Rof_Program->insert(2);
						$DB_Apogee_Rof_Program->addSub($rofid, $rofid2);
						unset($DB_Apogee_Rof_Program);
						unset($data);
					}
                    }
                    }
                    }
				}
				unset($cursor);
			}
			 oci_close($conn);
			$this->feuilleProgrameNiveau1();
		} elseif ($typeSynchro == 'update') {
			
		}
	}
	
	/**
	 * 
	 * Mapping des anciens Rofids, Rofpath avec la nouvelle nomenclature
	 * Cette méthode parcours la table mdl_custom_info_field et mdl_custom_info_data est permet de mettre à jour les ROFID.
	 * Cette méthode n'est nécéssaire que pour l'année de transition
	 * Table de référence mdl_mapping_rofids(NUM_OBJ,COD1_ORI_OBJ,COD2_ORI_OBJ,ROFID)
	 * -- NUM_OBJ : identifiant Autoincrémental
	 * -- COD1_ORI_OBJ 
	 * 		-- Pour les diplômes = COD_DIP, 
	 * 		-- Pour les semestres = COD_ELP, 
	 * 		-- Pour les personnes = UID, 
	 * 		-- Pour les cours = COD_ELP, 
	 * -- COD2_ORI_OBJ
	 * 		-- Pour les diplômes = COD_VRS_VDI, 
	 * 		-- NULL dans les autres cas
	 * ROFID : Anciens Rofid issus d'UNIFORM
	 * @param string $typeSynchro
	 */
	public function MappingRofIds() {
		global $DB;
		$retourrofid = '';
    	$nameofrowids = array();
		$this->FillHistory('mapping_rofids', 'initialize');
		$DB_Apogee_Rof_Mapping = new apogee_rof_mapping();
		$DB_Apogee_Rof_Mapping->RemoveDoubleTuples();//Eviter les doublons
		// récupération des fieldid pour les données pour up1rofpath, up1rofpathid, et up1rofid
		$liste_rofids = $DB_Apogee_Rof_Mapping->getAllDatas('up1rofid');
		$liste_rofpaths = $DB_Apogee_Rof_Mapping->getAllDatas('up1rofpath');
		$liste_rofpathids = $DB_Apogee_Rof_Mapping->getAllDatas('up1rofpathid');
		$liste_courses = $DB_Apogee_Rof_Mapping->getCourses();
		// Mapping des ROFIDS
		foreach($liste_rofids as $i=>$row_rofids) {
			$rofids = explode(';',$row_rofids->data);
			$data_remodeler = '';
			for($i=0;$i<count($rofids);$i++){
				if (!empty($data_remodeler)) {
					$retourrofid= $DB_Apogee_Rof_Mapping->getMappingInfo($rofids[$i]);
					if (empty($retourrofid)) {
						// on recherche dans la tabke vdivet_correspond_vdivet
						list($retourrofid,$retourrofname) = $this->getVdiVetCorrespondVdiVet($rofids[$i]);
					}
					else $data_remodeler.=';'.$retourrofid;
				} else {
					$data_remodeler.=$DB_Apogee_Rof_Mapping->getMappingInfo($rofids[$i]);
				}
			}
			$DB_Apogee_Rof_Mapping->update($data_remodeler, $row_rofids->id);
		}
		
		/**
		 * Pour le rof path on balaye tous les cours via la table mdl_custom_info_data
		 * puis on déconstruit les rofpathid et on remplace les rofid par les nouveaux
		 * pour chaque node on récupère le nom et on reconstruit en même temps le rofpath et le rofpathid
		 */
		foreach($liste_courses as $i=>$course) {
			 
			$courseid = $course->objectid;
			$datas = $DB_Apogee_Rof_Mapping->getInfoDataByCourse($courseid);
			$tab = array();
			foreach($datas as $j=>$data) {
				$tab[$data->shortname]['id'] = $data->id;
				$tab[$data->shortname]['data'] = $data->data;
			}
			if (!empty($tab['up1rofpathid']['id'] )&&!empty($tab['up1rofpathid']['data'])) {
				$listerofids = explode('/',$tab['up1rofpathid']['data']);
				$data_pathid_remodeler = '/'.$listerofids[1];
				$data_path_remodeler = '/'.$listerofids[1];
				for($k=2;$k<count($listerofids);$k++) {
					list($pathid,$path) = $DB_Apogee_Rof_Mapping->getMappingInfo($listerofids[$k],true);
					if (empty($pathid)&&empty($path)) {
						list($pathid,$path) = $this->getVdiVetCorrespondVdiVet($rofids[$i]);
					}
					$data_pathid_remodeler.= '/'.$pathid;
					$data_path_remodeler.= '/'.$path;
				}
				// Suppression des Doubles slashes
				$data2 = explode('/',$data_pathid_remodeler);
				$data_a_remodeler = '';
				for($i=0;$i<count($data2);$i++) {
					if (!empty($data2[$i])) $data_a_remodeler .='/'.$data2[$i];
				}
				$DB_Apogee_Rof_Mapping->update($data_a_remodeler, $tab['up1rofpathid']['id']);
			
				// Suppression des Doubles slashes
				$data3 = explode('/',$data_path_remodeler);
				$data_a_remodeler = '';
				for($i=0;$i<count($data3);$i++) {
					if (!empty($data3[$i])) $data_a_remodeler .='/'.$data3[$i];
				}
				$DB_Apogee_Rof_Mapping->update($data_a_remodeler, $tab['up1rofpath']['id']);
			}
		}
		
		/*
		 * @todo test en ajoutant l'année en début du rofpath
		 */
	}
	private function getVdiVetCorrespondVdiVet ($rofid) {
		global $DB;
		$select = "SELECT cod1_ori_obj,cod2_ori_obj from {mapping_rofids} where rofid like ? ";
        $obj = $DB->get_record_sql($select,array($rofid));

        if (preg_match('#PROG#i',$rofid)) {
        	if (empty($obj->cod1_ori_obj) && empty($obj->cod2_ori_obj))  return array('','');    
        	$explode = explode('-', $rofid);
        	if (!empty($explode[3])&&!empty($explode[4]))  {
	        	$conn = $this->OpenOracleConn();
	        	$SELECT = "	SELECT VCV.COD_DIP_NEW, VCV.COD_VRS_VDI_NEW, D.LIB_DIP 
	        				FROM vdivet_correspond_vdivet VCV 
	        				INNER JOIN DIPLOME D ON (VCV.COD_DIP_NEW=D.COD_DIP) 
	        				WHERE COD_DIP_OLD='".$obj->cod1_ori_obj."' 
	        				AND COD_VRS_VDI_OLD ='".$obj->cod2_ori_obj."'";
	        	$cursor = OCIParse($conn, $SELECT);
				$result = OCIExecute($cursor); 
				$values = oci_fetch_assoc($cursor);
				if (isset($values['COD_DIP_NEW'])&&isset($values['COD_VRS_VDI_NEW'])&&isset($values['LIB_DIP '])){
					$explode[3] = $values['COD_DIP_NEW'];
					$explode[4] = $values['COD_VRS_VDI_NEW'];
					return array( implode('-',$explode) ,$values['LIB_DIP']);
				}
	        	oci_close($conn);
        	} else return array('','');
        }
        return array('','');
	}
	
    private function feuilleProgrameNiveau1(){
    	/**
    	 * GLPI : 0049956
    	 * Pour les diplômes n'ayant pas de semestres on affichera les Etapes
    	 * Notament nécéssaire pour les Capactiés
    	 */
		global $DB;
        $annee =$this->getAnneeUniversitaire();
		$select = "SELECT id, rofid from {rof_program} where level=1 and subnb=0 ";
        $obj = $DB->get_records_sql($select,array());
	    $conn = $this->OpenOracleConn();
        foreach($obj as $i=>$rof) {
			$rofArray = explode('-',$rof->rofid);
			if (!empty($rofArray[3])&&!empty($rofArray[4])) {
				$SELECT_DIPLOMES = " 
  					select VET.COD_ETP, VET.COD_VRS_VET, ETP.LIB_ETP from VDI_FRACTIONNER_VET VDE inner join VERSION_ETAPE VET on (VET.COD_ETP=VDE.COD_ETP and VET.COD_VRS_VET=VDE.COD_VRS_VET) inner join etape ETP on (ETP.cod_etp = VET.cod_etp) 
  					where '$annee' BETWEEN VDE.daa_deb_rct_vet and VDE.daa_fin_val_vet
					and VDE.COD_DIP='".$rofArray[3]."' 
					and VDE.COD_VRS_VDI='".$rofArray[4]."'";
				$cursor = OCIParse($conn, $SELECT_DIPLOMES);
				$result = OCIExecute($cursor);
				while ($values = oci_fetch_array($cursor)) {
					$rofid = 'UP1-PROG-ETP-'.$values['COD_ETP'].'-'.$values['COD_VRS_VET'];
					$data = array(
								'rofid' 		=> $rofid,
								'name' 			=> $values['LIB_ETP'],
								'parentsnb'		=> 1,
								'subnb'			=> 0,
								'sub'			=> '',
								'parents' 		=> $rof->rofid,
								'oneparent' 	=> $rof->rofid
					);
					
					$DB_Apogee_Rof_Program = new apogee_rof_program(2,$data);
					$DB_Apogee_Rof_Program->insert(2);
					$DB_Apogee_Rof_Program->addSub($rof->rofid, $rofid);
					unset($DB_Apogee_Rof_Program);
					unset($data);
				}
			}
        }
 		oci_close($conn);
    }

    
    public function evolutionELP() {
    	global $DB; 
    	$select  =  "select id ,data from {custom_info_data}  where fieldid = 9 and data !=''";
    	$obj = $DB->get_records_sql($select,array());
    	foreach($obj as $i => $row) {
    		$reconstructiondata_array = explode(';',$row->data);
    		$reconstructiondata_string = '';
    		foreach ($reconstructiondata_array as $j=>$rofid) {
    			if ( (substr($rofid, 0,12)=='UP1-PROG-ELP') || (substr($rofid, 0,9)=='UP1-C-ELP') ) {
	    			
				$table ='';
				$entete_roid = '';
	    			if (substr($rofid, 0,12)=='UP1-PROG-ELP'){
				    $COD_ELP = substr($rofid,13,strlen($rofid));
				    $entete_rofid = 'UP1-PROG-ELP-';
				}
				if (substr($rofid, 0,9)=='UP1-C-ELP'){ 
				    $COD_ELP = substr($rofid,10,strlen($rofid));
				    $entete_rofid = 'UP1-C-ELP-';
				}
	    			if (substr($rofid, 0,12)=='UP1-PROG-ELP') {
	    				$table='rof_program';
	    			} else { 
	    				$table='rof_course';
	    			}
				$if_exist_into_rofcache = 'select rofid from {'.$table.'} where rofid = ?';
	    			$obj2 = $DB->get_records_sql($if_exist_into_rofcache,array($rofid)); 
	    			if (empty($obj2)) {
	    				$newelp = $this->getNewELP($COD_ELP);
	    				if (!empty($newelp)) $reconstructiondata_string .= $entete_rofid . "$newelp;"; else $reconstructiondata_string .= "$rofid;";
	    			} else {
	    				$reconstructiondata_string .= "$rofid;";
	    			}
    			} elseif ( (substr($rofid, 0,9)=='UP1-PROG-')) {
   /**
 TODO terminer recherche du diplome
  */
                            $cod_dip='';
                            $cod_vrs_dip='';
    			    $tabrofidsplited = explode('-', $rofid);
			    $INDICE_COD_DIP = count($tabrofidsplited)-2;
			    $INDICE_COD_VDI = count($tabrofidsplited)-1;
	echo 'old : '.$tabrofidsplited[$INDICE_COD_DIP].'-'.$tabrofidsplited[$INDICE_COD_VDI];
    			    list ($cod_dip , $cod_vrs_dip) = $this->getNewDip($tabrofidsplited[$INDICE_COD_DIP],$tabrofidsplited[$INDICE_COD_VDI]);
    			    if (!empty($cod_dip)) $tabrofidsplited[$INDICE_COD_DIP] = $cod_dip;
    			    if (!empty($cod_vrs_dip)) $tabrofidsplited[$INDICE_COD_VDI] = $cod_vrs_dip;
			    $reconstructiondata_string = implode('-',$tabrofidsplited).';';
  	echo '    new : '.$cod_dip.'-'.$cod_vrs_dip.'<br />';
                } else {
    				$reconstructiondata_string .= "$rofid;";
    			}
    		}
    		$sqlupdate = "UPDATE {custom_info_data} SET data = :reconstructiondata_string WHERE id = :rowid";
		$params = array (
			'reconstructiondata_string' => $reconstructiondata_string,
			'rowid' => $row->id
		);
		$DB->execute($sqlupdate, $params);
    	}

 /*       
        // Modif du ROFPATH
        $select = "select distinct objectid from {custom_info_data} where (fieldid = 7  and data !='') ";
        $obj = $DB->get_records_sql($select,array());
        foreach($obj as $i => $row) {
        	 $selectpathid  =  "select id,objectid ,data from {custom_info_data}  where fieldid = 7 and objectid= :objectid";
        	 $objpathid = $DB->get_record_sql($selectpathid,array('objectid'=>$row->objectid));
        	 $reconstructiondatapathid_array = explode('/',$objpathid->data);
             $reconstructiondatapathid_string = $reconstructiondatapathid_array[0].'/'.$reconstructiondatapathid_array[1];
			 
        	 $selectpathname =  "select id, objectid ,data from {custom_info_data}  where fieldid = 6 and objectid= :objectid";
        	 $objpathname = $DB->get_record_sql($selectpathname,array('objectid'=>$row->objectid));
        	 $reconstructiondatapathname_array = explode('/',$objpathname->data);
             $reconstructiondatapathname_string = $reconstructiondatapathname_array[0].'/'.$reconstructiondatapathname_array[1];
			 
			 if (count($reconstructiondatapathid_array) == count($reconstructiondatapathname_array)) {
			 	for ($cpt = 2; $cpt < count($reconstructiondatapathid_array);$cpt++) {
			 		$rofid = $reconstructiondatapathid_array[$cpt];
			 		if ( (substr($reconstructiondatapathid_array[$cpt], 0,12)=='UP1-PROG-ELP') || (substr($reconstructiondatapathid_array[$cpt], 0,9)=='UP1-C-ELP') ) {

                    	$table ='';
                        $entete_roid = '';
                        if (substr($rofid, 0,12)=='UP1-PROG-ELP'){
                        	$COD_ELP = substr($rofid,13,strlen($rofid));
                            $entete_rofid = 'UP1-PROG-ELP-';
                        }
                        if (substr($rofid, 0,9)=='UP1-C-ELP'){
                             $COD_ELP = substr($rofid,10,strlen($rofid));
                             $entete_rofid = 'UP1-C-ELP-';
                        }
                        if (substr($rofid, 0,12)=='UP1-PROG-ELP') {
                            $table='rof_program';
                        } else {
                            $table='rof_course';
                        }
                        $if_exist_into_rofcache = 'select rofid,name from {'.$table.'} where rofid = ?';
                        $obj2 = $DB->get_records_sql($if_exist_into_rofcache,array($rofid));
                        if (empty($obj2)) {
                            $newelp = $this->getNewELP($COD_ELP);
                            if (!empty($newelp)) {
                            	$reconstructiondatapathid_string .= $entete_rofid . "$newelp/"; 
                        		$selectname_into_rofcache = 'select name from {'.$table.'} where rofid = ?';
                       			$obj3 = $DB->get_record_sql($selectname_into_rofcache,array($entete_rofid . "$newelp"));
                       			if (!empty($obj3)) {
                       				$reconstructiondatapathname_string .= $obj3->name."/";
                       			}
                            }
                            else {
                            	$reconstructiondatapathid_string .= "$rofid/";
                        		$reconstructiondatapathname_string .= $reconstructiondatapathname_array[$cpt]."/";
                            }
                        } else {
                            $reconstructiondatapathid_string .= "$rofid/";
                        $reconstructiondatapathname_string .= $reconstructiondatapathname_array[$cpt]."/";
                        }
                    } else {
                        $reconstructiondatapathid_string .= "$rofid/";
                        $reconstructiondatapathname_string .= $reconstructiondatapathname_array[$cpt]."/";
                    }
			 	}
			 	
			 	// UPDATE ROF_ACHE
		echo 'ANCIEN DATA ROFPATHID : '.$objpathid->data.' <br />--> NOUVEAU ROFPATHID'.$reconstructiondatapathid_string.'<br />';
                $sqlupdate = "UPDATE {custom_info_data} SET data = :datapathid_string WHERE objectid = :objectid AND fieldid = :fieldid";
                $params = array (
                        'datapathid_string' => $reconstructiondatapathid_string,
                        'objectid' => $objpathid->objectid,
                	'fieldid' => 7
                );
                $DB->execute($sqlupdate, $params);	
                echo 'ANCIEN DATA ROFPATHNAME : '.$objpathname->data.' <br />--> NOUVEAU ROFPATHNAME'.$reconstructiondatapathname_string.'<br /><br />';
                $sqlupdate = "UPDATE {custom_info_data} SET data = :datapathname WHERE objectid = :objectid AND fieldid = :fieldid";
                $params = array (
                        'datapathname' => $reconstructiondatapathname_array,
                        'objectid' => $objpathname->objectid,
                	'fieldid' => 6
                );
                $DB->execute($sqlupdate, $params);			 	
			 	
			 	
			 }
             
        }
*/



    }
    
    private function getNewELP($COD_ELP) {
    	$conn = $this->OpenOracleConn();
    	$SELECT = "SELECT COD_ELP_CIBLE_LCC FROM ELP_CORRESPOND_ELP WHERE COD_ELP_S1_LCC = '$COD_ELP' OR COD_ELP_S2_LCC = '$COD_ELP'";
    	$cursor = OCIParse($conn, $SELECT);
	$result = OCIExecute($cursor);
	$value = oci_fetch_array($cursor);
	oci_close($conn);
	if (!empty($value['COD_ELP_CIBLE_LCC'])) return $value['COD_ELP_CIBLE_LCC'];
    	return '';
    }
    
    private function getNewDip($DIP,$VDI) {
    	$conn = $this->OpenOracleConn();
    	$SELECT = "SELECT COD_DIP_NEW AS DIP, COD_VRS_VDI_NEW AS VDI FROM VDIVET_CORRESPOND_VDIVET WHERE COD_DIP_OLD = '$DIP' AND COD_VRS_VDI_OLD = '$VDI'";
    	$cursor = OCIParse($conn, $SELECT);
	$result = OCIExecute($cursor);
	$value = oci_fetch_array($cursor);
	oci_close($conn);
	if (!empty($value['DIP']) &&  !empty($value['VDI']) ) return array($value['DIP'],$value['VDI']);
    	return array('','');
    }

}

