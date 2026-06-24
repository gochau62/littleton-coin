<?php
ini_set('max_execution_time', 14400);
ini_set('memory_limit', '8384M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ("/www/websitephp/htdocs/FeedsV9/clario/Clario_model.php");
Require_Once("/www/websitephp/htdocs/FeedsV9/Feed_model.php");
require_once '/www/seidenphp/htdocs/vendor/autoload.php';
require_once '../LCCOnline/Utils/common_functions.php';

//db2 connection information:
$user="webdog";
$passWd="w3bd0g13";

// Clario SFTP credentials
$hostname   = 'sftp.clar.io';
$port       = 22;
$username   = '8c11f8b0a73702036847a5a2a137fc30475f39b9';
$ssh_key    = '/home/sftpuser/.ssh/clario';
$ssh_pubkey = '/home/sftpuser/.ssh/clario.pub';

// remote directory with Clario generated files
define('CLARIO_REMOTE_DIR', 'fromClario');

// File to pull, prefix and extension
define('CLARIO_FILE_PREFIX', 'customer_segments_');
define('CLARIO_FILE_EXT', '.txt');

// Empty directory for Clario landing
define('CLARIO_LOCAL_DIR', '/www/seidenphp/htdocs/utils/Clario_SFTP_Pull');
define('CLARIO_LOG_FILE', CLARIO_LOCAL_DIR . '/ClarioSFTP_pull.log');
 
/* =========================================================================
 * MODEL FUNCTIONS FOR CLARIO_MODEL WHEN IMPLEMENTED
 * ====================================================================== */

function clarioLog($message)
{
    if (function_exists('putLCCFeedLogRecord')) {
        putLCCFeedLogRecord($message);
    }
    @file_put_contents(
        CLARIO_LOG_FILE,
        date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

// From Date = rpt_date + 1 day  (the next day, Sunday)
// To Date   = From Date + 6 days (the next Saturday)
// e.g. rpt_date 20260613 -> From 20260614, To 20260620.
// Returns array(fromYmd, toYmd) as YYYYMMDD ints, or array(null,null).
function weekRangeFromRptDate($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return array(null, null);
    }
    if (preg_match('/^\d{8}$/', $raw)) {
        $dt = DateTime::createFromFormat('Ymd', $raw);
    } else {
        $dt = date_create($raw); // handles 2026-06-13
    }
    if (!$dt) {
        return array(null, null);
    }
    $dt->modify('+1 day');               // Sunday (start of the next week)
    $fromDt = (int) $dt->format('Ymd');
    $dt->modify('+6 days');              // following Saturday
    $toDt = (int) $dt->format('Ymd');
    return array($fromDt, $toDt);
}

// Write one record to the physical file table
// Field order: customer #, segment, from date, to date.
function writeClarioRecord($conn, $fromDt, $toDt, $custNo, $segCode)
{
    $stmt = db2_prepare($conn, 'CALL CLARIO001S(?, ?, ?, ?)');
    if (!$stmt) {
        throw new Exception('CLARIO001S prepare error: ' . db2_stmt_errormsg());
    }
    db2_bind_param($stmt, 1, 'fromDt',  DB2_PARAM_IN);   // -> INFRDT
    db2_bind_param($stmt, 2, 'toDt',    DB2_PARAM_IN);   // -> INTODT
    db2_bind_param($stmt, 3, 'custNo',  DB2_PARAM_IN);   // -> INCUST
    db2_bind_param($stmt, 4, 'segCode', DB2_PARAM_IN);   // -> INSEG
    if (!db2_execute($stmt)) {
        throw new Exception('CLARIO001S execute error: ' . db2_stmt_errormsg());
    }
    return true;
}

// Does this customer# / segment combo already exist in the history table?
// Calls CLARIO002S, which returns the match count in its OUT parameter.
function clarioHistExists($conn, $custNo, $segCode)
{
    $outCnt = 0;
    $checkStmt = db2_prepare($conn, 'CALL CLARIO002S(?, ?, ?)');
    if (!$checkStmt) {
        throw new Exception('CLARIO002S prepare error: ' . db2_stmt_error() .
            ' - ' . db2_stmt_errormsg());
    }
    db2_bind_param($checkStmt, 1, 'custNo',  DB2_PARAM_IN);
    db2_bind_param($checkStmt, 2, 'segCode', DB2_PARAM_IN);
    db2_bind_param($checkStmt, 3, 'outCnt',  DB2_PARAM_OUT);
    if (!db2_execute($checkStmt)) {
        throw new Exception('CLARIO002S execute error: ' . db2_stmt_error() .
            ' - ' . db2_stmt_errormsg());
    }
    return ((int) $outCnt) > 0;
}

// Insert one record into the history table via CLARIO003S
function writeClarioHistRecord($conn, $fromDt, $toDt, $custNo, $segCode)
{
    $histStmt = db2_prepare($conn, 'CALL CLARIO003S(?, ?, ?, ?)');
    if (!$histStmt) {
        throw new Exception('CLARIO003S prepare error: ' . db2_stmt_error() .
            ' - ' . db2_stmt_errormsg());
    }
    db2_bind_param($histStmt, 1, 'fromDt',  DB2_PARAM_IN);
    db2_bind_param($histStmt, 2, 'toDt',    DB2_PARAM_IN);
    db2_bind_param($histStmt, 3, 'custNo',  DB2_PARAM_IN);
    db2_bind_param($histStmt, 4, 'segCode', DB2_PARAM_IN);
    if (!db2_execute($histStmt)) {
        throw new Exception('CLARIO003S execute error: ' . db2_stmt_error() .
            ' - ' . db2_stmt_errormsg());
    }
    return true;
}

/* =========================================================================
 * ====================================================================== */

// bail if there is another copy of this job already running
$lockFile = fopen(__FILE__ . '.lock', 'w');
if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
    clarioLog('Script is already running. Exiting to prevent overlap.');
    exit; 
}

// connect, authenticate, pull each file into the local directory.
clarioLog('ClarioSFTP_pull Begin');
$pulledCount = 0;

try {
    // Connect + authenticate
    $conn = ssh2_connect($hostname, $port);
    if (!$conn) {
        throw new Exception('Connection failed!');
    }
    if (!ssh2_auth_pubkey_file($conn, $username, $ssh_pubkey, $ssh_key)) {
        throw new Exception('Authentication failed!');
    }
    clarioLog('Authentication successful');

    $sftp_conn = ssh2_sftp($conn);
    if (!$sftp_conn) {
        throw new Exception('Could not initialize SFTP session.');
    }

    // Find the newest customer_segments_YYYYMMDD.txt in /fromClario
    $remoteDirUri = 'ssh2.sftp://' . intval($sftp_conn) . '/' . CLARIO_REMOTE_DIR;
    $dh = @opendir($remoteDirUri);
    if (!$dh) {
        throw new Exception('Could not open remote directory: /' . CLARIO_REMOTE_DIR);
    }
    $fileToPull = array();
    while (($entry = readdir($dh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (strpos($entry, CLARIO_FILE_PREFIX) === 0 &&
            substr($entry, -strlen(CLARIO_FILE_EXT)) === CLARIO_FILE_EXT) {
            $fileToPull[] = $entry;
        }
    }
    closedir($dh);
    if (empty($fileToPull)) {
            throw new Exception('No ' . CLARIO_FILE_PREFIX . '*' . CLARIO_FILE_EXT . ' files in /' . CLARIO_REMOTE_DIR);
        }
    
    sort($fileToPull);              // date stamped names sort oldest -> newest
    $newest = end($fileToPull);     // only pull the most recent
    clarioLog('Newest file on server: ' . $newest);
    
    $fileToPull = array($newest);
    $remoteUri = $remoteDirUri . '/' . $newest;
    $localPath = CLARIO_LOCAL_DIR . '/' . $newest;
    if (file_exists($localPath)) {
        clarioLog('Already pulled: ' . $newest);
    } else {
        $tmpPath = $localPath . '.' . getmypid() . '.tmp';
 
        if (!copy($remoteUri, $tmpPath)) {
            throw new Exception('Could not pull ' . $newest);
        }
        if (!rename($tmpPath, $localPath)) {
            throw new Exception('Could not rename temp file for ' . $newest);
        }
        
        $pulledCount = 1;
        clarioLog('Pulled ' . $newest . ' (' . filesize($localPath) . ' bytes)');
    }
    
    echo "1";
    $db2 = getDB2PConn($user, $passWd); // batch/AJS

    // Read the file with PhpSpreadsheet as a tab-delimited CSV. 
    if (class_exists('\PhpOffice\PhpSpreadsheet\Cell\StringValueBinder')) {
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(
            new \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder()
        );
    }
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
    $reader->setDelimiter("\t");
    $reader->setSheetIndex(0);
    $spreadsheet = $reader->load($localPath);
    $completed_array = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    echo "2";

    // derive the From/To week once from the rpt_date (saturday)
    $rptDate = isset($completed_array[1][0]) ? trim((string) $completed_array[1][0]) : '';
    list($fromDt, $toDt) = weekRangeFromRptDate($rptDate);
    if ($fromDt === null || $toDt === null) {
        throw new Exception('Could not determine report date (rpt_date = "' . $rptDate . '")');
    }
    clarioLog('Week from rpt_date ' . $rptDate . ': From ' . $fromDt . ' To ' . $toDt);
    echo "3";

    // Iterate the array (row 0 is the header), store fields in variables, and pass them to the stored-procedure writer.
    $inserted = 0;
    $histInserted = 0;
    $errors   = 0;
    $rowCount = count($completed_array);
    for ($i = 1; $i < $rowCount; $i++) {
        $row = $completed_array[$i];
        if (!is_array($row)) {
            continue;
        }
        $custNo  = trim((string) (isset($row[1]) ? $row[1] : ''));
        $segCode = trim((string) (isset($row[2]) ? $row[2] : ''));
        if ($custNo === '') {
            continue;
        }
        try {
            writeClarioRecord($db2, $fromDt, $toDt, $custNo, $segCode);
            $inserted++;
            // history table: only the FIRST time a customer#/segment combo is seen
            if (!clarioHistExists($db2, $custNo, $segCode)) {
                writeClarioHistRecord($db2, $fromDt, $toDt, $custNo, $segCode);
                $histInserted++;
            }
        } catch (Exception $rowEx) {
            $errors++;
            clarioLog('Row ' . $i . ' insert failed: ' . $rowEx->getMessage());
        }
    }
    clarioLog('Loaded ' . $inserted . ' rows into ' . 'LSCDEVLIBP.CLRCUSSEGT' . ' (' . $errors . ' errors)');
    clarioLog('Inserted ' . $histInserted . ' new records into ' . 'LSCDEVLIBP.CLRHSTSEGT' . ' (' . $errors . ' errors)');
} catch (Exception $e) {
    clarioLog('Exception: ' . $e->getMessage());
}
echo "4";

// log pull request and close lock file
clarioLog("ClarioSFTP_pull End  (pulled={$pulledCount})\n");
flock($lockFile, LOCK_UN);
fclose($lockFile);
?>