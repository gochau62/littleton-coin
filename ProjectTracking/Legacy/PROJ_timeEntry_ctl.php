<?php
	include("StartBlock.php");

?>
<script type='text/javascript' src='Utils/common_JS_functions.js'></script>
<script type='text/javascript' src='Utils/calendar_us.js'></script>
<script type='text/javascript' src='PROJ_JS_functions.js'></script>
<script type="text/javascript">
	document.title = "Programmer time entry";
</script>

<!--  Begin Content Here -->
<?php 
//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline
//include("Utils/common_functions.php");
$authConn = getDB2PConn($user, $password);
$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);

if ( $authorized != "yes") {
		showNotAuthorized();
} else {

	require_once ("PROJ_model.php");

	require_once ("PROJ_timeEntry_dsp.php"); 

	if (isset($_GET['addproj']) && (!in_array($_GET['addproj'], (array)$_SESSION['projTimeList']))) { //add array cast to session variable to prevent fatal error, post PHP8.1 upgrade - kjr - 09-06-22
		$_SESSION['projTimeList'][] = $_GET['addproj'];
	}
	// Use date from $_GET if available. Otherwise use 'this week' as date
	if (isset($_GET['date'])) {
		$wrkDate = date("Ymd", strToTime($_GET['date']));
	} else {
		$wrkDate = date("Ymd");
	}
	
	if (date("N", $wrkDate) == 0) { // day of the week. 0=Sunday 1=Monday ... 6=Saturday
	    // changed formatting character above from 'w' to 'N' after experiencing issues in 2024
	    // kjr - 01/04/24 - WO#66619
		$sunday = date("Ymd", strToTime($wrkDate));
	} else {
		$days = date("w", strToTime($wrkDate));
		$sunday = date("Ymd", strtotime($wrkDate . "-".$days." days"));
	}
	
	$sunTotal = 0;
	$monTotal = 0;
	$tueTotal = 0;
	$wedTotal = 0;
	$thuTotal = 0;
	$friTotal = 0;
	$satTotal = 0;
	
	// Ymd is yyyymmdd format
	$day[0] = $sunday;
	$day[1] = date("Ymd", strtotime("$sunday +1 days"));
	$day[2] = date("Ymd", strtotime("$sunday +2 days"));
	$day[3] = date("Ymd", strtotime("$sunday +3 days"));
	$day[4] = date("Ymd", strtotime("$sunday +4 days"));
	$day[5] = date("Ymd", strtotime("$sunday +5 days"));
	$day[6] = date("Ymd", strtotime("$sunday +6 days"));

	$longDate = date("l F j".', '.'o', strtotime($day[6])); //wrap o in quotes to avoid undefined constant error - 06-28-22 - kjr
	$prevWeek = date("Ymd", strtotime("$day[0] -7 days"));
	$nextWeek = date("Ymd", strtotime("$day[0] +7 days"));
	
	$screenData['lnkBack'] = "<a href='PROJ_timeEntry_ctl.php?date=" . $prevWeek . "'>&lt;&lt;</a>";
	
	$screenData['longDate'] = $longDate;
	
	$screenData['lnkForward'] = "<a href='PROJ_timeEntry_ctl.php?date=" . $nextWeek . "'>&gt;&gt;</a>";
	
			
	// Get project list
	
	$time = array();
	$projTime = array();
	// get list and set cookie
	
//	$conn = geti5PConn($user, $password);
	$conn = getDB2PConn($user, $password);
	// verify not null to avoid deprecation warning - kjr - 08/10/23
	if (!(is_null($_SESSION['altUserNm']))) {
    	if (strlen(trim($_SESSION['altUserNm'])) >= 1) {
    		$devUser = $_SESSION['altUserNm'];
    	} else {
    		$devUser = $user;
    	}
	} 
	else {
	    $devUser = $user;
	}
	$projWithTime = getProjsTimeEntered($conn, $devUser, $day[0], $day[6]);
	if (sizeof((is_countable($projWithTime) ? $projWithTime:[])) < 1) {
		$projWithTime = array();
	}
//	var_dump($projWithTime);
//	echo "<br/>";

	$includeComp = 'yes';
	$allProjects = getProjListAll($conn, $includeComp);
//	var_dump($allProjects);
//	echo "<br/>";
	
	foreach ($allProjects as $project) {
		if ((trim($project['PRPGMR']) == trim($devUser) && $project['PRECOM'] != 0 && $project['PRACOM'] == 0 && $project['PRRESCOD'] != 'REJ')
			 || ($project['PR#'] >= 90000 && $project['PR#'] <= 90100)
			 || (isset($_SESSION['projTimeList']) && in_array($project['PR#'], $_SESSION['projTimeList']))
			 || (in_array($project['PR#'], $projWithTime))
			 ) {
			// Get time records for this project
			
			$timeRecs = getProjUserTime($conn, $project['PR#'], $devUser);
			foreach ($timeRecs as $timeRec) {
				if(isset($time[$project['PR#']][$timeRec['PTDATE']])) {
					$time[$project['PR#']][$timeRec['PTDATE']] += $timeRec['PTTIME'];
					
				} else {
					$time[$project['PR#']][$timeRec['PTDATE']] = $timeRec['PTTIME'];
					$time[$project['PR#']]['Desc'] = $project['PRDESC'];
				}
				
			}

			$projTime[$project['PR#']]['Desc'] = trim($project['PRDESC']);  
			$projTime[$project['PR#']]['PR#'] = $project['PR#'];
			for ($i=0; $i<=6; $i++) {
				if (!isset($time[$project['PR#']][$day[$i]])) {
					$projTime[$project['PR#']][$i] = 0;
				} else {
					$projTime[$project['PR#']][$i] =  rtrim(rtrim($time[$project['PR#']][$day[$i]], "0"),".");
				}
  
			}
		}
	}
	
	unset($project);


	foreach ($projTime as $tmpProj) {
		
		if ($tmpProj['PR#'] <= 89999) {
			break;
		}
		if ($tmpProj['PR#'] > 89999) {
			$projTime[] = $tmpProj;
			array_shift($projTime);
		}
	}
	
	$screenData['timeTable'] = "<table><tr><th>Proj #</th><th>Description</th><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>";
	$i = 0;
	foreach ($projTime as $project) {
		
		$screenData['timeTable'] .= "<tr>" 
		."<td><a href='PROJ_ctl.php?projnum=" . $project['PR#'] . "'>" . $project['PR#'] . "</a></td>" 
		."<td>" . trim($project['Desc']) . "</td>" 
		."<td><input type='text' class='numData' size='1'
			id='sun" . $project['PR#'] . "'   
			name='sun' onchange=\"totalElementsByName('sun', 'sunTotal', '".$project['PR#']."', '".$day[0]."')\" value ='" . $project[0] . "'></td>"
		."<td><input type='text' class='numData' size='1' 
			id='mon" . $project['PR#'] . "' 
			name='mon' onchange=\"totalElementsByName('mon', 'monTotal', '".$project['PR#']."', '".$day[1]."')\" value ='" . $project[1] . "'></td>"
		."<td><input type='text' class='numData' size='1'  
			id='tue" . $project['PR#'] . "' 
			name='tue' onchange=\"totalElementsByName('tue', 'tueTotal', '".$project['PR#']."', '".$day[2]."')\" value ='" . $project[2] . "'></td>"
		."<td><input type='text' class='numData' size='1'  
			id='wed" . $project['PR#'] . "' 
			name='wed' onchange=\"totalElementsByName('wed', 'wedTotal', '".$project['PR#']."', '".$day[3]."')\" value ='" . $project[3] . "'></td>"
		."<td><input type='text' class='numData' size='1'  
			id='thu" . $project['PR#'] . "' 
			name='thu' onchange=\"totalElementsByName('thu', 'thuTotal', '".$project['PR#']."', '".$day[4]."')\" value ='" . $project[4] . "'></td>"
		."<td><input type='text' class='numData' size='1'  
			id='fri" . $project['PR#'] . "' 
			name='fri' onchange=\"totalElementsByName('fri', 'friTotal', '".$project['PR#']."', '".$day[5]."')\" value ='" . $project[5] . "'></td>"
		."<td><input type='text' class='numData' size='1'  
			id='sat" . $project['PR#'] . "' 
			name='sat' onchange=\"totalElementsByName('sat', 'satTotal', '".$project['PR#']."', '".$day[6]."')\" value ='" . $project[6] . "'></td>"
		."</tr>";
		$sunTotal += $project[0];
		$monTotal += $project[1];
		$tueTotal += $project[2];
		$wedTotal += $project[3];
		$thuTotal += $project[4];
		$friTotal += $project[5];
		$satTotal += $project[6];
		
		$i += 1;
	}
	
	$screenData['timeTable'] .= "<tr class='total'><td class='txtData' colspan='2'>&nbsp;&nbsp;&nbsp;&nbsp;TOTAL</td>"
		 . "<td id='sunTotal'>" . $sunTotal . "</td>"
		 . "<td id='monTotal'>" . $monTotal . "</td>"
		 . "<td id='tueTotal'>" . $tueTotal . "</td>"
		 . "<td id='wedTotal'>" . $wedTotal . "</td>"
		 . "<td id='thuTotal'>" . $thuTotal . "</td>"
		 . "<td id='friTotal'>" . $friTotal . "</td>"
		 . "<td id='satTotal'>" . $satTotal . "</td>"
		 . "</tr></table>";
	
	
	showTimeEntry($screenData); // change funcName to match _dsp.php
//<!--  End Content Here -->

} //end authority check "if"

	include("EndBlock.php");
?>