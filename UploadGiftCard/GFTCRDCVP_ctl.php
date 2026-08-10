<?php
/*    ***************************************************  -->
<!--  * Program Name - GFTCRDCVP_ctl.php                *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/10/2025                         *  -->
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
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />


<script type="text/javascript">
	document.title = "Upload GiftCard File";
    // sets title of the web page

    // functions to set and show error messages
	function hideErrorMessage() {
		$("#errorMsg")
			.text('')
			.hide();
	}

	function showErrorMessage(msg) {
		$("#errorMsg")
			.text(msg)
			.show();
	}

	function hideSuccessMessage() {
		$("#successMsg")
			.text('')
			.hide();
	}

	function showSuccessMessage(msg) {
		$("#successMsg")
			.text(msg)
			.show();
	}

	function resetPage() {
		hideErrorMessage();
     	hideSuccessMessage();
     	$("#dspResults").html("");
	}
	
	function showNotAuthorized() {
		// alert("Current user profile is not authorized\nto view selected documents");
		showErrorMessage("Current user profile is not authorized to view selected document.");
	}

    // file extension check
	// takes two arguments inputID and an array of file extensions (.jpg)
	// returns true if valid extension otherwise returns false
	function hasExtension(inputID, exts) {
	    var fileName = document.getElementById(inputID).value;
	    return (new RegExp('(' + exts.join('|').replace(/\./g, '\\.') + ')$')).test(fileName);
	}


	//file upload and handling
	function myFunction() {

		// hides element with id resultsCallback
		// check for .xlsx file extension
		$('#resultsCallback').hide();
	    var bool = hasExtension('myFile', ['.xlsx']);
	    		
		// creates a new form with form id form1
		var formData = new FormData($('#form1')[0]);

		//make sure user has attached a file 
		if ($('#myFile').get(0).files.length === 0){ 
			swal("No file attached", "You must attach an XLSX file containing in order: SKU#, Design, Giftcard#, CVV - attach a file and try again", "error");
			return;
		}

		// make sure the file the user is attaching is an XLSX, XLS, or CSV file. 
		if (bool == false) { 
			swal("Incorrect file type", "File type attached is not supported - please attach a file type of .xlsx  and try again", "error");
			return; 
		}
			// sends ajax request to url (GFTCRDCVP_ajax.php)
			// response is process in the success function
			$.ajax({
				url: 'GFTCRDCVP_ajax.php',
				data: formData,
			    contentType: false,
			    datatype: 'text',
			    processData: false,
			    type: 'POST',
				success: function(rtnData) {
					console.log("made it here!");
					var parsed = JSON.parse(rtnData);
					console.log("Parsed is: " + parsed);
					if (parsed['returnClass'] == "error") {
						//showErrorMessage(rtnData);
						swal("Some errors occurred", "Records successfully updated/inserted: " + parsed['insertCount'] + "\n Records not inserted: " + parsed['errorCount'], "warning");
					} else if (parsed['returnClass'] == "success") {
						swal("All records uploaded", "All records have been uploaded/updated successfully \n\n Records updated/inserted: " + parsed['insertCount'], "success");
						
					}	
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
		// selectrs id spinner, while ajax in progress add class with name 'progress'
		
	});

</script>

<!--  Begin Content Here -->
<?php 
require_once 'StartBlockScriptB.php';

//***--- Check users authority ---***
//*** 10 is the minimum to use LCCOnline
$authConn = getDB2PConn($user, $password);
$authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);

 if ( $authorized != "yes") {
 		showNotAuthorized();
 } else {

	include("GFTCRDCVP_dsp.php"); 
	$screenData = "";
	dspExcelTest($screenData); 
?>
<!--  End Content Here -->

<?php

 } //end authority check "if"

	include("EndBlock.php");
?>
