<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_model.php           *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

$GLOBALS['rqsErr'] = '';


// TESTING ONLY - answers the sign on lookup as this profile instead of whoever is really signed on, so the screen can be tried out as somebody else. Set it back to '' before this goes anywhere near production; while it holds anything the screen carries a red banner saying so.
define('RQS_TEST_AS', 'CPEREZ');


// activity log path: the LCCOnline_logs folder beside the PHP is writable by the web profile while the docroot itself is not, so this is where the file actually appears, and keeping it relative to __DIR__ means it stays correct on every instance
define('RQS_ACT_LOG', __DIR__ . '/LCCOnline_logs/requisition_activity.log');


// append one line to the activity log, with the write suppressed so a bad one never takes the app down, and if it still fails (usually the web profile lacking authority to the folder) the reason and the line fall to php.log so nothing is lost
function rqsActLog($user, $action, $detail = '') {
    $line = date('Y-m-d H:i:s') . ' ' .
            ($user !== '' ? $user : 'unknown') . ' ' .
            ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' .
            $action . ($detail !== '' ? ' ' . $detail : '');
    if (@file_put_contents(RQS_ACT_LOG, $line . PHP_EOL, FILE_APPEND) === false) {
        error_log('requisition_activity.log write failed (' .
                  (error_get_last()['message'] ?? 'unknown reason') .
                  ') - activity: ' . $line);
    }
}


// record the real Db2 error for the caller and the log, then return false so callers can bail
function rqsFail($where) {
    $GLOBALS['rqsErr'] = $where . ': ' . db2_stmt_error() . ' ' . db2_stmt_errormsg();
    error_log('Requisitions ' . $GLOBALS['rqsErr']);
    return false;
}


// shared runner for every proc that returns a result set: prepare, bind each parameter in order, execute, and collect every row as an associative array
function rqsFetchAll($conn, $sql, $params = array()) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare $sql"); }

    foreach ($params as $i => $p) {
        $GLOBALS['rqsP' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'rqsP' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return rqsFail("execute $sql"); }

    $result = array();
    while ($row = db2_fetch_assoc($stmt)) {
        $result[] = $row;
    }
    return $result;
}


// PROGRAM NAME REQSTN003S: grid rows where show O gives open lines (the default), R gives returned lines, A gives all, the search narrows by req number name item or badge (blank means everything), and returned or all come back capped to the 500 most recent
function rqsGetOpen($conn, $show = 'O', $search = '') {
    $show = strtoupper(substr(trim($show), 0, 1));
    if (!in_array($show, array('O', 'R', 'A'))) { $show = 'O'; }
    return rqsFetchAll($conn, "CALL REQSTN003S(?, ?)", array($show, substr(trim($search), 0, 50)));
}


// PROGRAM NAME REQSTN004S: one requisition by number, its header plus every line including already returned ones, for the view window
function rqsGet($conn, $reqNum) {
    return rqsFetchAll($conn, "CALL REQSTN004S(?)", array($reqNum));
}


// PROGRAM NAME REQSTN008S: the monthly report rows for a given yyyymm accounting period
function rqsMonthly($conn, $yyyymm) {
    return rqsFetchAll($conn, "CALL REQSTN008S(?)", array($yyyymm));
}


// PROGRAM NAME REQSTN007S: code lists by type, where the BADGE type reads live active employees rather than a stored list
function rqsLookup($conn, $type) {
    $allowed = array("NAMES", "AREACODE", "AREATYPE", "AUTHBY", "BADGE");
    if (!in_array($type, $allowed)) {
        $GLOBALS['rqsErr'] = "rqsLookup: list type not allowed";
        return false;
    }
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array($type, ""));
}


// PROGRAM NAME REQSTN007S type ITEM: the entry form autofill that fills description, coin date, cost and retail from one exact item number
function rqsItemLookup($conn, $item) {
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array("ITEM", $item));
}


// PROGRAM NAME REQSTN007S type ITEMSRCH: the typeahead item search that lists matching item master rows for the entry form dropdown
function rqsItemSearch($conn, $prefix) {
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array("ITEMSRCH", $prefix));
}


