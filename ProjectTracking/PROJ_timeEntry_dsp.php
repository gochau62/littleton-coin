<?php
function showTimeEntry(&$screenData) { // Change funcName to something appropriate
?>
	<div id='stdPage'>
		<h1>Project Time Entry</h1>
	
		For week ending: <br/>

		<div style='text-align: left; width: 50%; float: left;'>
			<?php echo $screenData['lnkBack']?>
			&nbsp;&nbsp;
			<?php echo $screenData['longDate']?>
			&nbsp;&nbsp;
			<?php echo $screenData['lnkForward']?>
		</div>
	
		<div style='text-align: right; width: 50%; float: left;'>
			Add project 
			<input type='text' id='projToAdd' maxlength='6' size='2'/> 
			to list.
			&nbsp;&nbsp;
			<button onclick='addProjToTimeList()'>Add</button>
		</div>
	
		<?php echo $screenData['timeTable']?>

	</div>
<?php 	
}
?>
