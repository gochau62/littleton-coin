<?php
function dspUserName() { // Change funcName to something appropriate
?>
	<div id='stdPage'>
	<h1>Maintain Requestor/Sponsor</h1>
	 
	<h3>Retrieve Requestor/Sponsor</h3>
	
	<hr>
	<br/>
	<form name="promptUser" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<label>User Name:</label>   
	<input size='10' maxlength='10' type='text' name='userName' id="username" onchange="nameToUpperCase()" value=""/>
	<input type="submit" value='Submit'/>
	</form>
	</div>

<?php
}
?>
<?php
function dspRequestMaint(&$dspUser) { // Change funcName to something appropriate
?>
	<div id='stdPage'>
	<h1>Maintain Requestor/Sponsor</h1> 
	<h3>Add/Change a Requestor and/or Sponsor</h3>
	<hr>
	<br/>
	<form name="addRequestor" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<label>User Name:</label>   
	<input size='10' maxlength='10' type='text' name='userName2' value="<?php echo $dspUser['userName']?>"/> 
	<br/>
	<br>
	<label>First Name:</label> 
	<input size='15' maxlength='15' type='text' name='firstName' value="<?php echo $dspUser['firstName']?>"/>
	&nbsp;&nbsp;&nbsp;
	<label>Last Name:</label> 
	<input size='35' maxlength='35' type='text' name='lastName' value="<?php echo $dspUser['lastName']?>"/>
	<br>
	<br>
	<label>Department:</label> 
	<?php  
	echo $dspUser['html']['deptName'];
	?>
	&nbsp;&nbsp;&nbsp;
	<label>Subdepartment:</label> 
	<?php
	echo $dspUser['html']['subDeptName'];
	?>
	<br>
	<br>
	&nbsp;&nbsp;
	<label>LCC Email:</label> 
	<input size='50' maxlength='50' type='text' name='emailAddress' value="<?php echo $dspUser['emailAddress']?>"/>
	<br>
	<br>
	<label>Requestor (Y/N):</label> 
	<input size='1' maxlength='1' type='text' name='projRequestor' value="<?php echo $dspUser['requestorFlg']?>"/>
	&nbsp;&nbsp;&nbsp;
	<label>Sponsor (Y/N):</label> 
	<input size='1' maxlength='1' type='text' name='projSponsor' value="<?php echo $dspUser['sponsorFlg']?>"/>
	<i>Remove old sponsor before assigning new one</i>
	<br>
	<br>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php
	If ($dspUser['dspUserGroup'] == 'Y') {
		
		echo '<label>User Group:</label>';
		echo " " . $dspUser['LCGROUP'];
	}
	?>
	
	<input type="submit" value='Submit'/>
	</form>
	</div>

<?php
}
?>
