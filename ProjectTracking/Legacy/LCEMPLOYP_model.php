<?php
function getRecLCEMPLOYP($conn, $userId) { 
	
	if (isset($userId) && isset($conn)) {

		$callStmnt = "Call LCC0002S(?)";

		$stmt = db2_prepare($conn, $callStmnt)
			or die("Get LCEMPLOYP rec prepare error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
		db2_bind_param($stmt, 1, "userId", DB2_PARAM_IN);
			
		db2_execute($stmt)
			or die("Get LCEMPLOYP recs execute error: " . db2_stmt_error() . "<br/>" .
			"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
		// Fetch the results
		while ($row = db2_fetch_assoc($stmt)) {
			$records[] = $row;
		}
	}
	return $records;	

}
?>