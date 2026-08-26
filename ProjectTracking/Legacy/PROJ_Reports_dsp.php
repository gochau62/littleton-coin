<?php
function dspProjReports(&$screenData) { // Change funcName to something appropriate
?>
	<div id='stdPage'>
	<h1>Project Tracking Reports</h1>
	
	<h3>"Submit" Summary Programs</h3>
	"Workload" and "Streering Committee Review" summarized data are generated from from programs PR8000R and PR8001R. These only need to be run once a month prior to downloading reports.
	<br/> 
	<br/>
	<form name="scWorkload">
	From <?php echo $screenData['fromDate']?>
	&nbsp;&nbsp;&nbsp; 
	To <?php echo $screenData['toDate']?>
	</form>
	<br/>
<!-- 	<input type='button' onclick='sbmtSCReview()' value='submit' />  -->
	<button id="sbmtSCReports">Submit</button>
	<div id='scReviewConf' class='confMsgHide'> Submitted</div> 
	<br/>
	<div id="lastRan" style="display:inline"></div> 
	<a href="#" id="refresh">refresh</a>
	<br/>
	The "last ran" date range is used for "Projects Submitted" and "Projects Completed" below.
	<br/>
	<br/>
	<hr>
	<br/>
	<h3>Select options and click "Download"</h3>
	<br/>
	ERP 
	<input type="radio" name="itSubDept" value="ERP" checked="checked" />
	&nbsp;&nbsp;&nbsp;
	Web 
	<input type="radio" name="itSubDept" value="WEB" />
	&nbsp;&nbsp;&nbsp; 
	Net 
	<input type="radio" name="itSubDept" value="NET" /> 
	
	<br/> 
	<br/>
	
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="workLoad" checked="checked" />&nbsp;&nbsp;&nbsp; Workload
	<br/>
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="projSubmitted" checked="checked" />&nbsp;&nbsp;&nbsp; Projects Submitted
	<br/>
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="projCompleted" checked="checked" />&nbsp;&nbsp;&nbsp; Projects Completed
	<br/>
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="SCReview" checked="checked" />&nbsp;&nbsp;&nbsp; Steering Committee Review
	<br/>
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="FFProjects" checked="checked" />&nbsp;&nbsp;&nbsp; Formula Friday Projects
	<br/>
	&nbsp;&nbsp;&nbsp;<input type="checkbox" name="selReports" id="postMortem" checked="checked" />&nbsp;&nbsp;&nbsp; Post Mortem
	<br/>
	<br/>
	<button id="downloadReports">Download</button>
	<br/>
	<br/>
	<div id='spinner'></div>
	<hr>
	<br/>
	<form name="pgmrActivity" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<h3>Run Programmer Activity Analysis for date range:</h3>
	Reports <i>should</i> go to your outq.
	<br/>
	<br/>
	From <?php echo $screenData['pgmrFromDate']?> To <?php echo $screenData['pgmrToDate']?> <input type="submit" value='submit'/>
	</form>
	<br/>
	<hr/>
	<br/>
<!-- 	<a href='file:///R:\2IT\Projects\Project Management\PTSReportsV2.xls' target='_blank'>PTS Reports Spreadsheet</a> -->
	</div>
<?php 	
}
?>
