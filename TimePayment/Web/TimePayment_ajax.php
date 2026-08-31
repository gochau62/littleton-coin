<?php
/*    ***************************************************  -->
<!--  * Program Name - TimePayment_ajax.php             *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/30/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 230077                              *  -->
<!--  ***************************************************   */

// AJAX endpoint, buffer from byte 0 so stray include output can't corrupt the JSON
ob_start();
foreach (['Utils/common_functions.php', 'Utils/default_values.php', 'Utils/EZMail.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}

// the same vendored PhpSpreadsheet copy project 230036 reads its uploads with
$tpyVendor = '/www/seidenphp/htdocs/vendor/autoload.php';
if (file_exists($tpyVendor)) { require_once $tpyVendor; }

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

require_once __DIR__ . '/TimePayment_model.php';

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json');

function tpyOut($arr) { echo json_encode($arr); exit; }


function tpyOutFail($msg = '') {
    tpyOut(array("ok" => false,
                 "msg" => $msg !== '' ? $msg : ($GLOBALS['tpyErr'] ?: 'Request failed.')));
}


// a spreadsheet cell as text: Excel hands whole numbers (item numbers, source codes, plans) back as floats, so those lose the trailing .0 here
function tpyCellText($v) {
    if ($v === null) { return ''; }
    if (is_numeric($v) && !is_string($v) && floatval($v) == floor(floatval($v))) {
        return strval(intval($v));
    }
    return trim((string)$v);
}


// a stored YYYYMMDD number as MM/DD/YYYY for the report
function tpyFmtDate($dec) {
    $s = strval(intval($dec));
    if (strlen($s) !== 8) { return ''; }
    return substr($s, 4, 2) . '/' . substr($s, 6, 2) . '/' . substr($s, 0, 4);
}


// e-mail the exception report to whoever ran the upload, the way the Order File import does - only the rows that were skipped ride along, since the
// good rows are already on TPITEMSP
function tpyEmailExceptions($user, $report, $fileName) {
    if (!function_exists('sendMSG')) {
        error_log('TimePayment: EZMail sendMSG is unavailable - the exception report was not e-mailed');
        return false;
    }

    $table = "<table border='1' cellpadding='4' cellspacing='0'>"
           . "<tr><th>Row</th><th>Item #</th><th>Source Code</th><th>Plan</th>"
           . "<th>Expiration Date</th><th>Reason</th></tr>";
    foreach ($report as $r) {
        if ($r['status'] !== 'error') { continue; }
        $table .= "<tr><td>" . htmlspecialchars($r['row']) . "</td>"
                . "<td>" . htmlspecialchars($r['item']) . "</td>"
                . "<td>" . htmlspecialchars($r['src']) . "</td>"
                . "<td>" . htmlspecialchars($r['plan']) . "</td>"
                . "<td>" . htmlspecialchars($r['exp']) . "</td>"
                . "<td>" . htmlspecialchars($r['msg']) . "</td></tr>";
    }
    $table .= "</table>";

    $emailRecipient = strtolower(trim($user)) . "@littletoncoin.com";
    $subject = "Item Time Payment upload - exception report";
    $message = "The following rows of " . htmlspecialchars($fileName)
             . " were skipped and are NOT on the time payment file. "
             . "Fix them and upload just those rows again:"
             . "<br><br>" . $table
             . "<br><br><br><br>"
             . "Originating program: /www/seidenphp/htdocs/LCCOnline/TimePayment_ajax.php";
    $sender      = "lcc1@littletoncoin.com";
    $failAddress = "helpdesk@littletoncoin.com";
    sendMSG($emailRecipient, $subject, $message, $sender, $failAddress, false);
    return true;
}


if (!$conn) {
    tpyOutFail("No database connection - sign in to LCC Online first.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // the review grid rows; q narrows by item or source code
    case 'list':
        $rows = tpyList($conn, $_POST['q'] ?? $_GET['q'] ?? '');
        if ($rows === false) { tpyOutFail(); }
        tpyOut(array("ok" => true, "rows" => $rows, "today" => intval(date('Ymd'))));

    // a blank workbook with the four column headings, so Marketing starts from the layout the upload expects
    case 'template':
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            tpyOutFail("The spreadsheet library is not available on this server.");
        }
        $book  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Time Payments');
        $sheet->fromArray(array('Item #', 'Source Code', 'Plan (optional)', 'Expiration Date'), NULL, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        foreach (array('A' => 14, 'B' => 14, 'C' => 16, 'D' => 18) as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="TimePaymentUpload.xlsx"');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx');
        $writer->save('php://output');
        exit;

    // the upload itself: validate each row the way the project write-up lays it out, write the good ones to TPITEMSP, and e-mail the skipped ones
    // back to the user as the exception report
    case 'upload':
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
            tpyOutFail("The spreadsheet library is not available on this server.");
        }
        if (!isset($_FILES['myFile']) || $_FILES['myFile']['error'] !== UPLOAD_ERR_OK) {
            tpyOutFail("No file arrived with the upload - attach a spreadsheet and try again.");
        }

        $inputFileName = $_FILES['myFile']['tmp_name'];
        $origName      = $_FILES['myFile']['name'] ?? 'upload';

        try {
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($inputFileName);
            if (!in_array($fileType, array('Xlsx', 'Xls', 'Csv'))) {
                tpyOutFail("Unsupported file type ($fileType) - upload an .xlsx, .xls or .csv file.");
            }
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
            $reader->setReadDataOnly(true);
            $book = $reader->load($inputFileName);
        } catch (Exception $e) {
            tpyOutFail("Could not read the spreadsheet: " . $e->getMessage());
        }

        // Marketing's list rides on the first sheet; row 1 is the column headings
        $sheet      = $book->getSheet(0);
        $highestRow = $sheet->getHighestDataRow();
        $today      = intval(date('Ymd'));

        $added = 0; $updated = 0; $errors = 0;
        $report = array();

        for ($row = 2; $row <= $highestRow; $row++) {
            $cells  = $sheet->rangeToArray('A' . $row . ':D' . $row, NULL, TRUE, FALSE);
            $item   = tpyCleanItem(tpyCellText($cells[0][0]));
            $src    = tpyCleanSrc(tpyCellText($cells[0][1]));
            $plan   = tpyCleanPlan(tpyCellText($cells[0][2]));
            $expRaw = $cells[0][3];
            $expTxt = tpyCellText($expRaw);

            // resolve the date up front so the report shows a readable date even when
            // the row fails an earlier check - an Excel date cell is a raw serial
            // number like 46752 until it is converted
            $expDate = tpyNormDate($expRaw);
            $expShow = $expDate > 0 ? tpyFmtDate($expDate) : $expTxt;

            // an entirely blank row is just Excel padding, not an error
            if ($item === '' && $src === '' && $plan === '' && $expTxt === '') { continue; }

            // a Db2 failure mid-file stops the run cold rather than half reporting - the rows above this one are already written and stay written
            $halt = 'Row ' . $row . ' stopped the upload (';

            // item # against the item master
            $found = tpyGetItem($conn, $item);
            if ($found === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }
            if ($found === null) {
                $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                                  'exp' => $expShow, 'status' => 'error',
                                  'msg' => 'Item is not on the Item Master.');
                $errors++;
                continue;
            }

            // source code against OEPSRCE
            $found = tpyGetSource($conn, $src);
            if ($found === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }
            if ($found === null) {
                $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                                  'exp' => $expShow, 'status' => 'error',
                                  'msg' => 'Source code is not on OEPSRCE.');
                $errors++;
                continue;
            }

            if ($plan !== '') {
                // a plan Marketing typed must exist on TPPLANSP - per Dennis this is an error condition, not something to quietly ignore
                $found = tpyGetPlan($conn, $plan);
                if ($found === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }
                if ($found === null) {
                    $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                                      'exp' => $expShow, 'status' => 'error',
                                      'msg' => 'Plan is not on the time payment plan file.');
                    $errors++;
                    continue;
                }
            } else {
                // no plan given: price the item the way OP0800R does and tie the price out to the TPTIERSP ranges. A price outside every range
                // (under $99.00) skips the row onto the report - per Josh those get looked at individually, not defaulted into a plan
                $price = tpyItemPrice($conn, $item, $src);
                if ($price === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }

                $tier = tpyTierPlan($conn, $price);
                if ($tier === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }
                if ($tier === null || $tier === '') {
                    $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => '',
                                      'exp' => $expShow, 'status' => 'error',
                                      'msg' => 'Item price ($' . number_format($price, 2) .
                                               ') falls outside the time payment plan ranges.');
                    $errors++;
                    continue;
                }
                $plan = $tier;
            }

            // expiration date: a real date, written as YYYYMMDD, and not already passed
            if ($expDate === 0) {
                $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                                  'exp' => $expShow, 'status' => 'error',
                                  'msg' => 'Expiration date is not a valid date.');
                $errors++;
                continue;
            }
            if ($expDate < $today) {
                $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                                  'exp' => $expShow, 'status' => 'error',
                                  'msg' => 'Expiration date has already passed.');
                $errors++;
                continue;
            }

            // all four columns are good: update the record when the item/source key is already on TPITEMSP, add it when it is not
            $existing = tpyGetRecord($conn, $item, $src);
            if ($existing === false) { tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.'); }

            if (!tpyUpsert($conn, $item, $src, $plan, $expDate)) {
                tpyOutFail($halt . $GLOBALS['tpyErr'] . ') - rows above it were already written.');
            }

            if ($existing === null) { $added++; } else { $updated++; }
            $report[] = array('row' => $row, 'item' => $item, 'src' => $src, 'plan' => $plan,
                              'exp' => $expShow,
                              'status' => $existing === null ? 'added' : 'updated',
                              'msg' => $existing === null ? 'Added.' : 'Updated the existing record.');
        }

        $emailed = false;
        if ($errors > 0) {
            $emailed = tpyEmailExceptions($user, $report, $origName);
        }

        tpyActLog($user, 'UPLOAD', $origName . ' - ' . $added . ' added, ' .
                  $updated . ' updated, ' . $errors . ' exceptions');

        tpyOut(array("ok" => true, "added" => $added, "updated" => $updated,
                     "errors" => $errors, "emailed" => $emailed, "report" => $report));

    default:
        tpyOutFail("Unknown action.");
}
?>
