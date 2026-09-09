<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectDetail_ctl.php            *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/03/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */
?>

<?php
	// temporary while we chase the blank screen - fatals only
	ini_set('display_errors', '1');
	error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR);

	// retrieves and sets password and username
	require_once 'StartBlockScriptA.php';
	$user     = $_SESSION['username'];
	$password = $_SESSION['password'];
?>
<style>
/* the ProjectTracking look, applied to this screen's own markup */
/* the same palette the ProjectTracking screens use */
#stdPage {
    --pt-blue: #2a78d6; --pt-blue-dk: #1c5cab; --pt-red: #d03b3b;
    --pt-green: #008300; --pt-bg: #f6f7f9; --pt-card: #ffffff;
    --pt-line: #e4e7ec; --pt-line-soft: #eef0f4;
    --pt-text: #101828; --pt-muted: #667085; --pt-faint: #98a2b3;
    --pt-field: #d0d5dd; --pt-chip-gray: #f0f2f5;
    --pt-shadow: 0 1px 2px rgba(16, 24, 40, .05);

    font-family: "Segoe UI", -apple-system, system-ui, Roboto,
                 "Helvetica Neue", Arial, sans-serif;
    font-size: 14px; line-height: 1.45; color: var(--pt-text);
    background: var(--pt-bg); padding: 1rem; box-sizing: border-box;
}
#stdPage * { box-sizing: border-box; }

/* the title row: project number left, the buttons right */
#stdPage h1, #stdPage h2 { font-weight: 700; color: var(--pt-text); }
#stdPage h1 { font-size: 1.3rem; margin: 0 0 .6rem; }
#stdPage h2 { font-size: 1.05rem; margin: 0; display: inline-flex;
    align-items: center; gap: .5rem; }
#stdPage h2 u, #stdPage label u, #stdPage u { text-decoration: none; }
#stdPage #projectNumber { font-size: 1.15rem; font-weight: 700; width: 104px;
    text-align: center !important; letter-spacing: .02em; }

/* Print, Save Project, Discard Project */
#stdPage a { color: var(--pt-blue); text-decoration: none; font-weight: 600; }
#stdPage a:hover { text-decoration: underline; }
#stdPage input[type=button], #stdPage input[type=submit], #stdPage button {
    font: inherit; font-size: .82rem; font-weight: 600; cursor: pointer;
    padding: .42rem .8rem; border-radius: 8px;
    border: 1px solid var(--pt-line); background: var(--pt-card);
    color: var(--pt-text); margin-left: .35rem; }
#stdPage input[type=button]:hover, #stdPage button:hover {
    border-color: var(--pt-blue); color: var(--pt-blue); }
/* the save is the one action that carries the accent */
#stdPage input[value*="Save"], #stdPage input[value*="save"] {
    background: var(--pt-blue); border-color: var(--pt-blue); color: #fff; }
#stdPage input[value*="Save"]:hover { background: var(--pt-blue-dk);
    border-color: var(--pt-blue-dk); color: #fff; }
#stdPage input[value*="Discard"], #stdPage input[value*="Cancel"] {
    color: var(--pt-red); }
#stdPage input[value*="Discard"]:hover { border-color: var(--pt-red);
    color: var(--pt-red); background: #fceaea; }
#stdPage input[disabled], #stdPage button[disabled] {
    opacity: .5; cursor: default; }

/* the header's right-hand block keeps its buttons on one line */
#stdPage > div[style*="text-align: right"] { white-space: nowrap; }

/* the tab strip, the live tab reading as a raised card edge */
#stdPage .tabArea { border-bottom: 1px solid var(--pt-line);
    padding: .5rem 0 0; margin: .5rem 0 0; clear: both; }
#stdPage .tabArea br { display: none; }
#stdPage .tabArea a { display: inline-block; font-size: .86rem; font-weight: 600;
    color: var(--pt-muted); padding: .45rem .8rem; margin: 0 .1rem -1px 0;
    border: 1px solid transparent; border-bottom: 0;
    border-radius: 8px 8px 0 0; text-decoration: none; }
#stdPage .tabArea a:hover { color: var(--pt-text);
    background: var(--pt-line-soft); text-decoration: none; }
#stdPage .tabArea a.selected, #stdPage .tabArea a.active,
#stdPage .tabArea a[class*="sel"] { color: var(--pt-text);
    background: var(--pt-card); border-color: var(--pt-line); }

/* each tab's pane is a card */
#stdPage .pageSection { background: var(--pt-card);
    border: 1px solid var(--pt-line); border-top: 0;
    border-radius: 0 0 10px 10px; box-shadow: var(--pt-shadow);
    padding: 1rem 1.15rem 1.25rem; }

/* labels and fields */
#stdPage label { font-size: .82rem; font-weight: 600; color: var(--pt-muted);
    margin-right: .35rem; }
#stdPage input[type=text], #stdPage input[type=date], #stdPage input[type=number],
#stdPage select, #stdPage textarea {
    font: inherit; font-size: .86rem; color: var(--pt-text);
    background: var(--pt-card); border: 1px solid var(--pt-field);
    border-radius: 8px; padding: .38rem .55rem; }
#stdPage input[type=text]:focus, #stdPage input[type=date]:focus,
#stdPage select:focus, #stdPage textarea:focus {
    outline: 0; border-color: var(--pt-blue);
    box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
#stdPage input[readonly], #stdPage input[disabled], #stdPage select[disabled] {
    background: var(--pt-bg); color: var(--pt-muted); }
#stdPage input[type=checkbox], #stdPage input[type=radio] {
    vertical-align: middle; margin: 0 .3rem 0 0; }
#stdPage input.date { width: 130px; }
#stdPage input.userID { width: 130px; }
#stdPage input.numData, #stdPage .numData input { text-align: right; }

/* the tooltip icons the screen sprinkles before labels */
#stdPage img[src*="Info_icon"] { vertical-align: middle; opacity: .5;
    margin-right: .15rem; }
#stdPage img[src*="Info_icon"]:hover { opacity: 1; }

/* the breathing room the markup asks for with <br/> pairs */
#stdPage hr { border: 0; border-top: 1px solid var(--pt-line); margin: 1rem 0; }
#stdPage small { color: var(--pt-muted); font-size: .78rem; }
#stdPage .data { display: inline-block; font-weight: 600;
    padding: .1rem .45rem; border-radius: 6px;
    background: var(--pt-chip-gray); }

/* the payback grid and any other table the screen prints */
#stdPage table { border-collapse: collapse; margin: .3rem 0 .6rem;
    background: var(--pt-card); }
#stdPage caption, #stdPage CAPTION { text-align: left; font-size: .72rem;
    font-weight: 600; letter-spacing: .07em; text-transform: uppercase;
    color: var(--pt-muted); padding: 0 0 .4rem; }
#stdPage th { font-size: .68rem; font-weight: 600; letter-spacing: .04em;
    text-transform: uppercase; color: var(--pt-muted); text-align: left;
    padding: .45rem .55rem; border-bottom: 1px solid var(--pt-line);
    white-space: nowrap; }
#stdPage th.blank { border-bottom-color: transparent; }
#stdPage td { padding: .35rem .55rem; font-size: .84rem;
    border-bottom: 1px solid var(--pt-line-soft); }
#stdPage tbody tr:hover { background: var(--pt-line-soft); }
#stdPage td.numData { text-align: right; }
#stdPage tr.total td { font-weight: 700; border-top: 1px solid var(--pt-line);
    border-bottom: 0; }

/* the steering committee checklist, ticked or outstanding */
#stdPage ul { list-style: none; margin: .3rem 0 .8rem; padding: 0; }
#stdPage ul li { font-size: .86rem; padding: .14rem 0; color: var(--pt-text); }
#stdPage ul li img { vertical-align: middle; margin-right: .35rem; }

/* the action items box */
#stdPage fieldset { border: 1px solid var(--pt-line); border-radius: 10px;
    padding: .6rem .9rem 1rem; margin: .6rem 0; }
#stdPage legend { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
    text-transform: uppercase; color: var(--pt-muted); padding: 0 .3rem; }

/* comments and the editor blocks the WebNotes code drops in */
#stdPage .comment, #stdPage .webNote { background: var(--pt-bg);
    border: 1px solid var(--pt-line); border-radius: 8px;
    padding: .5rem .65rem; margin: .35rem 0; font-size: .85rem; }

</style>

<script type='text/javascript'>
	// the framework prints the body tag, so run the onload here
	window.addEventListener('load', function () {
		switchTab('PROJ_mainTabs', 'tabGeneral', 'pageSection', 'general');
	});
</script>

<script type='text/javascript' src='ckeditor/ckeditor.js'></script>
<script type='text/javascript' src='WebNotes/WebNote_JS_functions.js'></script>
<script type='text/javascript' src='Utils/calendar_us.js'></script>
<script type='text/javascript' src='PROJ_JS_functions.js'></script>
<link href="jQuery/jquery-ui-custom.css" rel="stylesheet"
	type="text/css" />
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='jQuery/jquery-ui.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.core.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.position.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.widget.js'></script>
<script type='text/javascript' src='jQuery/jquery.formatCurrency.js'></script>
<script type='text/javascript' src='jQuery/jquery.tablesorter.min.js'></script>
<script type='text/javascript' src='ckeditor/ckeditor.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />

<script type='text/javascript'>
function updCurEst(lowEst,hiEst) {
document.getElementById("CurLowEst").innerHTML = lowEst;
document.getElementById("curHiEst").innerHTML = hiEst;
projCalcPayback();
} 
</script>

<script type="text/javascript">
	document.title = "Project Detail";
</script>

