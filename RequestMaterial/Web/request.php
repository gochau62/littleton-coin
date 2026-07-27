<?php
/*    ***************************************************  -->
<!--  * Program Name - request.php                      *  -->
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

// the old requisition page keeps its address and now sends everyone to the new screen, so saved bookmarks and desktop shortcuts still work

define('RQS_NEW_APP', 'http://lcc1:8068/LCCOnline');

// a requisition number on the old link opens that same requisition, anything else opens the work floor entry form
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$target = ($id > 0)
    ? RQS_NEW_APP . '/Requisitions_ctl.php?id=' . $id
    : RQS_NEW_APP . '/Requisitions_ctl.php?mode=entry';

// adding shimtest to the old address prints where it would send you without going there, for checking the address before anyone is pointed at it
if (isset($_GET['shimtest'])) {
    header('Content-Type: text/plain');
    echo "would redirect to: " . $target . "\n";
    exit;
}

// a temporary redirect on purpose, because a browser remembers a permanent one forever and would keep sending people here even after this file was put back
if (!headers_sent()) {
    header('Location: ' . $target, true, 302);
}

// the meta refresh and the link cover the case where something printed before the header, which makes the browser ignore it
$safe = htmlspecialchars($target, ENT_QUOTES);
echo '<html><head><meta http-equiv="refresh" content="0;url=' . $safe . '"></head>' .
     '<body>This page has moved. <a href="' . $safe . '">Open the requisition form</a>.</body></html>';
exit;
?>
