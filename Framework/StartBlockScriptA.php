<!-- begin StartBlockScriptA.php -->
<?php
require_once ("Utils/default_values.php");
require_once ("Utils/common_functions.php");
session_name(SESSION_NAME);
session_start();

// an unsigned visit is headed for a refusal or the sign on; the address asked for is kept here so
// LogOnProcess can land the person back on it after they sign in. Every page includes this block
// first, so no tool needs a line of its own for this - the sign on validator only ever honors a
// local path and never index, LogOnProcess or LogOut, so nothing harmful can ride in through it
if (empty($_SESSION['username'])) {
	$_SESSION['return_after_logon'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
<title>LCC Online</title>
<link href="LCCOnline.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" href="favicon.ico" />
<script type='text/javascript' src='Utils/common_JS_functions.js'></script>
<script type='text/javascript'>
var gblSlashToday = "<?php echo date('m/d/Y') ?>";
</script>
<!-- end StartBlockScriptA.php -->
