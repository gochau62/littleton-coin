<?php
require_once 'Utils/default_values.php';
session_name(SESSION_NAME);
session_start(); 

if (isset($_SESSION['username']) and isset($_SESSION['password'])) {
	$user = $_SESSION['username'];
	$password = $_SESSION['password'];
}

require_once "Utils/common_functions.php";
require_once "PROJ_model.php";
require_once "LCDEPTP_model.php";

if (isset($_GET)) {
	switch ($_GET['action']) {
		case 'sidebarShowHide':
			$menu = $_GET['menuid'];
			$newClass = $_GET['mode'];
			
			$findString = "id='" . $menu . "' class='"; // add null coalescing operator below to avoid deprecation warning - kjr - 05/15/26 - WO#74426
			$startPos = stripos( ($_SESSION['sidebar'] ?? '') , $findString) + strlen($findString);
			// add null coalescing operator below to avoid deprecation warning - kjr - 05/15/26 - WO#74426
			$oldClass = substr( ($_SESSION['sidebar'] ?? ''), $startPos, 4);
			// add null coalescing operator below to avoid deprecation warning - kjr - 05/15/26 - WO#74426
			$_SESSION['sidebar'] = substr_replace( ($_SESSION['sidebar'] ?? '') , $newClass, $startPos, 4);
			
			
			
			break;
			
		case 'updatescreview':
			
			$conn = getDB2PConn($user, $password);

			$sql = "Call PTS0034S()";
			
			$stmt = db2_prepare($conn, $sql)
			or die ("Update SC Review prepare error: " . db2_stmt_error() . "<br/>" .
					"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
			db2_execute($stmt)
					or die("Update SC Review execute error: " . db2_stmt_error() . "<br/>" .
							"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
			
			break;
			
		case 'updatewrkload':
			$conn = getDB2PConn($user, $password);
			
			$fromDate = $_GET['startdate'];
			$toDate = $_GET['enddate'];
			
			$sql = "Call PTS0030S(?, ?)";
				
			$stmt = db2_prepare($conn, $sql)
			or die ("Update Workload prepare error: " . db2_stmt_error() . "<br/>" .
					"Error Msg: " . db2_stmt_errormsg() . "<br/>");
			
			db2_bind_param($stmt, 1, "fromDate", DB2_PARAM_IN);
			db2_bind_param($stmt, 2, "toDate", DB2_PARAM_IN);
				
			db2_execute($stmt)
			or die("Update Workload execute error: " . db2_stmt_error() . "<br/>" .
					"Error Msg: " . db2_stmt_errormsg() . "<br/>");
				
				
			break;
				
		case 'subdeptlist':
			$conn = getDB2PConn($user, $password);
			if (isset($_GET['dept'])) {
				// Get Sub Department list
				
				$recs = getRecsLCDEPTP($conn, $_GET['dept']);
				
				$options = "";
				foreach ($recs as $rec) {
					$options .= "<option value='" . $rec['LDSUBDEPT'] . "'>" . $rec['LDDESC'] . "</option>";
				}
				echo urlencode($options);
			}
			
			// XML tag name to use = subdeptlist
			
			break;
			
		case 'devgrplist':
			$conn = getDB2PConn($user, $password);
			if (isset($_GET['pgmr'])) {
				// Get dev rate
// 				$query = "Select * From PRIDTRANSP Where PGDEVPRF = ? For Fetch Only";
// 				$parms[] = $_GET['pgmr'];
				$idTransPFile = getPgmrListPRIDTRANSP($conn);
				foreach ($idTransPFile as $record) {
					if (trim($record['PGDEVPRF']) == trim($_GET['pgmr'])) {
						$rec = $record;
						break;
					}
				}
				
// 				$rec = excSelectSQL($conn, $query, $parms);
				$rtnXML = "<?xml version=\"1.0\" ?><outer>";
				$rtnXML .= "<devrate>" . $rec['PGRATE'] . "</devrate>";
				$rtnXML .= "<group>" . $rec['PGGROUP'] . "</group>";
				$rtnXML .= "</outer>";
				
				header('Content-Type: text/xml');
				echo $rtnXML;
			}
			
			break;
			
		case 'logcomment':
			
			switch (trim($_GET['type'])) {
				case 'ComntGen':
					$updText = "General comment"; // 15
					break;
				case 'ComntIT':
					$updText = "IT comment"; // 10
					break;
				case 'ComntSC':
					$updText = "SC comment"; // 10
					break;
				case 'Descrip':
					$updText = "Description"; // 11
					break;
				default:
					$updText = "";
			}
			
			
			switch (trim($_GET['mode'])) {
				case 'update':
					$updText .= " updated"; // 8 
					break;
				case 'add':
					$updText .= " added"; // 8 
					break; 
				case null:
					$updText .= " deleted"; // 8 
					break; 
			}
			
			$values[] = $_GET['id'];
			$values[] = date('Ymd');
			$values[] = date('His');
			$values[] = $user;
			$values[] = "WebNotes";
			$values[] = " ";
			$values[] = $updText; // 30 characters
			
			$conn = getDB2PConn($user, $password);
			insertPRCHGLOGP($conn, $values);
			
			break;
			
		case 'projGetPTSRPTDATPRec':
			$conn = getDB2PConn($user, $password);
				
			$lastRanNm = $_GET['rptName'];
			$returnRec = getPTSRPTDATPRec($conn, $lastRanNm);
				
			$rtnXML = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><record>";
		
			foreach($returnRec as $key => $value) {
				$rtnXML .= "<" . $key . ">" . htmlentities(trim($value)) . "</" . $key . ">";
			}
			
			$rtnXML .= "<FMTIME>" . htmlentities(trim(formatTime((double) $returnRec['RUN_TIME']))) . "</FMTIME>";
			
			$rtnXML .= "</record>";
					
			header('Content-Type: text/xml');
			echo $rtnXML;
			
			break;
	}
}
?>