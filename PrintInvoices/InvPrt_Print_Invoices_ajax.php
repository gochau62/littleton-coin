<?php
/*    ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  * Date      - 05/20/2025                          *  -->
<!--  * Purpose   - PHP Dashboard Rewrite ajax calls    *  -->
<!--  *                                                 *  -->
<!--  * Project   - 240197                              *  -->
<!--  ***************************************************   */
?>

<?php 
//KRENW 130189 *ALL
/*session_start(); */
//require_once dirname(__FILE__) . '/../Classes/PHPExcel.php';
//include '../Classes/PHPExcel/IOFactory.php';
//require '/www/seidenphp/htdocs/vendor/autoload.php';

///use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once "Utils/common_functions.php";
//require_once "CRM_model.php";
include "Utils/default_values.php";
session_name(SESSION_NAME);
session_start(); 

function validateInteger($value) {
    //need is_numeric to avoid $value = true returns true with preg_match
    return is_numeric($value) && preg_match('/^\d+$/', $value);
}

if (isset($_SESSION['username']) and isset($_SESSION['password'])) {
    $user = $_SESSION['username'];
    $password = $_SESSION['password'];
}
else {
    putLCCOnlineLogRec("Username/password are undefined");
}
$table = "";

if (!isset($_POST['action'])) {
            $conn = getDB2PConn($user, $password);
    }
