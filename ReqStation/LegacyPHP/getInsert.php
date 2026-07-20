<?
   include 'RQUtils/dbStr.php';
   $db=getCon();
   
   $rname=$_REQUEST['req_name'];
   $rdate=$_REQUEST['date'];
   $acode=$_REQUEST['area_code'];
   $atype=$_REQUEST['area_type'];
   $auth=$_REQUEST['authorized_by'];
   $rush=$_REQUEST['rush'];
   $today=date("Y-m-d H:i:s");
   $comments=$_REQUEST['comments'];
   $shname=substr($rname,0,4); //found that changing to MySQL v8 this field would fail as it automatically truncated data to 4chars 07/01/26 BC

   //getting the table results and putting them into arrays
   for ($c=0;$c<30;$c++) { //grabbing a glob of ram....
      $im="item$c";
	  $lc="loc$c";
	  $dt="date$c";
	  $ds="desc$c";
	  $qt="qty$c";
	  $ct="cost$c";
	  $rt="retail$c";
	  $ac="acost$c";
	  $sk="sku$c";
      $itm[$c]=$_REQUEST[$im];
	  $loc[$c]=$_REQUEST[$lc];
	  $dat[$c]=$_REQUEST[$dt];
      $dsc[$c]=$_REQUEST[$ds];
      $qty[$c]=$_REQUEST[$qt];
      $cos[$c]=$_REQUEST[$ct];
      $ret[$c]=$_REQUEST[$rt];
      $acs[$c]=$_REQUEST[$ac];
      $sku[$c]=$_REQUEST[$sk];
   } 
   //print $itm;

   if ($rush==-1) { $trush='Yes'; } else{ $trush='No'; }
	echo "0k";
   //get parent table id
   $query="Select max(req_num)+1 as newreq From ReqMaterial";
   $result=mysqli_query($db, $query);
   echo $query;
   if (!$result) {
       $message  = 'Invalid query: ' . mysqli_error() . "\n";
       $message .= 'Whole query: ' . $query;
       die($message);
   }
   else {
	   $req=mysqli_result($result,$i,"newreq");
   } // add null coalescing operator below to avoid deprecation warning - kjr - 05/13/26 - wo#74406
   $comments = mysqli_real_escape_string($db, ($comments ?? '')); // escape special characters to avoid fatal errors - kjr - 07/14/23
   //do parent table insert first
   $query="Insert Into ReqMaterial (req_num,req_name,req_date,area_code,area_type,rush,authorized_by,comments) 
           values('$req','$rname','$today','$acode','$atype','$rush','$auth','$comments')";
   $result=mysqli_query($db, $query);
   if (!$result) {
       $message  = 'Invalid query: ' . mysqli_error() . "\n";
       $message .= 'Whole query: ' . $query;
       die($message);
   }
   //then do the children
   for ($i=0;$i<30;$i++){
      $varnull=0; // add null coalescing operator below to avoid deprecation warning - kjr - 05/13/26 - wo#74406
	  if (!strlen( ($itm[$i] ?? '') )==$varnull) {
	     if (empty($acs[$i])) { $acs[$i]=0; } //needed for blank space and non zero 07/01/26  BC
         $query="Insert Into ReqMaterialDetails (req_num,item_num,loc,coin_date,description,quantity,cost,retail,badge,add_cost,sku_to) values('$req','$itm[$i]','$loc[$i]','$dat[$i]','$dsc[$i]','$qty[$i]','$cos[$i]','$ret[$i]','$shname','$acs[$i]','$sku[$i]')";   //changed to $shname 07/01/26 BC	  
	     print "<html><head><title>Requested Material Insert.</title><form method=\"\" action=\"\"></tr><font size=\"2\" color=\"#000000\" face=\"Arial\">
                Item$i: $req, $itm[$i], $loc[$i], $dat[$i], $dsc[$i], $qty[$i], $cos[$i], $ret[$i], $acs[$i], $sku[$i] <br>\n";
	     //print "query: " . $query . "<br>\n"; //helps with debug 07/01/26 BC 
	     $result=mysqli_query($db, $query);
	  }
   }
   if (!$result) {
       $message  = 'Invalid query: ' . mysqli_error() . "\n";
       $message .= 'Whole query: ' . $query;
       die($message);
   }
    
   print "Request inserted... <br><bre>
         Name: <input type=\"text\" name=\"name\" value=\"$rname\"><br>
         Date: <input type=\"text\" name=\"date\" value=\"$today\"><br> 
         Area Code: <input type=\"text\" name=\"acode\" value=\"$acode\"><br>
         Area Type: <input type=\"text\" name=\"area\" value=\"$atype\"><br> 
         Rush: <input type=\"text\" name=\"rush\" value=\"$trush\"><br> ";

   getClose($db);
   print "
   <br><FORM> <a href=\"request.php\">Back!</a> </FORM><BR> ";
   
?>