<?php
/*    ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  * Date      -  09/10/2026                         *  -->
<!--  * Purpose   -  Redirect to New Project Detail     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// old bookmarks and shortcuts still land on the screen
// both files sit in the same folder, so the address stays relative

// a project number on the old link opens that same project
$projnum = '';
if (isset($_GET['projnum'])) { $projnum = trim(strval($_GET['projnum'])); }

$target = 'ProjectDetail_ctl.php';
if ($projnum !== '') { $target .= '?projnum=' . rawurlencode($projnum); }

// add shimtest to the old address to see where it would send you
if (isset($_GET['shimtest'])) {
    header('Content-Type: text/plain');
    echo "old address reached : " . $_SERVER['REQUEST_URI'] . "\n";
    echo "project number      : " . ($projnum !== '' ? $projnum : 'none, so a new project') . "\n";
    echo "would redirect to   : " . $target . "\n";
    exit;
}

// 302 and not 301 so a browser does not remember this forever
if (!headers_sent()) {
    header('Location: ' . $target, true, 302);
}

// a meta refresh and a script move the browser if the header was too late
$safe = htmlspecialchars($target, ENT_QUOTES);
echo '<html><head><meta http-equiv="refresh" content="0;url=' . $safe . '">' .
     '<script>window.location.replace(' . json_encode($target) . ');</script></head>' .
     '<body>This page has moved. <a href="' . $safe . '">Open the project screen</a>.</body></html>';
exit;
?>
