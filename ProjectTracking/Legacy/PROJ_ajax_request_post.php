<?php 

require_once 'Utils/default_values.php';
session_name(SESSION_NAME);
session_start();

if (isset($_SESSION['username']) and isset($_SESSION['password'])) {
    $user = $_SESSION['username'];
    $password = $_SESSION['password'];
}

require_once "Utils/common_functions.php";
require_once "PROJ_model.php";
require_once "LCDEPTP_model.php";
require_once "PROJ_pgmrs_dsp.php";

if (isset($_POST['action'])) {
    switch ($_POST['action']) {

        // the programmers panel; every change hands the panel back
        case 'pgmrPanel':
        case 'pgmrSave':
        case 'pgmrRemove':
        case 'pgmrCommentAdd':
        case 'pgmrCommentRemove':
            $conn = getDB2PConn($user, $password);
            $screenUser = $_SESSION['altUserNm'] ?? $user;
            $projNum = intval($_POST['projNum'] ?? 0);
            $projRec = getRecordPRPROJP($conn, $projNum);
            if (!$projRec || intval($projRec['PR#'] ?? 0) <= 0) {
                echo "ERROR:Project not found.";
                break;
            }
            $auth    = getRecPRAUTHP($conn, $screenUser);
            $isPM    = (trim(strval($auth['PAPRJMNGR'] ?? '')) == 'Y');
            $canEdit = pgmrCanEdit($conn, $screenUser);
            $what    = $_POST['action'];
            if ($what != 'pgmrPanel' && !$canEdit) {
                echo "ERROR:Not authorized to change programmers.";
                break;
            }
            $pgmr = strtoupper(trim($_POST['pgmr'] ?? ''));
            $ok   = true;
            $why  = 'Problem updating the database.';
            switch ($what) {
                case 'pgmrSave':
                    // the primary programmer is the project's own field
                    if ($pgmr == '' || $pgmr == strtoupper(trim($projRec['PRPGMR']))) {
                        $ok = false; $why = 'Pick a programmer other than the primary.';
                        break;
                    }
                    $had = false;
                    foreach (getProjPgmrs($conn, $projNum) ?: array() as $r) {
                        if (strtoupper(trim($r['PGPGMR'])) == $pgmr) { $had = true; }
                    }
                    $ok = saveProjPgmr($conn, $projNum, $pgmr,
                                       strtoupper(trim($_POST['sts'] ?? '')),
                                       pgmrDecDate($_POST['date'] ?? ''), $screenUser);
                    if ($ok) {
                        pgmrLogChange($conn, $projNum, $screenUser,
                                      "Programmer " . $pgmr . ($had ? " status" : " added"));
                    }
                    break;
                case 'pgmrRemove':
                    $ok = ($pgmr != '') && removeProjPgmr($conn, $projNum, $pgmr);
                    if ($ok) {
                        pgmrLogChange($conn, $projNum, $screenUser, "Programmer " . $pgmr . " removed");
                    }
                    break;
                case 'pgmrCommentAdd':
                    $text = trim(strip_tags(strval($_POST['text'] ?? '')));
                    if ($pgmr == '' || $text == '') { $ok = false; $why = 'Nothing to add.'; break; }
                    $ok = addProjPgmrComment($conn, $projNum, $pgmr, $screenUser, $text);
                    if ($ok) {
                        pgmrLogChange($conn, $projNum, $screenUser, "Comment for " . $pgmr . " added");
                    }
                    break;
                case 'pgmrCommentRemove':
                    // the writer or a project manager, nobody else
                    $seq = intval($_POST['seq'] ?? 0);
                    $mine = false;
                    foreach (getProjPgmrComments($conn, $projNum) ?: array() as $c) {
                        if (intval($c['CMSEQ']) == $seq
                            && strtoupper(trim($c['CMUSER'])) == strtoupper(trim($screenUser))) {
                            $mine = true;
                        }
                    }
                    if (!$isPM && !$mine) { $ok = false; $why = 'Only the writer can remove it.'; break; }
                    $ok = removeProjPgmrComment($conn, $projNum, $seq);
                    break;
            }
            if (!$ok) { echo "ERROR:" . $why; break; }
            echo renderProjPgmrPanel($conn, $projRec, $canEdit, $screenUser, $isPM);
            break;

        case 'addNewProjectAction':
            $conn = getDB2PConn($user, $password);
            $projNum = $_POST['projNum'];
            $action = $_POST['projAction'];
            $active = $_POST['active'];
            $timestamp = $_POST['timestamp'];
            //$dateDue = $_POST['date'];
            $user = $_POST['user'];
            //putLCCOnlineLogRec("\nKJR XYZ TEST");
            $success = addNewProjectAction($conn, $projNum, $action, $active, $timestamp, $user);
            $message = $success;
            echo $message;
            break;
             
        case 'getSequenceNumberOfProjectAction':
            $conn = getDB2PConn($user, $password);
            $projNum = $_POST['projNum'];
            $action = $_POST['projAction'];
            $active = $_POST['active'];
            $timestamp = $_POST['timestamp'];
            $user = $_POST['user'];
            $result = getSequenceNumberOfProjectAction($conn, $projNum, $action, $active, $timestamp, $user);
            echo json_encode($result);
            break;

        case 'getProjectActionPlan':
            $conn = getDB2PConn($user, $password);
            $projNum = $_POST['projNum'];
            //$year = $_POST['year'];
            $result = getProjectActionPlan($conn, $projNum);
            echo json_encode($result);
            break;
            
        case 'updateProjectActionState':
            $conn = getDB2PConn($user, $password);
            $projNum = $_POST['projNum'];
            //$year = $_POST['year'];
            $seqNum = $_POST['seqNum'];
            $success = updateProjectActionState($conn, $projNum, $seqNum);
            $message = $success;
            echo $message;
            break;
             
    }
}

?>

