<?php
/*    ***************************************************  -->
<!--  * Program Name - InvPrt_Print_Invoices_dsp.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 01/9/2025                          *  -->
<!--  ***************************************************   */
?>

<!-- This is the second part change availability of the printers, as well as adding new printers 
    comeback and retouch this at a later date?-->
<!--  Begin Content Here -->
<!-- Step one. Pull out HTML that is used inside PHP that builds the UI to display printer information, disregard logic -->
<!-- Step two. Create the model for the print invoices which allows for database connections, business logic, and crud. -->
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- html included here with style for the display of the page -->
    <meta charset="UTF-8">
    <title>Print Invoices</title>
    <style>
        #head {
            font-size: -1;
            font-weight: bold;
        }
        #message {
            color: red;
        }
        .borderless td, .borderless th {
            border: none !important;
        }
        #error {
            font-size: +3;
        }
        #addNewPrinter, #changePrinterDescription {
            margin-bottom: 20px;
        }
        #spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
        }
        .spinner-loader {
            position: absolute;
            top: 40%;
            left: 43%;
            border: 6px solid #f3f3f3;
            border-top: 6px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<!-- Spinner shown during AJAX load -->
<div id="spinner-overlay">
  <div class="spinner-loader"></div>
</div>

<?php
function dspPrintInvoices(&$screenData) {
?>
<!-- change availability button -->
 <div id="printerAvbtyData">
<div id="invPrtAvbty" style="display:none">
    <div id="btnAra">
        <span><button id="modeButton2" onclick="javascript:changeDivs();">Invoice Print Table</button></span>
    </div>
    <?= $_SESSION['printerHTML'] ?>
    <br><br>

<!-- printer history selection and summary information --> 
    <label for="historyPrinters">Choose a printer for recent history:</label>
    <select name="historyPrinters" id="historyPrinters" onchange="javascript:populateHistory()">
        <?= $_SESSION['historyDropdown'] ?>
    </select>
    <br>
    <?= $_SESSION['summaryDivs'] ?>
    <br>
</div>

<!--add new printer heading and dropdown menu as well as description-->
<div id="addNewPrinter" style="display:none">
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

<!-- change printer description heading and dropdown menu as well as description-->
<div id="changePrinterDescription" style="display:none">
        <fieldset>
            <legend><h5>Change Printer Description</h5></legend>
            Current Printer Description:
            <select name="CurrentPrintDescription" id="CurrentPrintDescription">
                <?= $_SESSION['descriptionOptions'] ?>
            </select>
            New Printer Description:
            <input type="text" name="NewPrintDescription" id="NewPrintDescription" maxlength="60"/>
            <input type="button" name="changePrinterDescButton" id="changePrinterDescButton" value="Change" onclick="submitChangeDescription()">
        </fieldset>
    </form>
</div>
</div>
<!-- includes the button to change between the two different parts of the page -->
<div style="background-color: <?php echo $screenData['backgroundColor']; ?>">
    <div id="invPrtTable" style="display: block;">
        <div id="btnAra1">
            <span>
                <button id="modeButton1" onclick="javascript:changeDivs()">Change Availability</button>
            </span>
        </div>
        <form NAME="FORM_Print_Invoices" id="FORM_Print_Invoices"
	        ACTION="InvPrt_Print_Invoices_Validate.php"
 	        METHOD="POST"
            onSubmit="return processScanData(document.FORM_Invoice_Print.scanData.value)">

            <!-- header information along with the printButton -->
            <div id="head">
                <center>
                Invoice Print<br>
                Assign Batches to the Printer<br>
                <?php echo $screenData['printButton']; ?>

            <!-- using php command lockoutFlag to determine the status of printButton -->
            <div align="left">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspReassign Batch</div>
                <table class="borderless">
                    <tr>
                        <td>From Batch Seq</td>
                        <td>To Batch Seq</td>
                        <td>Printer</td>
                    </tr>
                    <tr>
                        <td><input type="Text" name="FromBatchSeq" id="FromBatchSeq"></td>
                        <td><input type="Text" name="ToBatchSeq" id="ToBatchSeq"></td>

                        <!-- <td style="border:none;"><select name="Printer" id="Printer"><option>10<option>11</option></select> </td> -->
                        <td><select name="Printer" id="Printer"> <?php echo $screenData['printerAvailable'] ?> </select></td>
                        <td><input type="button" name="Update" id="Update" value="Update" onclick="submitUpdateAJAX()"></td>
                    </tr>
                </table>
            </div></b>
        
        <!-- create form for selection as well as adding the default form -->
        <?php echo $screenData['selectForm']?>
        <?php echo $screenData['defaultForm']?>
        </option></select></td></tr>
        <tr><td style="border:none;">
        </form>

        <?php if (!$screenData['emptyFlag']): ?>
        <!-- create form for direct send selection -->
        <label for="isolateDirectSends1">Isolate Direct Sends?</label><br>
        <select name="isolateDirectSends1" id="isolateDirectSends1" onchange="isolateDirectSends()">
        <?php echo $screenData['directSends']?>
        </td></tr>

        <!-- create the headers for total invoices and grand total -->
        <?php echo $screenData['totalInvoices']?>
        <tr>
        <td> Grand Total</td><td><?php echo $screenData['grandTotal']?></td>
        <?php echo $screenData['pckdtlpCount']?>
        <?php echo $screenData['tableRows']?>
        </tr>

        <!-- add original table formating displaying the printer number and table headers -->
        <tr> <?php echo $screenData['splitends']?> </tr>
        <tr> <?php echo $screenData['displayPRT']?> </tr>
        <tr> <?php echo $screenData['tableHeader']?> </tr>

        <!-- show tool tip when hovering over batch sequence numbers -->
        <tr> <?php echo $screenData['batSeq']?> </tr>
        <tr> <?php echo $screenData['finalTally']?> </tr>

        <!-- formatting for bottom of the page-->
        <?php echo $screenData['footer']?>
        <?php echo $screenData['endRows']?>
        </tr></table>
        <?php endif; ?>
        
        <div id="error"> <?php echo $screenData['error']?> </div>
    </div>
</div>
<?php
} 
?>
