<?php
if(!strpos($_SERVER['HTTP_USER_AGENT'],'Firefox') && !strpos($_SERVER['HTTP_USER_AGENT'],'Minefield')) { 
	print "Sorry, you need to use <big><b>Firefox</b></big> for this site...<br>";
	print "you are using ". $_SERVER['HTTP_USER_AGENT'];
	exit;
} 
//print "you are using ". $_SERVER['HTTP_USER_AGENT'];
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="LCC1"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<b>Sorry, your not logged in....</b>';
    exit;
} else {
    //echo "<p>Hello {$_SERVER['PHP_AUTH_USER']}.</p>";
    //echo "<p>You entered {$_SERVER['PHP_AUTH_PW']} as your password.</p>";
}
//echo $_SERVER['HTTP_USER_AGENT'] . "\n\n";
//$browser=get_browser(null,true);
//echo $browser;
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

include 'RQUtils/dbStr.php';
$css="RQUtils/LCCweb.css";
$db=getCon();
$id=$_GET['id'];

print "
<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
<META http-equiv=\"expires\" content=\"-1\">
<META HTTP-EQUIV=\"REFRESH\">
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<html>
<head>
<link rel=\"stylesheet\" href=\"$css\" ... />  "; 
if (!$id==NULL) { //we want to see a record in the database

   include 'getIdInfo.php';
   getIdInfo($db,$id);

} else { //we want to add records to the database....

   include 'getEntry.php';
   getEntry($db);
   
}  //footer
   print "
</html>";  
getClose($db);
?>

