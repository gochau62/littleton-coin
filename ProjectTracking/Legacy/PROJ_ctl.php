<?php
	/*************************************************  
	* Page Name - PROJ_ctl.php                       *
	* Narrative - Project tracking entry and         *
	*             mainenance                         *
	* Author    - D Whitehead                        *
	*             Littleton Coin Company             *
	*             Littleton NH                       *
	* Date Written 02/25/2011                        *
	************************************************ */

	include("StartBlockHead.php");
?>
<!--<body onload="projCalcPayback(); setInitialTab('PROJ_mainTabs', 'tabGeneral', 'pageSection', 'general')">-->
<body onload="switchTab('PROJ_mainTabs', 'tabGeneral', 'pageSection', 'general')">
<?php
	include("StartBlockBody.php");
?>

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
	
// <!--  Begin Content Here -->
	require_once("WebNotes/webNotesModel.php");
//	require_once("Utils/common_functions.php");
	require_once("PROJ_dsp.php");
	require_once("PROJ_model.php");
	require_once("LCEMPLOYP_model.php");
	require_once("LNKDOCP_model.php");
	require_once("LCDEPTP_model.php");
	
	
	// get connection - user and password are defined in StartBlock.php
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