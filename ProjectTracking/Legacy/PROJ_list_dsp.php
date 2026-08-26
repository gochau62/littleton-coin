<?php
function dspProjList(&$screenData) { // Change funcName to something appropriate
?>
<div id='stdPage'>

	<h3><?php echo $screenData['fName'] ?>'s Projects</h3>
	<br/>
	<form id='projList' method='POST' action='PROJ_list_ctl.php'>

		View: <?php echo $screenData['selectList'] ?>
		&nbsp;&nbsp;&nbsp;<input type='checkbox' name='includeComplete' value='yes' <?php echo $screenData['incChecked']?> onchange='projSubmitProjList()' /> Include completed projects
		&nbsp;&nbsp;&nbsp;<input type='checkbox' name='includeRejected' value='yes' <?php echo $screenData['incRejChecked']?> onchange='projSubmitProjList()' /> Include rejected projects
	</form>
	<br/>
	<a href='downloadToExcel.php?table=projList'>view in Excel</a>

	<?php echo $screenData['projList']?>

</div>
<?php 	
}
?>
