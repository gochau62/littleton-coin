<?php
//	include("StartBlock.php");
	require_once("Utils/common_functions.php");
	require_once("PROJ_model.php");
	require_once("/../utils/EZMail.php");
		
	// initialize variables
// $today defined in common_functions.php
// $now defined in common_functions.php
$requestor = "NONE"; // for initialization


//--------------------------------------------//
//----------Begin here------------------------//
//--------------------------------------------//
// freq = frequency (D-daily W-Weekly blank-both)
if (isset($_GET)) {
	$conn = geti5Conn("DWHITEHEAD", "XXXXXXXXX"); // Dev
//	$conn = geti5Conn("webdog", "w3bd0g13"); // Production

	if (isset($_GET['freq'])) {
		$freq = $_GET['freq']; // D-daily or W-weekly
	} else {
		$freq = "B"; // B = both
	}
	
	$requestor = 'DWHITEHEAD';
//	$requestor = $_GET['user'];

	$chgTable = buildProjChgTable($conn, $requestor, $freq);

	// update date and time stamps in PRNTFPRFP
	$updDateTime = "Update PRNTFPRFP Set PNTLSTSNTD = " . $today . ", PNTLSTSNTT = " . $now
				  . " Where PNTUSER = '" . $requestor . "'";
	// If $freq = D or B update D recs
//	if ($freq == "D" or $freq == "B") {
	if ($freq == "D") {
		$updDateTime .= " And PNTFREQNCY = 'D'";
	}
	// If $freq = W or B update W recs
//	if ($freq == "W" or $freq == "B") {
	if ($freq == "W") {
		$updDateTime .= " And PNTFREQNCY = 'W'";
	}
//	excUpdateSQL($conn, $updateDateTime);
		

	switch ($freq) {
		case "D":
			$subject = "Daily Project Changes Report"; 
			break;
		case "W":
			$subject = "Weekly Project Changes Report";
			break;
		case "B":
			$subject = "On-Demand Project Changes Report";
	}
	$msgBody = "<h1>" . $subject . " for " . $_GET['user'] . "</h1>";
	$msgBody .= "<h4>Report ran on " . formatDate($today) . " at " . formatTime($now) . ".</h4>";
	$msgBody .= $chgTable;
	
	$message = file_get_contents("EmailTemplate.html");
	
	$message = str_replace("BODYPLACEHOLDER", $msgBody, $message);
	
//--    Call CL program to send email   --//
//	if ($freq == 'D' || $freq == 'W') {
		$toAddr = excSelectSQL($conn, "Select LCEMAILNM From LCEMPLOYP Where LCUSRID = '" . $requestor . "'");
//	}
		i5_close($conn);
//		var_dump($toAddr);
		$toAddr = $toAddr[0]['LCEMAILNM'];
//		echo "<br/><br/>";
//		var_dump($toAddr);
//		$msgKey = "PRJCHG" . trim($requestor) . $now; // 25 char
	
		$fromAddr = "events@littletoncoin.com";
		$failAddr = "eventemailfail@littletoncoin.com";
		$attachPath = "";
		$attachFile = "";
		$errorFlag = "";
	
		
	
		sendMSG($toAddr, 
				$subject, 
				$message, 
				$fromAddr, 
				$failAddr, 
				$attachFile);
//		sendLCCEmail($conn,
//					 $toAddr, 
//					 $subject, 
//					 $message, 
//					 $msgKey, 
//					 $fromAddr, 
//					 $failAddr, 
//					 $attachPath, 
//					 $attachFile, 
//					 $errorFlag);
	
}	
//	echo "To Address = " . $toAddr . "<br/>";
//		echo "Subject = " . $subject ."<br/>";
//		echo "FromAddr = " . $fromAddr . "<br/>";
//		echo "Fail Addr = " . $failAddr . "<br/>";		
//echo "<br/>";
//echo $chgTable;
//echo $message;
//echo "Complete<br/>";


?>