else {
        switch ($_POST['action']) {
        case 'getBatchPrinterHistory':
            $conn = getDB2PConn($user, $password);
            $printer = $_POST['prtNum'];
            $maxTime = $_POST['maxTim'];
            $minTime = $_POST['minTim'];
            $table = getBatchPrinterHistory($conn, $printer, $maxTime, $minTime); 
            //$message = $success; 
            echo json_encode($table);
            break;
        case 'getInvoiceHistory':
            $conn = getDB2PConn($user, $password);
            $batch = $_POST['batchnum'];
            $table = getInvoiceHistory($conn, $batch);
            //$message = $success;
            echo json_encode($table);
            break;
        case 'reloadScreen':
            require_once("Utils/InvPrt_Utils.php");
            $conn = getDB2PConn($user, $password);
            $printerAvailability = array();
            $printerAvailability = renderPrinterAvailability();//call function in InvPrt_Utils.php to call stored procedure and assign returned value(s) to array
            $printerCount = count($printerAvailability);
            $availablePrinterCount = null;//problem - this variable is loaded with all 'Y' printers, but it is used against an array with all the printers - desired effect unachieved 
            $rowsCountArray = array();
            $printerAvailable = array();
            
            foreach ($printerAvailability as $prt) {
                if ($prt[2] === 'Y') {
                $printerAvailable[] = $prt[0];
                $availablePrinterCount++;
                }
            }

            // select form dropdown
            $formArray = getBatchForms();
            $ids = $_POST['ids'] ?? null; 
            $formFromUser = $_POST["formName"] ?? $_SESSION["defaultForm"] ?? $formArray[0][0];
            
            // if id is Direct Sends 'isoldated' or 'excluded' only allow OF-387
            if ($ids == "1" || $ids == "2") {
                $form = "OF-387";
            } else {
                // else allow users to switch the select form if in the 'no selection' mode
                $form = $formFromUser;
                $_SESSION["defaultForm"] = $form;
            }
            
            // formatting selectForm dropdown for alignment
            $formSelectHTML = '<select id="selectFormDropdown" name="selectForm" style="margin-left: 7px;">';
            // hardcode to ensure only option 'OF-387' is allowed in direct sends mode
            if ($ids == "1" || $ids == "2") {
                $displayText = str_replace(' ', '&nbsp;', $formArray[1][0] . ' ');
                $formSelectHTML .= "<option value=\"OF-387\" selected>{$displayText}</option>";
            } else {
                // insert form options into dropdown from array
                foreach ($formArray as $formOption) {
                    $selected = (trim($formOption[0]) === trim($form)) ? 'selected' : '';
                    $formSelectHTML .= "<option value=\"{$formOption[0]}\" $selected>{$formOption[0]}</option>";
                }
            }    
            $formSelectHTML .= '</select></form>';

            // direct send form formatting for alignment
            $directSendHTML = '<br><label style="margin-left: 7px;">Isolate Direct Sends?<label>';
            $directSendHTML .= '<select id="directSendsDropdown" name="directSends" style="margin-left: 7px; margin-right: 7px;">';

            // for specific ids show which mode is being selected based on the dropdown item selected
            if ($ids == "1") {
                $directSendHTML .= '<option value="0">No selection</option><option value="1" selected>Direct Sends Isolated</option><option value="2">Direct Sends Excluded</option>';
            } elseif ($ids == "2") {
                $directSendHTML .= '<option value="0">No selection</option><option value="1">Direct Sends Isolated</option><option value="2" selected>Direct Sends Excluded</option>';
            } else {
                $directSendHTML .= '<option value="0" selected>No selection</option><option value="1">Direct Sends Isolated</option><option value="2">Direct Sends Excluded</option>';
            }
            $directSendHTML .= '</select>';

            // initialize a new array for available printers
            $aNewTest = array();
            for ($i = 0; $i < $availablePrinterCount; $i++) {
                $printer = $printerAvailable[$i];

            // based on id get the batch sequences for the specific direct sends mode or if 'no selection' choose from the selectForm
            if ($ids == "1") {
                $rows = getPrintBatchesDirectSends($printer);
            } elseif ($ids == "2") {
                $rows = getPrintBatchesDirectSendsExcluded($printer);
            } else {
                $rows = getPrintBatches($printer, $form);
            }
            $aNewTest[$printer] = $rows;
            $rowsCountArray[] = count($rows);
            }
            $rowsCount = max($rowsCountArray);

            // intialize table for ajax call
            $tableHTML = '<table id="invDashBoardTableData">';

            // intialize arrays and values for total invoice and grand total
            $totalInvoices = [];
            $grandTotalInvoice = 0;
            foreach ($printerAvailable as $printer) {
                foreach ($aNewTest[$printer] as $row) {
                $invCnt = isset($row[2]) ? $row[2] : '';
                if (is_numeric($invCnt)) {
                    $totalInvoices[$printer] = ($totalInvoices[$printer] ?? 0) + $invCnt;
                    $grandTotalInvoice += $invCnt;
                    }
                }
            }
            // based on 'direct sends' mode for the chosen dropdown show the correct PCKDTLP count for the chosen form
            $invoiceCountPCKDTLP = ($ids === "1") ? getPCKDTLPCount("DSI") : (($ids === "2") ? getPCKDTLPCount("DSO") : getPCKDTLPCount($form));

            // intialize table and add the formatting for total invoices, grand total, and PCKDTLP count
            $tableHTML .= '<tr>';
            foreach ($printerAvailable as $printer) {
                $tableHTML .= '<td>Total Invoices</td><td>' . ($totalInvoices[$printer] ?? 0) . '</td>';
            }
            $tableHTML .= '</tr>';
            $tableHTML .= '<tr><td> Grand Total</td><td>' . $grandTotalInvoice . '</td>';
            $tableHTML .= '<td>PCKDTLP Count</td><td>' . $invoiceCountPCKDTLP . '</td>';

            // Formatting row to pad extra printers, empty boxes to fill white space
            if ($availablePrinterCount > 2) {
                for ($z = 2; $z < $availablePrinterCount; $z++) {
                    $tableHTML .= '<td></td><td></td>';
                }
                $tableHTML .= '</tr>';
            }

            // Split ends row by adding a column of '-' to separate headings from information (removed one '-' to make the look better)
            $tableHTML .= '<tr>';
            for ($i = 0; $i < $availablePrinterCount; $i++) {
                $tableHTML .= '<td>---------</td><td></td>';
            }
            $tableHTML .= '</tr>';
          
            // intialize printer label row into the table
            $tableHTML .= '<tr>';
            foreach ($printerAvailable as $printer) {
                $tableHTML .= "<td>PRT-$printer</td><td></td>";
            }
            $tableHTML .= '</tr>';

            // intialize header label row for batch seq, and invoice count into the table
            $tableHTML .= '<tr>';
            foreach ($printerAvailable as $printer) {
                $tableHTML .= '<td>Batch Seq</td><td>Invoice Count</td>';
            }
            $tableHTML .= '</tr>';

            // initalize the batch sequences based on the specific form and assign them into the table
            for ($i = 0; $i < $rowsCount; $i++) {
                $tableHTML .= '<tr>';
                foreach ($printerAvailable as $printer) {
                    $rows = $aNewTest[$printer];
                    $batSeq = isset($rows[$i][1]) ? $rows[$i][1] : '';
                    $invCnt = isset($rows[$i][2]) ? $rows[$i][2] : '';
                    $invoiceList = '';
                    if ($batSeq !== '') {
                        $invoiceData = getInvoicesByBatch((int)$batSeq);
                        foreach ($invoiceData as $inv) {
                            $invoiceList .= $inv[0] . '-' . $inv[1] . '-' . $inv[2] . '<br>';
                        }
                    }
                    // include mouseOver tip information to show the number of invoice counts in the batch sequence
                    $tableHTML .= "<td class='batSeqTableClass' onMouseOver=\"tooltip.show('$invoiceList')\" onmouseout=\"tooltip.hide()\">$batSeq</td><td>$invCnt</td>";
                }
                $tableHTML .= '</tr>';
            }

            // initalize the final tally row into the table, (repeating total invoice)
            $tableHTML .= '<tr>';
            foreach ($printerAvailable as $printer) {
                $tableHTML .= '<td>Total Invoices</td><td>' . ($totalInvoices[$printer] ?? 0) . '</td>';
            }
            $tableHTML .= '</tr>';

            // intialize final footer row into the table (repeating grand total, and PCKDTLP count)
            $tableHTML .= '<tr><td> Grand Total</td><td>' . $grandTotalInvoice . '</td>';
            $tableHTML .= '<td>PCKDTLP Count</td><td>' . $invoiceCountPCKDTLP . '</td>';

            // intialize empty filler boxes to remove white space
            if ($availablePrinterCount > 2) {
                for ($z = 2; $z < $availablePrinterCount; $z++) {
                    $tableHTML .= '<td></td><td></td>';
                }
                $tableHTML .= '</tr>';
                }
            $tableHTML .= '</table>';
        
            // echo out all the added information such as form dropdown, directSend dropdown, and table html through ajax call (in Invoices.js)
            echo $formSelectHTML . $directSendHTML . $tableHTML;
            break;
        case'reloadAvailability':
            require_once("Utils/InvPrt_Utils.php");
            $testArray = renderPrinterAvailability();
            $testCount = count($testArray);

            // intialize printer table html 
            $printerHTML = '';
            for ($i = 0; $i < $testCount; $i++) {
                if ($i === 0) {
                    $printerHTML .= 
                    ' <div id="PrtNo">Printer Number</div>
                    <div id="PrtDesc">Printer Description</div>
                    <div id="PrtAvl">Available?</div>
                    <br><br class="clear">';
                }

                $id = htmlspecialchars($testArray[$i][0]);
                $desc = htmlspecialchars($testArray[$i][1]);
                $avail = htmlspecialchars($testArray[$i][2]);
                $altOption = ($avail === 'Y') ? 'N' : 'Y';

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

        // dynamically build create print history dropdown menu that updates via ajax call
        $historyDropdown = '<option value="0" selected>Choose a printer...</option>';
        $printerIDs = []; // store IDs for summary too

        foreach ($testArray as $row) {
            $printerID = htmlspecialchars($row[0]);
            $historyDropdown .= "<option value=\"$printerID\">PRT$printerID</option>";
            $printerIDs[] = $printerID;
        }

        // dynamically build through ajax show the summary of information for the printer that was selected
        $summaryDivs = '<div id="prt-summary"></div>';
        foreach ($printerIDs as $printerID) {
            $summaryDivs .= "<div id=\"prt$printerID-summary\"></div>\n";
        }

        // intialize description dropdown
        $descriptionOptions = '';
        foreach ($testArray as $row) {
            $desc = htmlspecialchars($row[1]);
            $descriptionOptions .= "<option value=\"$desc\">$desc</option>";
        }

        // echo out all the added information such as form dropdown, directSend dropdown, and table html through ajax call (in Invoices.js)
        // Build full screen content (match full HTML layout in a container)
        $fullHTML = '
        <div id="invPrtAvbty">
            <div id="btnAra"> <span><button id="modeButton2" onclick="javascript:changeDivs();">Invoice Print Table</button></span></div>' . $printerHTML . '<br><br>
        <label for="historyPrinters">Choose a printer for recent history:</label>
        <select name="historyPrinters" id="historyPrinters" onchange="javascript:populateHistory()">' . $historyDropdown . '</select><br>
        ' . $summaryDivs . '<br>
        </div>

        <div id="addNewPrinter">
            <form action="InvPrt_Printer_Sbm.php" id="addPrinter" method="POST">
                <fieldset>
                    <legend><h5>Add New Printer</h5></legend>
                    Printer Number:<input type="text" name="PrintNumber" id="PrintNumber" size="1" maxlength="3" />
                    Printer Description:<input type="text" name="PrintDescription" id="PrintDescription"/>
                    Printer Availability:
                    <select name="PrintAvailable" id="PrintAvailable">
                        <option value="Y">Y</option>
                        <option value="N">N</option>
                    </select>
                    <input type="button" id="addPrinterButton" value="Add Printer" onclick="submitAddPrinter()">
                </fieldset>
            </form>
        </div>

        <div id="changePrinterDescription">
            <fieldset>
                <legend><h5>Change Printer Description</h5></legend>
                Current Printer Description:
                <select name="CurrentPrintDescription" id="CurrentPrintDescription">
                    ' . $descriptionOptions . '
                </select>
                New Printer Description:
                <input type="text" name="NewPrintDescription" id="NewPrintDescription" maxlength="60"/>
                <input type="button" name="changePrinterDescButton" id="changePrinterDescButton" value="Change" onclick="submitChangeDescription()">
            </fieldset>
        </div>';
        echo $fullHTML;
        '</select>';
        break;

        // intialize a new case for updating batch sequence assignment to other printers (removed form submission using Invoice_Validate)
        // no more submit in the dsp instead uses a button that calls this case as an ajax call!
        case 'updatePrinterAssignment':
            require_once("Utils/InvPrt_Utils.php");
            // Grab batch seq information from input
            $form = $_POST['selectForm'] ?? '';
            $fromBatch = $_POST['FromBatchSeq'] ?? '';
            $toBatch = $_POST['ToBatchSeq'] ?? $fromBatch;

            // For (SINGLE SUBMISSIONS) if no toBatch range then, default it to FromBatch
            if (trim($toBatch) === '') {
                $toBatch = $fromBatch;
            }
            $printer = $_POST['Printer'] ?? '';

            // Call update invoice after button click (from InvPrt_Utils)
            updateInvoicePrinter($fromBatch, $toBatch, $form, $printer);
        case 'addNewPrinter':
            require_once("Utils/InvPrt_Utils.php");
            $addPrtNo   = $_POST['PrintNumber'] ?? '';
            $addPrtDesc = $_POST['PrintDescription'] ?? '';
            $addPrtAvl  = $_POST['PrintAvailable'] ?? '';

            // call add new printer after button click (from InvPrt_Utils)
            addNewPrinter($addPrtNo, $addPrtDesc, $addPrtAvl);
        case 'changePrinterDescription':
            require_once("Utils/InvPrt_Utils.php");
            $oldDesc = $_POST['CurrentPrintDescription'] ?? '';
            $newDesc = $_POST['NewPrintDescription'] ?? '';

            // call change printer description after button click (from InvPrt_Utils)
            changePrintDescription($oldDesc, $newDesc);
        exit;
    }
}
function getBatchPrinterHistory($conn, $printer, $maxTime, $minTime){
     
    $result = array();

    $statement = db2_prepare($conn,'Call IP0185S(?,?,?)')
    or die("<br>db2_prepare failed! getBatchPrinterHistory ". db2_stmt_errormsg());

    db2_bind_param($statement, 1, "printer",DB2_PARAM_IN)
    or die("<br>db2_bind_param 1 getBatchPrinterHistory failed! ". db2_stmt_errormsg());


    db2_bind_param($statement, 2, "minTime",DB2_PARAM_IN)
    or die("<br>db2_bind_param 2 getBatchPrinterHistory failed! ". db2_stmt_errormsg());
    
    db2_bind_param($statement, 3, "maxTime",DB2_PARAM_IN)
    or die("<br>db2_bind_param 1 getBatchPrinterHistory failed! ". db2_stmt_errormsg());
    
    /*  db2_bind_param($statement, 3, "openCount",DB2_PARAM_OUT)
     or die("<br>db2_bind_param 3 getCountofOpenTrans failed! ". db2_stmt_errormsg());
     */
    /* db2_execute($statement)
     or die("<br>db2_execute failed! getCountofOpenTrans2". db2_stmt_errormsg()); */

    if (db2_execute($statement)) {
        while ($row =db2_fetch_assoc($statement)){
            $result[] = $row;
        }
    }

    else {
        die("Get getBatchPrinterHistory Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

function getInvoiceHistory($conn, $batch){
     
    $result = array();

    $statement = db2_prepare($conn,'Call IP0186S(?)')
    or die("<br>db2_prepare failed! getInvoiceHistory ". db2_stmt_errormsg());

    db2_bind_param($statement, 1, "batch",DB2_PARAM_IN)
    or die("<br>db2_bind_param 1 getInvoiceHistory failed! ". db2_stmt_errormsg());

    if (db2_execute($statement)) {
        while ($row =db2_fetch_assoc($statement)){
            $result[] = $row;
        }
    }

    else {
        die("Get getInvoiceHistory Recs execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}
?>
