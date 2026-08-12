<?php
	include("StartBlock.php");

?>
<!--javascript includes go here-->
<script type='text/javascript' src='PROJ_JS_functions.js'></script>
<script type='text/javascript' src='Utils/calendar_us.js'></script>

<script type="text/javascript">
	document.title = "Maintain Requestor and/or Sponsor";

	function nameToUpperCase(){
		var myUpperCase = document.getElementById("username").value.toUpperCase();
		document.getElementById("username").value = myUpperCase;
	};
</script>

<!--  Begin Content Here -->
<?php 

include("PROJ_model.php");
include("PROJ_Maint_Reqstr_dsp.php"); // don't forget to change file name here
include("LCDEPTP_model.php");

//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline
$authConn = getDB2PConn($user, $password);
$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 10);

$screenData = array();
$dspUser = array();
$requestorData = array();
$navigationGroups = array();

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
} 

else {
	
	// Display initial screen prompting a user name
	
	if ($_POST['userName'] == "") {
		dspUserName(); // change funcName to match _dsp.php
	}
	
	// Retrieve the data associated with the user name entered
	
	if (isset($_POST['userName']) && trim($_POST['userName']) != "") {
		
		$dspUser[] = ' ';
		$dspUser['userName'] = $_POST['userName'];
		 
		$conn = getDB2PConn($user, $password);
		$requestorData = getProjReqstrData($conn, $_POST['userName']);
		
		$dspUser['dspUserGroup'] = ' ';
		$dspUser['requestorFlg'] = $requestorData['PAREQSTR'];
		$dspUser['sponsorFlg'] = $requestorData['PASPONSR'];
		
		If ($dspUser['requestorFlg'] == ' ') {
			$dspUser['requestorFlg'] = 'N';
		}
		If ($dspUser['sponsorFlg'] == ' ') {
			$dspUser['sponsorFlg'] = 'N';
		}
		
		// New user routine - prompt for the navigation group the user belongs to
		
		If ($dspUser['requestorFlg'] == NULL) {
			
			$dspUser['requestorFlg'] = ' ';
			$dspUser['sponsorFlg'] = ' ';
			$dspUser['dspUserGroup'] = 'Y';
			
			unset($queryArray); // Clear array to avoid data drag
			
			$queryArray = getNavigationGroups($conn);
			
			$selAttribs = array("id" => "navigationGroups",
					"name" => "navigationGroups");
			
			$optAttribs = array("valueField" => "LDUSER",
					"displayField" => "LDUSER",
					"selectedCompareField" => "LDUSER",
					"selectedCompareValue" => $dspUser['LCGROUP']);
			
			$dspUser['LCGROUP'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
		}
		
		$dspUser['firstName'] = $requestorData['LCFNAME'];
		$dspUser['lastName'] = $requestorData['LCLNAME'];
		$dspUser['emailAddress'] = $requestorData['LCEMAILNM'];
		
		// Get Department list 
	    $queryArray = getRecsLCDEPTP($conn, "ALL", "   ");
	
	    $selAttribs = array("id" => "projRqstDept",
						"name" => "projRqstDept", 
						"onchange" => "reloadSubDept(\"projRqstDept\", \"projRqstSubDept\")");
	    $optAttribs = array("valueField" => "LDDEPT",
						"displayField" => "LDDESC",
						"selectedCompareField" => "LDDEPT",
						"selectedCompareValue" => $requestorData['LCLCCDEPT']);
	
	
	    $dspUser['html']['deptName'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	    
	    // Get Sub Department list
	    unset($queryArray); // Clear array to avoid data drag
	    
	    $queryArray = getRecsLCDEPTP($conn, $requestorData['LCLCCDEPT'], "ALL");
	    
	    $selAttribs = array("id" => "projRqstSubDept",
	    		"name" => "projRqstSubDept");
	    
	    $optAttribs = array("valueField" => "LDSUBDEPT",
	    		"displayField" => "LDDESC",
	    		"selectedCompareField" => "LDSUBDEPT",
	    		"selectedCompareValue" => $requestorData['LCSUBDEPT']);
	    
	    $dspUser['html']['subDeptName'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	     
	
		dspRequestMaint($dspUser); // change funcName to match _dsp.php
		
	}
		
	// Update the files with the changes
	
	if (isset($_POST['projRequestor']) && (trim($_POST['projRequestor']) != "" 
		|| trim($_POST['projSponsor']) != "")) {

		$screenData[] = ' ';
		$screenData['userName'] = $_POST['userName2'];
		$screenData['projRequestor'] = $_POST['projRequestor'];
		$screenData['projSponsor'] = $_POST['projSponsor'];
		$screenData['firstName'] = $_POST['firstName'];
		$screenData['lastName'] = $_POST['lastName'];
		$screenData['emailAddress'] = $_POST['emailAddress'];
		$screenData['department'] = $_POST['projRqstDept'];
		$screenData['subdepartment'] = $_POST['projRqstSubDept'];
		
		If ($_POST['navigationGroups'] <> Null) {
			$screenData['groupName'] = $_POST['navigationGroups'];
		}
		Else {
			$screenData['groupName'] = ' ';
		}
		
		$conn = getDB2PConn($user, $password);
	
		updRecsReqstr($conn, $screenData);
		
		$_POST['userName'] = ' ';
		$_POST['projRequestor'] = ' ';
	}
	
// <!--  End Content Here -->

} 

include("EndBlock.php");
?>