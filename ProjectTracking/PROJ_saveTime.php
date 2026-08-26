<?php 
require_once 'Utils/default_values.php';
session_name(SESSION_NAME);
session_start();

	require_once 'Utils/common_functions.php';
//	require_once '/www/php_dev/htdocs/i5Toolkit_library/Toolkit_Classes.php';
	if (isset($_POST['proj']) && isset($_POST['date']) && isset($_POST['time'])) {	
//	if (isset($_GET['proj']) && isset($_GET['date']) && isset($_GET['time'])) {	
//		var_dump($_GET);
// kjr - 220182
		$user = trim(  (is_null($_SESSION['username']) ? '' : $_SESSION['username'])  );
		$password = $_SESSION['password'];
		
//		$conn = geti5PConn($user, $password);
		$conn = getDB2PConn($user, $password);
	
	// send dev user ID when $user is an EPGMR
	// add ternary operation to avoid passing null to avoid deprecation - kjr - 08/14/23
	if (strlen(trim(   (is_null($_SESSION['altUserNm']) ? '' :  $_SESSION['altUserNm'])   )) >= 1) {
		$timeUser = $_SESSION['altUserNm'];
	} else {
		$timeUser = $user;
	}
	
	$userName = $user;
	$projNum = $_POST['proj'];
	$projDate = (float) $_POST['date'];
	$projTime = (float) $_POST['time'];
	
	$callStmnt = "Call PT0029S(?,?,?,?)";
	if (trim($userName) == '') {
		//die("Stale SESSION - transaction cancelled");
		// Project # 240205 - stale sessions were causing blank usernames to put time against projects 
		// (stale session, i.e. user leaves time tracking in browser overnight, then tries to apply time the next day without refreshing the page)
		// the conditional execution of the stored procedure and the HTTP response code below will prevent this from happening and notify user of issue if they attempt to use a stale session
		// kjr - 12/30/24
		http_response_code(404);
	} else {
	$stmt = db2_prepare($conn, $callStmnt);
	
	db2_bind_param($stmt, 1, "userName", DB2_PARAM_IN);
	db2_bind_param($stmt, 2, "projNum", DB2_PARAM_IN);
	db2_bind_param($stmt, 3, "projDate", DB2_PARAM_IN);
	db2_bind_param($stmt, 4, "projTime", DB2_PARAM_IN);
			

	db2_execute($stmt)	
		or die("<br>db2_execute nextnumber failed! ". db2_stmt_error($stmt));
	}
}
?>
