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

// this file replaces the old requisition entry page in place, so every saved bookmark and desktop shortcut keeps working and simply lands on the new screen
// it must stay at the old address; the old page body was removed because the browser never gets far enough to render anything

// the one line to change when the new screen moves between instances, with no trailing slash
define('RQS_NEW_APP', 'http://lcc1:8068/LCCOnline');

// a requisition number on the old link opens that same requisition on the new screen, anything else goes to the work floor entry form
$id = 0;
if (isset($_GET['id']))      { $id = intval($_GET['id']); }
if (isset($_REQUEST['req'])) { $id = intval($_REQUEST['req']); }

$target = ($id > 0)
    ? RQS_NEW_APP . '/Requisitions_ctl.php?id=' . $id
    : RQS_NEW_APP . '/Requisitions_ctl.php?mode=entry';

// add shimtest to the old address to see where it would send you without actually going there, which is how this gets checked before anyone is pointed at it
if (isset($_GET['shimtest'])) {
    header('Content-Type: text/plain');
    echo "old address reached : " . $_SERVER['REQUEST_URI'] . "\n";
    echo "requisition number  : " . ($id > 0 ? $id : 'none, so the entry form') . "\n";
    echo "would redirect to   : " . $target . "\n";
    exit;
}

// 302 and not 301 on purpose: a browser remembers a 301 forever, so a permanent code would keep sending people to the new screen even after this file was put back
if (!headers_sent()) {
    header('Location: ' . $target, true, 302);
}

// the header above is the normal path, but if anything printed before this point the header is ignored, so the page also carries a meta refresh and a script that move the browser anyway
$safe = htmlspecialchars($target, ENT_QUOTES);
echo '<html><head><meta http-equiv="refresh" content="0;url=' . $safe . '">' .
     '<script>window.location.replace(' . json_encode($target) . ');</script></head>' .
     '<body>This page has moved. <a href="' . $safe . '">Open the requisition form</a>.</body></html>';
exit;
?>
