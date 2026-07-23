<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_model.php           *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station model. All      *  -->
<!--  *   database access goes through the REQSTNnnnS   *  -->
<!--  *   stored procedures - no inline SQL, no string  *  -->
<!--  *   concatenation. Failures never die() mid-JSON: *  -->
<!--  *   functions return false and the real Db2       *  -->
<!--  *   message is kept in $GLOBALS['rqsErr'] (and    *  -->
<!--  *   the PHP error log) for the ajax layer.        *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

$GLOBALS['rqsErr'] = '';

define('RQS_ACT_LOG', __DIR__ . '/requisition_activity.log');

// append one line to the station's activity file - same pattern as
// ClarioSFTP_pull.log. Covers what the old Access "activity" table did:
// who opened the station, inserted, updated, returned, backed out.
// Logging must never take the app down, so write failures are ignored.
function rqsActLog($user, $action, $detail = '') {
    @file_put_contents(
        RQS_ACT_LOG,
        date('Y-m-d H:i:s') . ' ' .
        ($user !== '' ? $user : 'unknown') . ' ' .
        ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' .
        $action . ($detail !== '' ? ' ' . $detail : '') . PHP_EOL,
        FILE_APPEND
    );
}

// record the real Db2 error for the caller and the log, return false
function rqsFail($where) {
    $GLOBALS['rqsErr'] = $where . ': ' . db2_stmt_error() . ' ' . db2_stmt_errormsg();
    error_log('Requisitions ' . $GLOBALS['rqsErr']);
    return false;
}

// shared result-set runner for procs that return rows
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

// PROGRAM NAME: REQSTN003S - open requisitions for the main grid
function rqsGetOpen($conn) {
    return rqsFetchAll($conn, "CALL REQSTN003S()");
}

// PROGRAM NAME: REQSTN004S - one requisition, header + all lines
function rqsGet($conn, $reqNum) {
    return rqsFetchAll($conn, "CALL REQSTN004S(?)", array($reqNum));
}

// PROGRAM NAME: REQSTN008S - monthly report rows
function rqsMonthly($conn, $yyyymm) {
    return rqsFetchAll($conn, "CALL REQSTN008S(?)", array($yyyymm));
}

// PROGRAM NAME: REQSTN007S - the one lookup proc: code lists by type
function rqsLookup($conn, $type) {
    $allowed = array("NAMES", "AREACODE", "AREATYPE", "AUTHBY");
    if (!in_array($type, $allowed)) {
        $GLOBALS['rqsErr'] = "rqsLookup: list type not allowed";
        return false;
    }
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array($type, ""));
}

// PROGRAM NAME: REQSTN007S type ITEM - entry-form autofill
function rqsItemLookup($conn, $item) {
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array("ITEM", $item));
}

// PROGRAM NAME: REQSTN007S type ITEMSRCH - type-ahead item search
function rqsItemSearch($conn, $prefix) {
    return rqsFetchAll($conn, "CALL REQSTN007S(?, ?)", array("ITEMSRCH", $prefix));
}

// PROGRAM NAME: REQSTN001S - insert header, returns new req# (false on error).
// $authBy is the pre-selected authorizer from the entry form ('' = None),
// same as the legacy form; the authorized flag itself stays 'N'.
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

// PROGRAM NAME: REQSTN002S - insert one detail line
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

// PROGRAM NAME: REQSTN009S - back out a partial requisition after a
// failed line insert, so a failed submit never leaves half a requisition
function rqsDeleteRequisition($conn, $reqNum) {
    $sql = "CALL REQSTN009S(?)";
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN009S"); }
    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN009S"); }
    return true;
}

// PROGRAM NAME: REQSTN005S - update header: authorized-by, comments
// and DataEntry badge. NULL leaves a column unchanged - the view
// window sends authBy+comments, the grid's badge box sends badge only.
// (The authorized flag derives from the authorized-by value.)
function rqsUpdateReq($conn, $reqNum, $authBy, $comments, $badge = null) {
    $sql = "CALL REQSTN005S(?, ?, ?, ?)";

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN005S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "authBy", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "badge", DB2_PARAM_IN);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN005S"); }
    return true;
}

// PROGRAM NAME: REQSTN006S - mark/unmark a line returned (idempotent).
// $dateRet is the user-entered return date (yyyymmdd); 0 = stamp today.
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
