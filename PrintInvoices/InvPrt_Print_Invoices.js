/*    ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  * Date      - 05/20/2025                          *  -->
<!--  * Purpose   - PHP Dashboard Rewrite js calls      *  -->
<!--  *                                                 *  -->
<!--  * Project   - 240197                              *  -->
<!--  ***************************************************   */
document.title = "Print Invoices"

	document.getElementById('sidebar').style.display='none';
	function setFocusScanData(Parm1) {
		Parm1.focus();
	}
	
	function isolateDirectSends(){
		//var url = window.location.href;
		//var url = 'http://lcc1:8068/LCCOnline/InvPrt_Print_Invoices.php';
		var url = window.location.href;
		
		console.log(url);
		//console.log($('#isolateDirectSends1').is(':checked'));
		var selectedValue = $('#isolateDirectSends1').find(':selected').val();
		//if (($('#isolateDirectSends1').is(':checked')) == true) {
		console.log(url.indexOf("?"));
		if (url.indexOf("?") > 0) {
			url = url.substring(0, url.length-6);
		}
		if (selectedValue.trim() == '1') {
			url += '?ids=1';
		}
		else if (selectedValue.trim() == '2') {
			url += '?ids=2';
		}
		else {
			url += '?ids=0';
		}
		window.location.href = url;
		//console.log(url);
		//console.log( $('') );
	}
	
	// intialize the ajax call for reloading the table if the selectFormDropdown is updated
	$(document).on('change', '#selectFormDropdown', function () {
    reloadInvoicePrintTable();
	});
	
	// intialize the ajax call for reloading the table if the directSendsDropdown is updated
	$(document).on('change', '#directSendsDropdown', function () {
    reloadInvoicePrintTable();
	});

	// ensure error handling to reduce mistakes in updating batch sequence values from printer to printer
	function validateUpdate() {
    	// intialize variables
    	const fromBatchInput = document.getElementById('FromBatchSeq');
    	const toBatchInput = document.getElementById('ToBatchSeq');
    	const fromBatch = fromBatchInput.value.trim();
    	const toBatch = toBatchInput.value.trim();

    	if (isNaN(fromBatch) || isNaN(toBatch)) {
        	swal("Invalid Input", "Batch values must be numeric.", "error");
        	fromBatchInput.value = '';
        	toBatchInput.value = '';
        	return false;
    	}
    	const stringValidBatches = validBatchSeq.map(String);
    	if (!stringValidBatches.includes(fromBatch)) {
        	swal("Invalid Batch Number", "Batch number are not recognized.", "error");
        	fromBatchInput.value = '';
        	toBatchInput.value = '';
        	return false;
    	}
    	if (parseInt(fromBatch) > parseInt(toBatch)) {
        	swal("Range Error", "'FromBatchSeq' cannot be greater than 'ToBatchSeq'.", "error");
        	fromBatchInput.value = '';
        	toBatchInput.value = '';
        	return false;
    	}
    	return true;
	}
	// use an ajax call to refresh the screen after batch sequence update goes through, show success when completed
	function submitUpdateAJAX() {
    	// if validateUpdate does to get any errors continue to submission of update
    	if (!validateUpdate()) return;

    	// retrieve data
    	const postData = {
        	action: 'updatePrinterAssignment',
        	FromBatchSeq: $('#FromBatchSeq').val().trim(),
        	ToBatchSeq: $('#ToBatchSeq').val().trim(),
        	Printer: $('#Printer').val(),
        	selectForm: $('#selectFormDropdown').val()
    	};

    // refer to Invoices_ajax 
    $.ajax({
        url: 'InvPrt_Print_Invoices_ajax.php',
        type: 'POST',
        data: postData,
        success: function (response) {
            reloadInvoicePrintTable(false);
            $('#FromBatchSeq').val('');
            $('#ToBatchSeq').val('');
        },
        error: function (xhr, status, error) {
            swal("Error", "Failed to update Batch Seq.", "error");
            console.error(error);
        }
    });
}

