<?php
/*    ***************************************************  -->
<!--  * Program Name - getInsert.php                     *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Legacy URL shim. This address is    *  -->
<!--  *   saved on the workfloor PCs and by the         *  -->
<!--  *   inventory handlers (and the Access stations   *  -->
<!--  *   open it), so the filename stays alive and     *  -->
<!--  *   forwards to the new Requisition Station app.  *  -->
<!--  *   The destination is set once in                *  -->
<!--  *   ReqAppTarget.php - do not edit it here.       *  -->
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

$REQAPP = 'Requisitions_ctl.php';   // fallback: app in this same folder
if (file_exists('ReqAppTarget.php')) { include 'ReqAppTarget.php'; }

$id = 0;
if (isset($_GET['id']))      { $id = intval($_GET['id']); }
if (isset($_REQUEST['req'])) { $id = intval($_REQUEST['req']); }

if ($id > 0) {
    header('Location: ' . $REQAPP . '?id=' . $id, true, 302);
} else {
    header('Location: ' . $REQAPP . '?action=add', true, 302);
}
exit;
?>
