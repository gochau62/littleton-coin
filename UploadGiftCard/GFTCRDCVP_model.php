<?php
/*    ***************************************************  -->
<!--  * Program Name - GFTCRDCVP_model.php              *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/29/2025                         *  -->
<!--  ***************************************************   */

// PROGRAM NAME: GFTCRD001.PROC
// - Insert record in GFTCRDCVP file
function insertRecord($conn, $cvbtch, $cvcard, $cvcvv, $cvsku) {
    $sql = "CALL PLAYGROUND.GFTCRD001(?, ?, ?, ?)";

    // Prepare the statement
    $stmt = db2_prepare($conn, $sql)
        or die ("insertGiftCardRecord prepare error: " . db2_stmt_error() . "<br/>" .
                "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    // Bind parameters:
    db2_bind_param($stmt, 1, "cvbtch", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "cvcard", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "cvcvv", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "cvsku", DB2_PARAM_IN);

    // Execute the statement
    if (!$result = db2_execute($stmt)) {
        die("insertRecord execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }
    return $result;
}

// PROGRAM NAME: GFTCRD002.PROC
// - Return existing records that match a given card number
function checkDuplicateRecord($conn, $cvcard) {
    $sql = "CALL PLAYGROUND.GFTCRD002(?)";

    // Prepare the statement
    $stmt = db2_prepare($conn, $sql)
        or die ("checkDuplicateRecord prepare error: " . db2_stmt_error() . "<br/>" .
                "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    // Bind parameters
    db2_bind_param($stmt, 1, "cvcard", DB2_PARAM_IN);

    $result = array();
    // Execute and fetch
    if (db2_execute($stmt)) {
        while ($row = db2_fetch_assoc($stmt)) {
            $result[] = $row;
        }
    } else { 
        die("checkDuplicateRecord execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: GFTCRD003.PROC
// - Update Record in GFTCRDCVP file
function updateRecord($conn, $cvbtch, $cvcard, $cvcvv, $cvsku) {
    $sql = "CALL PLAYGROUND.GFTCRD003(?, ?, ?, ?)";

    // Prepare the statement
    $stmt = db2_prepare($conn, $sql)
        or die ("insertGiftCardRecord prepare error: " . db2_stmt_error() . "<br/>" .
                "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    // Bind parameters:
    db2_bind_param($stmt, 1, "cvbtch", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "cvcard", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "cvcvv", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "cvsku", DB2_PARAM_IN);

    // Execute the statement
    if (!$result = db2_execute($stmt)) {
        die("insertRecord execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }
    return $result;
}
?>
