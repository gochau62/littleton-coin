<?php
/*    ***************************************************  -->
<!--  * Program Name - AIS_WavePickSearch_ctl.php       *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/29/2024                         *  -->
<!--  ***************************************************   */
?>

<?php
	// retrieves and sets password and username
    require_once 'StartBlockScriptA.php';
	$user = $_SESSION['username'];
	$password = $_SESSION['password'];
?>

<!-- includes css and javascript libraries -->
<link href="jQuery/jquery-ui-custom.css" rel="stylesheet" type="text/css" />
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='jQuery/jquery-ui.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.core.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.position.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.widget.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<!-- this library contains the alert images and the styling -->
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />


<script type="text/javascript"> 
	document.title = "Wave Pick Search";

	// check authorization levels
    function showNotAuthorized() {
		alert("Current user profile is not authorized\nto view selected documents");
		showErrorMessage("Current user profile is not authorized to view selected document.");
	}

	function myFunction() {
		$('#resultsCallback').hide();
		var wavePickInput = $('#wavePickInput').val().toUpperCase().trim();		

		// validate the input length
    	if (wavePickInput.length === 0) {
        	swal("Please enter a search item", "You must enter a valid search. Please try again", "error"); 
        	return;
    	}
		if (wavePickInput.length > 30) {
        	swal("Search must be less than 30 characters", "You must enter a valid search. Please try again", "error");
        	return;
    	}

		// create dataArray called in ajax call
		dataArray = {
			action: "searchWavePickText",
			wavePickInput: wavePickInput,
		};

		$.ajax({
			url: 'AIS_ajax_request.php',
			data: dataArray,
			datatype: 'json',
			type: 'POST',
			async: false,
			success: function(rtnData) {
        		var parsed = JSON.parse(rtnData);

				// create a table buffer to hold the headers
        		var tableBuffer = "<table border='1'><tr><th>Selection</th><th>Sequence #</th><th>Wave Pick Text</th></tr>";

				// iterate through the json object array and input them into the table
        		if (Array.isArray(parsed)) {
            		var count = parsed.length;
            		for (var i = 0; i < count; i++) {
               			tableBuffer += "<tr><td>" + parsed[i].TXTCOD + "</td><td>" + parsed[i].TXTSEQ + "</td><td>" + parsed[i].TXTEXT + "</td></tr>";
            		}
        		} else {
					// error checking ensure there is a valid search
            		console.error("Expected an array but got:", parsed);
					swal("Search failed!", '\n\n No results for "' + wavePickInput + '"\n There were no records found', "warning")
        		}
				// close table and add it to the div to display the table on the webpage
        		tableBuffer += "</table>";
        		$('#query_data').html(tableBuffer);
				if(count === undefined) {
					count = 0;
				}
				// set the number of record count and clear the input field
				$('#count').html("Number of records found: " + count);
				$('#wavePickInput').val('');
    		},
			// error handling for ajax call
    		error: function(xhr, status, error) {
        		console.error("AJAX Error: " + status + " " + error); 
    		}
		});			
	}

/*-------------------------*/
/* Document Ready Function */
/*-------------------------*/
jQuery(document).ready(function() {
	$('#spinner')
	.ajaxStart(function() {
		$(this).addClass('progress');
	})

	.ajaxStop( function() {
		$(this).removeClass('progress');
	});
});
</script>

<!--  Begin Content Here -->
<?php 
require_once 'StartBlockScriptB.php';

//***--- Check users authority ---***
$authConn = getDB2PConn($user, $password);
$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);

// check authority level
if ( $authorized != "yes") {
 		showNotAuthorized();
 } else {
	// display the screen
	include("AIS_WavePickSearch_dsp.php"); 
	$screenData = "";
    dspWaveSearch($screenData)
?>
<!--  End Content Here -->

<?php
 } //end authority check "if"
	include("EndBlock.php");
?>
