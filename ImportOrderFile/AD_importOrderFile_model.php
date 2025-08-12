<?php
/*    ***************************************************  -->
<!--  * Program Name - AD_importOrderFile_model.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/09/2024                         *  -->
<!--  ***************************************************   */


// PROGRAM NAME: PLYGRND10S
// - Check for existence of record in TESTTABP file
function checkRecordExists($conn, $tsuser, $tsseqno) {
    $sql = "CALL PLYGRND10S(?, ?)";

    // Prepare the statement
    $stmt = db2_prepare($conn, $sql)
    or die ("Get checkRecordExists prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    // Bind parameters
    db2_bind_param($stmt, 1, "tsuser", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "tsseqno", DB2_PARAM_IN);

    // Execute the statement
    if (db2_execute($stmt)) {
        while ($row =db2_fetch_assoc($stmt)){
            $result[] = $row;
        }
    } else { 
        die("checkRecordExists execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

// PROGRAM NAME: PLYGRND11S
// - Update record in TESTTABP file
function updateRecord($conn, $tsuser, $tsseqno, $tscomment, $tsactive) {
    // update the record
    $sql = "CALL PLYGRND11S(?, ?, ?, ?)";
    $stmt = db2_prepare($conn, $sql)
    or die ("updateRecord prepare error: " . db2_stmt_error() . "<br/>" .
        "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "tsuser", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "tsseqno", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "tscomment", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "tsactive", DB2_PARAM_IN);

    // execute the statement
    if (!$result = db2_execute($stmt)) {
        die("updateRecord execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}


// PROGRAM NAME: PLYGRND12S
// - Insert record in TESTTABP file
function insertRecord($conn, $tsuser, $tsseqno, $tscomment, $tsactive) {
    // prepare parameters and bind
    $sql = "CALL PLYGRND12S(?, ?, ?, ?)";
    $stmt = db2_prepare($conn, $sql)
    or die ("insertRecord prepare error: " . db2_stmt_error() . "<br/>" .
    "Error Msg: " . db2_stmt_errormsg() . "<br/>");

    db2_bind_param($stmt, 1, "tsuser", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "tsseqno", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "tscomment", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "tsactive", DB2_PARAM_IN);

    // execute the statement
    if (!$result = db2_execute($stmt)) {
        die("insertRecord execute error: " . db2_stmt_error() . "<br/>" .
            "Error Msg: " . db2_stmt_errormsg() . "<br/>");
    }

    return $result;
}

?>
