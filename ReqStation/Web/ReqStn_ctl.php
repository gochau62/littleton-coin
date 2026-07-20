<?php
/*    ***************************************************  -->
<!--  * Program Name - ReqStn_ctl.php                   *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station controller.     *  -->
<!--  *   Web replacement for the Access frmMain grid   *  -->
<!--  *   and the legacy request.php entry page.        *  -->
<!--  *   Follows the InvPrt / AIS controller pattern:  *  -->
<!--  *   session sign-on, authority check, then the    *  -->
<!--  *   display include.                               *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************   */

session_name(SESSION_NAME);
session_start();

$user = $_SESSION['username'];
$password = $_SESSION['password'];

$conn = getDB2PConn();

// LCCONLINE authority check - same gate the other web apps use.
// Confirm the required authority level number with IT before promotion.
if (chkAutUsr($conn, $user, "LCCONLINE", 50)) {

    include("StartBlockHead.php");
    print '
    <script type="text/javascript" src="ReqStn.js"></script>';
    include("StartBlockBody.php");

    include("ReqStn_dsp.php");

} else {
    showNotAuthorized();
}

include("EndBlock.php");
?>
