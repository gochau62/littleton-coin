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

// PROGRAM NAME: REQSTN011S - monthly report rows
function rqsMonthly($conn, $yyyymm) {
    return rqsFetchAll($conn, "CALL REQSTN011S(?)", array($yyyymm));
}

// PROGRAM NAME: REQSTN012S - item autofill (most recent use of the item)
function rqsItemLookup($conn, $item) {
    return rqsFetchAll($conn, "CALL REQSTN012S(?)", array($item));
}

// PROGRAM NAMES: REQSTN007S / 008S / 009S / 010S - combo lookups
function rqsLookup($conn, $proc) {
    $allowed = array("REQSTN007S", "REQSTN008S", "REQSTN009S", "REQSTN010S");
    if (!in_array($proc, $allowed)) {
        $GLOBALS['rqsErr'] = "rqsLookup: procedure not allowed";
        return false;
    }
    return rqsFetchAll($conn, "CALL " . $proc . "()");
}

// PROGRAM NAME: REQSTN001S - insert header, returns new req# (false on error)
function rqsInsertHeader($conn, $reqName, $areaCode, $areaType,
                         $rush, $badge, $comments) {
    $sql = "CALL REQSTN001S(?, ?, ?, ?, ?, ?, ?)";
    $newReq = 0;

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN001S"); }

    db2_bind_param($stmt, 1, "reqName", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "areaCode", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "areaType", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "rush", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "badge", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "newReq", DB2_PARAM_OUT);

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

// PROGRAM NAME: REQSTN013S - back out a partial requisition after a
// failed line insert, so a failed submit never leaves half a requisition
function rqsDeleteRequisition($conn, $reqNum) {
    $sql = "CALL REQSTN013S(?)";
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN013S"); }
    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN013S"); }
    return true;
}

// PROGRAM NAME: REQSTN005S - authorize; returns 1 done, 0 already
// authorized by someone else, false on error
function rqsAuthorize($conn, $reqNum, $authBy, $comments) {
    $sql = "CALL REQSTN005S(?, ?, ?, ?)";
    $done = 0;

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN005S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "authBy", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "done", DB2_PARAM_OUT);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN005S"); }
    return intval($done);
}

// PROGRAM NAME: REQSTN006S - mark/unmark a line returned (idempotent)
function rqsSetReturned($conn, $reqNum, $lineNum, $flag) {
    $sql = "CALL REQSTN006S(?, ?, ?)";

    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN006S"); }

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "lineNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "flag", DB2_PARAM_IN);

    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN006S"); }
    return true;
}

// PROGRAM NAME: REQSTN014S - activity log; never blocks the real action
function rqsLogActivity($conn, $user, $action, $reqNum) {
    $sql = "CALL REQSTN014S(?, ?, ?)";
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return rqsFail("prepare REQSTN014S"); }
    db2_bind_param($stmt, 1, "user", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "action", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "reqNum", DB2_PARAM_IN);
    if (!db2_execute($stmt)) { return rqsFail("execute REQSTN014S"); }
    return true;
}
?>
