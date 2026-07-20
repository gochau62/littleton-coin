<? 
include 'RQUtils/dbStr.php';
getCon();
$req=$_REQUEST[req];
$auth=$_REQUEST[auth_by];
$comments=$_REQUEST[comments];

$query="Update ReqMaterial set authorized_by='$auth',authorized='1',comments='$comments' Where req_num='$req'";

$result=mysqli_query($query);
getClose();

print "<html><head><title>Requested Material.</title><form action=\"\" method=\"\">
      </tr><font size=\"2\" color=\"#000000\" face=\"Arial\">
      <div><table width=\"80%\"  border=\"0\" cellpadding=\"0\" bordercolor=\"#000000\" cellspacing=\"1\"> 
	  \"Record req_num=$req has been updated...\"
  </tr></head><body><form>";

?>


