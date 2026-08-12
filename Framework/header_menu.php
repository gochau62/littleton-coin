<!-- begin header_menu.php -->
<?php
//	session_start();
//	setlocale(LC_MONETARY, 'en_US');
	echo "<div id='header'>";
	echo "<img src='images/LCC-Logo.png' alt='Littleton Coin Company'></img>";
?>
<!-- 		<div id="betaText"><h1>Zendserver 6.2 Beta Site</h1></div> -->
<?php
	echo "<div id='login'>";

	if (isset($_SESSION['username'])) {
		$user = $_SESSION['username'];
		echo "<h3>Welcome</h3><br>".$_SESSION['longname']."<br>";
		echo "<a href='LogOut.php' action='logout()' name='Log Out'>Log Out</a>";
	} else {
?>
	<form id="form1" action="LogOnProcess.php" method="post">
		<fieldset>
			<legend>Sign-In</legend>
			<!-- the page the person is signing in from, so LogOnProcess can land them back on it instead of the home page -->
			<input type="hidden" name="return_to"
			       value="<?php echo htmlspecialchars(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', ENT_QUOTES); ?>" />
			<label for="inputtext1">User Name:</label>
			<input id="inputtext1" type="text" maxlength="10" name="username" value="" />
			<label for="inputtext2">Password:</label>
			<input id="inputtext2" type="password" name="password" value="" />
			<input id="inputsubmit1" type="submit" name="inputsubmit1" value="Sign In" />
		</fieldset>
	</form>
	<script type="text/javascript">
	document.getElementById("inputtext1").focus();
	</script>

<?php
	}

	echo "</div>";
	echo "</div>";

?>
<!-- end header_menu.php -->