// PROGRAM NAME REQSTN001S: insert the header and return the new req number, or false on error
function rqsInsertHeader($conn, $reqName, $areaCode, $areaType,
                         $rush, $authBy, $badge, $comments) {
    $sql = "CALL REQSTN001S(?, ?, ?, ?, ?, ?, ?, ?)";
    $newReq = 0;

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN001S"); }

    db2_bind_param($stmt, 1, "reqName", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "areaCode", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "areaType", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "rush", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "authBy", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "badge", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 8, "newReq", DB2_PARAM_OUT);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN001S"); }
    return $newReq;
}


// PROGRAM NAME REQSTN002S: insert one detail line onto an existing requisition header
function rqsInsertLine($conn, $reqNum, $lineNum, $item, $loc, $coinDate,
                       $desc, $qty, $cost, $retail, $addCost, $badge, $skuTo) {
    $sql = "CALL REQSTN002S(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN002S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "lineNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "item", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "loc", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "coinDate", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "desc", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "qty", DB2_PARAM_IN);
    db2_bind_param($stmt, 8, "cost", DB2_PARAM_IN);
    db2_bind_param($stmt, 9, "retail", DB2_PARAM_IN);
    db2_bind_param($stmt, 10, "addCost", DB2_PARAM_IN);
    db2_bind_param($stmt, 11, "badge", DB2_PARAM_IN);
    db2_bind_param($stmt, 12, "skuTo", DB2_PARAM_IN);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN002S"); }
    return true;
}


// PROGRAM NAME REQSTN009S: delete a whole requisition, used only to back out a partial insert after a line failed
function rqsDeleteRequisition($conn, $reqNum) {
    $sql = "CALL REQSTN009S(?)";
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN009S"); }
    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN009S"); }
    return true;
}


// PROGRAM NAME REQSTN007S type WHOAMI: the badge and full name of the employee behind a sign on name, returning an empty list when the sign on name does not match anyone
function rqsWhoAmI($conn, $user) {
    if (RQS_TEST_AS !== '') { $user = RQS_TEST_AS; }
    $rows = rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array("WHOAMI", substr(trim($user), 0, 50)));
    if ($rows === false || !count($rows)) { return null; }
    return array("badge" => trim($rows[0]['CDCODE']),
                 "name"  => rqsPreferredName($conn, trim($rows[0]['CDDESC'])));
}


// the employee file holds the name on the payroll while the requestor list holds the name people are known by, so Christopher Perez on the one is Topher Perez on the other
// an exact match wins, otherwise the one entry sharing a last name is taken, and failing both the payroll name is kept so nobody is left without a name at all
function rqsPreferredName($conn, $employeeName) {
    $employeeName = trim($employeeName);
    if ($employeeName === '') { return $employeeName; }

    $names = rqsLookup($conn, "NAMES");
    if ($names === false || !count($names)) { return $employeeName; }

    $parts = preg_split('/\s+/', $employeeName);
    $last  = strtolower(end($parts));
    $sameLast = array();
    foreach ($names as $row) {
        $listed = trim($row['CDCODE']);
        if (strcasecmp($listed, $employeeName) === 0) { return $listed; }
        $lp = preg_split('/\s+/', $listed);
        if (count($lp) > 1 && strtolower(end($lp)) === $last) { $sameLast[] = $listed; }
    }
    return (count($sameLast) === 1) ? $sameLast[0] : $employeeName;
}



// PROGRAM NAME REQSTN005S: update the header requestor name, area code, area type, authorized by, comments and badge, where a NULL argument leaves that column unchanged
function rqsUpdateReq($conn, $reqNum, $authBy, $comments, $badge = null,
                      $reqName = null, $areaCode = null, $areaType = null) {
    $sql = "CALL REQSTN005S(?, ?, ?, ?, ?, ?, ?)";

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN005S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "authBy", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "badge", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "reqName", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "areaCode", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "areaType", DB2_PARAM_IN);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN005S"); }
    return true;
}



// PROGRAM NAME REQSTN006S: mark or unmark a single line returned, where a dateRet of 0 stamps today
function rqsSetReturned($conn, $reqNum, $lineNum, $flag, $dateRet = 0) {
    $sql = "CALL REQSTN006S(?, ?, ?, ?)";

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN006S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "lineNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "flag", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "dateRet", DB2_PARAM_IN);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN006S"); }
    return true;
}
?>