<script type="text/javascript">
	var needToConfirm = true;
	var whichEditor = new Array;
	var whichParms = new Array;

	jQuery(document).ready(function() {
		
		$("#lgndRetailRange").hover(function() {
			$(this).css("cursor", "pointer");
			//$(this).css("cursor", "arrow");
		});
		
		$("#divRetailRange").hide();
		$("#lgndRetailRange").click(function() {
			$("#divRetailRange").toggle();
		});

		// create new action
		if ($('#projectNumber').val().trim() != '' ) {

			dataArray = {
	   				 action:         "getProjectActionPlan",
	   				 projNum:        $('#projectNumber').val().trim()
			             
			             };
	   		
	   		$.ajax({
	   			url: 'PROJ_ajax_request_post.php',
	   			data: dataArray,
	   		    datatype: 'json',
	   		    type: 'POST',
	   		    async: false,
	   			success: function(rtnData) {
	   				
	   				var json = JSON.parse(rtnData);
    	   				if (json != null) {
        	   				if (json.length > 0) {
        	   				for (var n = 0; n < json.length; n++) {
        	   					var sequenceNumber = json[n].CTSEQNUM;
        	   					var action = json[n].CTACTION;
        	   					var active = json[n].CTACTIVE;
        	   					var user = json[n].CTUSER;
        
        	   					var newLi = $('<li><input id =' + sequenceNumber + ' type="checkbox" value="' + action + '"> - <u>' + user + '</u> - ' + action + '</li>');
        	    				$('#actDtl ul').append(newLi);
        	    				console.log(newLi);
        	    				//newLi.fadeIn(500);
        	   					
        	   				}
    	   				}
	   				}
	   				

				}
	   		});

		}

		if ($('#hiddenUserClass').val() != '*PGMR') { // if user isn't in programmer class, hide action items - kjr 
			$('#lgndFldSet').hide();
		}

		$("#actDtl").on("change", "input", function () {
			
			var projectNumber = $('#projectNumber').val().trim();
			//var salesYear = $('#pg2YearSelect').val();
			var checkBoxId = this.id;
			
			var element = $(this).closest('li');
			
			swal({
				 title: "Complete current action?",
				 text: "Are you sure you want to complete this action and remove it from the actions list?",
				 type: "warning",
		 		 showCancelButton: true,
		         confirmButtonColor: "red",
		         cancelButtonColor: "blue",
		         confirmButtonText: "Continue",
		         cancelButtonText: "Cancel"
				   }, function (isConfirm) {
					   if (isConfirm) {
						 //KR210219
						   document.getElementById(checkBoxId).disabled = true;
						   // remove the action (<li>) from the list
						   
						   element.fadeOut(300, function() {
							   element.remove();
						   });
						 //KR210219-END
						   dataArray = {
				    				 action: "updateProjectActionState",             
						             seqNum:  checkBoxId
						             };
						
							$.ajax({
								url: 'PROJ_ajax_request_post.php',
								data: dataArray,
							    datatype: 'text',
							    type: 'POST',
							    async: false,
							    success: function(rtnData) {
							    	
							    	if (rtnData.trim() != 'success') {
							    		swal("Action update failed", "Problem updating database, refresh page and try again", "error");
							    		return;
							    	}
							    }
							});  
					   }
					   else {
						   $("#" + checkBoxId).prop("checked", false);
					   }
				   }
				);
			});
		
		$('#addActInfo').on("click", function() { 
		
		swal({
			 title: "Add Action for Project",
			 		showCancelButton: true,
			        html: true,
			        confirmButtonColor: "green",
			        confirmButtonText: "Add Action",
			        closeOnConfirm: false,
			        text: "Action:<br><br> <textarea rows='10' cols='60' maxlength='1000' id='addNewAction'></textarea><br><br>",
			        type:"input"
			   }, function () {
				   
				   var action = $('#addNewAction').val();
				   console.log($('#addNewAction').val());
				   //var dueDate = $('#actionDueDate').val();
			
						
						//fmtDueDate = slashDateToLcc(dueDate);
						var projectNumber = $.trim($('#projectNumber').val());
						//var year = date("Y");
						var dateToday = getSlashDateToday();
						var slashedDateToday = slashDateToLcc(dateToday);
						
						dataArray = {
			    				 	action:         "addNewProjectAction",
			    				 	projNum:        projectNumber,
			    				 	projAction:     action,
			    				 	active:         "Y",
			    				 	timestamp:      slashedDateToday,             
			    				 	user:           $('#hiddenUser').val()
					             	};
						
							$.ajax({
								url: 'PROJ_ajax_request_post.php',
								data: dataArray,
							    datatype: 'text',
							    type: 'POST',
							    async: false,
							    success: function(rtnData) {
							    	
							    	if (rtnData.trim() != 'success') {
								    	console.log("rtnData is: " + rtnData);
							    		swal("Table update failed", "Problem updating database, refresh page and try again", "error");
							    		return;
							    	}
							    	
							    	// append the new action to the action list
							    	else {
							    		dataArray = {
							    				 action:         "getSequenceNumberOfProjectAction",
							    				 projNum:        projectNumber,
									             projAction:     action,
									             active:         "Y",
									             timestamp:      slashedDateToday,              
									             user:           $('#hiddenUser').val()
									             };
							    		
							    		$.ajax({
							    			url: 'PROJ_ajax_request_post.php',
							    			data: dataArray,
							    		    datatype: 'json',
							    		    type: 'POST',
							    		    async: false,
							    			success: function(rtnData) {
							    				
							    				var json = JSON.parse(rtnData);
							    				var sequenceNumber = json[0].CTSEQNUM;
							    				var newLi = $('<li style="display:none"><input id =' + sequenceNumber + ' type="checkbox" value="' + action + '"> - <u>' + $('#hiddenUser').val() + '</u> - ' + action + '</li>');
							    				$('#actDtl ul').append(newLi);
							    				newLi.fadeIn(500);
							    				
							    			}
							    		});
							    		
							    		swal.close();
							    		return;
							    	}
							    }
							});
						
			   		});
				});

		
	});
</script>


<?php	
	
	require_once 'StartBlockScriptB.php';
	
// <!--  Begin Content Here -->
	require_once("WebNotes/webNotesModel.php");
//	require_once("Utils/common_functions.php");
	// the screen opens and closes its own stdPage div now
	require_once("PROJ_model.php");
	require_once("LCEMPLOYP_model.php");
	require_once("LNKDOCP_model.php");
	require_once("LCDEPTP_model.php");
	
	
	// get connection - user and password come from StartBlockScriptA.php
