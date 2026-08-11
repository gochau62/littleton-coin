<?php
function showAllAllComntsView(&$screenData) { // Change funcName to something appropriate
?>
	<div id='stdPage'>
	<h1>All comments view</h1>
	<h2>Project comments and description in cronological order</h2>
	<h3>Color Coding</h3>
	<div class='webNoteDesc'>Description</div>
	<div class='webNoteGen'>General comments</div>
	<div class='webNoteIT'>IT Comments</div>
	<div class='webNotePB'>Payback Comments</div>
	<div class='webNoteSC'>Steering Committe comments</div>
	<br/>

	<?php foreach ($screenData['projComntAll'] as $comment) {
		echo $comment;
		//echo "<hr/>";
	}?>


	</div>
<?php 	
}

function showProjPrompt () {
?>
<div id='stdPage'>
	<h1>All comments view</h1>
	<h2>Project comments and description in cronological order</h2>
	<h3>Color Coding</h3>
	<div class='webNoteDesc'>Description</div>
	<div class='webNoteGen'>General comments</div>
	<div class='webNoteIT'>IT Comments</div>
	<div class='webNotePB'>Payback Comments</div>
	<div class='webNoteSC'>Steering Committe comments</div>
	<br/>

	<h2><u>Project:</u> <input onchange='getProjComments()' id='projectNumber' style='text-align:right' type='text' size='2' maxlength='6' value='<?php echo $screenData['projnum']?>'/></h2>
	
	
</div>
<?php 	
}
?>
