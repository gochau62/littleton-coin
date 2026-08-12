<?php
	include("StartBlock.php");

?>
<!--javascript includes go here-->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='PROJ_JS_functions.js'></script>
<script type='text/javascript' src='Utils/calendar_us.js'></script>

<script type="text/javascript">
	document.title = "PTS Reports";
	var globalStartDate = 0;
	var globalEndDate = 0;
</script>
<script type="text/javascript">
function updLastRan() {
	dataAry = {action: "projGetPTSRPTDATPRec",
			   rptName: "PR8001R"};

	$.ajax({
		url: 'PROJ_ajax_request.php',
		dataType: 'xml', 
		data: dataAry,
		type: 'GET',
		async: false,
		success: function(rtnData) {
			var tmpString = "Last ran on "
			+ lccDateToSlashes($(rtnData).find("RUN_DATE").text())
			+ " at "
			+ $(rtnData).find("FMTIME").text()
			+ " by "
			+ $(rtnData).find("RUN_USER").text()
			+ " for date range <b>"
			+ lccDateToSlashes($(rtnData).find("START_DATE").text())
			+ " - "
			+ lccDateToSlashes($(rtnData).find("END_DATE").text())
			+ "</b>.";
			
			$("#lastRan").html(tmpString);

			globalStartDate = $(rtnData).find("START_DATE").text();
			globalEndDate = $(rtnData).find("END_DATE").text();
			
		}

	});
}

	/* document ready */
	jQuery(document).ready(function() {
		$('#spinner')
		.ajaxStart(function() {
			$(this).addClass('progress');
		})
		
		.ajaxStop( function() {
			$(this).removeClass('progress');
		});
		
		updLastRan();

		$("#refresh").click(function() {
			updLastRan();
		});

		$("#sbmtSCReports").click(function() {
			dataAry1 = {action: "updatescreview"};

			$.ajax({
				url: 'PROJ_ajax_request.php',
				dataType: 'xml', 
				data: dataAry1,
				type: 'GET',
				async: false,
				success: function() {
										
				}

			});

			dataAry2 = {action: "updatewrkload",
						startdate: slashDateToLcc($("#fromDate").val()),
						enddate: slashDateToLcc($("#toDate").val())};

			$.ajax({
				url: 'PROJ_ajax_request.php',
				dataType: 'xml', 
				data: dataAry2,
				type: 'GET',
				async: false,
				success: function() {
					updLastRan();					
				}

			});
			updLastRan();
		});
		
		$("#downloadReports").click(function() {
			var wkld = "N";
			var sbmt = "N";
			var cmplt = "N";
			var screv = "N";
			var frmfri = "N";
			var postmort = "N";

			if ($('#workLoad').is(':checked')) {
			    wkld="Y";
			}
			if ($('#projSubmitted').is(':checked')) {
			    sbmt="Y";
			}
			if ($('#projCompleted').is(':checked')) {
			    cmplt="Y";
			}
			if ($('#SCReview').is(':checked')) {
			    screv="Y";
			}
			if ($('#FFProjects').is(':checked')) {
			    frmfri="Y";
			}
			if ($('#postMortem').is(':checked')) {
			    postmort="Y";
			}
			
			var rptURL = "PROJ_includes/PTSReports_XML.php?subdept="
				+ $('input:radio[name=itSubDept]:checked').val()
				+ "&startdate=" + globalStartDate
				+ "&enddate=" + globalEndDate
				+ "&workload=" + wkld
				+ "&submitted=" + sbmt
				+ "&completed=" + cmplt
				+ "&screview=" + screv
				+ "&formulafriday=" + frmfri
				+ "&postmortem=" + postmort;
// 			alert (rptURL);
			window.open(rptURL);
		});

	});
</script>
<!--  Begin Content Here -->
<?php 
//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline
$conn = getDB2PConn($user, $password);
$authorized = chkAutUsr($conn, $user, "LCCONLINE", 10);

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


// 	if (isset($_POST['fromDate']) && trim($_POST['fromDate']) != "") {
// 		$date1 = (string) formatDateLCC($_POST['fromDate']);
// 		$date2 = (string) formatDateLCC($_POST['toDate']);
		
// 		$conn = getDB2PConn($user, $password);
// 		$callStmnt = "Call PR8001S(?, ?)";
		
// 		$stmt = db2_prepare($conn, $callStmnt) or die("Call PR8001R prepare error: " . db2_stmt_error() . "<br/>" .
// 				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
// 		db2_bind_param($stmt, 1, 'date1', DB2_PARAM_IN);
// 		db2_bind_param($stmt, 2, 'date2', DB2_PARAM_IN);
		
