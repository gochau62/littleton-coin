<?php function getEntry($db) {
 
   $result0 = mysqli_query($db, "Select req_name From lcc.ReqMaterial_ReqNames");
   $result1 = mysqli_query($db, "Select area_code, acdesc From lcc.ReqMaterial_AreaCode");
   $result2 = mysqli_query($db, "Select area_type From lcc.ReqMaterial_AreaType");
   $result3 = mysqli_query($db, "Select authorized_by From lcc.ReqMaterial_AuthBy");
   $num0 = mysqli_num_rows($result0);
   $num1 = mysqli_num_rows($result1);
   $num2 = mysqli_num_rows($result2);
   $num3 = mysqli_num_rows($result3);
 
   print "
<title>Requisition Material</title>
<script type=\"text/javascript\" src=\"RQUtils/RQSuper.js\"></script> 
<script type=\"text/javascript\" src=\"../utils/Clock.js\"></script>
<script language=\"JavaScript\" type=\"text/javascript\">
<!-- 
";
   //print "//result0=". $result0 . "\n//num0=".$num0;
   print "

function getInsert()
{
	document.frmGetEntry.submit();
}
    
function getFocus() {
	document.getElementById('itm0').focus();
}
// -->
</script>
</head>
<body onload=\"startclock()\">  
<form method=\"post\" name=\"frmGetEntry\" action=\"getInsert.php\" onload=\"getFocus()\">
<font size=\"2\" color=\"#000000\" face=\"Arial\">
<div>
<table width=\"70%\" bgcolor=\"#FFFFFF\" border=\"0\" cellpadding=\"1\" cellspacing=\"1\">
	<tr valign=\"top\"> 
	<td>Requestor: 
	<select name=\"req_name\">
	";
$i=0;
while ($i < $num0) {
	If ($num0<=0) {
       print "Sorry, no matching records....";
    } 
    else {
         $req0=mysqli_result($result0,$i,"req_name");
		 print "
		<option value=\"$req0\">$req0</option>";
		 $i++;
	}
}	
print "		
	</select>
	<td></td>
	<td>Date:<input type=\"text\" name=\"date\" value=\"\" id=\"f_date_b\" />
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	</tr>
	<tr valign=\"top\">
		<td>Rush: 
			<input type=\"radio\" name=\"rush\" value=\"-1\"> Yes
			<input type=\"radio\" name=\"rush\" checked=\"true\" value=\"0\"> No
    	</td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
	</tr>
	<tr valign=\"top\">
		<td> Area Code:<br>
			<select name=\"area_code\">
	";
$i=0;
while ($i < $num1) {
	If ($num1<=0) {
       print "Sorry, no matching records....";
    } 
    else {
         $req=mysqli_result($result1,$i,"area_code");
		 $req2=mysqli_result($result1,$i,"acdesc");
		 print "
		 		<option value=\"$req\">$req2</option>";
		 $i++;
	}
}	
print "
			</select>
		</br>
		</td>
		<td> Area Type:<br>
		<select name=\"area_type\">
	";
  $i=0;
while ($i < $num2) {
	If ($num2<=0) {
       print "Sorry, no matching records....";
    } 
    else {
         $req2=mysqli_result($result2,$i,"area_type");
		 print "
		 	<option value=\"$req2\">$req2</option>";
		 $i++;
	}
}	
print "
		</select>
		</br>
		</td>
		<td> Authorized By: <br>
			<select name=\"authorized_by\">
	";
$i=0;
while ($i < $num3) {
	If ($num3<=0) {
       print "Sorry, no matching records....";
    } 
    else {
         $req3=mysqli_result($result3,$i,"authorized_by");
		 print "
		 		<option value=\"$req3\">$req3</option>";
		 $i++;
	}
}	
print "	
			</select>
			</br>
		</td>
		<td></td>
		<td></td>
		<td></td>
   		<td></td>
   	</tr>
</table>
</div>
<br>
<div>
<table width=\"70%\" units=\"relative\" bgcolor=\"#FFFFFF\" border=\"1\" cellpadding=\"1\" cellspacing=\"1\">
   <tr valign=\"top\">
     <th width=\"12\">Item#:</th>
     <th width=\"8\">Location:</th>
     <th width=\"8\">Item Date:</th>
     <th width=\"50\">Description: </th>
     <th width=\"6\">Qty:</th>
     <th width=\"6\">Cost $:</th>
     <th width=\"6\">Retail$:</th>
     <th width=\"6\">Add Cost $:</th>
     <th width=\"12\">SKU to:</th>
   </tr>";
		
$i=0;
while ($i < 30) {
	$j=$i+1;
    print " 
		<tr valign=\"top\">
		  <td width=\"12\"><input type=\"text\" id=\"itm$i\" name=\"item$i\" size=\"12\" value=\"\" onchange=\"dRec(document.getElementById('itm$i').value,'dsc$i','cst$i','rtl$i','dt$i');document.getElementById('lc$i').focus();\"></td>
		  <td width=\"8\"><input type=\"text\" id=\"lc$i\" name=\"loc$i\" size=\"8\" value=\"\" onkeypress=\"onEnterKey(event,'dt$i')\"></td>
		  <td width=\"8\"><input type=\"text\" id=\"dt$i\" name=\"date$i\" size=\"8\" value=\"\" onkeypress=\"onEnterKey(event,'dsc$i').focus()\"></td>
		  <td width=\"50\"><input type=\"text\" id=\"dsc$i\" name=\"desc$i\" size=\"50\" value=\"\" onkeypress=\"onEnterKey(event,'qt$i').focus()\"></td>
		  <td width=\"6\"><input type=\"text\" id=\"qt$i\" name=\"qty$i\" size=\"6\" value=\"\" onkeypress=\"onEnterKey(event,'cst$i').focus()\"></td>
		  <td width=\"6\"><input type=\"text\" id=\"cst$i\" name=\"cost$i\" size=\"6\" value=\"\" onkeypress=\"onEnterKey(event,'rtl$i').focus()\"></td>
		  <td width=\"6\"><input type=\"text\" id=\"rtl$i\" name=\"retail$i\" size=\"6\" value=\"\" onkeypress=\"onEnterKey(event,'act$i').focus()\"></td>
		  <td width=\"6\"><input type=\"text\" id=\"act$i\" name=\"acost$i\" size=\"6\" value=\"\" onkeypress=\"onEnterKey(event,'skt$i').focus()\"></td>
		  <td width=\"12\"><input type=\"text\" id=\"skt$i\" name=\"sku$i\" size=\"12\" value=\"\" onkeypress=\"onEnterKey(event,'itm$j').focus()\"></td>
		</tr>";
    $i++;
}
print "
</table>
</div>
<br>Comments: <input type=\"text\" name=\"comments\" size=\"100\" value=\"$comments\" /><br>
<INPUT type=\"button\" value=\"Insert\" onclick=\"getInsert()\" />
</font>
</form><hr>
<!-- End of FORM -->
</body>   
<br>";

}

?>