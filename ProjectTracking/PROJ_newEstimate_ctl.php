
<?php 
require_once 'Utils/default_values.php';
session_name(SESSION_NAME);
session_start(); 
require_once 'Utils/common_functions.php';
require_once 'PROJ_newEstimate_dsp.php';
require_once 'PROJ_model.php';

echo "<div id='outer'>";
include("header_popup.php");
		
echo "<div id='content'>";
	///////////////////////////////////////////////////////////////
$conn = getDB2PConn($_SESSION['username'], $_SESSION['password']);
	
$authorized = chkAutUsr($conn, $_SESSION['username'], "LCCONLINE", 20);
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
	
	// If post, validate and write record or display errors
	if ($_POST != null) {
		// Project number must be numeric
		// Project number must exist
		// Estimated completion date must be date
		// Estimated completion date must be in the future
		// Low estimate must be numeric
		// Hi estimate must be numeric
		// Hi estimate must be greater than low estimate
		$updArray['PRPROJ#'] = $_POST['projNumber'];
		$updArray['PRPGMR'] = $_POST['PRPGMR'];
		$updArray['PRESTDATE'] = formatDateLCC($_POST['PRESTDATE']);
		$updArray['PRESTTIME'] = $now;
		$updArray['PRESTLOW'] = $_POST['lowEst'];
		$updArray['PRESTHI'] = $_POST['hiEst'];
		
		insertRecPRESTMTP($conn, $updArray);

		
	// Update project payback fields

		recalcProjPayBack($conn, $_POST['projNumber']);

	} else {  // POST not set. Display form.

		// Get Tool Tip records
		$toolTipRecs = getRecsPRTOOLTIPP($conn);
		
		$toolTips = array();
		
		foreach ($toolTipRecs as $tip) {
		$screenData['toolTip'][$tip['PTTFIELD']] = "<span onmouseover=\"tooltip.show('"
		. addslashes($tip['PTTTIPTXT']) . "');\" onmouseout='tooltip.hide();'>"
		. "<img src='images/Info_icon_20px.png' height='15' width='15' /></span>";
		}
		
		if (isset($_GET['projnum'])) {
			$screenData['projNum'] = $_GET['projnum']; 
		} else {
			$screenData['projNum'] = "";
		}
	
		if (isset($_SESSION['altUserNm'])) {
			$estimator = $_SESSION['altUserNm'];
		} else {
			$estimator = $user;
		}
		
		// Get estimator list
		$queryArray = getPgmrListPRIDTRANSP($conn);
	
		$selAttribs = array("id" => "projEstimator",
							"name" => "PRPGMR");
		$optAttribs = array("valueField" => "PGDEVPRF",
							"displayField" => "PGDEVPRF",
							"selectedCompareField" => "PGDEVPRF",
							"selectedCompareValue" => $estimator);
		
		$screenData['estimator'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
		$screenData['estDate'] = generateDateInput('newProjEstimate', 'PRESTDATE', "PRESTDATE", date('m/d/Y'));
	}

	showNewEstimate($screenData);

}
?>
