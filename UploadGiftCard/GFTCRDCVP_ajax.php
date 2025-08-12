<?php
/*    ***************************************************  -->
<!--  * Program Name - GFTCRDCVP_ajax.php               *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/17/2025                         *  -->
<!--  ***************************************************   */
?>
<?php 
/*session_start(); */
// loads php spreadsheet library
// includes custom functions
// starts a session
require '/www/seidenphp/htdocs/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require_once "Utils/common_functions.php";
require_once "GFTCRDCVP_model.php";
include "Utils/default_values.php";
include "Utils/EZMail.php";


session_name(SESSION_NAME);
session_start();

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
if (isset($_POST['action']) && $_POST['action'] == 'check_dupes') {
    $conn = getDB2PConn($user, $password);
    $cardNums = json_decode($_POST['cardnums']);
    $dupes = [];

    foreach ($cardNums as $num) {
        if (checkDuplicateRecord($conn, trim($num))) {
            $dupes[] = $num;
        }
    }

    echo json_encode($dupes);
    exit;
}

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
            
            $completed_array = array();
            $overallRow = 0;

            foreach ($objPHPExcel->getAllSheets() as $sheetIndex => $sheet) {
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                    // Only add rows with at least 3 columns and not blank
                    if (!empty($rowData[0]) && count($rowData[0]) >= 3 && $rowData[0][2] !== null && $rowData[0][2] !== '') {
                        $completed_array[$overallRow] = $rowData[0];
                        $overallRow++;
                    }
                }
            }
            
            for ($i = 0; $i < count($completed_array); $i++) {
                $cvsku  = isset($completed_array[$i][0]) ? trim($completed_array[$i][0]) : '';
                $cvcard = isset($completed_array[$i][2]) ? trim($completed_array[$i][2]) : '';
                $cvbtch = ($cvcard !== '') ? substr($cvcard, 0, 3) : '';
                $cvcvv  = isset($completed_array[$i][3]) ? trim($completed_array[$i][3]) : '';

                    // Validate required fields
                    if ($cvbtch === '' || $cvcard === '' || $cvcvv === '' || $cvsku === '') {
                        $errorFlag = true;
                        $errorCount = $errorCount + 1;
                        continue;
                    }
                    // Validate lengths
                    if (strlen($cvbtch) > 3 || strlen($cvcard) > 9 || strlen($cvcvv) > 3 || strlen($cvsku) > 10) {
                        $errorFlag = true;
                        $errorCount++;
                        continue;
                    }
                    $record = checkDuplicateRecord($conn, trim($cvcard));

                    if ($record) {
                        // Update
                        updateRecord($conn, $cvbtch, $cvcard, $cvcvv, $cvsku);
                        $insertCount++;
                    } else {
                        // Insert
                        insertRecord($conn, $cvbtch, $cvcard, $cvcvv, $cvsku);
                        $insertCount++;
                    }
                }
            }
        if ($errorCount > 0) {
            $returnVar = "error";
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
