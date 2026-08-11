<?php
	/*************************************************  
	* Page Name - PROJ_tooltipMaint.php              *
	* Narrative -                                    *
	*                                                *
	* Author    -                                    *
	*             Littleton Coin Company             *
	*             Littleton NH                       *
	* Date Written xx/xx/2011                        *
	************************************************ */

	include("StartBlock.php");
//	require_once("PROJ_model.php");
function getRecEMLTMPLTP($conn, $key) {
	$rec = array();
	if (isset($key)) {
		$query = "SELECT * FROM EMLTMPLTP WHERE EMAKEY = ?";
		$executeArray[] = $key;
		
		// Prepare the SQL Query for execution
		$stmt = i5_prepare( $query, $conn ) 
			or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());

		// Bind the parameters
		//i5_bind_param($stmt, $projNum);	
			
		// Execute the query
		i5_execute( $stmt, $executeArray )
			or die("<br>Execute failed! " . i5_errormsg());
			
		// Fetch the results
		$rec = i5_fetch_assoc( $stmt ); 
	}

	return $rec;
	
}

function updRecEMLTMPLTP($conn, $field, $desc) {
	
	if (isset($conn) && !empty($field) && !empty($desc)) {
		$query = "Update EMLTMPLTP Set EMABODY = ? Where EMAKEY = ?";
		
		$executeArray[] = $desc;
		$executeArray[] = $field;
		
		// Prepare the SQL Query for execution
		$stmt = i5_prepare( $query, $conn ) 
			or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());

		// Execute the query
		i5_execute( $stmt, $executeArray )
			or die("<br>Execute failed! " . i5_errormsg());
			
	}

}

function getRecsEMLTMPLTP($conn) { 
	
	if (isset($conn)) {
		$query = "SELECT * FROM EMLTMPLTP FOR FETCH ONLY";

		// Prepare the SQL Query for execution
		$stmt = i5_prepare( $query, $conn ) 
			or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());
			
		// Execute the query
		i5_execute( $stmt)
			or die("<br>Execute failed! " . i5_errormsg());
			
		// Fetch the results
		while ($row = i5_fetch_assoc($stmt,I5_READ_NEXT)) {
//			var_dump($row);
			$returnVal[] = $row;
		} 
		
	}
	return $returnVal;	

}

function insertRecEMLTMPLTP($conn, $field, $desc) {
	
		if (isset($conn) && !empty($field) && !empty($desc)) {
		$query = "Insert Into EMLTMPLTP (EMAKEY, EMABODY) Values (?, ?)";

		// add parameter values to an array
		$executeArray[] = $field;
		$executeArray[] = $desc;
		
		// Prepare the SQL Query for execution
		$stmt = i5_prepare( $query, $conn ) 
			or die("<br>Prepare failed! <br>$query <br>". i5_errormsg());

		// Execute the query
		i5_execute( $stmt, $executeArray )
			or die("<br>Execute failed! " . i5_errormsg());
	}		
}
	
	
?>
<script type="text/javascript">
	document.title = "EmailMsg maint";
</script>
<script type="text/javascript">
function reloadTTM() {

	var mode = document.getElementById('modeupdate');

	if (mode.checked == true) {
	
		var field = document.getElementById('msgField').value;
		window.location = 'PROJ_EmlMsgMaint.php?msgid='+field+'&mode=update';
	}
}
</script>
<div id='stdPage'>
<!--  Begin Content Here -->
		<h1>Email Message Template Maintenance</h1>
		
<?php 
if (isset($_GET['msgid'])) {
	$conn = geti5Conn($user, $password);
	$emlTemplt = getRecEMLTMPLTP($conn, trim($_GET['msgid']));
//	var_dump($toolTip);
	i5_close($conn);
	
	$inputBox = "<input type='text' name='msgField' id='msgField' maxlength='25' size='20'" 
				. "onchange='reloadTTM()' value='"
				. $emlTemplt['EMAKEY'] . "' />";
	$msgText = $emlTemplt['EMABODY'];
	
} else {
	$inputBox = "<input type='text' name='msgField' id='msgField' maxlength='25' size='20'" 
				. "onchange='reloadTTM()' />";
	
}
if ($_POST['mode'] == 'update') {
//	var_dump($_POST);
	$conn = geti5Conn($user, $password);
	updRecEMLTMPLTP($conn, $_POST['msgField'], $_POST['msgText']);
	i5_close($conn);
	$inputBox = "<input type='text' name='msgField' id='msgField' maxlength='25' size='20'" 
				. "onchange='reloadTTM()' />";
	
}
if ($_POST['mode'] == 'add') {
	$conn = geti5Conn($user, $password);
	insertRecEMLTMPLTP($conn, $_POST['msgField'], $_POST['msgText']);
	i5_close($conn);
	$inputBox = "<input type='text' name='msgField' id='msgField' maxlength='25' size='20'" 
				. "onchange='reloadTTM()' />";
	
}

$mode = "<input type='radio' name='mode' id='modeupdate' value='update' ";

if ($_GET['mode'] == 'update' || $_POST['mode'] == 'update') {
	$mode .= "checked='true' /> Update &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
		. "<input type='radio' name='mode' id='modeadd' value='add' /> Add";
} else {
	$mode .= "/> Update &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
		. "<input type='radio' name='mode' id='modeadd' value='add' checked='true' /> Add";
}


?>
<form name='projEmlMsg' method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

<label>Field Name </label>
<?php echo $inputBox . " " . $mode ?>
<br/>
<br/>

<textarea name="msgText" rows="30" cols="110"><?php echo $msgText?></textarea>

<br/>
<br/>
<input type="submit" value="Save" />
</form>
<!--  End Content Here -->
</div>
<?php
	include("EndBlock.php");
?>