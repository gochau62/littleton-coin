<?php
require_once ("Utils/common_functions.php");

function saveProjChg() {
	echo "Changes Saved";
	header('Location: index.php');
}

function getNewProjDefaults($conn, $user) {
	
	$newProj['PR#'] = getNewProj($conn);
	$newProj['PRDESC'] = "";
	$newProj['PRRQST'] = $user;
	$newProj['PRDEPT'] = $_SESSION['department'];
	$newProj['PRSUBDEPT'] = $_SESSION['subdept'];
	putLCCOnlineLogRec("\n DEPARTMENT IS: " . $newProj['PRDEPT'] . " <<<<<<<<<");
	putLCCOnlineLogRec("\n SUB-DEPARTMENT IS: " . $newProj['PRSUBDEPT'] . " <<<<<<<<<");
	$newProj['PRUPTY'] = 9; // changed default from 0 to 9 per Philip 3/31/2011
	$newProj['PRSPONSR'] = getSpnsrByDeptPRSPNSRP($conn, trim($_SESSION['department']), trim($_SESSION['subdept']));
	$newProj['PRSPAPVDTE'] = 0;
	$newProj['PRSUBD'] = date("Ymd");
	$newProj['PRNEED'] = 0;
	$newProj['PRESTMTR'] = " ";
	$newProj['PRPGMR'] = " ";
	$newProj['PRITDEVGRP'] = " ";
	$newProj['PRWRKSTS'] = " ";
	$newProj['PRECOM'] = 0;
	$newProj['PRACOM'] = 0;
	$newProj['PRIMPDTE'] = 0;
	$newProj['PRESTR'] = 0;
	$newProj['PRTYPE'] = " ";
	$newProj['PRANLPLN'] = " ";
	$newProj['PRPLAN'] = " ";
	$newProj['PRRELPRJ#'] = 0;
	$newProj['PRRELSHIP'] = 0;
	$newProj['PRRESCOD'] = " ";
	$newProj['PRPRTY'] = 9; // changed default from 0 to 9 per Philip 3/31/2011
	$newProj['PRSCREVDTE'] = 0;
	$newProj['PRFORCE2SC'] = " ";
	$newProj['PRITREVDTE'] = 0; // need to add to display
	$newProj['PRAUTH'] = 0;
	$newProj['PROCST1'] = 0;
	$newProj['PROCSTA'] = 0;
	$newProj['PROSAV1'] = 0;
	$newProj['PROSAVA'] = 0;
	$newProj['PROPYBK'] = 0;
	$newProj['PRCCST1'] = 0;
	$newProj['PRCCSTA'] = 0;
	$newProj['PRCSAV1'] = 0;
	$newProj['PRCSAVA'] = 0;
	$newProj['PRCPYBK'] = 0;
	$newProj['PRDRAT'] = getProjectDeveloperRate($conn);
	$newProj['PRPMDT'] = 0;
	$newProj['PRPAYBKTYP'] = " ";
	$newProj['PRPBJSTF'] = " ";
	$newProj['PRBRAND'] = " ";
		
	return $newProj;
}
function getBlankProj() {
	
	$blankProj['PR#'] = 0;
	$blankProj['PRDESC'] = " ";
	$blankProj['PRRQST'] = " ";
	$blankProj['PRDEPT'] = " ";
	$blankProj['PRSUBDEPT'] = " ";
	$blankProj['PRUPTY'] = 0;
	$blankProj['PRSPONSR'] = " ";
	$blankProj['PRSPAPVDTE'] = 0;
	$blankProj['PRSUBD'] = 0;
	$blankProj['PRNEED'] = 0;
	$blankProj['PRESTMTR'] = " ";
	$blankProj['PRPGMR'] = " ";
	$blankProj['PRITDEVGRP'] = " ";
	$blankProj['PRWRKSTS'] = " ";
	$blankProj['PRECOM'] = 0;
	$blankProj['PRACOM'] = 0;
	$blankProj['PRESTR'] = 0;
	$blankProj['PRIMPDTE'] = 0;
	$blankProj['PRTYPE'] = " ";
	$blankProj['PRANLPLN'] = " ";
	$blankProj['PRPLAN'] = " ";
	$blankProj['PRRELPRJ#'] = 0;
	$blankProj['PRRELSHIP'] = 0;
	$blankProj['PRRESCOD'] = " ";
	$blankProj['PRPRTY'] = 0;
	$blankProj['PRSCREVDTE'] = 0;
	$blankProj['PRFORCE2SC'] = " ";
	$blankProj['PRITREVDTE'] = 0;
	$blankProj['PRAUTH'] = 0;
	$blankProj['PROCST1'] = 0;
	$blankProj['PROCSTA'] = 0;
	$blankProj['PROSAV1'] = 0;
	$blankProj['PROSAVA'] = 0;
	$blankProj['PROPYBK'] = 0;
	$blankProj['PRCCST1'] = 0;
	$blankProj['PRCCSTA'] = 0;
	$blankProj['PRCSAV1'] = 0;
	$blankProj['PRCSAVA'] = 0;
	$blankProj['PRCPYBK'] = 0;
	$blankProj['PRDRAT'] = 0;
	$blankProj['PRPMDT'] = 0;
	$blankProj['PRPAYBKTYP'] = " ";
	$blankProj['PRPBJSTF'] = " ";
	$blankProj['PRUSRACPT'] = " ";
	$blankProj['PRACPTDTE'] = 0;
		
	return $blankProj;
}

