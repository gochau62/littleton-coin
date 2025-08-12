<?php 
/*session_start(); */
// loads php spreadsheet library
// includes custom functions
// starts a session
require '/www/seidenphp/htdocs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once "Utils/common_functions.php";
require_once "AD_importOrderFile_model.php";
include "Utils/default_values.php";
include "Utils/EZMail.php";

session_name(SESSION_NAME);
session_start();

function emailUserError($errorTable) {
    $emailRecipient = strtolower(trim($_SESSION['username'])) . "@littletoncoin.com";
    //$emailRecipient = 'krainville@littletoncoin.com';
    $subject = "Errors uploading Order File records";
    $message = "Errors uploading Order File records. See errors below:"
        . "<br><br> " . $errorTable 
            . "<br><br><br><br>"
                    . "Originating program: " . "/www/seidenphp/htdocs/LCCOnline/AD_importOrderFile_ajax_GORDON.php";
                        $sender = "lcc1@littletoncoin.com";
                        $failAddress = "helpdesk@littletoncoin.com";
                        $attachedFile = false;
                        sendMSG($emailRecipient, $subject, $message, $sender, $failAddress, $attachedFile);

}

// validates that the integer is a numeric number
function validateInteger($value) {
    //need is_numeric to avoid $value = true returns true with preg_match
    return is_numeric($value) && preg_match('/^\d+$/', $value);
}

// retrieves username and password for the session returns error is none found
if (isset($_SESSION['username']) and isset($_SESSION['password'])) {
    $user = $_SESSION['username'];
    $password = $_SESSION['password'];
}
else {
    putLCCOnlineLogRec("Username/password are undefined");
}

// main spreedsheet logic
// file handling using PHPspreadsheet
// row and cell iteration using loop
// validation and insertion, validates data and inserts if valid
// error handling and returns JSON value

