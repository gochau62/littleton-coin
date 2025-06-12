<?php
/*    ***************************************************  -->
<!--  * Program Name - InvPrt_Print_Invoices_ctl.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 01/9/2025                          *  -->
<!--  ***************************************************   */
?>

<?php
    include("StartBlockHead.php");	
    include("StartBlockBody.php");
    include("Utils/InvPrt_Utils.php");
?>

<!-- Following the model-view-controller paradigm, we need to rewrite this code to improve readability and be able to increase
    the efficiency in which individuals can navigate and understand the code -->
<!-- Include javascript librarys and starter block head and body imported from jQuery -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='jQuery/jquery-ui.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.core.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.position.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.widget.js'></script>
<script type='text/javascript' src='Utils/calendar_us.js'></script>
<script type='text/javascript' src='InvPrt_Print_Invoices.js'></script>
<script src="swal/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="swal/sweetalert.css">

<script type="text/javascript"> 
	document.title = "Print Invoices";
    
	// check authorization levels
    function showNotAuthorized() {
		alert("Current user profile is not authorized\nto view selected documents");
		showErrorMessage("Current user profile is not authorized to view selected document.");
	}

    window.addEventListener('DOMContentLoaded', function () {
        if (typeof reloadInvoicePrintTable === 'function') {
            reloadInvoicePrintTable();
        }
    });
</script>

<!--  Begin Content Here -->
<?php
$testArray = renderPrinterAvailability();
$testCount = count($testArray);
putLCCOnlineLogRec("\n !!! testCount is: " . $testCount);

// Build printer selection HTML
$printerHTML = '';
for ($i = 0; $i < $testCount; $i++) {
    if ($i == 0) {
        $printerHTML .= '
        <div id="PrtNo">Printer Number</div>
        <div id="PrtDesc">Printer Description</div>
        <div id="PrtAvl">Available?</div>
        <br><br class="clear">';
    }

    // intialize information from testArray for change availability screen
    $id = htmlspecialchars($testArray[$i][0]);
    $desc = htmlspecialchars($testArray[$i][1]);
    $avail = htmlspecialchars($testArray[$i][2]);
    $altOption = ($avail === 'Y') ? 'N' : 'Y';

    // add formatting and headers into printerHTML table
    $printerHTML .= "
    <div class=\"InvPrtSel\">
        <div id=\"PrtNo-$i\">$id</div>
        <div id=\"PrtDesc-$i\">$desc</div>
        <div id=\"PrtAvl-$i\">
            <form action=\"InvPrt_Printer_Sbm.php\" id=\"$id\" method=\"POST\">
                <input type=\"text\" name=\"printer_number$id\" style=\"display:none\" />
                <input type=\"text\" name=\"printer_availability$id\" style=\"display:none\" />
            </form>
            <select id=\"PrtAvl-$i-select\" name=\"$id\" onchange=\"javascript:confirmChange(this.id, this.value, this.name);\">
                <option value=\"$avail\">$avail</option>
                <option value=\"$altOption\">$altOption</option>
            </select>
        </div>
        <br><br>
    </div>";
}

// dynamically build history dropdown based on actual printer numbers in $testArray
$historyDropdown = '<option value="0" selected>Choose a printer...</option>';
$printerIDs = []; // store IDs for summary too

foreach ($testArray as $row) {
    $printerID = htmlspecialchars($row[0]);
    $historyDropdown .= "<option value=\"$printerID\">PRT$printerID</option>";
    $printerIDs[] = $printerID;
}

// dynamically build summary divs
$summaryDivs = '<div id="prt-summary"></div>';
foreach ($printerIDs as $printerID) {
    $summaryDivs .= "<div id=\"prt$printerID-summary\"></div>\n";
}

// intialize descriptions dropdown and allow users to update information
$descriptionOptions = '';
for ($i = 0; $i < $testCount; $i++) {
    $desc = htmlspecialchars($testArray[$i][1]);
    $descriptionOptions .= "<option value=\"$desc\">$desc</option>";
}

// pass data 
$_SESSION['printerHTML'] = $printerHTML;
$_SESSION['historyDropdown'] = $historyDropdown;
$_SESSION['summaryDivs'] = $summaryDivs;
$_SESSION['descriptionOptions'] = $descriptionOptions;
?>

<?php 
//***--- Check users authority ---***
$username = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';
$conn = getDB2PConn($_SESSION['username'], $_SESSION['password']);
$authorized = chkAutUsr($conn, $_SESSION['username'], "LCCONLINE", 50);