//	$conn = geti5PConn($user, $password);
	$conn2 = getDB2PConn($user, $password);
	
	// check user athourity
	if (chkAutUsr($conn2, $user, "LCCONLINE", 20) != "yes") {
		showNotAuthorized();
	} else {
	
	// Get PRAUTHP record
	if (isset($_SESSION['altUserNm'])) {
		$screenUser = $_SESSION['altUserNm'];
	} else {
		$screenUser = $user;
	}
	
	$projAuthority = getRecPRAUTHP($conn2, $screenUser);
	
	// Get PRPROJP record
	if ($_GET['projnum'] == 'newproj') {
		$projRecord = getNewProjDefaults($conn2, $screenUser);
	} else {
	    if (is_numeric($_GET['projnum'])) { // kjr - 09-06-2022 post PHP 8.1 upgrade
		$projRecord = getRecordPRPROJP($conn2, $_GET['projnum']);
	    }
	}
	
	// Get Tool Tip records
	$toolTipRecs = getRecsPRTOOLTIPP($conn2);
	
	$screenData = array();
	$toolTips = array();
	foreach ($toolTipRecs as $tip) {
		$toolTips['toolTip'][$tip['PTTFIELD']] = "<span onmouseover=\"tooltip.show('"
		. addslashes($tip['PTTTIPTXT']) . "');\" onmouseout='tooltip.hide();'>"
		. "<img src='images/Info_icon_20px.png' height='15' width='15' /></span>";
//		echo $tip['PTTFIELD'] . " = " . addslashes($tip['PTTTIPTXT']) . "<br/><br/>"; 
	}
//	echo "One Time Savings " . $screenData['toolTip']['1TimeSavng'];
	
	
//	var_dump($projRecord);
	
	$screenData = array_merge((array) $projAuthority, (array) $projRecord, (array) $toolTips);
	
	// Get LNKDOCP records 
	if (is_numeric($_GET['projnum'])) {	// kjr - 09-06-2022 post PHP 8.1 upgrade
	$screenData['linkedDocs'] = buldDocList($conn2, "PROJ_", ltrim($_GET['projnum'])); 
	}
	//
	// Format data for screen
	//
	if ($_GET['projnum'] == 'newproj') {
		$screenData['saveButton'] = "<input id='saveButton' type='button' value='Save Project' onclick='needToConfirm=false; saveProjChanges()'/>";
		$screenData['cancelButton'] = "<input id='cancelButton' type='button' value='Discard Project' onclick='needToConfirm=false; cancelProjChanges(\"" . $projRecord['PR#'] . "\")'/>";
	} else {
		$screenData['saveButton'] = "<input id='saveButton' type='button' value='Save Changes' onclick='needToConfirm=false; saveProjChanges()'/>";
		$screenData['cancelButton'] = "<input id='cancelButton' type='button' value='Cancel Changes' onclick='needToConfirm=false; cancelProjChanges(\"" . $projRecord['PR#'] . "\")'/>";
	}
	
	
	$screenData['PRDESC'] = trim(htmlentities(  ( is_null($screenData['PRDESC']) ? '' : $screenData['PRDESC'] )  ));

	// radio selection
	$screenData['tstType'] = "<input type='radio' name='tstType' onchange='selectFire()' value='regular' ";
	if ($projRecord['PRTYPE'] != 'FR' && $screenData['PRANLPLN'] != 'Y') {
		$screenData['tstType'] .= "checked "; 
	}
	$screenData['tstType'] .= "/> Regular &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	
	$screenData['tstType'] .= "<input type='radio' name='tstType' onchange='selectFire()' value='fire' ";
	if ($projRecord['PRTYPE'] == 'FR') {
		$screenData['tstType'] .= "checked "; 
	}
	$screenData['tstType'] .= "/> <img src='images/fire.png' alt='fire' height='25' width='25'></img> Fire  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	
	$screenData['tstType'] .= "<input type='radio' name='tstType' onchange='selectFire()' value='anualPlan' ";
	if ($screenData['PRANLPLN'] == 'Y') {
		$screenData['tstType'] .= "checked "; 
	}
	$screenData['tstType'] .= "/> For annual planning only";
	
	
	// format project created date
	$formName = "projForm";
	
	
	// Allow sponsors and project managers to edit these elements
	if ($screenData['PAPRJMNGR'] == 'Y' || $screenData['PASPONSR'] == 'Y'  || $_SESSION['usrclass'] == '*PGMR     ' || $_SESSION['usrclass'] == '*SYSOPR   ') {
		
		// Get Sponsor list
		unset($queryArray); // Clear array to avoid data drag
	
		$queryArray = getSpnsrListPRSPNSRP($conn2, "ALL");
		putLCCOnlineLogRec("Query array before remove: " . $queryArray . " <");
		rmvArrayDupesBySubKey($queryArray, "PSSPNSRID");
		putLCCOnlineLogRec("Query array after remove: " . $queryArray . " <");
		putLCCOnlineLogRec("PRSPONSR:" . $screenData['PRSPONSR']);
		$selAttribs = array("id" => "projSponsor", "name" => "projSponsor");
		$optAttribs = array("valueField" => "PSSPNSRID",
							"displayField" => "PSSPNSRID",
							"selectedCompareField" => "PSSPNSRID",
							"selectedCompareValue" => $screenData['PRSPONSR']);
	    
		$screenData['PRSPONSR'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
		// Sponsor approval date
		if ($screenData['PAPRJMNGR'] == 'Y' || $screenData['PASPONSR'] == 'Y') {
			$name_id = "projSponsAprvDate";
			$screenData['html']['PRSPAPVDTE'] = generateDateInput($formName, $name_id, "PRSPAPVDTE", $screenData['PRSPAPVDTE']);
		} else {
			$screenData['html']['PRSPAPVDTE'] = "<input class='date' readonly='true' name='projSponsAprvDate' value='" . formatDateSlashes($screenData['PRSPAPVDTE']) . "' />";
		}
	} else {
		// SPONSOR
		putLCCOnlineLogRec("\n Kyle Project Task");
		putLCCOnlineLogRec("\n PRSPONSR: " . $screenData['PRSPONSR']);
		$screenData['PRSPONSR'] = "<input class='userID' readonly='true' name='projSponsor' value='" . $screenData['PRSPONSR'] . "' />";
		putLCCOnlineLogRec("\n PRSPONSR AFTER: " . $screenData['PRSPONSR']);
		// Sponsor approval date
		$screenData['html']['PRSPAPVDTE'] = "<input class='date' readonly='true' name='projSponsAprvDate' value='" . formatDateSlashes($screenData['PRSPAPVDTE']) . "' />";
	}

		
	// Allow sponsors and programmers to edit these elements
	if ($screenData['PAPRJMNGR'] == 'Y' || $_SESSION['usrclass'] == '*PGMR     ' || $_SESSION['usrclass'] == '*SYSOPR   ') {
		// scheduled start date
		$name_id = "projSchdStart";
		putLCCOnlineLogRec("\n Form name = " . $formName . " <");
		putLCCOnlineLogRec("\n Name ID = " . $name_id . " <");
		//putLCCOnlineLogRec("\n Form name = " . $formName . " <");
		putLCCOnlineLogRec("\n screenData[PRESTR] = " . $screenData['PRESTR'] . " <");
		$screenData['html']['PRESTR'] = generateDateInput($formName, $name_id, "PRESTR", $screenData['PRESTR']);

		// scheduled completion date
		$name_id = "projSchdComp";
		$screenData['html']['PRECOM'] = generateDateInput($formName, $name_id, "PRECOM", $screenData['PRECOM']);
	
		// Actual Completion date
		$name_id = "projActComp";
		$screenData['html']['PRACOM'] = generateDateInput($formName, $name_id, "PRACOM", $screenData['PRACOM']);
		
		// Implemented date
		$name_id = "PRIMPDTE";
		$screenData['html']['PRIMPDTE'] = generateDateInput($formName, $name_id, "PRIMPDTE", $screenData['PRIMPDTE']);
		
		// Get Brand Options
		unset($queryArray); // Clear array to avoid data drag
	
		$queryArray = getRecsBRANDMSTP($conn2);
		$queryArray[] = array("BMBRAND" => " ",
							  "BMCOMPANYS" => "All"); 
		
		$selAttribs = array("id" => "projBrand", 
									"name" => "projBrand");//, 
//									"onchange" => "chgBrand()");
		$optAttribs = array("valueField" => "BMBRAND",
						"displayField" => "BMCOMPANYS",
						"selectedCompareField" => "BMBRAND",
						"selectedCompareValue" => $screenData['PRBRAND']);
	
		$screenData['PRBRAND'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
		
		// Get parent project description
		
		$parentProj = $screenData['PRRELPRJ#'];
		If ($parentProj != 0 and $parentProj != null) {
		   $parentProjRecord = getRecordPRPROJP($conn2, $parentProj);
		   $parentProjDesc = $parentProjRecord['PRDESC'];
		   If ($parentProjDesc == ' ' or $parentProjDesc == null) {
		       $screenData['parentProjDesc'] = '*** Project Number Not Found ***';
		   }
		   Else {
		      $screenData['parentProjDesc'] = $parentProjDesc;
		   }
		}
		
		// Get children project numbers and descriptions
		
        $currentProj = $screenData['PR#'];
	    $outputArray = getChildrenProjs($conn2, $currentProj);
	    
	    // Get summation of children project hours
	    
	    // verify not null to avoid deprecation warning - kjr - 08/10/23
	    if (!(is_null($outputArray[0]['PR#']))) {
    	    if (trim($outputArray[0]['PR#']) != '') {
    	        $childrenFlag = true;
    	        $sumTotalChildrenPrjHours = 0;
    	        //putLCCOnlineLogRec("Sum is set to " . $sumTotalChildrenPrjHours);
    	        for ($i = 0; $i < count($outputArray); $i++) {
    	    
    	            $sumTotalChildrenPrjHours += sumHoursForProject($conn2, $outputArray[$i]['PR#']);
    	    
    	        }
    	    }
	    }
	    //
	    
        $i=0;
        $totalRecs = 0;
        If ($outputArray != ' ' and $outputArray != null) {
            
            $screenData['childrenProjects'] .= "<table style=width:55%><th>Project #</th><th>Description</th></tr>";
            
        }
        
        if (is_array($outputArray)) { //check for result set array before iterating through it to avoid PHP Warning - kjr - 07-12-22
            foreach ($outputArray as $children) {
        
                // Update session array
    
                            
                If ($children['PR#'] > 0 and $children['PR#'] <= 999999) {
    	           $childProjNumber = $children['PR#'];
                }
                Else {
    	           $childProjNumber = 0; 
                }
                
                If ($children['PRDESC'] != null and $children['PRDESC'] != ' ') {
                    $childProjDesc = $children['PRDESC'];
                }
                Else {
                    $childProjDesc = ' ';
                }
                $screenData['childrenProjects'] .= "<tr><td>" . "<font size=2>" . trim($childProjNumber) . "</font>" . "</td>" . "<td>" . "<font size=2>" . trim($childProjDesc) . "</font>". "</td>" . "</tr>";
                
                
                $i++;
                
            }
        }
        
        If ($outputArray != ' ' and $outputArray != null) {
            
        
            $screenData['childrenProjects'] .= "</table>";
            
        }
        
        
		// Get Programmer list
		unset($queryArray); // Clear array to avoid data drag
	
		$queryArray = getPgmrListPRIDTRANSP($conn2);

		$selAttribs = array("id" => "projProgrammer", 
									"name" => "projProgrammer", 
									"onchange" => "chgProgrammer()");
		$optAttribs = array("valueField" => "PGDEVPRF",
						"displayField" => "PGDEVPRF",
						"selectedCompareField" => "PGDEVPRF",
						"selectedCompareValue" => $screenData['PRPGMR']);
	
		$screenData['PRPGMR'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
		
		// Get Developer Groups
		unset($queryArray); // Clear array to avoid data drag
	
		$queryArray = getRecsPRGROUPP($conn2);
	
		$selAttribs = array("id" => "projDevGrp", "name" => "projDevGrp");
		$optAttribs = array("valueField" => "PGGROUP",
						"displayField" => "PGGRPDESC",
						"selectedCompareField" => "PGGROUP",
						"selectedCompareValue" => $screenData['PRITDEVGRP']);
	
	
		$screenData['html']['PRITDEVGRP'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
		
		// Get assigned estimator
		$queryArray = getPgmrListPRIDTRANSP($conn2);
	
		$selAttribs = array("id" => "projEstimator",
							"name" => "projEstimator", 
							"onchange" => "chgEstimator()");
		$optAttribs = array("valueField" => "PGDEVPRF",
							"displayField" => "PGDEVPRF",
							"selectedCompareField" => "PGDEVPRF",
							"selectedCompareValue" => $screenData['PRESTMTR']);
		
		$screenData['html']['PRESTMTR'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
		$screenData['html']['PRAUTH'] = "<input type='text' name='projAuthHrs' style='text-align:right' size='3' maxlength='5' value='" . $screenData['PRAUTH'] . "' />";
		
	} else {
		// scheduled start date
		$screenData['html']['PRESTR'] = "<input class='date' readonly='true' name='projSchdStart' value='" . formatDateSlashes($screenData['PRESTR']) . "' />";

		// scheduled completion date
		$screenData['html']['PRECOM'] = "<input class='date' readonly='true' name='projSchdComp' value='" . formatDateSlashes($screenData['PRECOM']) . "' />";
		
		// Actual Completion date
		$screenData['html']['PRACOM'] = "<input class='date' readonly='true' name='projActComp' value='" . formatDateSlashes($screenData['PRACOM']) . "' />";
		
		// Implemented date 
		$screenData['html']['PRIMPDTE'] = "<input class='date' readonly='true' name='PRIMPDTE' value='" . formatDateSlashes($screenData['PRIMPDTE']) . "' />";
		
		// Brand
		$brandAry = getRecsBRANDMSTP($conn2, $screenData['PRBRAND']);
		if (count($brandAry) == 1) {
			$brand = $brandAry[0]['BMCOMPANYS'];
		} else {
			$brand = "All";
		}
		$screenData['PRBRAND'] = "<input readonly='true' size='12' name='projBrand' value='" . $brand . "' />";
		
		// Programmer
		$screenData['PRPGMR'] = "<input readonly='true' size='12' name='projProgrammer' value='" . $screenData['PRPGMR'] . "' />";
	
		// Developer Group
		$tmpResult = getRecsPRGROUPP($conn2, $screenData['PRITDEVGRP']);
		$screenData['html']['PRITDEVGRP'] = "<input disabled='true' size='10' name='projDevGrp' value='" . $tmpResult[0]['PGGRPDESC'] . "' />";
		unset($tmpResult);
		
		// Get assigned estimator
		$screenData['html']['PRESTMTR'] = "<input readonly='true' size='12' name='projEstimator' value='" . $screenData['PRESTMTR'] . "' />";
		
	}
	
	// Only allow Project manager to edit certain fields
	if ($screenData['PAPRJMNGR'] == 'Y') { // if user is project manager
		// created date - defaults to 'today' PM can override
		$name_id = "projCreateDate";
		$screenData['html']['PRSUBD'] = generateDateInput($formName, $name_id, "PRSUBD", $screenData['PRSUBD']);
		
		// postmortem date
		$name_id = "postMortDate";
		$screenData['html']['PRPMDT'] = generateDateInput($formName, $name_id, "PRPMDT", $screenData['PRPMDT']);
	
		// SC review date
		$name_id = "scRevDate";
		$screenData['html']['PRSCREVDTE'] = generateDateInput($formName, $name_id, "PRSCREVDTE", $screenData['PRSCREVDTE']);
		// insert "onchange" event
		$insrtPos = strpos($screenData['html']['PRSCREVDTE'], ">");
		$str1 = substr($screenData['html']['PRSCREVDTE'], 0, $insrtPos);
//		$str2 = " onchange='activateSave()'";
		$str3 = substr($screenData['html']['PRSCREVDTE'], $insrtPos);
		$screenData['html']['PRSCREVDTE'] = $str1.$str3;
		

		//Developement rate
		$screenData['html']['PRDRAT'] = "<input type='text' size='4' maxlength='7' onchange='projCalcPayback()' id='projDevRate' name='projDevRate' style='text-align:right' value='" . fmtTwoDecimal($screenData['PRDRAT']) . "' />";
		$developerRate = $screenData['PRDRAT'];
		$estimate = getRecsPRESTMTP( $conn2, $projRecord['PR#']);
		$curEstimate = end($estimate);
		$currHiEst = $curEstimate['PRESTHI'];
		$developerCost = $developerRate * $currHiEst;
		$developerCost = fmtTwoDecimal($developerCost);
		$screenData['developerCost'] = $developerCost;
		$origEstimate = $estimate[0]['PRESTHI'];
		$origDevCost = $developerRate * $origEstimate;
		$origDevCost = fmtTwoDecimal($origDevCost);
		$screenData['origDevCost'] = $origDevCost;
	
	} else { // readonly if not project manager
		// created date
		$screenData['html']['PRSUBD'] = "<input class='date' readonly='true' name='projCreateDate' value='" . formatDateSlashes($screenData['PRSUBD']) . "' />";
		
		
		// postmortem date
		$screenData['html']['PRPMDT'] = "<input class='date' readonly='true' name='postMortDate' value='" . formatDateSlashes($screenData['PRPMDT']) . "' />";
	
		// SC review date
		$screenData['html']['PRSCREVDTE'] = "<input class='date' readonly='true' name='scRevDate' value='" . formatDateSlashes($screenData['PRSCREVDTE']) . "' />";
		

		//Developement rate
		$screenData['html']['PRDRAT'] = "<input readonly='true' name='projDevRate' id='projDevRate' size='3' value='" . fmtTwoDecimal($screenData['PRDRAT']) . "' />";
		$developerRate = $screenData['PRDRAT'];
		$estimate = getRecsPRESTMTP( $conn2, $projRecord['PR#']);
		$curEstimate = end($estimate);
		$currHiEst = $curEstimate['PRESTHI'];
		$developerCost = $developerRate * $currHiEst;
		$developerCost = fmtTwoDecimal($developerCost);
		$screenData['developerCost'] = $developerCost;
		$origEstimate = $estimate[0]['PRESTHI'];
		$origDevCost = $developerRate * $origEstimate;
		$origDevCost = fmtTwoDecimal($origDevCost);
		$screenData['origDevCost'] = $origDevCost;
		

		$screenData['html']['PRAUTH'] = "<input type='text' readonly='true' name='projAuthHrs' style='text-align:right' size='3' value='" . $screenData['PRAUTH'] . "' />";
	}
	
	$name_id = "projNeedBy";
	$screenData['html']['PRNEED'] = generateDateInput($formName, $name_id, "PRNEED", $screenData['PRNEED']);
	
	//kjr - The new field will need to go here!!!! Shall I use the 'loadListboxFromArray' function found below? - kjr **** /
	putLCCOnlineLogRec("Project number is: " . $projRecord['PR#']);
	$dftPbkJstTpe = getPaybackJustificationTypeBasedOnProject($conn2, $projRecord['PR#']);
	$arrayOfTypes = getPaybackJustificationTypes($conn2);
	putLCCOnlineLogRec("Project's recorded payback justification type is: " . $dftPbkJstTpe);
	putLCCOnlineLogRec("Type of just. types variable is: " . gettype($arrayOfTypes));
	putLCCOnlineLogRec("Just, types variable is: " . $arrayOfTypes);
	putLCCOnlineLogRec("Att1: " . $arrayOfTypes[0]);
	//putLCCOnlineLogRec("Att2: " . $arrayOfTypes[0][0]);
	//putLCCOnlineLogRec("Att3: " . $arrayOfTypes[0]['PJDESC']);
	//putLCCOnlineLogRec("Att4: " . $arrayOfTypes['PJDESC'][0]);
	/*if ($dftPbkJstTpe != ' ' and $dftPbkJstTpe != '-') {
	 $screenData['html']['PJDESC'] = loadListboxFromArray($arrayOfTypes, $dftPbkJstTpe);
	 }
	 else {*/
	$selAttbs = array("id" => "projPaybackJustType", "name" => "projPaybackJustType");
	$optAttribs = array("valueField" => "PJDESC",
	    "displayField" => "PJDESC",
	    "selectedCompareField" => "PJDESC",
	    "selectedCompareValue" => $dftPbkJstTpe);
	 
	$screenData['html']['PJDESC'] = loadListboxFromArray($arrayOfTypes, $selAttbs, $optAttribs);
	//}
	
	//$screenData['html']['PJDESC'] = getPaybackJustificationTypes($conn);
	// Get ID for Requestor
	unset($queryArray); // Clear array to avoid data drag
	
	$queryArray = getRecsPRAUTHP($conn2, "RQSTR");
	$selAttribs = array("id" => "projRequester", "name" => "projRequestor");
	$optAttribs = array("valueField" => "PAUSER",
						"displayField" => "PAUSER",
						"selectedCompareField" => "PAUSER",
						"selectedCompareValue" => $screenData['PRRQST']);
	
	
	$screenData['PRRQST'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);

	// Get Department list
	$queryArray = getRecsLCDEPTP($conn2, "ALL", "   ");
	
	$selAttribs = array("id" => "projRqstDept",
						"name" => "projRqstDept", 
						"onchange" => "reloadSubDept(\"projRqstDept\", \"projRqstSubDept\")");
	$optAttribs = array("valueField" => "LDDEPT",
						"displayField" => "LDDESC",
						"selectedCompareField" => "LDDEPT",
						"selectedCompareValue" => $screenData['PRDEPT']);
	
	
	$screenData['html']['PRDEPT'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
	// Get Sub Department list
	unset($queryArray); // Clear array to avoid data drag
	
	$queryArray = getRecsLCDEPTP($conn2, $screenData['PRDEPT'], "ALL");
	
	$selAttribs = array("id" => "projRqstSubDept",
						"name" => "projRqstSubDept");
	
	$optAttribs = array("valueField" => "LDSUBDEPT",
						"displayField" => "LDDESC",
						"selectedCompareField" => "LDSUBDEPT",
						"selectedCompareValue" => $screenData['PRSUBDEPT']);
	
	$screenData['html']['PRSUBDEPT'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);

	// set up user acceptance check box
	$screenData['html']['PRUSRACPT'] = "<input type='checkbox' id='PRUSRACPT' name='PRUSRACPT' value='Yes' onclick='showHideUsrAcpt(\"" . $user . "\")' ";
	if (trim(  (is_null($projRecord['PRUSRACPT']) ? '' : $projRecord['PRUSRACPT'])  ) != "") {
		$screenData['html']['PRUSRACPT'] .= "checked ";
		$screenData['acceptText'] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Accepted by " . $projRecord['PRUSRACPT'] . " on " . formatDateSlashes($projRecord['PRACPTDTE']) . "."; 
	} else {
		$screenData['acceptText'] = "";
	}
	if (trim(  (is_null($user) ? '' : $user)   ) != trim(  (is_null($projRecord['PRRQST']) ? '' : $projRecord['PRRQST'])  ) && trim(  (is_null($user) ? '' : $user)  ) != trim(  (is_null($projRecord['PRSPONSR']) ? '' : $projRecord['PRSPONSR'])  ) && $screenData['PAPRJMNGR'] != 'Y') {
		$screenData['html']['PRUSRACPT'] .= "disabled='true' ";
	}
	$screenData['html']['PRUSRACPT'] .= "/>";

	// define payback fields
//	$screenData['PROCST1'] = fmtTwoDecimal($screenData['PROCST1']);
//	$screenData['PROCSTA'] = fmtTwoDecimal($screenData['PROCSTA']);
//	$screenData['PROSAV1'] = fmtTwoDecimal($screenData['PROSAV1']);
//	$screenData['PROSAVA'] = fmtTwoDecimal($screenData['PROSAVA']);
//	$screenData['PRCCST1'] = fmtTwoDecimal($screenData['PRCCST1']);
//	$screenData['PRCCSTA'] = fmtTwoDecimal($screenData['PRCCSTA']);
//	$screenData['PRCSAV1'] = fmtTwoDecimal($screenData['PRCSAV1']);
//	$screenData['PRCSAVA'] = fmtTwoDecimal($screenData['PRCSAVA']);

	
	// Get estimate info
	if ($_GET['projnum'] >= 1 && is_numeric($_GET['projnum'])) {
		$estimate = getRecsPRESTMTP( $conn2, $_GET['projnum']);
		$curEstimate = end($estimate);
	
		$origEstimator = $estimate[0]['PRPGMR'];
		$screenData['origHiEst'] = $estimate[0]['PRESTHI'];
		$screenData['origEstDate'] = formatDateSlashes($estimate[0]['PRESTDATE']);
	
		$curEstimator = $curEstimate['PRPGMR'];
		$screenData['curLowEst'] = $curEstimate['PRESTLOW'];
		$screenData['curHiEst'] = $curEstimate['PRESTHI'];
		$screenData['curEstDate'] = formatDateSlashes($curEstimate['PRESTDATE']);
	
	}

	// show link to Enter New Estimate if user is a programmer
	if ($_SESSION['usrclass'] == "*PGMR     " || $screenData['PAPRJMNGR'] == 'Y' || $_SESSION['usrclass'] == '*SYSOPR   ') {
		$screenData['estLink'] = "<a href=\"javascript:popupWindow('PROJ_newEstimate_ctl.php?projnum=".$screenData['PR#']."', 'New_Estimate')\">Enter new estimate</a>";
	} else {
		// $screenData['estLink'] = " ";
	}
	
		// Get long name for Original Estimator
	$origEstimator = getRecLCEMPLOYP($conn2, $origEstimator);
	if (strlen(trim(   (is_null($origEstimator[0]['LCFNAME']) ? '' : $origEstimator[0]['LCFNAME'])  )) > 0 ||
		strlen(trim(   (is_null($origEstimator[0]['LCLNAME']) ? '' : $origEstimator[0]['LCLNAME'])  )) > 0) {
			
		$screenData['origEstimator'] = trim($origEstimator[0]['LCFNAME'])." ".trim($origEstimator[0]['LCLNAME']);
	}
		// Get long name for Current Estimator
	$curEstimator = getRecLCEMPLOYP($conn2, $curEstimator);
	if (strlen(trim(   (is_null($curEstimator[0]['LCFNAME']) ? '' : $curEstimator[0]['LCFNAME'])     )) > 0 ||
		strlen(trim(   (is_null($curEstimator[0]['LCLNAME']) ? '' : $curEstimator[0]['LCLNAME'])     )) > 0) {
			
		$screenData['curEstimator'] = trim($curEstimator[0]['LCFNAME'])." ".trim($curEstimator[0]['LCLNAME']);
	}
		
		// Get programming time
	$projTime = getProjUserTime($conn2, $screenData['PR#']);
	$pgmrTime = array();
	foreach ($projTime as $timeRec) {
		$pgmrTime[$timeRec['PTPGMR']]['Time'] += $timeRec['PTTIME'];
		$pgmrTime[$timeRec['PTPGMR']]['PGMR'] = $timeRec['PTPGMR'];
	}
	$screenData['pgmrTime'] = "<table style='display:inline'>";
	foreach ($pgmrTime as $pgmrRec) {
	$screenData['pgmrTime'] .= "<tr><td class='txtData'>" . $pgmrRec['PGMR'] . "</td><td class='numData'> " . $pgmrRec['Time'] . " hours</td></tr>";
	$timeTotal += $pgmrRec['Time'];
	}
	// include children project total hours if the project is a parent project
	if ($childrenFlag == true) {
	    $screenData['pgmrTime'] .= "<tr><td class='txtData'>&nbsp;&nbsp;&nbsp;Total </td><td>".$timeTotal." hours</td></tr>";
	    $screenData['pgmrTime'] .= "<tr><td class='txtData'>&nbsp;&nbsp;&nbsp;Children Total </td><td>".$sumTotalChildrenPrjHours." hours</td></tr></table>";
	}
	else {
	    $screenData['pgmrTime'] .= "<tr><td class='txtData'>&nbsp;&nbsp;&nbsp;Total </td><td>".$timeTotal." hours</td></tr></table>";
	}
	
	
	
	//***********************************//
	// Load Pay Back section   //
	//***********************************//
	//Load project Payback type selection
	
	$queryArray = getRecsPRPAYBCKP($conn2);
	
	$selAttribs = array("id" => "projPBType",
 						"name" => "projPBType");
	$optAttribs = array("valueField" => "PBTYPE",
						"displayField" => "PBDESC",
						"selectedCompareField" => "PBTYPE",
						"selectedCompareValue" => $screenData['PRPAYBKTYP']);
	
	
	$screenData['html']['PRPAYBKTYP'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	

	//***********************************//
	// Load Steering Committee section   //
	//***********************************//
	
	//
	//Load project type selection
	$queryArray = getRecsPRTYPEP($conn2);
	
	$selAttribs = array("id" => "projtype",
						"name" => "projtype");
	$optAttribs = array("valueField" => "PYTYPE",
						"displayField" => "PYDESC",
						"selectedCompareField" => "PYTYPE",
						"selectedCompareValue" => $screenData['PRTYPE']);
	
	
	$screenData['html']['PRTYPE'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);

	
	//
	// Load "planned?" selection
		
	$queryArray = getRecsPRPLNDEFP($conn2);
	
	$selAttribs = array("id" => "projPlan",
						"name" => "projPlan");
	$optAttribs = array("valueField" => "PLTYPE",
						"displayField" => "PLDESC",
						"selectedCompareField" => "PLTYPE",
						"selectedCompareValue" => $screenData['PRPLAN']);
	
	
	$screenData['html']['PRPLAN'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
	//
	// Load resolution code
	
	$queryArray = getRecsPRRESCODEP($conn2);
	
	$selAttribs = array("id" => "projResCode",
						"name" => "projResCode");
	if ($screenData['PAPRJMNGR'] != 'Y' && $screenData['PASPONSR'] != 'Y') {
		$selAttribs["disabled"] = "true";
	}
	$optAttribs = array("valueField" => "PRCCODE",
						"displayField" => "PRCDESC",
						"selectedCompareField" => "PRCCODE",
						"selectedCompareValue" => $screenData['PRRESCOD']);
	
	
	$screenData['html']['PRRESCOD'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
	//
	// Steering committee priority
	
	$screenData['html']['PRPRTY'] = "<input type='text' name='projPriority' style='text-align:right' size='1' maxlength='1' value='" . $screenData['PRPRTY'];
//*********	// insert "readonly"
	if ($screenData['PAPRJMNGR'] != 'Y') {
		$screenData['html']['PRPRTY'] .=  "' readonly='true'";
	}
	$screenData['html']['PRPRTY'] .=  "' />";
	//
	// Load work status

//	$queryArray = array("fromFile" => "PRSTATUSP");
	$queryArray = getRecsPRSTATUSP($conn2);
	
	$selAttribs = array("id" => "projWrkSts",
						"name" => "projWrkSts");
	if ($screenData['PAPRJMNGR'] != 'Y' && $_SESSION['usrclass'] != '*PGMR     ' || $_SESSION['usrclass'] != '*SYSOPR   ') {
		$selAttribs['readonly'] =  "true";
	}
	$optAttribs = array("valueField" => "PRSCODE",
						"displayField" => "PRSDESC",
						"selectedCompareField" => "PRSCODE",
						"selectedCompareValue" => $screenData['PRWRKSTS']);
	
	
//	$screenData['html']['PRWRKSTS'] = loadListboxFromFile($conn, $queryArray, $selAttribs, $optAttribs);
	$screenData['html']['PRWRKSTS'] = loadListboxFromArray($queryArray, $selAttribs, $optAttribs);
	
	$screenData['html']['PRFORCE2SC'] = "<input type='checkbox' name='projForce2SC' value='Y' ";
	if ($screenData['PRFORCE2SC'] == 'Y') {
		$screenData['html']['PRFORCE2SC'] .= "checked "; 
	}
	if ($projAuthority['PAPRJMNGR'] != 'Y') {
		$screenData['html']['PRFORCE2SC'] .= " disabled='true'";
	}
	$screenData['html']['PRFORCE2SC'] .= "/>";

	//***********************//
	// Load WebNotes files   //
	//***********************//
	$prefix = 'PROJ_';
	$projStrg = trim(  (is_null($projRecord['PR#']) ? '' : $projRecord['PR#'])  );
	while (strlen($projStrg) < 6) {
		$projStrg = "0" . $projStrg;
	}
//	echo $projStrg."<br/>";

	// Get db2 records
	$notes = getRecordsWebNotes($projStrg, $prefix, $conn2);
	
	$i = 0;
	foreach ($notes as $note) {
		
		// make 'time' 6 chatacters long
		while (strlen(trim($note['WNTIME'])) < 6) {
			$note['WNTIME'] = '0' . $note['WNTIME'];
		}
		
		$note['WNPREFIX'] = trim($note['WNPREFIX']);
		$note['WNIDVAL'] = trim($note['WNIDVAL']);
		$note['WNPATH'] = trim($note['WNPATH']);
		
		// Set some generic parameters
		$comntHead = "<br/><b>" . formatDateSlashes($note['WNDATE']) . " " . $note['WNUSER'] . "</b><br/>";
		$fh = $note['WNPATH']."/".$note['WNPREFIX'].$note['WNIDVAL'].$note['WNDATE'].$note['WNTIME'];
		$deleteParms = "id=".$note['WNIDVAL'].
					"&type=".$note['WNTYPE'].
					"&prefix=".$note['WNPREFIX'].
					"&path=".$note['WNPATH'].
					"&user=".$note['WNUSER'].
					"&date=".$note['WNDATE'].
					"&time=".$note['WNTIME'];
		$saveParms = "&" . $deleteParms . "&mode=update";
		
		$i += 1;
		switch (trim($note['WNTYPE'])) {
			case 'Descrip': // Description
				$scDescFound = true;
				$screenData['projDesc'] .= "<div id='projDesc' class='webNote'>".file_get_contents($fh)."</div>";
				
				// lock description when Steering committee action date is filled in
				if ($screenData['PRRESCOD'] != 'ACP' 
				|| $screenData['PAPRJMNGR'] == "Y") {
					$screenData['projDesc'] .= "<div id='projDescLinks'>";
					$screenData['projDesc'] .= "<a href=\"javascript:flipShowHide('edit', 'projDesc', 'projDescLinks', ' ');\">Edit</a>";
					$screenData['projDesc'] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projDesc', 'projDescLinks', '$saveParms');\">Accept</a>";
					$screenData['projDesc'] .= " | ";
					$screenData['projDesc'] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projDesc', 'projDescLinks', ' ');\">Cancel</a>";
					$screenData['projDesc'] .= "</div>";
				} else {
					$screenData['projDesc'] .= "<div id='projDescLinks'>";
					$screenData['projDesc'] .= "<i>Project has been reviewed by steering committee. ";
					$screenData['projDesc'] .= "Description changes require Project Manager authority.</i></div>";
				}
				
				break;
			case 'ComntGen': // General comments
				// Show date and user for each comment

				$screenData['projComntGen'][$i] = $comntHead;
				$screenData['projComntGen'][$i] .= "<div id='projComntGen".$i."' class='webNote'>";
				$screenData['projComntGen'][$i] .= file_get_contents($fh)."<br/>";
				$screenData['projComntGen'][$i] .= "</div>";
				
				$screenData['projComntGen'][$i] .= "<div id='projGenCommLinks".$i."'>";
				$screenData['projComntGen'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntGen".$i."', 'projGenCommLinks".$i."', ' ');\">Edit</a>";
				$screenData['projComntGen'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntGen".$i."', 'projGenCommLinks".$i."', '$saveParms');\">Accept</a>";
				$screenData['projComntGen'][$i] .= " | ";
				$screenData['projComntGen'][$i] .= "<a href=\"javascript:flipShowHide('delete', 'projComntGen".$i."', 'projGenCommLinks".$i."', '$deleteParms');\">Delete</a>";
				$screenData['projComntGen'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntGen".$i."', 'projGenCommLinks".$i."', ' ');\">Cancel</a>";
				$screenData['projComntGen'][$i] .= "</div>";
				
				$screenData['projComntGen'][$i] .= "<hr>";
				break;
			case 'ComntIT': // IT comments

				$screenData['projComntIT'][$i] = $comntHead;
				$screenData['projComntIT'][$i] .= "<div id='projITCommLinks".$i."'>";
				$screenData['projComntIT'][$i] .= "<div id='projComntIT".$i."' class='webNote'>";
				$screenData['projComntIT'][$i] .= file_get_contents($fh)."<br/>";
				$screenData['projComntIT'][$i] .= "</div>";
				$screenData['projComntIT'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntIT".$i."', 'projITCommLinks".$i."', ' ');\">Edit</a>";
				$screenData['projComntIT'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntIT".$i."', 'projITCommLinks".$i."', '$saveParms');\">Accept</a>";
				$screenData['projComntIT'][$i] .= " | ";
				$screenData['projComntIT'][$i] .= "<a href=\"javascript:flipShowHide('delete', 'projComntIT".$i."', 'projITCommLinks".$i."', '$deleteParms');\">Delete</a>";
				$screenData['projComntIT'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntIT".$i."', 'projITCommLinks".$i."', ' ');\">Cancel</a>";
				$screenData['projComntIT'][$i] .= "</div>";
				
				break;
			case 'ComntPB': // Payback comments

				$screenData['projComntPB'][$i] = $comntHead;
				$screenData['projComntPB'][$i] .= "<div id='projPBCommLinks".$i."'>";
				$screenData['projComntPB'][$i] .= "<div id='projComntPB".$i."' class='webNote'>";
				$screenData['projComntPB'][$i] .= file_get_contents($fh)."<br/>";
				$screenData['projComntPB'][$i] .= "</div>";
				$screenData['projComntPB'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntPB".$i."', 'projPBCommLinks".$i."', ' ');\">Edit</a>";
				$screenData['projComntPB'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntPB".$i."', 'projPBCommLinks".$i."', '$saveParms');\">Accept</a>";
				$screenData['projComntPB'][$i] .= " | ";
				$screenData['projComntPB'][$i] .= "<a href=\"javascript:flipShowHide('delete', 'projComntPB".$i."', 'projPBCommLinks".$i."', '$deleteParms');\">Delete</a>";
				$screenData['projComntPB'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntPB".$i."', 'projPBCommLinks".$i."', ' ');\">Cancel</a>";
				$screenData['projComntPB'][$i] .= "</div>";
				
				break;
				
				case 'ComntSC': // Steering Committee comments
				
				$screenData['projComntSC'][$i] = $comntHead;
				$screenData['projComntSC'][$i] .= "<div id='projSCCommLinks".$i."'>";
				$screenData['projComntSC'][$i] .= "<div id='projComntSC".$i."' class='webNote'>";
				$screenData['projComntSC'][$i] .= file_get_contents($fh)."<br/>";
				$screenData['projComntSC'][$i] .= "</div>";
				$screenData['projComntSC'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntSC".$i."', 'projSCCommLinks".$i."', ' ');\">Edit</a>";
				$screenData['projComntSC'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntSC".$i."', 'projSCCommLinks".$i."', '$saveParms');\">Accept</a>";
				$screenData['projComntSC'][$i] .= " | ";
				$screenData['projComntSC'][$i] .= "<a href=\"javascript:flipShowHide('delete', 'projComntSC".$i."', 'projSCCommLinks".$i."', '$deleteParms');\">Delete</a>";
				$screenData['projComntSC'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntSC".$i."', 'projSCCommLinks".$i."', ' ');\">Cancel</a>";
				$screenData['projComntSC'][$i] .= "</div>";
				
				break;
		}
	}

	$postParms = "&prefix=".$prefix."&id=".$projStrg."&path=WebNotes/PTS&mode=add"; // "PROJ_" plus project number
	$i += 1;
	
	// If no description yet make room for one
	if (empty($screenData['projDesc'])) {	
		$descParms = "&prefix=".$prefix."&id=".$projStrg."&path=WebNotes/PTS" . "&type=Descrip&mode=add";
				$screenData['projDesc'] .= "<div id='projDesc' class='webNote'></div>";
				
				$screenData['projDesc'] .= "<div id='projDescLinks'>";
				$screenData['projDesc'] .= "<a href=\"javascript:flipShowHide('edit', 'projDesc', 'projDescLinks', ' ');\">Edit</a>";
				$screenData['projDesc'] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projDesc', 'projDescLinks', '$descParms');\">Accept</a>";
				$screenData['projDesc'] .= " | ";
				$screenData['projDesc'] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projDesc', 'projDescLinks', ' ');\">Cancel</a>";
				$screenData['projDesc'] .= "</div>";
				
				$screenData['projDesc'] .= "<br/>";
	}
	
	
	// Add an empty <div> to projComntGen, projComntIT, and projComntSC so another comment can be added
	//$mode = 'add';
	if ($_GET['projnum'] == 'newproj') {
		$screenData['projComntGen'][$i] = "You must save this project before comments can be added.";
		$screenData['projComntIT'][$i] = "You must save this project before comments can be added.";
		$screenData['projComntPB'][$i] = "You must save this project before comments can be added.";
		$screenData['projComntSC'][$i] = "You must save this project before comments can be added.";
	} else {
		$genPostParms = $postParms . "&type=ComntGen";
		$screenData['projComntGen'][$i] = "<br/><div id='projComntGen".$i."' class='webNote'>";
			$screenData['projComntGen'][$i] .= "</div>";
			$screenData['projComntGen'][$i] .= "<div id='projGenCommLinks".$i."'>";
			$screenData['projComntGen'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntGen".$i."', 'projGenCommLinks".$i."', ' ');\">New Comment</a>";
			$screenData['projComntGen'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntGen".$i."', 'projGenCommLinks".$i."', '$genPostParms');\">Accept</a>";
			$screenData['projComntGen'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntGen".$i."', 'projGenCommLinks".$i."', ' ');\"> | Cancel</a>";
			$screenData['projComntGen'][$i] .= "</div>";
	
		$itPostParms = $postParms . "&type=ComntIT";
		$screenData['projComntIT'][$i] = "<br/><div id='projComntIT".$i."' class='webNote'>";
			$screenData['projComntIT'][$i] .= "</div>";
			$screenData['projComntIT'][$i] .= "<div id='projITCommLinks".$i."'>";
			$screenData['projComntIT'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntIT".$i."', 'projITCommLinks".$i."', ' ');\">New Comment</a>";
			$screenData['projComntIT'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntIT".$i."', 'projITCommLinks".$i."', '$itPostParms');\">Accept</a>";
			$screenData['projComntIT'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntIT".$i."', 'projITCommLinks".$i."', ' ');\"> | Cancel</a>";
			$screenData['projComntIT'][$i] .= "</div>";
	
		$pbPostParms = $postParms . "&type=ComntPB";
		$screenData['projComntPB'][$i] = "<br/><div id='projComntPB".$i."' class='webNote'>";
			$screenData['projComntPB'][$i] .= "</div>";
			$screenData['projComntPB'][$i] .= "<div id='projPBCommLinks".$i."'>";
			$screenData['projComntPB'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntPB".$i."', 'projPBCommLinks".$i."', ' ');\">New Comment</a>";
			$screenData['projComntPB'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntPB".$i."', 'projPBCommLinks".$i."', '$pbPostParms');\">Accept</a>";
			$screenData['projComntPB'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntPB".$i."', 'projPBCommLinks".$i."', ' ');\"> | Cancel</a>";
			$screenData['projComntPB'][$i] .= "</div>";
			
		$scPostParms = $postParms . "&type=ComntSC";
		$screenData['projComntSC'][$i] = "<br/><div id='projComntSC".$i."' class='webNote'>";
			$screenData['projComntSC'][$i] .= "</div>";
			$screenData['projComntSC'][$i] .= "<div id='projSCCommLinks".$i."'>";
			$screenData['projComntSC'][$i] .= "<a href=\"javascript:flipShowHide('edit', 'projComntSC".$i."', 'projSCCommLinks".$i."', ' ');\">New Comment</a>";
			$screenData['projComntSC'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('save', 'projComntSC".$i."', 'projSCCommLinks".$i."', '$scPostParms');\">Accept</a>";
			$screenData['projComntSC'][$i] .= "<a class='hidden' href=\"javascript:flipShowHide('cancel', 'projComntSC".$i."', 'projSCCommLinks".$i."', ' ');\"> | Cancel</a>";
			$screenData['projComntSC'][$i] .= "</div>";
	}
				
	//***********************//
	// Load Steering committee prep checklist
	//***********************//
				
	$imgYes = "<img src='images/GreenCheck.png' alt='yes' height='15' width='15'></img>";
	$imgNo = "<img src='images/RedX.png' alt='no' height='15' width='15'></img>";
	$screenData['scCheckList'] = "<ul>";
			
	if ($scDescFound) {
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Description</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . "  Has Description</li>";
	}
	
	if (trim(   (is_null($projRecord['PRESTMTR']) ? '' : $projRecord['PRESTMTR'])    ) != "") {
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Estimator Assigned</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . " Estimator Assigned</li>";
	}

	if ($screenData['PRSPAPVDTE'] != 0) {	
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Sponsor Approval</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . "  Has Sponsor Approval</li>";
	}

	if ($screenData['origHiEst'] != 0) {	
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Estimate</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . "  Has Estimate</li>";
	}
	if (trim(    (is_null($screenData['PRPAYBKTYP']) ? '' : $screenData['PRPAYBKTYP'])    ) == "O" || 
	(trim(   (is_null($screenData['PRPAYBKTYP']) ? '' : $screenData['PRPAYBKTYP'])   ) == "F" && ($projRecord['PROCST1'] != 0 || $projRecord['PROCSTA'] != 0 || $projRecord['PROSAV1'] != 0
											|| $projRecord['PROSAVA'] != 0 || $projRecord['PRCCST1'] != 0 || $projRecord['PRCCSTA'] != 0
											|| $projRecord['PRCSAV1'] != 0 || $projRecord['PRCSAVA'] != 0)) ) {	
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Payback Justification</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . "  Has Payback Justification</li>";
	}
	
	if ($projRecord['PRUPTY'] >= 0 && $projRecord['PRUPTY'] != 9) {	
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Department Priority</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . " Has Department Priority</li>";
	}
	
	if (trim(   (is_null($screenData['PRTYPE']) ? '' : $screenData['PRTYPE'])    ) != "") {	
		$screenData['scCheckList'] .= "<li>" . $imgYes . " Has Project Type</li>";
	} else {
		$screenData['scCheckList'] .= "<li>" . $imgNo . "  Has Project Type</li>";
	}
	
	
	$screenData['scCheckList'] .= "</ul>";
	
	if (isset($screenData['PR#'])) {
		showProjectDetailScreen($screenData);
	} elseif ($_GET['projnum'] == 'prompt') {
		showProjPrompt();
	} else {
		showProjNotFound();
	}
	
	} // Authentication "else"
// <!--  End Content Here -->

	include("EndBlock.php");
?>

<?php
// the display half, inlined
 
function showProjPrompt() {
?>
	<div id='stdPage'>
	<h1><i>Know which project you want?</i></h1>
	<h2><u>Go to project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6'/></h2>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjNotFound() {
?>
	<div id='stdPage'>
	<h1 style='color:red'><i>Project not found</i></h1>
	<h2><u>Project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6'/></h2>
	</div>
	<script type="text/javascript">
	document.getElementById("projectNumber").focus();
	</script>
<?php 
}
function showProjectDetailScreen(&$screenData) {
?>
	<div id='stdPage'>
	
	<div style='text-align: left; width: 65%; float: left; vertical-align:bottom;'>
	<h2 style='float: left'><u>Project:</u> <input onchange='goToProject()' id='projectNumber' style='text-align:right' type='text' size='5' maxlength='6' value='<?php echo $screenData['PR#']?>'/></h2>
	<div style='padding-top: 8px'>&nbsp;&nbsp;<?php echo $screenData['PRDESC']?></div>
	<br/>
	<br/>
	</div>
	<div style='text-align: right; width: 30%; float: left;'>
	<a href="PROJ_print_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">Print  </a>
	<?php echo $screenData['saveButton']?>
	<?php echo " " . $screenData['cancelButton']?>
<!--	<input type='button' value='Save Changes' onclick='saveProjChanges()'/>-->
	</div>
	
	<br/>
	<br/>
	<br/>
	
	<div style='text-align: left;' class='tabArea' id='PROJ_mainTabs'>
	<br/>
	<?php
			echo "<a id='tabGeneral' href=\"javascript:switchTab('PROJ_mainTabs', 'tabGeneral', 'pageSection', 'general');\">General</a>";
			echo " <a id='tabIt' href=\"javascript:switchTab('PROJ_mainTabs', 'tabIt', 'pageSection', 'itStuff');\">IT Stuff</a>";
			echo " <a id='tabPayBack' href=\"javascript:switchTab('PROJ_mainTabs', 'tabPayBack', 'pageSection', 'payBack')\">Payback</a>";
			echo " <a id='tabStrComm' href=\"javascript:switchTab('PROJ_mainTabs', 'tabStrComm', 'pageSection', 'strComm')\">Steering Committee</a>";
	?>
	</div>

<div id='general' class='pageSection' style="display:block">
<form id='projForm' name='projForm' action='PROJ_save.php' method="post">
	<input type='hidden' name='projnum' value='<?php echo $screenData['PR#']?>' />
	<input type='hidden' id='hiddenUser' value='<?php echo strtoupper($_SESSION['username'])?>' /> 
	<input type='hidden' id='hiddenUserType' value='<?php echo strtoupper($_SESSION['username'])?>' />
	<input type='hidden' id='hiddenUserClass' value='<?php echo strtoupper(trim($_SESSION['usrclass']))?>' />
	 
	<br/>

	<span onmouseover="tooltip.show('Descriptive, accurate, clear and short. 50 Characters max.');" onmouseout="tooltip.hide();">
		<img src='images/Info_icon_20px.png' height='15' width='15' />
	</span>	

	<label>Project Name:</label> 

	<input type="text" id="projName" name="projName" size="60" maxlength="50"
		value="<?php echo $screenData['PRDESC']?>"/>

	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRDescrip']?>
	<label>Description:</label> <?php echo $screenData['projDesc'];?>
	
	
	<?php 
	If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) {
	   echo '<br/>';
	   echo '<font size="+1">';
	   echo 'Parent Project:';
	}
	?>
	&nbsp;
	<?php
	If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) { 
	   echo $screenData['PRRELPRJ#'];
	}
	?>
	&nbsp;
    <?php 
    If ($screenData['PRRELPRJ#'] != 0 and $screenData['PRRELPRJ#'] != null) {
       echo $screenData['parentProjDesc']; 
       echo '<br/>';
    }
    ?>
    </font>
    
	<font size="+1">
	<?php
    If ($screenData['childrenProjects'] != ' ' and $screenData['childrenProjects'] != null) {
        
        echo "<br/>";
        echo 'Children Projects:';
        
    }
    ?>
    </font>
    <?php 
    If ($screenData['childrenProjects'] != ' ' and $screenData['childrenProjects'] != null) {
        echo '<font size="+1">';
        echo $screenData['childrenProjects'];
        echo '</font>';
    }
    ?>
	<br/>
	<?php echo $screenData['toolTip']['LNKDOC']?>
	<label>Attached Documents:</label><br/>
	<div id="linkedDocs"><?php echo $screenData['linkedDocs'];?></div>
	
	<br/>
	<input type='button' onclick="javascript:popupWindow('LNKDOC_upload_ctl.php?prefix=PROJ_&idval=<?php echo $screenData['PR#']?>', 
		'Upload Doc', 400)" value="Attach a Document" />
	
	<hr/>
	
	<br/>
	<br/>
		
	<?php echo $screenData['tstType']?>
	
	<br/>	
	<br/>	
	
	<?php echo $screenData['toolTip']['PRRQST']?>
	<label>Requestor: </label> 
		<?php echo " " . $screenData['PRRQST']?>
	
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		
	<label>Created Date:</label>
	<?php echo $screenData['html']['PRSUBD']?>
	<br/>
	<br/>
		
	<?php echo $screenData['toolTip']['PRSPONSR']?>
	<label>Sponsor:</label> 
	<?php echo $screenData['PRSPONSR'] ?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRSPAPVDTE']?>
	<label>Sponsor Approval Date:</label>
	<?php echo $screenData['html']['PRSPAPVDTE']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRNEED']?>
	<label>Need By Date:</label> 
	<?php echo $screenData['html']['PRNEED']?>
	<!-- kjr -->
	&nbsp;&nbsp;&nbsp;
	
	<label>Justification Type:</label>
	<?php echo $screenData['html']['PJDESC'] ?>
	<br/>
	<br/>
	<!-- kjr -->	
		
	<label>Requesting Department:</label> 
	<?php echo $screenData['html']['PRDEPT']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Sub Dept:</label> 
	<?php echo $screenData['html']['PRSUBDEPT']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRUPTY']?>
	<span onmouseover="tooltip.show(&apos; 1 - Needs to be completed in 1 to 3 months <br> 2 - Needs to be completed in 3 to 6 months <br> 3 - Needs to be completed in 6 months to a year <br> 4 - On Hold <br> 5 -> 8 - Not Used <br> 9 - Default (has not been changed since project creation) &apos;)" onmouseout="tooltip.hide();">
		<img src="images/Info_icon_20px.png" width="15" height="15">
		</span>
	<label>Department Priority:</label> 
	<input size='2' maxlength='1' type='text' name='projUsrPrty' onchange='activateSave()' value='<?php echo $screenData['PRUPTY']?>' />
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRUSRACPT']?>
	<label>Project Acceptance:</label> 
	<?php echo $screenData['html']['PRUSRACPT']?> By checking this box the user agrees the project is complete and is ready for implementation.
	<br/>
	<div id='acceptDiv'><?php echo $screenData['acceptText']?></div>
<!--	<br/>-->
	<br/>
	
	<a href="PROJ_allComntView_ctl.php?projnum=<?php echo $screenData['PR#']?>" target="_blank">View All Comments</a>
	<br/>
	
	<?php echo $screenData['toolTip']['GenCommnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntGen'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	
<!--</form> -->
</div><!-- general -->	

<div class='pageSection' id='itStuff' style="display:none">
<!--<form id='itStuffForm'>-->
	<br/>
	<?php echo $screenData['estLink']?>
	<br/>
	<label>Assigned Estimator:</label> 
	<?php echo $screenData['html']['PRESTMTR']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<label>Dev Group</label>
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['html']['PRITDEVGRP']?>
	<br/>
	<br/>
	<label>Original estimate:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo "<span><div class='data' id='origHiEst'>" . $screenData['origHiEst'] . "</div>"?>
				<small>Originaly estimated on <?php echo " " . $screenData['origEstDate'] . 
				" by " . $screenData['origEstimator'] .
				"</small></span>"?>
	<br/>
	<br/>


	<label>Current low estimate:</label> 
	<?php echo "<div class='data' id='CurLowEst'>" . $screenData['curLowEst'] . "</div>"?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['toolTip']['CurHiEst']?>
	<label>Current hi estimate:</label> 
	<?php echo "<div class='data' id='curHiEst'>" . $screenData['curHiEst'] . "</div>"?>
	<br/>
<!--	<br/>-->
	<small>
	current estimate was done on <?php echo " " . $screenData['curEstDate'] . " by " . $screenData['curEstimator']?>
	</small>
	<br/>
	<br/>
	
	<label>Brand:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['PRBRAND']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Parent Project:</label> 
	&nbsp;
	<input size='6' maxlength='6' type='text' name='parentProj' value='<?php echo $screenData['PRRELPRJ#']?>' />
	&nbsp;
	<font size="+1">
	<?php echo $screenData['parentProjDesc'] //wrap key in quotes to prevent undefined constant warning - 06-28-22 - kjr ?>
	</font>
	
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRPGMR']?>
	<label>Programmer assigned:</label> 
	&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['PRPGMR']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRWRKSTS']?>
	<label>Programmer work status: </label>
	<?php echo $screenData['html']['PRWRKSTS']?>
	<br/>
	
	<br/>
	<label>Programmer time to date:</label>
	<?php echo " " . $screenData['pgmrTime']?>
	<br/>
	<br/>
			
	<label>Scheduled start date:</label> 
	<?php echo $screenData['html']['PRESTR']?>
<!--	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
	<br/>
	<br/>
	
	<label>Scheduled implementation date:</label> 
	<?php echo $screenData['html']['PRECOM']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<label>Actual implementation date:</label> 
	<?php echo $screenData['html']['PRACOM']?>
	<br/>
	<br/>
	<fieldset id = 'lgndFldSet'>
	<legend id="lgndRetailRange">Action Items:</legend>
	<div id='divRetailRange'>
	<input id="addActInfo" type="button" value="Add Action"> 
	<div id='actDtl' style="width: 700px; padding: 25px; ">
    <ul></ul>
	</div>
	<?php //echo $screenData['instOrd']['RETRANGE']; ?>
	<br />
	</div>
	</fieldset>
	<br/>

<!--	<label>Implemented date:</label> -->
<!--	<php echo $screenData['html']['PRIMPDTE']?>-->
<!--	<br/>-->
<!--	<br/>-->
	
	<?php echo $screenData['toolTip']['ITCommnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntIT'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
<!--</form> -->
</div> <!-- itStuff  -->

<div class='pageSection' id='payBack' style="display:none">
<!--<form id='payBackForm'>-->
	<br/>
	<?php echo $screenData['toolTip']['PRPAYBKTYP']?>
	<label>Payback type: </label>
	<?php echo $screenData['html']['PRPAYBKTYP']?>

	<br/>
	<br/><label>Developer rate: </label>
	<?php echo $screenData['html']['PRDRAT']?>
	
	<br/><br/>
	<table>
		<CAPTION>
  			Payback data
		</CAPTION>
		<tr>
			<th class='blank'></th>
			<th>Original</th>
			<th>Current</th>
		</tr>
		<tr>
			<td>Developer cost</td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='origDevCost' name='origDevCost' disabled value='<?php echo $screenData['origDevCost']?>'></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='developerCost' name='developerCost' disabled value='<?php echo $screenData['developerCost']?>'></td>
			
		</tr>
		<tr>
			<td>One-time cost <?php echo $screenData['toolTip']['1TimeCost']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='orig1TimeCost' name='orig1TimeCost' value='<?php echo $screenData['PROCST1']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5'  id='cur1TimeCost' name='cur1TimeCost' value='<?php echo $screenData['PRCCST1']?>' /></td>
		</tr>
		<tr>
			<td>Annual cost <?php echo $screenData['toolTip']['AnnualCost']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='origAnnualCost' name='origAnnualCost' value='<?php echo $screenData['PROCSTA']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='curAnnualCost' name='curAnnualCost' value='<?php echo $screenData['PRCCSTA']?>' /></td>
		</tr>
		<tr>
			<td>One-time savings <?php echo $screenData['toolTip']['1TimeSavng']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='orig1TimeSav' name='orig1TimeSav' value='<?php echo $screenData['PROSAV1']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='cur1TimeSav' name='cur1TimeSav' value='<?php echo $screenData['PRCSAV1']?>' /></td>
		</tr>
		<tr>
			<td>Annual savings <?php echo $screenData['toolTip']['AnnualSavn']?></td>
			<td class='numData'><input onchange='projUpdCurr(); projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='origAnnualSav' name='origAnnualSav' value='<?php echo $screenData['PROSAVA']?>' /></td>
			<td class='numData'><input onchange='projCalcPayback()' type='text' maxlength='12' style="text-align:right" size='5' id='curAnnualSav' name='curAnnualSav' value='<?php echo $screenData['PRCSAVA']?>' /></td>
		</tr>
		<tr>
			<td>Payback <?php echo $screenData['toolTip']['PayBack']?></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='origPayback' name='origPayback' readonly value='<?php echo $screenData['PROPYBK']?>' /></td>
			<td class='numData'><input type='text' maxlength='12' style="text-align:right" size='5' id='curPayback' name='curPayback'readonly value='<?php echo $screenData['PRCPYBK']?>' /></td>
		</tr>
	</table>
		
	<br/>
	<br/>
	<?php echo $screenData['toolTip']['PBComnts']?>
	<label>Comments:</label> 
	<?php 
	foreach ($screenData['projComntPB'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	<br/>
	
<!--</form> -->
</div> <!-- Payback  -->

<div class='pageSection' id='strComm' style="display:none">
<!--<form id='strCommForm'>-->
	<br/>
	<?php echo $screenData['toolTip']['PRSCREVDTE']?>
	<label>Steering committee action date: </label>
	<?php echo $screenData['html']['PRSCREVDTE']?>
	<br/>
	<br/>
	
	<?php echo $screenData['toolTip']['PRRESCOD']?>
	<label>Resolution: </label>
	<?php echo $screenData['html']['PRRESCOD']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRAUTH']?>
	<label>Authorized hours: </label>
	<?php echo $screenData['html']['PRAUTH']?>
	<br/>
	<br/>
	
	<label>Project Type: </label>
	<?php echo $screenData['html']['PRTYPE']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	
	<?php echo $screenData['toolTip']['PRPLAN']?>
	<label>Planned?: </label>
	<?php echo $screenData['html']['PRPLAN']?>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php echo $screenData['toolTip']['PRPRTY']?>
	<label>SC Priority: </label>
	<?php echo $screenData['html']['PRPRTY']?>
	<br/>
	<br/>
	
	<label>Postmortem Date: </label>
	<?php echo $screenData['html']['PRPMDT']?>
	
	<br/>
	<br/>
	<label>Force Steering Committee Review: </label>
	<?php echo $screenData['html']['PRFORCE2SC']?>
	<br/>
	
	<br/><label>Steering Committee Review Checklist: </label>
	<br/>
	<?php echo $screenData['scCheckList']?>
	
	<label> Steering Committee Comments:</label> 
	<?php 
	foreach ($screenData['projComntSC'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}
	?>
	<script type="text/javascript">
	document.getElementById("projName").focus();
	</script>

</form>

<script>
	
  // The following arrays will be used to check for changes. If changes have been made to a project
  // and the PTS user hasn't saved their changes before trying to venture out of the project
  // request, a warning message will pop up.  
  
  var ids = new Array('projEstimator','projSponsor','projBrand','projProgrammer','projDevGrp','projRequester','projRqstDept','projRqstSubDept','PRUSRACPT','projPBType','projtype','projPlan','projResCode','projWrkSts','projDesc','projComntGen','projComntIT','projComntPB','projComntSC','projName','projSponsAprvDate','tstType','projSponsAprvDate','projSchdStart','projSchdComp','projActComp','PRIMPDTE','projAuthHrs','postMortDate','scRevDate','projDevRate','projNeedBy','projPriority','projForce2SC','projUsrPrty','orig1TimeCost','cur1TimeCost','origAnnualCost','curAnnualCost','orig1TimeSav','cur1TimeSav','origAnnualSav','curAnnualSav','origPayback','curPayback','parentProj');
  var values = new Array('','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','');
  
  populateArrays();

  // Populate the values array with the current project data
    
  function populateArrays() {
    	    
    for (var i = 0; i < ids.length; i++) {
    
      var elem = document.getElementById(ids[i]);
      if (elem) {
        if (elem.type == 'checkbox' || elem.type == 'radio') {
           values[i] = elem.checked;
        }
        else {
           values[i] = elem.value;
        }
      }
    }
  }

  // If the user is trying to unload the current project request, check to make sure there aren't any
  // unsaved changes to the project request.  Pop up warning message if there are unsaved changes.
  
  window.onbeforeunload = confirmExit;
  
  function confirmExit()
  {
    if (needToConfirm)
    {
      // check to see if any changes to the data entry fields have been made
      for (var i = 0; i < values.length; i++)
      {
        var elem = document.getElementById(ids[i]);
        if (elem) {
          if ((elem.type == 'checkbox' || elem.type == 'radio') 
               && values[i] != elem.checked) {
            return 'Need to Save or Cancel!';
          }
          else if (!(elem.type == 'checkbox' || elem.type == 'radio') &&
                  elem.value != values[i]) {
            return 'Need to Save or Cancel!';
          }
        }
      }
      if (whichEditor[0] != ' ' && whichEditor[0] != null) {
         return 'Need to Save or Cancel';
      }

      // no changes - return nothing      
    }
  }
</script>	


</div> <!-- Tab4  -->
</div> <!-- stdPage -->
<?php 
}
?>
