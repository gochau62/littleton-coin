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

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
         
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

