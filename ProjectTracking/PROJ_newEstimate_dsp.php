<?php 

function showNewEstimate(&$screenData) {
	include("StartBlockHead.php");
?>
<script type="text/javascript" src="Utils/common_JS_functions.js"></script>
<script type="text/javascript" src="Utils/calendar_us.js"></script>

<script type='text/javascript'> 
function updCurEstimate() {
	var lowEstimate = document.getElementById("lowEst").value;
	var hiEstimate = document.getElementById("hiEst").value;
	window.opener.updCurEst(lowEstimate,hiEstimate);
}
</script>

<script type="text/javascript">
	document.title = "Project Estimate";
</script>

<body style='width: 800px'>

<!--	Show form for adding new estimate -->
<form id='newProjEstimate' name='newProjEstimate' onSubmit="JavaScript:updCurEstimate()" method="post" action=<?php $_SERVER["PHP_SELF"] ?>>

<h2>Enter Project Estimate</h2>
<p>Complete the form below and press the "Save" button.</p>
	
<div id='stdPage'>
<!--	Select Date -->
<label>Project #: &nbsp;</label>
<input type='text' id='projNumber' name='projNumber' value='<?php echo $screenData['projNum']?>' maxlength='6' size='4'/>
<br/>
<br/>
	
<label>Estimator: &nbsp;</label>
<?php echo $screenData['estimator']?>
<input type='hidden' name='estimator' value='<?php echo $screenData['user']?>' />
<br/>
<br/>
	
<label>Date estimated: &nbsp;</label>

<?php echo $screenData['estDate']?>
<br/>
<br/>
	
<?php echo $screenData['toolTip']['estFmLoEst']?>
<label>Low Estimate: &nbsp;</label>
<input type="text" name="lowEst" id="lowEst" maxlength="4" size="3"/>

&nbsp;&nbsp;&nbsp;&nbsp;

<?php echo $screenData['toolTip']['estFmHiEst']?>
<label>Hi Estimate: &nbsp;</label>
<input type="text" name="hiEst" id="hiEst" maxlength="4" size="3"/>

<br/>
<br/>

Please remember to enter an IT comment if appropriate.
<!--<label style="vertical-align:top">Estimate Comment: &nbsp;</label> -->
<!--<textarea rows="4" cols="50" name="estComment" maxlength="255" onkeyup="return ismaxlength(this)"></textarea>-->
<!-- maxlength is not a valid attribute for a textarea but function ismaxlength needs it. -->
<br/>
<br/>
<input type="submit" name="submit" value="Save">
<button type='button' onclick='closeWindow()'>Close Window</button>
</form>
</div> <!--  stdPage  -->
<?php
	include("EndBlock.php"); 
} // end "Function"

?>
