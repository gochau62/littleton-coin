<?php
	include("StartBlock.php");
?>
<!--javascript includes go here-->
<script type='text/javascript' src='WebNotes/WebNote_JS_functions.js'></script>


<script type="text/javascript">
	document.title = "All Comments View";
</script>

<script type="text/javascript">
function getProjComments() {
	var proj = document.getElementById('projectNumber').value;
	window.location = "PROJ_allComntView_ctl.php?projnum=" + proj;
	
}
</script>


<!--  Begin Content Here -->
<?php
//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline
	// get connection - user and password are defined in StartBlock.php
$conn = getDB2PConn($user, $password);
	
if (chkAutUsr($conn, $user, "LCCONLINE", 20) != "yes") {
	showNotAuthorized();
//$authConn = geti5Conn($user, $password);
//$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);
//i5_close($authConn);
//
//if ($authorized != "yes") {
//	showNotAuthorized ();
} else {
	
	//	php includes go here
	//				-- EXAMPLE --
	require_once("WebNotes/webNotesModel.php");
	require_once ("PROJ_allComntView_dsp.php"); // don't forget to change file name here
//	include("PROJ_dsp.php"); // don't forget to change file name here
	require_once ("PROJ_model.php"); // don't forget to change file name here
//	$conn = geti5PConn ( $user, $password );
	
	$screenData = array();
	
	if (is_numeric ( $_GET ['projnum'] )) {
		$screenData['projnum'] = $_GET ['projnum'];
		$projRecord = getRecordPRPROJP ( $conn, $_GET['projnum'] );
	} elseif ($_GET ['projnum'] == 'prompt' || !isset($_GET['projnum'])) {
//		i5_close ( $conn );
		showProjPrompt ();
//		return;
	}
	
	if (empty ( $projRecord )) {
//		i5_close ( $conn );
		showProjPrompt();
		return;
	} else {
		
		$prefix = 'PROJ_';
		$projStrg = trim ( $projRecord['PR#'] );
		while ( strlen ( $projStrg ) < 6 ) {
			$projStrg = "0" . $projStrg;
		}

		// Get db2 records
		$notes = getRecordsWebNotes ( $projStrg, $prefix, $conn );
		$i = 0;
		foreach ( $notes as $note ) {
			// make 'time' 6 chatacters long
			while ( strlen ( trim ( $note ['WNTIME'] ) ) < 6 ) {
				$note ['WNTIME'] = '0' . $note ['WNTIME'];
			}
			
			$note ['WNPREFIX'] = trim ( $note ['WNPREFIX'] );
			$note ['WNIDVAL'] = trim ( $note ['WNIDVAL'] );
			$note ['WNPATH'] = trim ( $note ['WNPATH'] );
			
			// Set some generic parameters
			$comntHead = "<br/><b>" . formatDateSlashes ( $note ['WNDATE'] ) . " " . $note ['WNUSER'];
			$fh = $note ['WNPATH'] . "/" . $note ['WNPREFIX'] . $note ['WNIDVAL'] . $note ['WNDATE'] . $note ['WNTIME'];
			
			$i += 1;
			switch (trim($note ['WNTYPE'])) {
				case 'Descrip' : // Description
					$comntHead .= " - Description</b><br/>";
					$scDescFound = true;
					$screenData ['projComntAll'][$i] = $comntHead;
					$screenData ['projComntAll'][$i] .= "<div id='projComntAll' class='webNoteDesc'>" . file_get_contents ( $fh ) . "</div>";
					$screenData ['projComntAll'][$i] .= "<br/>";
					
					break;
				case 'ComntGen' : // General comments
					// Show date and user for each comment
					$comntHead .= " - General Comment</b><br/>";

					$screenData ['projComntAll'] [$i] = $comntHead;
					$screenData ['projComntAll'] [$i] .= "<div id='projComntAll" . $i . "' class='webNoteGen'>";
					$screenData ['projComntAll'] [$i] .= file_get_contents ( $fh ) . "<br/>";
					$screenData ['projComntAll'] [$i] .= "</div>";
					
					break;
				case 'ComntIT' : // IT comments
					$comntHead .= " - IT Comment</b><br/>";

					$screenData ['projComntAll'] [$i] = $comntHead;
					$screenData ['projComntAll'] [$i] .= "<div id='projComntAll" . $i . "' class='webNoteIT'>";
					$screenData ['projComntAll'] [$i] .= file_get_contents ( $fh ) . "<br/>";
					$screenData ['projComntAll'] [$i] .= "</div>";
//					
					break;
				case 'ComntPB' : // Payback comments
					$comntHead .= " - Payback Comment</b><br/>";

					$screenData ['projComntAll'] [$i] = $comntHead;
					$screenData ['projComntAll'] [$i] .= "<div id='projComntAll" . $i . "' class='webNotePB'>";
					$screenData ['projComntAll'] [$i] .= file_get_contents ( $fh ) . "<br/>";
					$screenData ['projComntAll'] [$i] .= "</div>";
//					
					break;
				case 'ComntSC' : // Steering Committee comments
					$comntHead .= " - Steering Committee Comment</b><br/>";

					$screenData ['projComntAll'] [$i] = $comntHead;
					$screenData ['projComntAll'] [$i] .= "<div id='projComntAll" . $i . "' class='webNoteSC'>";
					$screenData ['projComntAll'] [$i] .= file_get_contents ( $fh ) . "<br/>";
					$screenData ['projComntAll'] [$i] .= "</div>";
//					
					break;
			}
		}
//		i5_close($conn);
	
	showAllComntsView($screenData); // change funcName to match _dsp.php	
	}

?>
<!--  End Content Here -->

<?php

} //end authority check "if"

	include("EndBlock.php");
?>