// check authority level
if ( $authorized != "yes") {
 	showNotAuthorized();
} else {
	// display the screen
	include("InvPrt_Print_Invoices_dsp.php");
    $screenData = array();

    // initalize array keys to support php error handling
    foreach ([
        'printButton', 'printerAvailable', 'selectForm', 'defaultForm', 'directSends', 'totalInvoices', 
        'grandTotal', 'pckdtlpCount', 'tableRows', 'splitends', 'displayPRT', 'tableHeader', 'batSeq', 
        'finalTally', 'footer', 'endRows', 'error', 
    ] as $key) {
        if (!isset($screenData[$key])) {
            $screenData[$key] = '';
        }
    }

    // intialize background color
    $screenData['backgroundColor'] = (isset($_SESSION['ErrorMessage']) && $_SESSION['ErrorMessage'] > " ") ? '#00FF00' : '#CCFFCC';
        
    // Check the Submit Lockout Flag
    // If on then disable the button
    $lockoutFlag = getLockoutFlag();

    if ($lockoutFlag == 'Y') {
        $screenData['printButton'] .= '<input type="submit" name="PrintButton" id="PrintButton" value="Print Invoices" disabled><br>';
        $screenData['printButton'] .= '<font color="red"><br>Invoice Print Submitted</font>';
    } else {
        //echo '<input type="submit" name="PrintButton" id="PrintButton" value="Print Invoices" ><br />';   
        $screenData['printButton'] .= '<input type="submit" name="PrintButton" id="PrintButton" value="Print Invoices"><br>';
    }
    
    // call variables
    $printerAvailability = array();
    $printerAvailability = renderPrinterAvailability();//call function in InvPrt_Utils.php to call stored procedure and assign returned value(s) to array
    $printerCount = count($printerAvailability);
    $availablePrinterCount = null;//problem - this variable is loaded with all 'Y' printers, but it is used against an array with all the printers - desired effect unachieved 
    $rowsCountArray = array();
    $printerAvailable = array();

    // create dropdown menu for printerAvailable
    for ($i = 0; $i < count($printerAvailability); $i++) {
        if ($printerAvailability[$i][2] == 'Y') {
            $screenData['printerAvailable'] .= "<option>{$printerAvailability[$i][0]}</option>";
            $availablePrinterCount++;
            $printerAvailable[$i] = $printerAvailability[$i][0];
        }
    }
    $printerAvailable = array_values($printerAvailable);//reindex the array 

    // get all Batch Form Types
    $formArray = getBatchForms();//KR - method that exists in 'InvPrt_Utils.php'
    // Set the default to the 1st form (if not already set)
    if(! isset($_SESSION["defaultForm"])){
        $_SESSION["defaultForm"] = $formArray[0][0];
    }

    $aNewTest = array();
    // Get all Batches for Printers and establish row counts /*
    for ($i = 0; $i < $availablePrinterCount; $i++) {
        $printer = $printerAvailable[$i];
        $ids = $_GET['ids'] ?? null;
        if ($ids == "1") {
            ${"rows$printer"} = getPrintBatchesDirectSends($printer);
        }
        else if ($ids == "2") {
            ${"rows$printer"} = getPrintBatchesDirectSendsExcluded($printer);
        }
        else {
            ${"rows$printer"} = getPrintBatches($printer, $_SESSION["defaultForm"]);
        }
        ${"rowsCount$printer"} = count(${"rows$printer"});
        $rowsCountArray[$i] = count(${"rows$printer"});//array loaded with the counts to for determination of max value
        ${"totalInvoices$printer"} = 0;
        ${"totalInvoices$printer"} = 0;
        ${"totalInvoices$printer"} = 0;
        array_push($aNewTest, ${"rows$printer"});
        }
        $grandTotalInvoice = 0;

    // establish variables
    $rowsCount = max($rowsCountArray);
    $nuiCount = count($aNewTest);
    $nuiItr = 0;
    $emptyFlag = false;

    foreach($aNewTest as $test) {
        if(empty($test)) {
            $nuiItr++;
            continue;
        }
    } 
    if ($nuiItr == $nuiCount) {
        $emptyFlag = true;
    } 
    else {
        $emptyFlag = false;
    }

    // Load all Batches for printers to the table
    for($i=0;$i<=($rowsCount-1);$i++) {
        $form = $_SESSION["defaultForm"];//KR - $form is now a 2D array******
        for ($z = 0; $z < $availablePrinterCount; $z++) {
            $printer = $printerAvailable[$z];
            ${"batSeq$printer"} = 0;
            ${"invoiceCount$printer"} = 0;

            if($i <= ${"rowsCount$printer"} -1) {//KR - though it appears the following if statement will overwrite the $form variable each time, it will not because $form is a 2D array
                $form = trim(${'rows'.$printer}[$i][0]);
                ${"batSeq$printer"} = trim(${'rows'.$printer}[$i][1]);
                ${"invoiceCount$printer"} = trim(${'rows'.$printer}[$i][2]);

                $batchNum = ${"batSeq$printer"};
                if ($batchNum !== '') {
                    $validBatchSeqs[] = $batchNum;
                }
            }
        }
        
    // If this is the 1st Row then B the Table
    // And Load the ListBox with all of the Form Types in the Table
        if($i == 0) {
            $screenData['selectForm'] .= '<input type="hidden" name="formName" value="'.$form.'"/><table id="invDashBoardTableData"><tr>';
            if ($ids == "1" )  {
                $screenData['selectForm'] .= '<td style="border:none;"><select name="selectForm"><option value="OF-387 " selected>OF-387</option></select></td></tr>';
            }
            else if ($ids == "2") {  
                $screenData['selectForm'] .= '<td style="border:none;"><select name="selectForm"><option value="OF-387 " selected>OF-387</option></select></td></tr>';
            }
            else {
                $screenData['selectForm'] .= '<td style="border:none;"><select name="selectForm" onchange="newForm()">'; //KR - creates the select box for the form type - when changed, it calls the "newForm()" function		
    																					  //KR - which is defined in this same file - this function simply submits the form and returns the "scanData" value
            $ii=0;

            // set the default value stored inside the form
            foreach ($formArray as $formArrayRow) {
                If ($_SESSION['defaultForm'] == $formArray[$ii][0]) { //KR - will populate the select box with form types and set a default (seems to usually be the OF-111s)
                    $screenData['defaultForm'] .= '<option value="' . $formArray[$ii][0] . '" selected>' . $formArray[$ii][0];
                } else {
                    $screenData['defaultForm'] .= '<option value="' . $formArray[$ii][0] . '">' . $formArray[$ii][0];
                }
                $ii = $ii + 1;
            }
        }
        
            // set the value for the direct sends form
            if ($ids == "1") { 
                $screenData['directSends'] .= '<option value="0">No selection</option><option value="1" selected>Direct Sends Isolated</option><option value="2">Direct Sends Excluded</option></select>'; 
            } else if ($ids == "2") { 
                $screenData['directSends'] .= '<option value="1">Direct Sends Isolated</option><option value="0">No selection</option><option value="2" selected>Direct Sends Excluded</option></select>'; 
            } else { 
                $screenData['directSends'] .= '<option value="0" selected>No selection</option><option value="1">Direct Sends Isolated</option><option value="2">Direct Sends Excluded</option></select>'; 
            }

            // get the total amount of invoices for each printer
            for ($z = 0; $z < $availablePrinterCount; $z++) {
                $printer = $printerAvailable[$z];
                if ($ids == "1") {
                    ${"totalInvoices$printer"} = getInvoiceTotal($printer,'DSI');
                }
                else if ($ids == "2") {
                    ${"totalInvoices$printer"} = getInvoiceTotal($printer,'DSO');
                }
                else {
                    ${"totalInvoices$printer"} = getInvoiceTotal($printer,$form);
                }
                putLCCOnlineLogRec(" KR 130189: \n");
                putLCCOnlineLogRec($printer . " > > > " . ${"totalInvoices$printer"} . " \n");
                $grandTotalInvoice += ${"totalInvoices$printer"};
                }  

            for ($z = 0; $z < $availablePrinterCount; $z++) {
                $printer = $printerAvailable[$z];
                $screenData['totalInvoices'] .= '<td>Total Invoices</td><td>'.${"totalInvoices$printer"}.'</td>';
            }
            $screenData['grandTotal'] .= $grandTotalInvoice;
            
            if ($ids == "1") { 
                $invoiceCountPCKDTLP = getPCKDTLPCount("DSI");//KR - method exists in InvPrt_Utils.php - how exactly does this one work?
            }
            else if ($ids == "2") { 
                $invoiceCountPCKDTLP = getPCKDTLPCount("DSO");//KR - method exists in InvPrt_Utils.php - how exactly does this one work?
            }
            else {
                $invoiceCountPCKDTLP = getPCKDTLPCount($form);//KR - method exists in InvPrt_Utils.php - how exactly does this one work?
            }
            $screenData['pckdtlpCount'] .='<td>PCKDTLP Count</td><td>'.$invoiceCountPCKDTLP.'</td>';

            // formating for table rows, original call before ajax rebuilt the table
            for ($z = 0; $z < $availablePrinterCount; $z++) {
                if ($availablePrinterCount == 1 || $availablePrinterCount == 2) {
                    break;
                }
                else if ($z == 0 || $z == 1) {
                    continue;
                }
                else {
                    $screenData['tableRows'] .= '<td></td><td></td>';
                }
            }
            for ($z = 0; $z < $availablePrinterCount; $z++) {
                $printer = $printerAvailable[$z];
                ${"totalInvoices$printer"} = 0;
                $screenData['splitends'] .= '<td>---------</td><td></td>';
            }
            $grandTotalInvoice = 0;

            // display the printer number
            for ($z = 0; $z < $availablePrinterCount; $z++) {
                $printer = $printerAvailable[$z];
                $screenData['displayPRT'] .= '<td>PRT-' .$printer. '</td><td></td>';

            }

            // display the table header
            for ($z = 0; $z < $availablePrinterCount; $z++) {
                $screenData['tableHeader'] .='<td>Batch Seq</td><td>Invoice Count</td>';
            }
        }
        for ($z = 0; $z < $availablePrinterCount; $z++) {
            $printer = $printerAvailable[$z];
            if(${"batSeq$printer"} == 0){//KR - will not write a batch sequence value of 0 to the table - but what does this imply? Why would a batch seq. be 0? If it doesn't exist?
                ${"batSeq$printer"} = '';
            }
            if(${"invoiceCount$printer"} == 0){//KR - will not write a batch sequence value of 0 to the table - but what does this imply? Why would a batch seq. be 0? If it doesn't exist?
                ${"invoiceCount$printer"} = '';
            }
            // Get all the invoices for the batch sequence number
            ${"invoices$printer"} = getInvoicesByBatch(intval(${"batSeq$printer"}));
        }

        // Establish the list of invoices for each printer
        for ($z = 0; $z < $availablePrinterCount; $z++) {
            $printer = $printerAvailable[$z];
            $il = 0;
            ${"invoiceList$printer"} = '';

            foreach (${"invoices$printer"} as ${"invoice$printer"}) {
                ${"invoiceList$printer"} = ${"invoiceList$printer"}.${'invoices'.$printer}[$il][0].'-'.${'invoices'.$printer}[$il][1].'-'.${'invoices'.$printer}[$il][2].'<br>';//KR - this assignment subsequently is stored as a tooltip but what do the numbers mean?
                $il = $il + 1;//KR - do the invoices for one batch sequence number at a time
            }
        }

        $screenData['batSeq'] .= "<tr>";
        for ($z = 0; $z < $availablePrinterCount; $z++) {
            
            $printer = $printerAvailable[$z];
            // Insert the row into the table
            $screenData['batSeq'] .= "<td class='batSeqTableClass' onMouseOver=\"tooltip.show('".${"invoiceList$printer"}."')\" onmouseout=\"tooltip.hide()\">"
            .${"batSeq$printer"}."</td><td>".${"invoiceCount$printer"}."</td>";
            
        }
        $screenData['batSeq'] .= "</tr>";

        // grand total of invoices shown on screen added up
        for ($z = 0; $z < $availablePrinterCount; $z++) {
            $printer = $printerAvailable[$z];
            ${"totalInvoices$printer"} = ${"totalInvoices$printer"} + (is_numeric(${"invoiceCount$printer"}) ? ${"invoiceCount$printer"} : 0 ); // add ternary operator to avoid PHP warning about non - numeric values - kjr -07-14-22
            $grandTotalInvoice = $grandTotalInvoice + (is_numeric(${"invoiceCount$printer"}) ? ${"invoiceCount$printer"} : 0 ); // add ternary operator to avoid PHP warning about non - numeric values - kjr -07-14-22
        }
    }

    // total invoices that appear at the bottom of the screen
    for ($z = 0; $z < $availablePrinterCount; $z++) {
        $printer = $printerAvailable[$z];
        if (!($emptyFlag)) {
            $screenData['finalTally'] .= '<td>Total Invoices</td><td>' .${"totalInvoices$printer"}. '</td>';
        }
    }

    if (!($emptyFlag)) { # don't echo this if available printers have no current batches assigned to them - if you echo it, it causes rendering issues with the DOM
        $screenData['footer'] .='<tr><td> Grand Total</td><td>'.$grandTotalInvoice.'</td>';
        $screenData['footer'] .='<td>PCKDTLP Count</td><td>'.$invoiceCountPCKDTLP.'</td>';
    }
    for ($z = 0; $z < $availablePrinterCount; $z++) {
        if ($availablePrinterCount == 1 || $availablePrinterCount == 2) {
            break;
        }
        else if ($z == 0 || $z == 1) {
            continue;  
        }
        else {
            $screenData['endRows'] .= '<td></td><td></td>';
        }
    }

    // create emptyFlag for empty data purposes
    $screenData['emptyFlag'] = $emptyFlag;
    // display the updated screen data that was added in controller file by using display file call
    dspPrintInvoices($screenData);
    }
?>

<script>
const validBatchSeq = <?php echo json_encode($validBatchSeqs); ?>;
</script>

<!--  End Content Here -->
<?php
if (isset($_SESSION['ErrorMessage']) and $_SESSION['ErrorMessage'] > " "){
    $screenData['error'] .= '<br>';
	$screenData['error'] .= $_SESSION['ErrorMessage'];
    $screenData['error'] .= '<br>';
	include("EndBlock.php");	
	return;
} //end authority check "if"
include("EndBlock.php");
?>