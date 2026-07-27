<?php
/*    ***************************************************  -->
<!--  * Program Name - getUpdate.php                    *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/24/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

// this was the authorize and comment save of the old view page and it wrote straight into the old database
// it is stopped here for the same reason as the old insert: a tab left open could still post to it after cutover and quietly change a record nobody is reading

define('RQS_NEW_APP', 'http://lcc1:8068/LCCOnline');

// carry the requisition number through when the old page sent one, so the new screen opens the same requisition
$req = 0;
if (isset($_REQUEST['req'])) { $req = intval($_REQUEST['req']); }
$target = ($req > 0)
    ? RQS_NEW_APP . '/Requisitions_ctl.php?id=' . $req
    : RQS_NEW_APP . '/Requisitions_ctl.php';

$safe = htmlspecialchars($target, ENT_QUOTES);
echo '<html><head><title>Requisition Material</title></head><body style="font-family:Arial,sans-serif;margin:40px;">';
echo '<h2 style="color:#c0392b;">This change was not saved</h2>';
echo '<p>The requisition system has moved, and the page you just submitted belongs to the old screen. ';
echo 'Nothing was changed, so please make the update again on the new screen.</p>';
echo '<p><a href="' . $safe . '" style="font-weight:bold;">Open the requisition</a></p>';
echo '</body></html>';
exit;
?>
