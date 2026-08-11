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
	require_once("PROJ_model.php");
	
?>
<script type="text/javascript">
	document.title = "Tool Tip";
</script>
<script type="text/javascript">
function reloadTTM() {

	var mode = document.getElementById('modeupdate');

	if (mode.checked == true) {
	
		var field = document.getElementById('ttField').value;
		window.location = 'PROJ_tooltipMaint.php?ttid='+field+'&mode=update';
	}
}
</script>
<div id='stdPage'>
<!--  Begin Content Here -->
		<h1>Tool Tip Maintenance</h1>
		
<?php 
if (isset($_GET['ttid'])) {
	$conn = geti5Conn($user, $password);
	$toolTip = getRecPRTOOLTIPP($conn, trim($_GET['ttid']));
//	var_dump($toolTip);
	i5_close($conn);
	
	$inputBox = "<input type='text' name='ttField' id='ttField' maxlength='10' size='14'" 
				. "onchange='reloadTTM()' value='"
				. $toolTip['PTTFIELD'] . "' />";
	$tipText = $toolTip['PTTTIPTXT'];
	
} else {
	$inputBox = "<input type='text' name='ttField' id='ttField' maxlength='10' size='14'" 
				. "onchange='reloadTTM()' />";
	
}
if ($_POST['mode'] == 'update') {
//	var_dump($_POST);
	$conn = geti5Conn($user, $password);
	updRecPRTOOLTIPP($conn, $_POST['ttField'], $_POST['ttText']);
	i5_close($conn);
	$inputBox = "<input type='text' name='ttField' id='ttField' maxlength='10' size='14'" 
				. "onchange='reloadTTM()' />";
	
}
if ($_POST['mode'] == 'add') {
	$conn = geti5Conn($user, $password);
	insertRecPRTOOLTIPP($conn, $_POST['ttField'], $_POST['ttText']);
	i5_close($conn);
	$inputBox = "<input type='text' name='ttField' id='ttField' maxlength='10' size='14'" 
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
<form name='projToolTip' method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

<label>Field Name </label>
<?php echo $inputBox . " " . $mode ?>
<br/>
<br/>

<textarea name="ttText" rows="10" cols="100"><?php echo $tipText?></textarea>

<br/>
<br/>
<input type="submit" value="Save" />
</form>
<!--  End Content Here -->
</div>
<?php
	include("EndBlock.php");
?>