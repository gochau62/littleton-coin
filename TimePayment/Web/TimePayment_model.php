<?php
/*    ***************************************************  -->
<!--  * Program Name - TimePayment_model.php            *  -->
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

$GLOBALS['tpyErr'] = '';

// the file widths the keys are written against: the item master key is CHAR(10), the source code (SRCCOD) is CHAR(6) on OEPSRCE, and the plan is a
// two digit code like '01' or '02'
define('TPY_ITEM_LEN', 10);
define('TPY_SRC_LEN', 6);
define('TPY_PLAN_LEN', 2);

// activity log path in the LCCOnline_logs folder beside the PHP, which is writable by the web profile while the docroot itself is not
define('TPY_ACT_LOG', __DIR__ . '/LCCOnline_logs/timepayment_activity.log');


// append one line to the activity log, with the write suppressed so a bad one never takes the app down, and if it still fails the reason and the line
// fall to php.log so nothing is lost
function tpyActLog($user, $action, $detail = '') {
    $line = date('Y-m-d H:i:s') . ' ' .
            ($user !== '' ? $user : 'unknown') . ' ' .
            ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' .
            $action . ($detail !== '' ? ' ' . $detail : '');
    if (@file_put_contents(TPY_ACT_LOG, $line . PHP_EOL, FILE_APPEND) === false) {
        error_log('timepayment_activity.log write failed (' .
                  (error_get_last()['message'] ?? 'unknown reason') .
                  ') - activity: ' . $line);
    }
}


// record the real Db2 error for the caller and the log, then return false so callers can bail
function tpyFail($where) {
    $GLOBALS['tpyErr'] = $where . ': ' . db2_stmt_error() . ' ' . db2_stmt_errormsg();
    error_log('TimePayment ' . $GLOBALS['tpyErr']);
    return false;
}


// shared runner for every proc that returns a result set: prepare, bind each parameter in order, execute, and collect every row as an associative array
function tpyFetchAll($conn, $sql, $params = array()) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return tpyFail("prepare $sql"); }

    foreach ($params as $i => $p) {
        $GLOBALS['tpyP' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'tpyP' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return tpyFail("execute $sql"); }

    $result = array();
    while ($row = db2_fetch_assoc($stmt)) {
        $result[] = $row;
    }
    return $result;
}


// shared runner for the procs that only write: bind in order and execute
function tpyExec($conn, $sql, $params, $where) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return tpyFail("prepare $where"); }

    foreach ($params as $i => $p) {
        $GLOBALS['tpyW' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'tpyW' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return tpyFail("execute $where"); }
    return true;
}


// the keys are compared against fixed CHAR fields, so they are upper cased and trimmed here and nowhere else
function tpyCleanItem($item) {
    return strtoupper(substr(trim((string)$item), 0, TPY_ITEM_LEN));
}


function tpyCleanSrc($src) {
    return strtoupper(substr(trim((string)$src), 0, TPY_SRC_LEN));
}


// the plan file carries two digit codes, so a bare '2' typed in the spreadsheet becomes '02' before it is checked
function tpyCleanPlan($plan) {
    $plan = strtoupper(substr(trim((string)$plan), 0, TPY_PLAN_LEN));
    if (preg_match('/^\d$/', $plan)) { $plan = '0' . $plan; }
    return $plan;
}


// turn whatever the spreadsheet cell holds into the YYYYMMDD number TPITEMSP carries, 0 when it is not a real date. Excel hands date cells back as
// serial numbers, and Marketing may also type slashes or hyphens, so all of those land here
function tpyNormDate($v) {
    if ($v === null) { return 0; }

    if (is_numeric($v) && !is_string($v)) {
        $n = floatval($v);
        // an eight digit number is already YYYYMMDD; anything smaller is an Excel serial date, which runs out at 2958465 (9999-12-31), so the two
        // ranges cannot collide
        if ($n >= 19000101 && $n <= 29991231 && $n == floor($n)) {
            $s = strval(intval($n));
            return checkdate(intval(substr($s, 4, 2)), intval(substr($s, 6, 2)),
                             intval(substr($s, 0, 4))) ? intval($n) : 0;
        }
        if ($n > 0 && $n < 2958466) {
            // serial day 25569 is 1970-01-01, the unix epoch
            return intval(gmdate('Ymd', intval(round(($n - 25569) * 86400))));
        }
        return 0;
    }

    $s = trim((string)$v);
    if ($s === '') { return 0; }

    // YYYYMMDD straight through
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $s, $m)) {
        return checkdate(intval($m[2]), intval($m[3]), intval($m[1])) ? intval($s) : 0;
    }
    // MM/DD/YYYY or MM-DD-YYYY, with a two digit year read as 20xx
    if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2}|\d{4})$#', $s, $m)) {
        $yr = intval($m[3]);
        if ($yr < 100) { $yr += 2000; }
        return checkdate(intval($m[1]), intval($m[2]), $yr)
            ? $yr * 10000 + intval($m[1]) * 100 + intval($m[2]) : 0;
    }
    // YYYY/MM/DD or YYYY-MM-DD
    if (preg_match('#^(\d{4})[/-](\d{1,2})[/-](\d{1,2})$#', $s, $m)) {
        return checkdate(intval($m[2]), intval($m[3]), intval($m[1]))
            ? intval($m[1]) * 10000 + intval($m[2]) * 100 + intval($m[3]) : 0;
    }
    return 0;
}


// PROGRAM NAME TIMPAY001S type ITEM: one exact item from the item master. No row back means the item is not on file
function tpyGetItem($conn, $item) {
    $rows = tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                        array('ITEM', tpyCleanItem($item), ''));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}


// PROGRAM NAME TIMPAY001S type SRC: one exact source code from OEPSRCE. No row back means the source code is not valid
function tpyGetSource($conn, $src) {
    $rows = tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                        array('SRC', tpyCleanSrc($src), ''));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}


// PROGRAM NAME TIMPAY001S type PLAN: one exact plan from TPPLANSP, for the rows where Marketing filled in the plan column. No row back means the plan
// is not valid, which per Dennis is an error condition rather than something to guess at
function tpyGetPlan($conn, $plan) {
    $rows = tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                        array('PLAN', tpyCleanPlan($plan), ''));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}


// PROGRAM NAME TIMPAY001S type TIER: the plan whose TPTIERSP price range covers the given item price. Null back means the price falls outside every
// range (under $99.00), and per Josh that row is skipped onto the exception report instead of defaulted into a plan
function tpyTierPlan($conn, $price) {
    $rows = tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                        array('TIER', number_format(floatval($price), 2, '.', ''), ''));
    if ($rows === false) { return false; }
    return $rows ? trim($rows[0]['TPPLAN']) : null;
}


// PROGRAM NAME TIMPAY002S: the item price retrieval module OE0337R, called the way OP0800R calls it - the ten parameters mirror the program's Dcl-PR
// (LSCARCLIB/QRPGLESRC mbr OE0337R), the item number and source code are the only non-blank/non-zero values passed, and the price comes back in WKPRIC
function tpyItemPrice($conn, $item, $srcCod) {
    $sql = "CALL TIMPAY002S(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $wkItem = tpyCleanItem($item);
    $wkSelt = '';
    $wkSrcd = tpyCleanSrc($srcCod);
    $wkPric = 0;
    $wkCprc = 0;
    $wkDisc = 0;
    $wkAdjc = '';
    $wkShrv = 0;
    $wkCust = 0;
    $wkMtlv = 0;

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return tpyFail("prepare TIMPAY002S"); }

    db2_bind_param($stmt, 1, "wkItem", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 2, "wkSelt", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 3, "wkSrcd", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 4, "wkPric", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 5, "wkCprc", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 6, "wkDisc", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 7, "wkAdjc", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 8, "wkShrv", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 9, "wkCust", DB2_PARAM_INOUT);
    db2_bind_param($stmt, 10, "wkMtlv", DB2_PARAM_INOUT);

    if (!db2_execute($stmt)) { return tpyFail("execute TIMPAY002S"); }
    return floatval($wkPric);
}


// PROGRAM NAME TIMPAY001S type ONE: the TPITEMSP record for one item and source code, so the upload knows whether it is about to add or update
function tpyGetRecord($conn, $item, $src) {
    $rows = tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                        array('ONE', tpyCleanItem($item), tpyCleanSrc($src)));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}


// PROGRAM NAME TIMPAY001S type LIST: the records on file for the review grid, narrowed by the search box; anything that expired five or more years
// ago stays off the grid entirely
function tpyList($conn, $q = '') {
    return tpyFetchAll($conn, "CALL TIMPAY001S(?, ?, ?)",
                       array('LIST', substr(trim((string)$q), 0, 20), ''));
}


// commit the unit of work - on a journaled table (the SQL-created dev copy is one) an uncommitted write rolls back when the script exits, so each
// written row is committed on the spot; then a failure further down the spreadsheet really does leave the rows above it on file
function tpyCommit($conn) {
    if (!function_exists('db2_commit')) { return true; }
    if (!db2_commit($conn)) { return tpyFail('commit'); }
    return true;
}


// PROGRAM NAME TIMPAY003S: write one time payment record, updating it when the item/source key is already on TPITEMSP and inserting it when it is not
function tpyUpsert($conn, $item, $src, $plan, $expDate) {
    if (!tpyExec($conn, "CALL TIMPAY003S(?, ?, ?, ?)",
                 array(tpyCleanItem($item), tpyCleanSrc($src),
                       tpyCleanPlan($plan), intval($expDate)),
                 'TIMPAY003S')) {
        return false;
    }
    return tpyCommit($conn);
}
?>
