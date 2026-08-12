<?php
	include("StartBlock.php");

?>
<!--javascript includes go here-->
<!--                          --EXAMPLE--                              -->
<!--<script type='text/javascript' src='PROJ_JS_functions.js'></script>-->
<script type='text/javascript' src='PROJ_JS_functions.js'></script>
<script type='text/javascript'> document.title = 'Project List' </script>
<script type="text/javascript">
	document.title = "Project Listing";
</script>

<!--  Begin Content Here -->
<?php 
//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline

$conn = getdb2PConn($user, $password);
$authorized = chkAutUsr($conn, $user, "LCCONLINE", 20); // 20 required for PMS

if ( $authorized != "yes") {
	// the framework's standard refusal page where the framework provides the call, and the same page drawn right here where it
	// does not, so a profile that is turned away always sees the message on the address it asked for and is never carried off it
	if (function_exists('showNotAuthorized')) {
		showNotAuthorized();
	} else {
		echo '<div style="background:#eef0fa;padding:1.5rem 1.75rem;margin:.5rem 0;' .
		     'color:#e01b24;font-style:italic;font-weight:bold;font-size:1.5rem;">' .
		     'You are not authorized to view the page requested</div>';
	}
} else {

//	php includes go here
	require_once("PROJ_list_dsp.php"); // don't forget to change file name here
	require_once("PROJ_model.php");
	require_once("LCEMPLOYP_model.php");
	
	// clear xls download array for project list
	$_SESSION['projList'] = array();
	
	$selectOptions = array("All my active projects",
							"My \"in-play\" projects",
							"All my projects",
							"All my departments projects",
							"All my sub-dept projects",
							"All projects");
	
	if (!isset($_POST['projListOption'])) {
		$ListFilter = "opt2";
		$selected = array(2 => "selected");
	} else {
		
		$ListFilter = $_POST['projListOption'];
		for ($i=1; $i<=(count($selectOptions)+1); $i++) {
			if ($_POST['projListOption'] == "opt$i") {
				$selected[$i] = "selected";
			} else {
				$selected[$i] = null;
			}
		}
	}
	
	if (isset($_POST['includeComplete'])) {
		$includeComp = $_POST['includeComplete'];
		$screenData['incChecked'] = "Checked";
	} else {
		$includeComp = "";
		$screenData['incChecked'] = null;
	}
	
	if (isset($_POST['includeRejected'])) {
		$includeRej = $_POST['includeRejected'];
		$screenData['incRejChecked'] = "Checked";
	} else {
		$includeRej = "";
		$screenData['incRejChecked'] = null;
	}
	
	$screenData['fName'] = trim($_SESSION['lcemployp']['LCFNAME']);

	$screenData['selectList'] = "<select name='projListOption' onchange='projSubmitProjList()'>";

	
	$i = 0;
	foreach ($selectOptions as $option) {
		$i += 1;
		$screenData['selectList'] .= "<option value='opt" . $i . "' " . $selected[$i] . ">" . $option . "</option>";
	}

    $screenData['selectList'] .= "</select>"; 
	
	$file = getProjListAll($conn, $includeComp, $includeRej);
	
//	foreach ($file as $key => $projRec) {
//		if ($screenData['incChecked'] != "Checked" && $projRec['PRACOM'] > 0) {
//			unset ($file[$key]);
//		}
//		if ($screenData['incRejChecked'] != "Checked" && $projRec['PRRESCOD'] == 'REJ') {
//			unset ($file[$key]);
//		}
//	}
	
	$screenData['projList'] = "<br/><table>";

	// Process the arry of field names writing column headings
	$screenData['projList'] .= "<th>Project #</th><th>Description</th><th>Dept Priority</th><th>SC Priority</th><th>Requestor</th><th>Programmer</th><th>Low<br/>Estimate</th><th>Hi<br/>Estimate</th><th>Hours To<br/>Date</th><th>Sched Comp Date</th>"; //<th>Time to date</th>"
	$screenData['projList'] .= "</tr>";

	// Print all records in file
	if (!empty($_SESSION['altUserNm']) ) {
		$compareUser = $_SESSION['altUserNm'];
	} else {
		$compareUser = $user;
	}
	foreach ($file as $key => $row) {
		if ($screenData['incChecked'] != "Checked" && $row['PRACOM'] > 0) {
			unset ($file[$key]);
			continue;
		}
		if ($screenData['incRejChecked'] != "Checked" && $row['PRRESCOD'] == 'REJ') {
			unset ($file[$key]);
			continue;
		}
		if ($row['PR#'] >= 90000 && $row['PR#'] <= 90999) {
			continue;
		}

		$programmer = $row['PRPGMR'];
		$requestor = $row['PRRQST'];

		// Get estimate info
		$estimate = getRecsPRESTMTP( $conn, $row['PR#']);
		$curEstimate = end($estimate);
		$lowEst = $curEstimate['PRESTLOW'];
		$hiEst = $curEstimate['PRESTHI'];
		
		// Get programming time
		$totHours = 0;
		$projTime = getProjUserTime($conn, $row['PR#']);
		foreach ($projTime as $timeRec) {
			$totHours += $timeRec['PTTIME'];
		}
		
//		$ListFilter = "opt1";
		$tblRow = "<tr>
					<td><a href=\"PROJ_ctl.php?projnum=".$row['PR#']."\">".$row['PR#']."</a>";
		if (isset($_SESSION['usrprf']['UPUSCL']) && $_SESSION['usrprf']['UPUSCL'] == '*PGMR') {
			$tblRow .= "<br/><small><a href='PROJ_timeEntry_ctl.php?addproj=". $row['PR#'] . "'>Add to TimeEntry</a></small>";
		}
			    
		$tblRow .= "</td><td>".$row['PRDESC']."</td>"
					. "<td>" . $row['PRUPTY'] . "</td>"
					. "<td>" . $row['PRPRTY'] . "</td>"
					. "<td>" . $requestor . "</td>"
					. "<td>" . $programmer . "</td>"
					. "<td class='numData'>" . $lowEst . "</td>"
					. "<td class='numData'>" . $hiEst . "</td>"
					. "<td class='numData'>" . $totHours . "</td>"
					. "<td class='numData'>" . formatDateSlashes($row['PRECOM']) . "</td>"
					."</tr>";
					
		$xlsDownload = array("Project #" => $row['PR#'], 
							"Description" => $row['PRDESC'], 
							"Dept Prty" => $row['PRUPTY'],
							"SC Prty" => $row['PRPRTY'],
							"Requestor" => $row['PRRQST'],
							"Programmer" => $row['PRPGMR'], 
							"Low Estimate" => $lowEst,
							"Hi Estimate" => $hiEst,
							"Hours to Date" => $totHours,
							"Sched Comp Date" => $row['PRECOM'],
							"Proj Type" => $row['PRTYPE']);
		$xlsDownload = array_merge($xlsDownload, $row);

		switch($ListFilter) {
			case "opt1": // My Active Projects
				if ((trim($row['PRRQST']) == trim($compareUser) 
					Or trim($row['PRSPONSR']) == trim($compareUser) 
					Or trim($row['PRPGMR']) == trim($compareUser))
						&& $row['PRACOM'] == 0) {
						$screenData['projList'] .= $tblRow;
						$_SESSION['projList'][] = $xlsDownload;
				}
				break;
			case "opt2": // My "In-Play" Projects
				if ((trim($row['PRRQST']) == trim($compareUser) 
					Or trim($row['PRSPONSR']) == trim($compareUser) 
					Or trim($row['PRPGMR']) == trim($compareUser))
						&& $row['PRECOM'] > 0 && $row['PRACOM'] == 0) {
						$screenData['projList'] .= $tblRow;
						$_SESSION['projList'][] = $xlsDownload;
				}
				break;
			case "opt3": // My Projects
				if (trim($row['PRRQST']) == trim($compareUser) 
					Or trim($row['PRSPONSR']) == trim($compareUser) 
					Or trim($row['PRPGMR']) == trim($compareUser)) {
						$screenData['projList'] .= $tblRow;
						$_SESSION['projList'][] = $xlsDownload;
				}
				break;
			case "opt4": // My Departments Projects
				if (trim($row['PRDEPT']) == trim($_SESSION['department'])) { 
					$screenData['projList'] .= $tblRow;
					$_SESSION['projList'][] = $xlsDownload;
				}
				break;
			case "opt5": // My Subdepartments projects
				if (trim($_SESSION['subdept']) != '' && trim($row['PRSUBDEPT']) == trim($_SESSION['subdept'])) {
					$screenData['projList'] .= $tblRow;
					$_SESSION['projList'][] = $xlsDownload;
					
				} 
				if (trim($_SESSION['subdept']) == '' && trim($row['PRDEPT']) == trim($_SESSION['department']))
				 {
					$screenData['projList'] .= $tblRow;
					$_SESSION['projList'][] = $xlsDownload;
				}
				break;
			case "opt6": // All projects
					$screenData['projList'] .= $tblRow;
					$_SESSION['projList'][] = $xlsDownload;
				break;
						
		}
		
	} // end of foreach loop

	$screenData['projList'] .= "</table>";
	
	dspProjList($screenData); // change funcName to match _dsp.php
?>
<!--  End Content Here -->

<?php

} //end authority check "if"

	include("EndBlock.php");
?>