<?php
/*    ***************************************************  -->
<!--  * Program Name - getInsert.php                    *  -->
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

// this was the save target of the old entry form and it wrote straight into the old database
// it is stopped here because a browser tab left open on the old form could still post to it after cutover, and that requisition would land in the old database where nobody is looking
// the entry is deliberately NOT saved and NOT forwarded, because a silent redirect would let someone believe their work was filed when it was not

define('RQS_NEW_APP', '/LCCOnline');
$target = RQS_NEW_APP . '/Requisitions_ctl.php?mode=entry';

$safe = htmlspecialchars($target, ENT_QUOTES);
echo '<html><head><title>Requisition Material</title></head><body style="font-family:Arial,sans-serif;margin:40px;">';
echo '<h2 style="color:#c0392b;">This requisition was not saved</h2>';
echo '<p>The requisition system has moved, and the form you just submitted belongs to the old screen. ';
echo 'Nothing was filed, so please enter it again on the new screen.</p>';
echo '<p><a href="' . $safe . '" style="font-weight:bold;">Open the requisition form</a></p>';
echo '<p style="color:#5b6371;">If you reached this page from a bookmark or a shortcut, ask IT to point it at the new address.</p>';
echo '</body></html>';
exit;
?>