function getNewProj($conn) {
	
	// Get the Next Project Number from the DTAARA PROJNXT
			
	$nextNumber = "";
	
	$callStmnt = "Call PT0028S(?)";
	
	$stmt =  db2_prepare($conn, $callStmnt)
		or die ("Get next project number prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
		db2_bind_param($stmt, 1, "nextNumber", DB2_PARAM_OUT);
		
		// Execute the query
	db2_execute($stmt)
			or die("Get next project number execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
	return $nextNumber;
}

function getRecordPRPROJP($conn, $projNum) {
	$projRec = array();
	if ($projNum > 0) {
		
		$callStmnt = "Call PTS0013S(?)";
		
		$stmt =  db2_prepare($conn, $callStmnt)
			or die ("Get project record prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
		db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
			or die("Get project record execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		// Fetch the results
		$projRec = db2_fetch_assoc($stmt);
	}

	return $projRec;
	
}


// $projFields is an array containing field => values for
// for fields that are being updated. Changes will be logged
// so notifications can be sent.
function instupdtRecPRPROJP($conn, &$projFields, $mode) {
	
	if (isset($mode) && isset($projFields)) {
		$callStmnt = "Call PTS0027S("
					. "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "
					. "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "
					. "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "
					. "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "
//kjr
					. "?, ?, ?, ?, ?, ?, ?, ?)";
//kjr					
		$stmt = db2_prepare($conn, $callStmnt)
			or die ($mode . " PRPROJP rec prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		// add parameter values 
		$parm1 = (int) $projFields['PR#'];		
		$parm2 = $projFields['PRDESC'];		
		$parm3 = $projFields['PRRQST'];		
		$parm4 = $projFields['PRDEPT'];		
		$parm5 = $projFields['PRSUBDEPT'];		
		$parm6 = (int) $projFields['PRUPTY'];		
		$parm7 = $projFields['PRSPONSR'];		
		$parm8 = (int) $projFields['PRSPAPVDTE'];		
		$parm9 = (int) $projFields['PRSUBD'];		
		$parm10 = (int) $projFields['PRNEED'];		
		$parm11 = $projFields['PRESTMTR'];		
		$parm12 = $projFields['PRPGMR'];		
		$parm13 = $projFields['PRITDEVGRP'];		
		$parm14 = $projFields['PRWRKSTS'];		
		$parm15 = (int) $projFields['PRECOM'];		
		$parm16 = (int) $projFields['PRACOM'];		
		$parm17 = (int) $projFields['PRESTR'];		
		$parm18 = (int) $projFields['PRIMPDTE'];		
		$parm19 = $projFields['PRTYPE'];		
		$parm20 = $projFields['PRANLPLN'];		
		$parm21 = $projFields['PRPLAN'];		
		$parm22 = (int) $projFields['PRRELPRJ#'];		
		$parm23 = (int) $projFields['PRRELSHIP'];		
		$parm24 = $projFields['PRRESCOD'];		
		$parm25 = (int) $projFields['PRPRTY'];		
		$parm26 = (int) $projFields['PRSCREVDTE'];		
		$parm27 = $projFields['PRFORCE2SC'];		
		$parm28 = (int) $projFields['PRITREVDTE'];		
		$parm29 = (int) $projFields['PRAUTH'];		
		$parm30 = (double) $projFields['PROCST1'];		
		$parm31 = (double) $projFields['PROCSTA'];		
		$parm32 = (double) $projFields['PROSAV1'];		
		$parm33 = (double) $projFields['PROSAVA'];		
		$parm34 = (int) $projFields['PROPYBK'];		
		$parm35 = (double) $projFields['PRCCST1'];		
		$parm36 = (double) $projFields['PRCCSTA'];		
		$parm37 = (double) $projFields['PRCSAV1'];		
		$parm38 = (double) $projFields['PRCSAVA'];		
		$parm39 = (int) $projFields['PRCPYBK'];		
		$parm40 = (int) $projFields['PRDRAT'];		
		$parm41 = (int) $projFields['PRPMDT'];		
		$parm42 = $projFields['PRPAYBKTYP'];		
		$parm43 = $projFields['PRPBJSTF'];		
		$parm44 = $projFields['PRUSRACPT'];		
		$parm45 = (int) $projFields['PRACPTDTE'];
		if ($projFields['PRBRAND'] == 'All') {
			$parm46 = ' ';
		} else {
			$parm46 = substr($projFields['PRBRAND'], 0, 1);
		}
		$parm47 = $mode;
//kjr
		$parm48 = $projFields['PRPBJSTT'];
//kjr
		
		// add parameter values 
		db2_bind_param($stmt, 1, "parm1", DB2_PARAM_IN);		
		db2_bind_param($stmt, 2, "parm2", DB2_PARAM_IN);		
		db2_bind_param($stmt, 3, "parm3", DB2_PARAM_IN);		
		db2_bind_param($stmt, 4, "parm4", DB2_PARAM_IN);		
		db2_bind_param($stmt, 5, "parm5", DB2_PARAM_IN);		
		db2_bind_param($stmt, 6, "parm6", DB2_PARAM_IN);		
		db2_bind_param($stmt, 7, "parm7", DB2_PARAM_IN);		
		db2_bind_param($stmt, 8, "parm8", DB2_PARAM_IN);		
		db2_bind_param($stmt, 9, "parm9", DB2_PARAM_IN);		
		db2_bind_param($stmt, 10, "parm10", DB2_PARAM_IN);		
		db2_bind_param($stmt, 11, "parm11", DB2_PARAM_IN);		
		db2_bind_param($stmt, 12, "parm12", DB2_PARAM_IN);		
		db2_bind_param($stmt, 13, "parm13", DB2_PARAM_IN);		
		db2_bind_param($stmt, 14, "parm14", DB2_PARAM_IN);		
		db2_bind_param($stmt, 15, "parm15", DB2_PARAM_IN);		
		db2_bind_param($stmt, 16, "parm16", DB2_PARAM_IN);		
		db2_bind_param($stmt, 17, "parm17", DB2_PARAM_IN);		
		db2_bind_param($stmt, 18, "parm18", DB2_PARAM_IN);		
		db2_bind_param($stmt, 19, "parm19", DB2_PARAM_IN);		
		db2_bind_param($stmt, 20, "parm20", DB2_PARAM_IN);		
		db2_bind_param($stmt, 21, "parm21", DB2_PARAM_IN);		
		db2_bind_param($stmt, 22, "parm22", DB2_PARAM_IN);		
		db2_bind_param($stmt, 23, "parm23", DB2_PARAM_IN);		
		db2_bind_param($stmt, 24, "parm24", DB2_PARAM_IN);		
		db2_bind_param($stmt, 25, "parm25", DB2_PARAM_IN);		
		db2_bind_param($stmt, 26, "parm26", DB2_PARAM_IN);		
		db2_bind_param($stmt, 27, "parm27", DB2_PARAM_IN);		
		db2_bind_param($stmt, 28, "parm28", DB2_PARAM_IN);		
		db2_bind_param($stmt, 29, "parm29", DB2_PARAM_IN);		
		db2_bind_param($stmt, 30, "parm30", DB2_PARAM_IN);		
		db2_bind_param($stmt, 31, "parm31", DB2_PARAM_IN);		
		db2_bind_param($stmt, 32, "parm32", DB2_PARAM_IN);		
		db2_bind_param($stmt, 33, "parm33", DB2_PARAM_IN);		
		db2_bind_param($stmt, 34, "parm34", DB2_PARAM_IN);		
		db2_bind_param($stmt, 35, "parm35", DB2_PARAM_IN);		
		db2_bind_param($stmt, 36, "parm36", DB2_PARAM_IN);		
		db2_bind_param($stmt, 37, "parm37", DB2_PARAM_IN);		
		db2_bind_param($stmt, 38, "parm38", DB2_PARAM_IN);		
		db2_bind_param($stmt, 39, "parm39", DB2_PARAM_IN);		
		db2_bind_param($stmt, 40, "parm40", DB2_PARAM_IN);		
		db2_bind_param($stmt, 41, "parm41", DB2_PARAM_IN);		
		db2_bind_param($stmt, 42, "parm42", DB2_PARAM_IN);		
		db2_bind_param($stmt, 43, "parm43", DB2_PARAM_IN);		
		db2_bind_param($stmt, 44, "parm44", DB2_PARAM_IN);		
		db2_bind_param($stmt, 45, "parm45", DB2_PARAM_IN);
		db2_bind_param($stmt, 46, "parm46", DB2_PARAM_IN);
		db2_bind_param($stmt, 47, "parm47", DB2_PARAM_IN);
//kjr
		db2_bind_param($stmt, 48, "parm48", DB2_PARAM_IN);
//kjr
		
// 		echo "<br/>Parm 45 = " . $parm45 . "<br/>";
// 		echo "<br/>Parm 46 = " . $parm46 . "<br/>";
// 		echo "<br/>Parm 47 = " . $parm47 . "<br/>";
// 		echo "<br/>" . var_dump($projFields) . "<br/>";
// 		echo "<br/>" . var_dump($stmt) . "<br/>";
		
		db2_execute($stmt)
			or die ($mode . " PRPROJP rec execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	}
}

function updRecPRTOOLTIPP($conn, $field, $desc) {
	
	if (isset($conn) && !empty($field) && !empty($desc)) {
		$query = "Update PRTOOLTIPP Set PTTTIPTXT = ? Where PTTFIELD = ?";
		
		$executeArray[] = $desc;
		$executeArray[] = $field;
		
		// Prepare the SQL Query for execution
		$stmt = i5_prepare( $query, $conn ) 
			or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());

		// Execute the query
		i5_execute( $stmt, $executeArray )
			or die("<br>Execute failed! " . i5_errormsg());
			
	}

}

function getRecsPRTOOLTIPP($conn) { 
	
	if (isset($conn)) {
		
		$callStmnt = "Call PTS0016S()";
		
		$stmt =  db2_prepare($conn, $callStmnt)
			or die ("Get PRTOOLTIP recs prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
//		db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
			or die("Get PRTOOLTIP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$returnVal[] = $row;
		}
	}
	return $returnVal;	
}

function getSpnsrByDeptPRSPNSRP($conn, $dept, $subDept="ALL") {
	
	if (isset($conn) && isset($dept)) {
		$records = array();
		$callStmnt = "Call PTS0015S(?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get sponsor by dept prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "dept", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get sponsor by dept execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($rec = db2_fetch_assoc($stmt)) {
		    $records[]  = $rec;
		}
		
		$sponsor = $records[0]['PSSPNSRID'];	
		
		foreach ($records as $rec) {
		    if (trim($rec['PSSUBDEPT']) == trim($subDept)) {
		        $sponsor = $rec['PSSPNSRID'];
		        break;
		    }
		}
	}

	return $sponsor;
	
}

function getSpnsrListPRSPNSRP($conn, $dept) {

	$records = array();
	
	if (isset($conn) && isset($dept)) {
		
		$callStmnt = "Call PTS0015S(?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get sponsor list prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "dept", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get sponsor list execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($rec = db2_fetch_assoc($stmt)) {	
			$records[]  = $rec;
		}
	}

	return $records;
	
}

function getPgmrListPRIDTRANSP($conn) {

	$records = array();
	
	if (isset($conn)) {
		
		$callStmnt = "Call PTS0017S()";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get developer list prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
//		db2_bind_param($stmt, 1, "dept", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get developer list execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($rec = db2_fetch_assoc($stmt)) {	
			$records[]  = $rec;
		}
	}

	return $records;
	
}

function getRecsPRGROUPP($conn, $group="ALL") {

	$records = array();
	
	if (isset($conn)) {
		
		$callStmnt = "Call PTS0018S(?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get developer group list prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "group", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get developer group list execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($rec = db2_fetch_assoc($stmt)) {	
			$records[]  = $rec;
		}
	}

	return $records;
	
}

function getProjListAll($conn, $includeComplete, $includeRejected = "") {

	if (isset($conn)) {

		$callStmnt = "Call PTS0002S( ?, ? )";

	if ($stmt = db2_prepare($conn, $callStmnt)) {
	} else {
		echo "<br/>Prepare failed<br/>";
	}
	
		db2_bind_param($stmt, 1, "includeComplete", DB2_PARAM_IN);
		db2_bind_param($stmt, 2, "includeRejected", DB2_PARAM_IN);
	
	if (db2_execute($stmt)) {
	
		while ($row = db2_fetch_assoc($stmt)) {
			
			$returnVal[] = $row;

		} 
	} else {
		echo "No Fetch<br/>";
		echo "Error: " . db2_stmt_error() . "<br/>";
		echo "Error Msg: " . db2_stmt_errormsg() . "<br/>";
		
	}
		
	}
	return $returnVal;
	
}

function getRecPRAUTHP($conn, $user) {
	$authRec = array();
	if (isset($conn) && isset($user)) {
		
		$callStmnt = "Call PTS0014S(?)";
		
		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get PRAUTHP rec prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get PRAUTHP rec execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
			
		$authRec  = db2_fetch_assoc( $stmt );
		
	}

	return $authRec;
	
}

function getRecsPRAUTHP($conn, $filter="ALL") {
	// $filter = 'RQSTR' returns requestors
	// $filter = 'SPNSR' returns sponsors
	// $filter = 'PMNGR' returns project managers
	// $filter = 'ALL' returns all records
	$authRecs = array();
	if (isset($conn)) {
		
		$callStmnt = "Call PTS0019S(?)";
		
		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get PRAUTHP recs prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "filter", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get PRAUTHP recs execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($rec = db2_fetch_assoc($stmt)) {
			$authRecs[] = $rec;
		}
	}
	return $authRecs;
}

function getRecsPRESTMTP($conn, $projNum) { 
	
	$returnVal = array();
	
	if (isset($projNum) && isset($conn)) {
		$callStmnt = "Call PTS0005S( ? )";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get estimate records prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
		db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
			or die("Get estimate records execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
//				var_dump($row);
			$returnVal[] = $row;
		} 
	
	}
	return $returnVal;	
}
function insertRecPRESTMTP($conn, $fields) {
	$callStmnt = "Call PTS0006S(?, ?, ?, ?, ?, ?)";
	
	$stmt = db2_prepare($conn, $callStmnt)
				or die("Insert estimate prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
	$parm1 = (int) $fields['PRPROJ#'];
	$parm2 = $fields['PRPGMR'];
	$parm3 = (int) $fields['PRESTDATE'];
	$parm4 = (int) $fields['PRESTTIME'];
	$parm5 = (int) $fields['PRESTLOW'];
	$parm6 = (int) $fields['PRESTHI'];
	
	db2_bind_param($stmt, 1, 'parm1', DB2_PARAM_IN, DB2_DOUBLE);
	db2_bind_param($stmt, 2, 'parm2', DB2_PARAM_IN, DB2_CHAR);
	db2_bind_param($stmt, 3, 'parm3', DB2_PARAM_IN, DB2_DOUBLE);
	db2_bind_param($stmt, 4, 'parm4', DB2_PARAM_IN, DB2_DOUBLE);
	db2_bind_param($stmt, 5, 'parm5', DB2_PARAM_IN, DB2_DOUBLE);
	db2_bind_param($stmt, 6, 'parm6', DB2_PARAM_IN, DB2_DOUBLE);
	
	db2_execute($stmt)
				or die("Insert estimate execute error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
}

function getRecsPRTYPEP($conn) {
	$records = array();
	 
	if (isset($conn)) {
		$callStmnt = "Call PTS0021S()";
		$stmt = db2_prepare($conn, $callStmnt)
			or die("Get PRTYPEP recs prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_execute($stmt)
			or die("Get PRTYPEP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$records[] = $row;
		}
	}
	return $records;	

}
function getRecsPRSTATUSP($conn) {

	$records = array();
	
	if (isset($conn)) {
		$callStmnt = "Call PTS0024S()";

		$stmt = db2_prepare($conn, $callStmnt)
			or die("Get PRSTATUSP recs prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
		db2_execute($stmt)
			or die("Get PRSTATUSP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$records[] = $row;
		}
	}
	return $records;	
}
function getRecsPRRESCODEP($conn) {
	$records = array(); 
	if (isset($conn)) {
		
		$callStmnt = "Call PTS0023S()";

		$stmt = db2_prepare($conn, $callStmnt)
			or die("Get PRRESCODEP recs prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
		db2_execute($stmt)
			or die("Get PRRESCODEP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$records[] = $row;
		}
	}
	return $records;	
}

function recalcProjPayBack($conn, $proj) {
	if (isset($conn)) {
		$callStmnt = "Call PT5000S(?)";

		$stmt = db2_prepare($conn, $callStmnt) 
			or die("<br>Recalc payback prepare failed!" . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		db2_bind_param($stmt, 1, "proj", DB2_PARAM_IN);
			
		db2_execute($stmt) 
			or die("<br>Recalc payback execute failed!" . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");	
	}
}
function getRecsPRPAYBCKP($conn) {
	$records = array();
	
	if (isset($conn)) {
		$callStmnt = "Call PTS0020S()";
		
		$stmt =  db2_prepare($conn, $callStmnt)
			or die ("Get PRPAYBCKP recs prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	
		// Execute the query
		db2_execute($stmt)
			or die("Get PRPAYBCKP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$records[] = $row;
		}
	}
	return $records;
}
function getProjsTimeEntered($conn, $user, $startDate, $endDate) {
	if (isset($conn) && isset($user) && isset($startDate) && isset($endDate)) {

		$callStmnt = "Call PTS0001S( ?, ?, ? )";

		$stmt = db2_prepare($conn, $callStmnt);
	
		db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);
		db2_bind_param($stmt, 2, "startDate", DB2_PARAM_IN);
		db2_bind_param($stmt, 3, "endDate", DB2_PARAM_IN);

		if (db2_execute($stmt)) {
	
			while ($row = db2_fetch_assoc($stmt)) {
			
				$returnVal[] = $row['PT#'];

			} 
		} else {
			
			$returnVal = array();
//			echo "Error: " . db2_stmt_error() . "<br/>";
//			echo "Error Msg: " . db2_stmt_errormsg() . "<br/>";
		}
	}
	
	return $returnVal;	
	
}
function getProjUserTime($conn, $projNum, $userIn = "none") {
	
	$returnVal = array();
	
	if (isset($conn) && isset($projNum)) {

		if ($userIn != "none") {
			$callStmnt = "Call PTS0004S( ?, ? )";

			$stmt = db2_prepare($conn, $callStmnt);
	
			db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
			db2_bind_param($stmt, 2, "userIn", DB2_PARAM_IN);
			
		} else {
			$callStmnt = "Call PTS0003S( ? )";

			$stmt = db2_prepare($conn, $callStmnt);
	
			db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
			
		}

		if (db2_execute($stmt)) {
	
			while ($row = db2_fetch_assoc($stmt)) {
			
				$returnVal[] = $row;
			}
		}
	}
	
	return $returnVal;	
	
}

function getRecsPRNTCTYPP($conn) { 
	$returnVal = array();
	
	if (isset($conn)) {
		$callStmnt = "Call PTS0011S()";

		$stmt = db2_prepare($conn, $callStmnt) 
			or die("<br>Get PRNTCTYP prepare failed! <br>");
		
		db2_execute($stmt) 
			or die("<br>Get PRNTCTYP execute failed! <br>");	
		
		while ($row = db2_fetch_assoc( $stmt )) {
			$returnVal[] = $row;
		}
	}
	return $returnVal;	

}
function getRecsPRPLNDEFP($conn) { 
	$records = array();
	
	if (isset($conn)) {
		$callStmnt = "Call PTS0022S()";

		$stmt = db2_prepare($conn, $callStmnt) 
			or die("<br>Get PRPLNDEFP prepare failed!");
		
		db2_execute($stmt) 
			or die("<br>Get PRPLNDEFP execute failed!");	
		
		while ($row = db2_fetch_assoc( $stmt )) {
			$records[] = $row;
		}
	}
	return $records;	

}

function saveRecPRNTFPRFP($conn, $values) { 
	if (isset($conn) && isset($values)) {
		
		// Is this an update or insert?
		if ($ntfRecord = getRecsPRNTFPRFP($conn, $values['PNTUSER'], $values['PNTTYPE'], $values['PNTDETAIL'])) {
			// PTS0008S 
			$callStmnt = "Call PTS0009S(?, ?, ?, ?, ?, ?, ?)";
			
			$stmt = db2_prepare($conn, $callStmnt)
				or die ("Update chng pref prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
			$parm1 = $ntfRecord[0]['PNTUSER'];
			$parm2 = (int) $ntfRecord[0]['PNTLSTSNTD'];
			$parm3 = (int) $ntfRecord[0]['PNTLSTSNTT'];
			$parm4 = $values['PNTSNDMYCG'];
			$parm5 = $values['PNTFREQNCY'];
			$parm6 = $ntfRecord[0]['PNTTYPE'];
			$parm7 = $ntfRecord[0]['PNTDETAIL'];
		
			db2_bind_param($stmt, 1, "parm1", DB2_PARAM_IN);
			db2_bind_param($stmt, 2, "parm2", DB2_PARAM_IN);
			db2_bind_param($stmt, 3, "parm3", DB2_PARAM_IN);
			db2_bind_param($stmt, 4, "parm4", DB2_PARAM_IN);
			db2_bind_param($stmt, 5, "parm5", DB2_PARAM_IN);
			db2_bind_param($stmt, 6, "parm6", DB2_PARAM_IN);
			db2_bind_param($stmt, 7, "parm7", DB2_PARAM_IN);
		
			// Execute the query
			db2_execute($stmt)
			or die("Update chng pref execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
		} else {

			// Otherwise write a new record
			// PTS0008S 
			$callStmnt = "Call PTS0008S(?, ?, ?, ?, ?, ?, ?)";
			
			$stmt = db2_prepare($conn, $callStmnt)
				or die ("Insert chng pref prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
			$parm1 = $values['PNTUSER'];
			$parm2 = $values['PNTLSTSNTD'];
			$parm3 = $values['PNTLSTSNTT'];
			$parm4 = $values['PNTSNDMYCG'];
			$parm5 = $values['PNTFREQNCY'];
			$parm6 = $values['PNTTYPE'];
			$parm7 = $values['PNTDETAIL'];
		
			db2_bind_param($stmt, 1, "parm1", DB2_PARAM_IN);
			db2_bind_param($stmt, 2, "parm2", DB2_PARAM_IN);
			db2_bind_param($stmt, 3, "parm3", DB2_PARAM_IN);
			db2_bind_param($stmt, 4, "parm4", DB2_PARAM_IN);
			db2_bind_param($stmt, 5, "parm5", DB2_PARAM_IN);
			db2_bind_param($stmt, 6, "parm6", DB2_PARAM_IN);
			db2_bind_param($stmt, 7, "parm7", DB2_PARAM_IN);
		
			// Execute the query
			db2_execute($stmt)
			or die("Insert chng pref execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
		} 
	}
}
//-----------------------------------------------
// Pproject Change Notification Functions
//-----------------------------------------------
function buildProjChgTable($conn, $requestor, $freq) {
//--   Get all Rules    --//
	
	$allRules = getRules($conn, $requestor, $freq); // Sets $allRules
	
	$minDateTime = getMinDateTime($allRules); // Sets $minDate and $minTime
	
	$minDate = $minDateTime['minDate'];
	$minTime = $minDateTime['minTime'];
	$_SESSION['projChanges'] = array();
	
	if (empty($minDate) && empty($minTime)) {
		$chgTable = "<table><tr><th>No changes to report.</th></tr></table>";
	} else {

		$changes = getRecsPRCHGLOGP($conn, $minDate, $minTime);
	
		foreach ($changes as $key => $logRecord) {
			
			// If record doesn't fit any rules, delete it from array 
			$deleteYN = applyRules($conn, $logRecord, $allRules, $requestor); // function applyRules returns "Yes" (delete) or "No" (don't delete)
	
			if ($deleteYN == "Yes") { // function applyRules returns "Yes" (delete) or "No" (don't delete)
				unset($changes[$key]); // deletes record from array
			} 
		}
//--    loop through changes building email message body (table)  --//
		$chgTable = "";
		$curProj = 0;
	
		// kjr WO#74100 - change below to avoid fatal uncountable error - 04/55/26
		// if (count($changes) < 1) {
		if (count(is_countable($changes) ? $changes : []) < 1) {
			$chgTable .= "No changes to report";
		} else {
			$chgTable = "<table>"
						. "<th>Project #</th><th>Change Date</th>"
						. "<th>Change Time</th><th>What Changed</th>"
						. "<th>Who dun it</th>";
			foreach ($changes as $change) {

				$chgTable .= "<tr><td>" . $change['PCH#'] . "</td>" 
							. "<td>" . formatDate($change['PCHDATE']) . "</td>"
							. "<td>" . formatTime($change['PCHTIME']) . "</td>"
							. "<td>" . $change['PCHTEXT'] . "</td>"
							. "<td>" . $change['PCHUSER'] . "</td></tr>";
							
				
				$_SESSION['projChanges'][] = array("Project#" => $change['PCH#'],
												   "Change Date" => $change['PCHDATE'],
												   "Change Time" => $change['PCHTIME'],
												   "What Changed" => $change['PCHTEXT'],
												   "Who dun it" => $change['PCHUSER']);
			}
			$chgTable .= "</table>";
		}
	}
	return $chgTable;
}
function getRecsPRNTFPRFP($conn, $user, $nType = " ", $nDetail = " ") {
	
	if (isset($conn) && isset($user)) {

		$callStmnt = "Call PTS0010S(?, ?, ?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get chng pref prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);
		db2_bind_param($stmt, 2, "nType", DB2_PARAM_IN);
		db2_bind_param($stmt, 3, "nDetail", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
			or die("Get chng pref execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
			
		while ($row = db2_fetch_assoc( $stmt )) {
			$returnVal[] = $row;
		}
	}

	return $returnVal;
	
}

function insertPRCHGLOGP($conn, $values) {
	if (isset($conn) && isset($values)) {
	  if (count($values) == 7) {
		$values[] = 0;
		$values[] = 0;
	  }
		
	} 
	
	if (count($values) == 9) {
	
		$values[9] = ' ';
		$values[10] = ' ';
	}
	
	$callStmnt = "Call PTS0026S(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

	$stmt = db2_prepare($conn, $callStmnt)
		or die ("Insert chng log recs prepare error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	
	$parm1 = (int) $values[0];
	$parm2 = (int) $values[1];
	$parm3 = (int) $values[2];
	$parm4 = $values[3];
	$parm5 = $values[4];
	$parm6 = $values[5];
	$parm7 = $values[6];
	$parm8 = (double) $values[7];
	$parm9 = (double) $values[8];
	$parm10 = $values[9];
	$parm11 = $values[10];
	
	if ($parm10 == null) {
	    $parm10 = ' ';
	}
	
	
	putLCCOnlineLogRec("Parm1 is " . $parm1);
	putLCCOnlineLogRec("Parm2 is " . $parm2);
	putLCCOnlineLogRec("Parm3 is " . $parm3);
	putLCCOnlineLogRec("Parm4 is " . $parm4);
	putLCCOnlineLogRec("Parm5 is " . $parm5);
	putLCCOnlineLogRec("Parm6 is " . $parm6);
	putLCCOnlineLogRec("Parm7 is " . $parm7);
	putLCCOnlineLogRec("Parm8 is " . $parm8);
	putLCCOnlineLogRec("Parm9 is " . $parm9);
	putLCCOnlineLogRec("Parm10 is " . $parm10);
	putLCCOnlineLogRec("Parm11 is " . $parm11);
	
	putLCCOnlineLogRec("Parm1 type is " . gettype($parm1));
	putLCCOnlineLogRec("Parm2 type is " . gettype($parm2));
	putLCCOnlineLogRec("Parm3 type is " . gettype($parm3));
	putLCCOnlineLogRec("Parm4 type is " . gettype($parm4));
	putLCCOnlineLogRec("Parm5 type is " . gettype($parm5));
	putLCCOnlineLogRec("Parm6 type is " . gettype($parm6));
	putLCCOnlineLogRec("Parm7 type is " . gettype($parm7));
	putLCCOnlineLogRec("Parm8 type is " . gettype($parm8));
	putLCCOnlineLogRec("Parm9 type is " . gettype($parm9));
	putLCCOnlineLogRec("Parm10 type is " . gettype($parm10));
	putLCCOnlineLogRec("Parm11 type is " . gettype($parm11));
	
	db2_bind_param($stmt, 1, "parm1", DB2_PARAM_IN);
	db2_bind_param($stmt, 2, "parm2", DB2_PARAM_IN);
	db2_bind_param($stmt, 3, "parm3", DB2_PARAM_IN);
	db2_bind_param($stmt, 4, "parm4", DB2_PARAM_IN);
	db2_bind_param($stmt, 5, "parm5", DB2_PARAM_IN);
	db2_bind_param($stmt, 6, "parm6", DB2_PARAM_IN);
	db2_bind_param($stmt, 7, "parm7", DB2_PARAM_IN);
	db2_bind_param($stmt, 8, "parm8", DB2_PARAM_IN);
	db2_bind_param($stmt, 9, "parm9", DB2_PARAM_IN);
	db2_bind_param($stmt, 10, "parm10", DB2_PARAM_IN);
	db2_bind_param($stmt, 11, "parm11", DB2_PARAM_IN);
    
  	// Execute the query
   	db2_execute($stmt)
   		or die("Insert chng log recs execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
   	
}  

function getRules($conn, $requestor, $freq) {
	
	$allRules = getRecsPRNTFPRFP($conn, $requestor);
	
	// If request is for "both" or "daily"...
	if ($freq == "D" || $freq == 'W') {
		foreach ($allRules as $key => $rule) {
			if ($rule['PNTFREQNCY'] != $freq) {
				unset ($allRules[$key]);
			}
		}
	}
	return ($allRules);
}

function getMinDateTime($inArray) {
	
	$minDate = 0;
	$minTime = 0;
	$counter = 0;
	
	foreach ($inArray as $rec) {
		if ($counter == 0
			|| ($rec['PNTLSTSNTD'] < $minDate)
			|| ($rec['PNTLSTSNTD'] == $minDate && $rec['PNTLSTSNTT'] < $minTime)
			) {
			$minDate = $rec['PNTLSTSNTD'];
			$minTime = $rec['PNTLSTSNTT'];
			$counter = 1;
		}
	}
	$retVal['minDate'] = $minDate;
	$retVal['minTime'] = $minTime;
	
	return ($retVal);
}

function applyRules($conn, $logRecord, $allRules, $requestor) {
	
	$delete = "Yes";
	
	$projRecord = getRecordPRPROJP($conn, $logRecord['PCH#']);
	
	foreach($allRules As $rule) {
		switch ($rule['PNTTYPE']) {
				
			case "DP": // Specific department
				$parts = explode(" ", trim($rule['PNTDETAIL']));
				if ($projRecord['PRDEPT'] == $parts[0]) {
					$delete = "No";
					if (!empty($parts[1]) && $projRecord['PRSUBDEPT'] != $parts[1]) {
						$delete = "Yes";
					}
				} else {
					$delete = "Yes";
				}
				break;

			case "SP": // Specific Project
				if ((int) $projRecord['PR#'] == (int) $rule['PNTDETAIL']) {
					$delete = "No";
				} else {
					$delete = "Yes";
				}
				break;
				
		 	case "AP": // Any project
				$delete = "No";
		 		break;
				
		 	case "PS": // User is sponsor
		 		if ($projRecord['PRSPONSR'] == $requestor) {
		 			$delete = "No";
		 		} else {
		 			$delete = "Yes";
		 		}
		 		break;
				
		 	case "RQ": // User is requestor
				if ($projRecord['PRRQST'] == $requestor) {
		 			$delete = "No";
		 		} else {
		 			$delete = "Yes";
		 		}
		} //
		
		// If rule says "don't send changes I made" and user made this change
		if ($rule['PNTSNDMYCG'] != 'Y' && $requestor == $logRecord['PCHUSER']) {
			$delete = "Yes";
		}
		
		// If ANY one rule says do-not-delete then leave
		if ($delete == "No") {
			break; // get out of foreach loop. Do not delete record
		}
	}
	
	return $delete;
} 
function getRecsPRCHGLOGP($conn, $minDate, $minTime=0) {
	if (isset($conn) && isset($minDate)) {

		$callStmnt = "Call PTS0012S(?, ?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die ("Get chng log recs prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, "minDate", DB2_PARAM_IN);
		db2_bind_param($stmt, 2, "minTime", DB2_PARAM_IN);
		
		// Execute the query
		db2_execute($stmt)
		or die("Get chng log recs execute error: " . db2_stmt_error() . "<br/>" .
		"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		// Fetch the results
			
		while ($row = db2_fetch_assoc( $stmt )) {
			$returnVal[] = $row;
		}
	}
	return $returnVal;
}
function getProjReqstrData($conn, $userName) {
	$requestorData = array();
	if ($userName <> ' ') {

		$callStmnt = "Call PTS0032S(?)";

		$stmt =  db2_prepare($conn, $callStmnt)
		or die ("Get project requestor record prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		db2_bind_param($stmt, 1, "userName", DB2_PARAM_IN);

		// Execute the query
		db2_execute($stmt)
		or die("Get project requestor record execute error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		// Fetch the results
		$requestorData = db2_fetch_assoc($stmt);
	}

	return $requestorData;

}
function updRecsReqstr($conn, $screenData) {
	if (isset($conn) && isset($screenData)) {

		$callStmnt = "Call PTS0031S(?,?,?,?,?,?,?,?,?)";

		$stmt = db2_prepare($conn, $callStmnt)
		or die ("Get requestor recs prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");

		$parm1 = $screenData['userName'];
		$parm2 = $screenData['projRequestor'];
		$parm3 = $screenData['projSponsor'];
		$parm4 = $screenData['firstName'];
		$parm5 = $screenData['lastName'];
		$parm6 = $screenData['emailAddress'];
		$parm7 = $screenData['department'];
		$parm8 = $screenData['subdepartment'];
		$parm9 = $screenData['groupName'];

		db2_bind_param($stmt, 1, "parm1", DB2_PARAM_IN);
		db2_bind_param($stmt, 2, "parm2", DB2_PARAM_IN);
		db2_bind_param($stmt, 3, "parm3", DB2_PARAM_IN);
		db2_bind_param($stmt, 4, "parm4", DB2_PARAM_IN);
		db2_bind_param($stmt, 5, "parm5", DB2_PARAM_IN);
		db2_bind_param($stmt, 6, "parm6", DB2_PARAM_IN);
		db2_bind_param($stmt, 7, "parm7", DB2_PARAM_IN);
		db2_bind_param($stmt, 8, "parm8", DB2_PARAM_IN);
		db2_bind_param($stmt, 9, "parm9", DB2_PARAM_IN);

		// Execute the query
		db2_execute($stmt)
		or die("Get requestor recs execute error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	}
}

function getNavigationGroups($conn) {

	$navigationGroups = array();
	$callStmnt = "Call PTS0033S()";

	$stmt =  db2_prepare($conn, $callStmnt)
	or die ("Get navigation group record prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	// Execute the query
	db2_execute($stmt)
	or die("Get navigation group record execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	// Fetch the results
	// $navigationGroups = db2_fetch_assoc($stmt);
	// Fetch the results
	while ($rec = db2_fetch_assoc($stmt)) {
		$navigationGroups[] = $rec;
	}

	return $navigationGroups;

}

function getPRWKLDPRecs($conn) {

    // change result to empty array, avoid deprecation - kjr - 09/05/23
	$result = [];
	$sql = "Call PTS0035S()";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get PRWKLDP records prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	try {
		if (db2_execute($stmt)) {
				
			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get PRWKLDP records execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getPRsbmtPRecs($conn, $startDate, $endDate) {

    // change result to empty array, avoid deprecation - kjr - 09/05/23
    $result = [];
	$sql = "Call PTS0036S(?, ?)";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get Projects Submitted records prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	db2_bind_param($stmt, 1, "startDate", DB2_PARAM_IN);
	db2_bind_param($stmt, 2, "endDate", DB2_PARAM_IN);
	
	try {
		if (db2_execute($stmt)) {

			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get Projects Submitted records execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getPRcmpltPRecs($conn, $startDate, $endDate) {

	// change result to empty array, avoid deprecation - kjr - 09/05/23
	$result = [];
	$sql = "Call PTS0037S(?, ?)";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get Projects Completed records prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	db2_bind_param($stmt, 1, "startDate", DB2_PARAM_IN);
	db2_bind_param($stmt, 2, "endDate", DB2_PARAM_IN);

	try {
		if (db2_execute($stmt)) {

			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get Projects Completed records execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getPRscrevPRecs($conn) {

	// change result to empty array, avoid deprecation - kjr - 09/05/23
	$result = [];
	$sql = "Call PTS0038S()";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get Projects for SC Review records prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	try {
		if (db2_execute($stmt)) {

			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get Projects for SC Review records execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getPRFFPRecs($conn) {

	// change result to empty array, avoid deprecation - kjr - 09/05/23
	$result = [];
	$sql = "Call PTS0039S()";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get FF Projects for SC Review records prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	try {
		if (db2_execute($stmt)) {

			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get FF Projects for SC Review records execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getPRpMortPRecs($conn) {

	$result = false;
	$sql = "Call PTS0040S()";

	$stmt = db2_prepare($conn, $sql)
	or die ("Get Post Mort Projects for SC Review prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");

	try {
		if (db2_execute($stmt)) {

			while ($row = db2_fetch_assoc($stmt)) {
				$result[] = $row;
			}
		} else {
			throw new Exception("Get Post Mort Projects for SC Review execute error");
		}

	} catch(Exception $e) {
		$result = false;
	}


	return $result; // do not use () around variable

}
function getChildrenProjs($conn, $parentProj) {
// kjr 220182
    $result = [];
    $sql = "Call PTS0042S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("Get Children Project prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "parentProj", DB2_PARAM_IN);

    try {
        if (db2_execute($stmt)) {

            while ($row = db2_fetch_assoc($stmt)) {
                $result[] = $row;
            }
        } else {
            throw new Exception("Get Children Projects execute error");
        }

    } catch(Exception $e) {
        $result = false;
    }


    return $result; // do not use () around variable

}

function sumHoursForProject($conn, $project) {
    //kjr 220182
    $result = [];
    $sql = "Call PTS0043S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("Sum Hours for Project prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "project", DB2_PARAM_IN);

    try {
        if (db2_execute($stmt)) {

            while ($row = db2_fetch_assoc($stmt)) {
                $result[] = $row;
            }
        } else {
            throw new Exception("Sum Hours for Project execute error");
        }

    } catch(Exception $e) {
        $result = false;
    }

    return $result[0]['PROJTOT']; // do not use () around variable

}

//kjr
function getPaybackJustificationTypes($conn) {

    $curatedResults = array();
    $result1 = false;
    $sql = "Call PTS0044S()";

    $stmt = db2_prepare($conn, $sql)
    or die ("get Payback Justification Types error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    //db2_bind_param($stmt, 1, "project", DB2_PARAM_IN);

    try {
        if (db2_execute($stmt)) {

            while ($row = db2_fetch_assoc($stmt)) {
                $curatedResults[] = $row;
            }
        } else {
            throw new Exception("get Payback Justification Tyeps execute error");
        }

    } catch(Exception $e) {
        $result1 = false;
    }

    //for ($i = 0; $i < count($result); $i++) {
    //    $curatedResults[$i] = $result[$i]['PJDESC'];
    // }

    return $curatedResults;


}

function getPaybackJustificationTypeBasedOnProject($conn, $project) {

    $result = []; // declare as array, avoid PHP deprecation warning - kjr - 08/15/23
    $sql = "Call PTS0045S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("get Payback Justification Types Based on Project prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "project", DB2_PARAM_IN);

    try {
        if (db2_execute($stmt)) {

            while ($row = db2_fetch_assoc($stmt)) {
                
                    $result[] = $row;
                
            }
        } else {
            throw new Exception("get Payback Justification Types Based on Project execute error");
        }

    } catch(Exception $e) {
        $result = false;
    }

    return $result[0]['PRPBJSTT'];

}

function addNewProjectAction($conn, $projNum, $action, $active, $timestamp, $user) {

    $sql = "CALL PTS0046S(?,?,?,?,?)";
    $success = "failure";
    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0046S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
    
    db2_bind_param($stmt, 2, "action", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "active", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "timestamp", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "user", DB2_PARAM_IN);

     
    if (db2_execute($stmt)) {

        $success = "success";

    } else {
        $success = "failure";
        die("Get PTS0046S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    }
    return $success;

}

function getSequenceNumberOfProjectAction($conn, $projNum, $action, $active, $timestamp, $user) {

    $sql = "CALL PTS0047S(?,?,?,?,?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0047S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
    //db2_bind_param($stmt, 2, "year", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "action", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "active", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "timestamp", DB2_PARAM_IN);
    //db2_bind_param($stmt, 6, "dateDue", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "user", DB2_PARAM_IN);
     
    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)){
            $result[] = $row;
        }
    }
    else {
        die("Get PTS0047S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    }
    return $result;



}

function getProjectActionPlan($conn, $projNum) {

    $sql = "CALL PTS0048S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0048S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
    //db2_bind_param($stmt, 2, "year", DB2_PARAM_IN);
     
    if (db2_execute($stmt)) {
        while ($row =db2_fetch_assoc($stmt)){
            $result[] = $row;
        }
    }

    else {
        die("Get PTS0048S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }
    return $result;


}

function updateProjectActionState($conn, $projNum, $seqNum) {
    
    $sql = "CALL PTS0049S(?,?)";
    $success = "failure";
    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0049S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    
    db2_bind_param($stmt, 1, "projNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "seqNum", DB2_PARAM_IN);
     
    if (db2_execute($stmt)) {
    
        $success = "success";
    
    } else {
        $success = "failure";
        die("Get PTS0049S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    
    }
    return $success;
    
}

function getProjectActionItemsForUser($conn, $user) {


    $sql = "CALL PTS0050S(?)";
    $result = [];
    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0050S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);
    //db2_bind_param($stmt, 2, "year", DB2_PARAM_IN);
     
    if (db2_execute($stmt)) {
        while ($row =db2_fetch_assoc($stmt)){
            $result[] = $row;
        }
    }

    else {
        die("Get PTS0050S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }
    
    return $result;

}

function getProjectDeveloperRate($conn) {


    $sql = "CALL PTS0051S()";
    $result = [];
    $stmt = db2_prepare($conn, $sql)
    or die ("Get PTS0051S Recs prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");
     
    if (db2_execute($stmt)) {
        while ($row =db2_fetch_assoc($stmt)){
            $result[] = $row;
        }
    }

    else {
        die("Get PTS0051S Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }
    
    return $result[0]['PTSDEVRATE'];

}

//kjr

?>