// 		db2_execute($stmt) or die("Call PR8001R execute error: " . db2_stmt_error() . "<br/>" .
// 				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
// 	} 
	
	if (isset($_POST['pgmrFromDate']) && trim($_POST['pgmrFromDate']) != "") {
		$date1 = (string) formatDateLCC($_POST['pgmrFromDate']);
		$date2 = (string) formatDateLCC($_POST['pgmrToDate']);

		$conn = getDB2PConn($user, $password);
		
		$callStmnt = "Call PRR915S(?, ?)";
		
		$stmt = db2_prepare($conn, $callStmnt) or die("Call PRR915 prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
		
		db2_bind_param($stmt, 1, 'date1', DB2_PARAM_IN);
		db2_bind_param($stmt, 2, 'date2', DB2_PARAM_IN);
		
		db2_execute($stmt) or die("Call PRR915 execute error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
	} 
	
	// Calculate the Monday before the previous steering committee meeting  
	// and the Sunday before the next steering committee meeting 
	// The proposed Steering Committe meeting is scheduled for the first Thursday
	// of each month. - Dennis Cote 7/19/2012 Proj #120021
	
	$Today_Date = date('Ymd');
	
	$First_Thurs = strtotime("first thursday",mktime(0,0,0,date("n")-1,date('t',mktime(0,0,0,date('n')-1))));
	
	$First_Thurs_This_Month = date('Ymd',$First_Thurs);
	
	$NextMonth = strtotime(date('Ymd', strtotime($Today_Date)) . " +1 month");
	
	$First_Thurs = strtotime("first thursday",mktime(0,0,0,date("n",$NextMonth)-1,date('t',mktime(0,0,0,date('n',$NextMonth)-1))));
	
	$First_Thurs_Next_Month = date('Ymd',$First_Thurs);
	
	$LastMonth = strtotime(date('Ymd', strtotime($Today_Date)) . " -1 month");
	
	$First_Thurs = strtotime("first thursday",mktime(0,0,0,date("n",$LastMonth)-1,date('t',mktime(0,0,0,date('n',$LastMonth)-1))));
	
	$First_Thurs_Last_Month = date('Ymd',$First_Thurs);
	
	
	If ($Today_Date > $First_Thurs_This_Month) {
		
		$Last_Meeting = date("Ymd", strtotime("$First_Thurs_This_Month -3 days"));
		$Next_Meeting = date("Ymd", strtotime("$First_Thurs_Next_Month -4 days"));
	}
	Else {
		
		$Last_Meeting = date("Ymd", strtotime("$First_Thurs_Last_Month -3 days"));
		$Next_Meeting = date("Ymd", strtotime("$First_Thurs_This_Month -4 days"));
	}
	
	include("PROJ_Reports_dsp.php"); // don't forget to change file name here

	$screenData = array();
	//putLCCOnlineLogRec("\n Last meeting: " . $Last_Meeting . " <");
	//putLCCOnlineLogRec("\n Next meeting: " . $Next_Meeting . " <");
	$screenData['fromDate'] = generateDateInput('scWorkload', 'fromDate', "$Last_Meeting", $Last_Meeting);
	//$screenData['fromDate'] = "<input type='text' id='fromDate' name='fromDate'  class='date' value='05/03/2021' /> <script language='JavaScript'> new tcal ({'formname': 'scWorkload','controlname': 'fromDate'}) </script>";
	//putLCCOnlineLogRec("\n From Date: " . $screenData['fromDate']);
	$screenData['toDate'] = generateDateInput('scWorkload', 'toDate', "$Next_Meeting", $Next_Meeting);
	//$screenData['toDate'] = "<input type='text' id='toDate' name='toDate'  class='date' value='05/30/2021' /> <script language='JavaScript'> new tcal ({'formname': 'scWorkload','controlname': 'toDate'}) </script>";
	//putLCCOnlineLogRec("\n To Date: " . $screenData['toDate']);
	$screenData['pgmrFromDate'] = generateDateInput('pgmrActivity', 'pgmrFromDate', "$Last_Meeting", $Last_Meeting);
	
	$screenData['pgmrToDate'] = generateDateInput('pgmrActivity', 'pgmrToDate', "$Next_Meeting", $Next_Meeting);
	
	unset ($_POST);
	
	dspProjReports($screenData); // change funcName to match _dsp.php
?>
<!--  End Content Here -->

<?php

} //end authority check "if"

	include("EndBlock.php");
?>