// condition checks if action key is set to POST
if (!isset($_POST['action'])) {

            // intializes connection 
            $conn = getDB2PConn($user, $password);
            // initialize error and insert counts set at 0
            $errorCount = 0;
            $insertCount = 0;
            // userprofile is assigned value of $user
            $userProfile = trim( (is_null($user) ? '' : $user) );

            $returnVar = "success";
            $inputFileName = $_FILES["myFile"]["tmp_name"];
            
            // identifies the uploaded file type using PhpSpreadsheet
            $fileTypeOX = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
            $completed_array = array();
            //  Read your Excel workbook

            // if file type is Xlsx (excel)
            if ($fileTypeOX == "Xlsx") {

            // load the file using PhpSpreadsheet
            try {
                $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
                $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($inputFileName);
            } catch(Exception $e) {
                die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
            }
            
            //  Get worksheet dimensions
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            $heresJohnny = array();
            for ($row = 1; $row <= $highestRow; $row++) {
                //  Read a row of data into an array
                $rowData = $sheet->rangeToArray('A'  . $row . ':' . $highestColumn . $row,
                    NULL,
                    TRUE,
                    FALSE);
                ${"heresJohnny$row"} = array();
                ${"heresJohnny$row"} = $rowData;
                
                
                $saved_index = 0;
                for ($l = 0; $l < count(${"heresJohnny$row"}[0]); $l++) {
                    $completed_array[$row - 1][$saved_index] = ${"heresJohnny$row"}[0][$l];
                    $saved_index++;
                }
            }

            // process the data into the created empty array 
            $completed_array = array_values($completed_array); // resequence array keys numerically
            $rowSize = count( $completed_array );
            $columnSize = max( array_map('count', $completed_array) );
            
            $errorString = "";
            $errorStringBuild = "<html><style>" .
            "table, th, td { " .
            "border: 1px solid black;" .
            "border-collapse: collapse;" .
            "} " .
            "</style><body>"; 

            $errorStringBuild = $errorStringBuild . "<table style='width:100%'><th><tr><td>TSUSER</td><td>TSSEQNO</td><td>TSCOMMENT</td><td>TSACTIVE</td><td>ERROR</td></tr></th>";
            
            for ($i=0; $i<count($completed_array); $i++) {
                $errorFlag = false;
                $tsuser = $completed_array[$i][0];
                $tsseqno = $completed_array[$i][1];
                $tscomment = $completed_array[$i][2];
                $tsactive = $completed_array[$i][3];
                /* putLCCOnlineLogRec("\n index is: " . $i . " <");
                putLCCOnlineLogRec("\n tsuser is: " . $tsuser . " <");
                putLCCOnlineLogRec("\n tsseqno is: " . $tsseqno . " <");
                putLCCOnlineLogRec("\n tscomment is: " . $tscomment . " <");
                putLCCOnlineLogRec("\n tsactive is: " . $tsactive . " <"); */
                // initiate instance variables from array

                if($tsuser == '') {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Missing cell value. Every field required!</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // username cannot be empty

                if($tsseqno == '') {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Missing cell value. Every field required!</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // sequence number cannot be empty

                if($tscomment == '') {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Missing cell value. Every field required!</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // comment field cannot be empty

                if (strlen(trim($tsuser)) > 10) {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Username " . $tsuser . " must be 10 characters or less</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // if username greater than 10 characters insert error

                if (strlen(trim($tsseqno) != 5 && ctype_digit($tsseqno))) {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Sequence Num " . $tsseqno . " must be packed decimal 5,0</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // if sequence number is not a packed decimal 5,0 

                if (strlen(trim($tscomment)) > 30) {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Comment " . $tscomment . " must be less than 30 characters</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // if comment line is greater than 30 characters 
                
                if (is_null($tsactive)) {
                    $tsactive = '';
                } // ensure there is no null values, cannot accept 

                if (trim($tsactive) != "Y" && trim($tsactive) != '') {
                    $errorString = "<tr><td>" . $tsuser . "</td><td>" . $tsseqno. "</td><td>" . $tscomment . "</td><td>" . $tsactive . "</td><td>" . "Active " . $tsactive . " must be either Y or blank</td></tr>";
                    $errorStringBuild = $errorStringBuild . $errorString;
                    $errorFlag = true;
                    $errorCount = $errorCount + 1;
                    continue;
                } // make sure tsactive is either Y or '' (empty space)
                
                $record = checkRecordExists($conn, trim($tsuser), trim($tsseqno));
                
                if ($record) {
                    putLCCOnlineLogRec("\n index BEFORE update: " . $i . " <");
                    putLCCOnlineLogRec("\n tsuser BEFORE update: " . $tsuser . " <");
                    putLCCOnlineLogRec("\n tsseqno BEFORE update: " . $tsseqno . " <");
                    putLCCOnlineLogRec("\n tscomment BEFORE update: " . $tscomment . " <");
                    putLCCOnlineLogRec("\n tsactive BEFORE update: " . $tsactive . " <");
                    updateRecord($conn, $tsuser, $tsseqno, $tscomment, $tsactive);
                    $insertCount = $insertCount + 1;
                    // increase insert count, log values before update
                } else {
                    putLCCOnlineLogRec("\n index BEFORE insert: " . $i . " <");
                    putLCCOnlineLogRec("\n tsuser BEFORE insert: " . $tsuser . " <");
                    putLCCOnlineLogRec("\n tsseqno BEFORE insert: " . $tsseqno . " <");
                    putLCCOnlineLogRec("\n tscomment BEFORE insert: " . $tscomment . " <");
                    putLCCOnlineLogRec("\n tsactive BEFORE insert: " . $tsactive . " <");
                    insertRecord($conn, $tsuser, $tsseqno, $tscomment, $tsactive);
                    $insertCount = $insertCount + 1;
                    // increase insert count, log values before insert
                }
            }

            if ($errorString != "") {
                $returnVar = "error";
                $errorStringBuild = $errorStringBuild . "</table></body></html>";
                emailUserError($errorStringBuild);
            } // create error string and email user if error
        }

        if ($returnVar == "error") {
            $returnValue = array('returnClass' => 'error', 'insertCount' => $insertCount, 'errorCount' => $errorCount);
        }
        else if ($returnVar == "success") {
            $returnValue = array('returnClass' => 'success', 'insertCount' => $insertCount);
        }
        echo json_encode($returnValue);
    }
?>
