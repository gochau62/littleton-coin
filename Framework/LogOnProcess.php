<!DOCTYPE html PUBLIC
	"-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html>
<head>
<title>Log-On Form</title>
</head>

<body>
<?php
require_once ("Utils/common_functions.php");
require_once ("Utils/default_values.php");
//$dataLib = DATALIB; // put constant into variable for string interpolation

function setUserProfile($conn, $user, $pass) {
	$lccuser = strtoupper($user);
	$pass = strtoupper($pass);
	if (!$conn) {
		echo 'no connection';
		header('Location: index.php');
	} else {

	$sql = "Call LSCPGMLIB/PHP0020S(?)";

	$stmt = db2_prepare($conn, $sql);

	db2_bind_param($stmt, 1, "lccuser", DB2_PARAM_IN);

	db2_execute($stmt);
	// or die("Get user profile record execute error: " . db2_stmt_error() . "<br/>" .
			// "Error Msg: " . db2_stmt_error() . "<br/>");
	$record = db2_fetch_assoc($stmt);
    // EPGMR's UPGRPF = *NONE, UPUSCL = *PGMR
    // Development ID's UPGRPF = QPGMR, UPUSCL = *PGMR
    // Groups with UPUSCL = *USER
    //	- ADVERTISIN
    //	- ARANALYZER
    //	- CSTSERVICE87
    //	- DIRECTORS
    //	- ICV
    //	- INVENTORY
    //	- MANIFEST
    //	- ORDERPROC
    switch ($record['LCUPGRPF']) {
    	case "ADVERTISIN":
    	$_SESSION['dftPage'] = "Ad_home.php";
    	break;

    	case "CSTSERVICE":
    	case "ARANALYZER":
    	case "INVENTORY ":
    	case "ORDERPROC ":
    	$_SESSION['dftPage'] = "FF_home.php";
    	break;

    	case "QPGMR     ":
    	case "EPGMR     ":
    	case "ITOPS     ":
    	case "GRPPGMR   ":
    	case "*NONE     ":
    	$_SESSION['dftPage'] = "IT_home.php";
    	break;

    	default:
    	$_SESSION['dftPage'] = "Welcome_home.php";
    	break;
    }

	$_SESSION['longname'] = $record['LCUPTEXT'];
	$_SESSION['usrgroup'] = $record['LCUPGRPF'];
//	$_SESSION['usrstatus'] = $record[49];
	$_SESSION['usrclass'] = $record['LCUPUSCL'];

	$_SESSION['usrprf'] = $record;

	}
}
//kjr 220182
$user = strtoupper(trim(  (is_null($_POST['username']) ? '' : $_POST['username'])  ));
$pass = strtoupper(trim(  (is_null($_POST['password']) ? '' : $_POST['password'])  ));

if (!$user or !$pass) {

	header('Location: index.php?logon=invalid');
} else {

	if (!$conn = getDB2PConn($user, $pass)) {
			header('Location: index.php?logon=invalid');

	} else {
		if (chkAutUsr($conn, $user, "LCCONLINE", 10) == "yes") {
			session_name(SESSION_NAME); // SESSION_NAME is a constant defined in Utils/default_values.php
			session_start();
			if (substr($user, 0, 5) == 'EPGMR') {
				$altUserQry = "Select PGDEVPRF From PRIDTRANSP Where PGEPGMRPRF = '" . $user . "' For Fetch Only";
				$altUserRslt = excSelectSQL($conn, $altUserQry);
				$_SESSION['altUserNm'] = $altUserRslt[0]['PGDEVPRF'];
			}
			$_SESSION['username'] = $user;
			$_SESSION['password'] = $pass;
			$_SESSION['sidebar'] = loadLeftNav($conn, $user);

			// Get user LCEMPLOYP record
			$sql = "Call PHP0019S(?)";

			$stmt = db2_prepare($conn, $sql);

			db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);

			db2_execute($stmt);

			$row = db2_fetch_assoc($stmt);

			$_SESSION['email'] = trim($row['LCEMAILNM']);
			$_SESSION['department'] = trim($row['LCLCCDEPT']);
			$_SESSION['subdept'] = trim($row['LCSUBDEPT']);

			$_SESSION['lcemployp'] = $row;

			setUserProfile($conn, $user, $pass);

			// land back on the page the person signed in from, when the sign-in form carried one
			// only a local path on this site is honored, and never index, this processor, or the log out
			// page, so the redirect cannot loop, cannot sign the person straight back out, and cannot be
			// aimed off the box
			$returnTo = isset($_POST['return_to']) ? trim($_POST['return_to']) : '';
			if ($returnTo !== '' && substr($returnTo, 0, 1) === '/' && substr($returnTo, 0, 2) !== '//'
			    && stripos($returnTo, 'index.php') === false
			    && stripos($returnTo, 'LogOnProcess') === false
			    && stripos($returnTo, 'LogOut') === false) {
				header("Location: ".$returnTo);
			} else {
				header("Location: ".$_SESSION['dftPage']);
			}
		} else {
			header('Location: index.php?logon=invalid');
		}

	}
}

?>
</body>
</html>
