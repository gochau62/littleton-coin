<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ctl.php                   *  -->
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
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
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
    // local library copies, same as the other LCC tools (SellbriteBulkLoader_ctl).
    // If this app deploys in a subfolder (e.g. /requisitions/), point these at
    // ../jQuery/ and ../swal/ or wherever the shared copies live on that instance.
    print '
    <script type="text/javascript" src="jQuery/jquery.js"></script>
    <script type="text/javascript" src="swal/sweetalert-dev.js"></script>
    <script type="text/javascript" src="swal/sweetalert.min.js"></script>
    <link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
    <script type="text/javascript">document.title = "Requisition Station";</script>';
    include("StartBlockBody.php");

    include("Requisitions_dsp.php");
    dspRequisitions($user);

} else {
    showNotAuthorized();
}

include("EndBlock.php");
?>
