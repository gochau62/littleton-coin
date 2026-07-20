<?php function getIdInfo($db,$id) {
    
   $query="Select t0.req_name as name,t0.req_date as date,t0.area_code,t0.area_type,t0.authorized_by,t0.comments,t1.* From ReqMaterial t0 Left Join ReqMaterialDetails t1 On t0.req_num=t1.req_num Where t0.req_num='$id'";
   $result=mysqli_query($db,$query);
   $num=mysqli_num_rows($result);

print "<title>Requested Material by Id.</title>
   <SCRIPT language=\"JavaScript\" type=\"text/javascript\">
   <!-- 

   function getUpdate()
   {
      document.forms[0].submit();
   }
   // -->
   </SCRIPT>
   
   <form action=\"getUpdate.php\" method=\"post\">
      </tr><font size=\"2\" color=\"#000000\" face=\"Arial\">
      <div><table width=\"80%\"  border=\"0\" cellpadding=\"0\" bordercolor=\"#000000\" cellspacing=\"1\"> 
      <tr valign=\"top\"><td width=\"10\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Item#:</div></font></td>
      <td width=\"10\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Location:</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Date:</div></font></td>
      <td width=\"237\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Description:</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Qty:</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Cost:</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Retail:</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>Add. Cost</div></font></td>
      <td width=\"40\"><font size=\"2\" color=\"#000000\" face=\"Arial\"><div>SKU To:</div><div>�</div></font></td>
   </tr></head><body><form>";

   $i=0;
   while ($i < $num) {
      If ($num<=0) {
         print "Sorry, no matching records....";
      } 
      else {
         $req=mysqli_result($result,$i,"req_num");
         $name=mysqli_result($result,$i,"name");
         $item=mysqli_result($result,$i,"item_num");
         $loc=mysqli_result($result,$i,"loc");
         $cdate=mysqli_result($result,$i,"coin_date");
         $desc=mysqli_result($result,$i,"description");
         $qty=mysqli_result($result,$i,"quantity");
         $cost=mysqli_result($result,$i,"cost");
         $retail=mysqli_result($result,$i,"retail");
         $area=mysqli_result($result,$i,"area_type");
         $acode=mysqli_result($result,$i,"area_code");
         $date=mysqli_result($result,$i,"date");
         $denum=mysqli_result($result,$i,"badge");
         $comments=mysqli_result($result,$i,"comments");
         IF (mysqli_result($result,$i,"returned")==0){
	        $returned='No';
	     }
	     else {
	        $returned='Yes';
	     }
         if (mysqli_result($result,$i,"authorized_by")==NULL) {
		    $auth_by="!!!NOT AUTHORIZED!!!"; 
		 }
		 else {
			$auth_by=mysqli_result($result,$i,"authorized_by");
		 }

         if ($i==0) { 
		    print "
			ID: <input type=\"text\" name=\"req\" value=\"$req\"><br> 
            Name: <input type=\"text\" name=\"name\" value=\"$name\"><br>
            Area Code: <input type=\"text\" name=\"acode\" value=\"$acode\"><br>
            Area Type: <input type=\"text\" name=\"area\" value=\"$area\"><br>
            Date: <input type=\"text\" name=\"date\" value=\"$date\"><br>
            Inv DE Number: <input type=\"text\" name=\"denum\" value=\"$denum\"><br>
            <OPTGROUP label=\"Returned\">
	           <OPTION label=\"Yes\" value=\"$returned\">Returned:  $returned</OPTION>
	        </OPTGROUP><br>
            Authorized By: <input type=\"text\" name=\"auth_by\" value=\"$auth_by\"><br> Comments: <input type=\"text\" name=\"comments\" value=\"$comments\"><br><hr><br>";
		   	
		 }
         print " 
         <tr valign=\"top\"><td width=\"12\"><input type=\"text\" name=\"item\" value=\"$item\" size=\"12\"/></td>
         <td width=\"8\"><input type=\"text\" name=\"loc\" value=\"$loc\" size=\"8\"/></td>
         <td width=\"8\"><input type=\"text\" name=\"cdate\" value=\"$cdate\" size=\"8\"/></td>
         <td width=\"50\"><input type=\"text\" name=\"desc\" value=\"$desc\" size=\"50\"/></td>
         <td width=\"6\"><input type=\"text\" name=\"qty\" value=\"$qty\" size=\"6\"/></td>
         <td width=\"6\"><input type=\"text\" name=\"cost\" value=\"$cost\" size=\"6\"/></td>
         <td width=\"6\"><input type=\"text\" name=\"retail\" value=\"$retail\" size=\"6\"/></td>
         <td width=\"6\"><input type=\"text\" name=\"acost\" size=\"6\"/></td>
         <td width=\"12\"><input type=\"text\" name=\"skuto\" size=\"12\"/></td>
         </tr>";

         $i++;
      }
   }
   print "</form></body><br><input type=\"button\" value=\"Update\" onclick=\"getUpdate()\" />";
}

?>
