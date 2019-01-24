<?php
ini_set('max_execution_time', 600);
ini_set('memory_limit', '2048M');
require_once("../../config.php");
require_once('../../lib/accesslib.php');
require_once('apogee_rof_sync.class.php');
require_login();

if (is_siteadmin()) {
	$objApogeeRofSync = new apogee_rof_sync();
	if (!empty($_POST['table'])) {
		switch($_POST['table']) {
			case 'rof_component' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->SynchronizeComponent($_POST['type']);
				break;
			}
			case 'rof_constant' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->SynchronizeConstant($_POST['type']);
				break;
			}
			case 'rof_course' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->SynchronizeCourse($_POST['type']);
				break;
			}
			case 'rof_person' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->SynchronizePerson($_POST['type']);
				break;
			}
			case 'rof_program' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->SynchronizeProgram($_POST['type']);
				break;
			}
			case 'mapping_rofids' : {
				if (!empty($_POST['type'])) $objApogeeRofSync->MappingRofIds();
				break;
			}
		}
	}
}