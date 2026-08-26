<?php

function getRecsLCDEPTP($conn, $lccDept="ALL", $lccSubDept="ALL") { 
	if (isset($conn)) {
		ob_start();
		var_dump($conn);
		$connString = ob_get_contents();
		ob_end_clean();
		//echo "db2 connection = " . $db2conn . "<br/>";
		
		if (substr_count($connString, "DB2") >= 1) {
			$callStmnt = "Call LCC0001S(?, ?)";

			$stmt = db2_prepare($conn, $callStmnt)
				or die("Get LCDEPTP recs prepare error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
			db2_bind_param($stmt, 1, "lccDept", DB2_PARAM_IN);
			db2_bind_param($stmt, 2, "lccSubDept", DB2_PARAM_IN);
			
			db2_execute($stmt)
				or die("Get LCDEPTP recs execute error: " . db2_stmt_error() . "<br/>" .
				"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
			// Fetch the results
			while ($row = db2_fetch_assoc($stmt)) {
				$records[] = $row;
			}
			
		} else { // i5 connection
		
			$query = "SELECT * FROM LCDEPTP FOR FETCH ONLY";

			// Prepare the SQL Query for execution
			$stmt = i5_prepare( $query, $conn ) 
				or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());

			// Execute the query
			i5_execute( $stmt )
				or die("<br>Execute failed! " . i5_errormsg());
			
			while ($row = i5_fetch_assoc( $stmt )) {
				$records[] = $row;
			}
		}
	}
	return $records;	

}

 
?>