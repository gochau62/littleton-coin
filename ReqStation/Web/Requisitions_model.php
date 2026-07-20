<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_model.php                 *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station model. All      *  -->
<!--  *   database access goes through the REQSTNnnnS   *  -->
<!--  *   stored procedures - no inline SQL, no string  *  -->
<!--  *   concatenation (replaces the injection-prone   *  -->
<!--  *   legacy mysqli queries).                       *  -->
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


// PROGRAM NAME: REQSTN003S
// - List open requisitions for the main grid (returned = 'N')
function rqsGetOpen($conn) {
    $result = array();
    $sql = "CALL REQSTN003S()";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsGetOpen prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)) {
            $result[] = $row;
        }
    } else {
        die("rqsGetOpen execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: REQSTN004S
// - Get one requisition: header + all detail lines
function rqsGet($conn, $reqNum) {
    $result = array();
    $sql = "CALL REQSTN004S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsGet prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);

    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)) {
            $result[] = $row;
        }
    } else {
        die("rqsGet execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: REQSTN001S
// - Insert requisition header, returns the new requisition number
function rqsInsertHeader($conn, $reqName, $areaCode, $areaType,
                                 $rush, $badge, $comments) {
    $sql = "CALL REQSTN001S(?, ?, ?, ?, ?, ?, ?)";
    $newReq = 0;

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsInsertHeader prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "reqName", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "areaCode", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "areaType", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "rush", DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "badge", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "comments", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "newReq", DB2_PARAM_OUT);

    if (!db2_execute($stmt)) {
        die("rqsInsertHeader execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $newReq;
}

// PROGRAM NAME: REQSTN002S
// - Insert one requisition detail line
function rqsInsertLine($conn, $reqNum, $lineNum, $item, $loc, $coinDate,
                               $desc, $qty, $cost, $retail, $addCost, $badge, $skuTo) {
    $sql = "CALL REQSTN002S(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsInsertLine prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

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

    if (!$result = db2_execute($stmt)) {
        die("rqsInsertLine execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: REQSTN005S
// - Authorize a requisition
function rqsAuthorize($conn, $reqNum, $authBy, $comments) {
    $sql = "CALL REQSTN005S(?, ?, ?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsAuthorize prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "authBy", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "comments", DB2_PARAM_IN);

    if (!$result = db2_execute($stmt)) {
        die("rqsAuthorize execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: REQSTN006S
// - Mark or unmark a requisition line returned
function rqsSetReturned($conn, $reqNum, $lineNum, $flag) {
    $sql = "CALL REQSTN006S(?, ?, ?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsSetReturned prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "reqNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "lineNum", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "flag", DB2_PARAM_IN);

    if (!$result = db2_execute($stmt)) {
        die("rqsSetReturned execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: REQSTN011S
// - Monthly Requisitioned Product summary (yyyymm in, lines w/extended $)
function rqsMonthly($conn, $yyyymm) {
    $result = array();
    $sql = "CALL REQSTN011S(?)";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsMonthly prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "yyyymm", DB2_PARAM_IN);

    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)) {
            $result[] = $row;
        }
    } else {
        die("rqsMonthly execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAMES: REQSTN007S / 008S / 009S / 010S
// - Combo lookups: requisitioner names, area codes, area types, authorizers
function rqsLookup($conn, $proc) {
    $allowed = array("REQSTN007S", "REQSTN008S", "REQSTN009S", "REQSTN010S");
    if (!in_array($proc, $allowed)) {
        die("rqsLookup error: procedure not allowed");
    }

    $result = array();
    $sql = "CALL " . $proc . "()";

    $stmt = db2_prepare($conn, $sql)
    or die ("rqsLookup prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)) {
            $result[] = $row;
        }
    } else {
        die("rqsLookup execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}
?>