function reloadPrinterAvailability() {
    $.ajax({
        url: 'InvPrt_Print_Invoices_ajax.php',
        type: 'POST',
        data: { action: 'reloadAvailability' },
        success: function (response) {
            $('#printerAvbtyData').html(response);
        },
        error: function (xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
}

// initialize ajax click call for changing printer description, showing spinner to visualize update
function submitAddPrinter() {
    const postData = {
        action: 'addNewPrinter',
        PrintNumber: $('#PrintNumber').val().trim(),
        PrintDescription: $('#PrintDescription').val().trim(),
        PrintAvailable: $('#PrintAvailable').val()
    };

    $('#spinner-overlay').show();
	 $.ajax({
            url: 'InvPrt_Print_Invoices_ajax.php',
            type: 'POST',
            data: postData,
            success: function (response) {
				reloadPrinterAvailability();
                $('#PrintNumber').val('');
                $('#PrintDescription').val('');
                $('#PrintAvailable').val('Y');
				reloadInvoicePrintTable();
			},
            complete: function () {
                setTimeout(() => {
                    $('#spinner-overlay').hide();
                }, 1000);
			}
    });
}

function submitChangeDescription() {
    const postData = {
        action: 'changePrinterDescription',
        CurrentPrintDescription: $('#CurrentPrintDescription').val().trim(),
        NewPrintDescription: $('#NewPrintDescription').val().trim()
    };

    $('#spinner-overlay').show();
	$.ajax({
            url: 'InvPrt_Print_Invoices_ajax.php',
            type: 'POST',
            data: postData,
        	success: function (response) {
				reloadPrinterAvailability();
           	 	$('#NewPrintDescription').val('');
        	},
            complete: function () {
                setTimeout(() => {
                    $('#spinner-overlay').hide();
                }, 1000);
			}
    });
}

	// if there is a newForm for whatever reason use reloadInvoicePrintTable ajax call instead of formSubmission (error checking)
	function newForm(){
		reloadInvoicePrintTable();
		//document.location.href="InvPrt_Print_Invoices_Validate.php";
	}

	// change divisions is used to update the screen display when clicking top right button
	function changeDivs() {
		if (document.getElementById("invPrtAvbty").style.display == 'block') {
			document.getElementById("invPrtAvbty").style.display = 'none';
			document.getElementById("addNewPrinter").style.display = 'none';
			document.getElementById("changePrinterDescription").style.display = 'none';
			document.getElementById("invPrtTable").style.display = 'block';

			// if printer availabilty is update, ajax call to refresh the table (shows new printers or removes printers)
			// do them all at one time here for the display change button rather than individually after every change (too many flashes)
			reloadInvoicePrintTable();

			//alert(document.getElementById("changePrinterDescription").style.display);
		}
		else {
			document.getElementById("invPrtTable").style.display = 'none';
			document.getElementById("invPrtAvbty").style.display = 'block';
			document.getElementById("addNewPrinter").style.display = 'block';
			document.getElementById("changePrinterDescription").style.display = 'block';
			//alert(document.getElementById("changePrinterDescription").style.display);
		}
	}
	
	// ajax call that reloads the table while include spinner to show updating of the data inside the table
	function reloadInvoicePrintTable(showSpinner = true) {
	const selectedForm = $('#selectFormDropdown').val();	
	const selectedSendOption = $('#directSendsDropdown').val();	
    $.ajax({
        url: 'InvPrt_Print_Invoices_ajax.php',
        data: { action: 'reloadScreen', formName: selectedForm, ids: selectedSendOption},
		type: 'POST',
		beforeSend: function () {
			// show spinner if the reload is not complete
            if (showSpinner === true) {
                $('#spinner-overlay').show();
            }
        },
        success: function(rtnTable) {
			// information successfully updated through ajax
 			$('#invDashBoardTableData').html(rtnTable);
        },
		complete: function () {
			// spinner is hidden once completed
			if (showSpinner === true) {
                $('#spinner-overlay').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", status, error);
        }
    });
}
	
	var oldValue;
	var tmp;
	function confirmChange(callerID, curVal, callerName) {
		
		var callID = callerID;
		var currentValue = curVal;
		var callName = callerName;
		
		var chgPrtAvl = [callName, currentValue];
		console.log(chgPrtAvl);
		
		$.ajax({
			url: "InvPrt_Printer_Sbm.php",
			type: "POST",
			data: { prtArray : chgPrtAvl },
			success: function() {
				$("#"+callID).val(currentValue);
			},
		});
		
		/*swal({
			  title: "Warning",
			  text: "Are you sure you want to change the availability of this printer?",
			  imageUrl: "images/laser_printer.png",
			  type: "warning",
			  showCancelButton: true,
			  confirmButtonColor: "#DD6B55",
			  confirmButtonText: "Yes, change it",
			  cancelButtonText: "Cancel",
			  closeOnConfirm: false,
			  closeOnCancel: false
			},
			function(isConfirm) {
				if (isConfirm) {
					document.getElementsByName('printer_number' + callerName)[0].value = callerName;
					document.getElementsByName('printer_availability' + callerName)[0].value = curVal;
					var form_id = callerName;
					$("#" + callerName).submit();
					swal("Changed!", "The availability of the printer has been changed.", "success");
				}
				else {
					
					swal("Cancelled", "Availability of printer unchanged", "error");
					if(oldValue == 'N') {
						tmp = 'Y';
					}
					else {
						tmp = 'N';
					}
					oldValue = null;
					changeValue(tmp, callerID);
					tmp = null;
				}
			});*/
		
	}
	function saveValue(value) {
		oldValue = value;
	}
	function changeValue(value, callerID) {
		$('#' + callerID).val(value);
	}
	//KRNEW 130189
	function populateHistory() {
		//$('#' + callerID).val(value);
		var prtValue = $('#historyPrinters option:selected').val();
		$('#prt-summary').empty();
		if (prtValue == "0") {
			return;
		}
		/*$('#prt17-summary').empty();
		$('#prt18-summary').empty();
		$('#prt19-summary').empty();
		$('#prt20-summary').empty();
		$('#prt21-summary').empty();*/
		console.log("We got " + prtValue);
		var currentTime = new Date();
		
		var id = 'prt' + prtValue + '-summary';
		var month = currentTime.getMonth() + 1;
		var day = currentTime.getDate();
		var year = currentTime.getFullYear();
		if (month < 10) {
			month = "0" + String(month);
		}
		if (day < 10) {
			day = "0" + String(day);
		}
		var YearDate = String(year) + "-" + String(month) + "-" + String(day);
		var minTime = YearDate.trim() + ' 00:00:00';
		var maxTime = YearDate.trim() + ' 24:00:00';
		//$('#' + id).text("We here");
		
		//$('#przTable').empty();
		var dataAry = {action: "getBatchPrinterHistory",
					   prtNum: prtValue,
					   maxTim: maxTime,
					   minTim: minTime
					   //maxTim: '2023-08-03 24:00:00',
					   //minTim: '2023-08-03 00:00:00'
					  };
			                    
	$('#spinner-overlay').show();
	$.ajax({
		url: 'InvPrt_Print_Invoices_ajax.php',
		data: dataAry,
	    datatype: 'json',
	    type: 'POST',
	    async: false,
		success: function(rtnData) {
			console.log(rtnData);
			var parsedResults = JSON.parse(rtnData);
			console.log("Now for parsley:")
			//console.log(parsedResults);
			//console.log(parsedResults[0][1]);
			//console.log(parsedResults[0]['PZIDFIELD']);
			var tableBuffer = "<table id='renderedHistoryTable'><tr><th>Spool</th><th>Batch Seq</th><th>Batch #</th> <th>Invoiced Date</th> <th>Invoiced Time</th> <th>Printed Time</th><th>Batch Type</th><th>Batch Desc</th><th>Batch Form</th><th>Print Group</th></tr>";
			var count = parsedResults.length;
			var i = 0;
			for (i = 0; i < count; i++) {
				
				//console.log("BLEEP" + parsedResults[i]['HSBATCH']);
				var tmpBatch = parsedResults[i]['HSBATCH'].trim();
				var dataAry = {action: "getInvoiceHistory",
						   batchnum: tmpBatch};
				 
				//var tempInvArray;
				var invCount;
				var invTooltip = "";
				$.ajax({
					url: 'InvPrt_Print_Invoices_ajax.php',
					data: dataAry,
				    datatype: 'json',
				    type: 'POST',
				    async: false,
					success: function(rtnData2) {
						//console.log("y"  +rtnData2);
						var parsedResults2 = JSON.parse(rtnData2);
						//var parsedResults2 = rtnData2;
						//console.log(parsedResults2);
						//console.log("BLOOP");
						//tempInvArray = parsedResults2;
						invCount = parsedResults2.length;
						for (x = 0; x < invCount; x++) {
							invTooltip = invTooltip + parsedResults2[x]['IHINVOICE'] + "<br>";
						}
					}});
				//console.log(tempInvArray);
				//var invCount = parsedResults2.length;
				
				
				/*echo "<td onMouseOver=\"tooltip.show('".${"invoiceList$printer"}."')\" onmouseout=\"tooltip.hide()\">"
		        
	            .${"batSeq$printer"}.'</td><td>'.${"invoiceCount$printer"}.'</td>';*/
	        
	        
				
				
				tableBuffer += "<tr><td>" + parsedResults[i]['HSSPOOL']   + "</td><td>" + parsedResults[i]['HSBATSEQ']   + "</td>  <td onMouseOver=\"tooltip.show('" + invTooltip + "')\" onmouseout=\"tooltip.hide()\">" + parsedResults[i]['HSBATCH']  + "</td> <td>" + lccDateToSlashes(parsedResults[i]['HSPDATE']) + "</td> <td>" + parsedResults[i]['HSPRTSTMP'] +  "</td> <td>" + parsedResults[i]['HSTIMSTMP'] + "</td><td>" + parsedResults[i]['HSBTYPE'] + "</td><td>" + parsedResults[i]['HSBDESC'] + "</td><td>" + parsedResults[i]['HSFORM'] + "</td><td>" + parsedResults[i]['HSPRINTGRP'] + "</td></tr>";
			}
	        //tableBuffer += "<tr><td>" + parsedResults[0]['PZINV#']   + "</td>  <td>" + parsedResults[0]['PZPSKU#']  + "</td> <td>" + parsedResults[0]['PZBSKU#'] + "</td> <td>" + parsedResults[0]['PZQTY'] +  "</td> <td>" + lccDateToSlashes(parsedResults[0]['BHPDATE']) + "</td><td>" + "<button type='button' class='buttonClass'>Click Me!</button>" +  "</td><td style='visibility: hidden;'>" + parsedResults[0]['PZIDFIELD'] + "</td></tr>";
	        //tableBuffer += "<tr><td>" + parsedResults[1]['PZINV#']   + "</td>  <td>" + parsedResults[1]['PZPSKU#']  + "</td> <td>" + parsedResults[1]['PZBSKU#'] + "</td> <td>" + parsedResults[1]['PZQTY'] +  "</td> <td>" + lccDateToSlashes(parsedResults[1]['BHPDATE']) + "</td><td>" + "<button type='button' class='buttonClass'>Click Me!</button>" +  "</td><td style='visibility: hidden;'>" + parsedResults[1]['PZIDFIELD'] + "</td></tr>";	
	        tableBuffer += "</table>";
			//$('#przTable').html(tableBuffer);
	        //('#' + id).html(tableBuffer);
	        $('#prt-summary').html(tableBuffer);
			
			console.log("The count is: " + count);
		},
		complete: function() {
            setTimeout(() => {
                $('#spinner-overlay').hide();
            }, 1000);
        }	
		});
		
	    const colors = ['#E1F8DC', '#FFEE93', '#F9DED6', '#ECD6FC', '#ADF7B6', '#FCF5C7', '#FFDAC1', '#E2F0CB'];
	    // assign colors to rows to more easily visually differentiate between batching runs 
		var table = document.getElementById("renderedHistoryTable");
		var colorCount = 0;
		var hourChange;
		for (var i = 0, row; row = table.rows[i]; i++) {
			if (i == 0) {
				continue;
			}
			if (i == 1) {
			hourChange = (table.rows[i].cells[4].innerHTML).substring(11, 13); // use the hour the batch record was written as basis for color change	
			console.log((table.rows[i].cells[4].innerHTML).substring(11, 13));

			   table.rows[i].style.background = colors[colorCount]; 
			}
			
			else {
				if ((table.rows[i].cells[4].innerHTML).substring(11, 13) != hourChange) {
					hourChange = (table.rows[i].cells[4].innerHTML).substring(11, 13);
					colorCount++;
					if (colorCount == 8) {
						colorCount = 0;
					}
				}	
				console.log((table.rows[i].cells[4].innerHTML).substring(11, 13));

				   table.rows[i].style.background = colors[colorCount]; 
				
				
			}
			//table.rows[i].style.background = '#E2F0CB';
			     
			 }
		//console.log(colors[1]);
		//console.log(colors[0]);
			
		
	}
	//KRNEW-END 130189
//	$('#batSeqTableId').click(function() {
//		console.log("Yuz");
//		console.log(this.value);
//	});
//	document.getElementById('batSeqTableId').onclick = function (){
//		console.log("YUP");
//	}
//	$(document).ready(function(){
//		$('.batSeqTableId').click(function() {
//			
//		});
//	});
//	$(".batSeqTableClass").on('click', function(event){
//		
//		console.log("WOW!");
//	});

$(document).on('click', '.batSeqTableClass', function () {
		// below is the javascript logic to allow users to click batch seq #s and have them populate into the input fields
		// for batch seq # reassignment - kjr - 08/04/23
    var tmp = $(this).html();
    if ($('#FromBatchSeq').val().trim() === "" && $('#ToBatchSeq').val().trim() === "") {
        $('#FromBatchSeq').val(tmp);
    } else if ($('#FromBatchSeq').val().trim() !== "" && $('#ToBatchSeq').val().trim() === "") {
        $('#ToBatchSeq').val(tmp);
    } else {
        $('#FromBatchSeq').val(tmp);
        $('#ToBatchSeq').val("");
    }
	    /*console.log(cells[0]);
	    console.log(cells[1]);

	    const content1 = cells[0].innerHTML;
	    console.log(content1);

	    const content2 = cells[1].innerHTML;
	    console.log(content2);*/
});
