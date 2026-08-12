<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_kiosk.php           *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 08/12/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

// the work floor terminal opens this page instead of the entry form directly; when nobody is signed in it signs the
// browser in as the kiosk profile and lands on the entry form, so the shared terminal never sees the sign on screen
// somebody already signed in keeps their own name: the kiosk only ever fills an empty session, it never replaces a person

foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// the profile and its password live in a two line file OUTSIDE the web root (user = / pass =), readable only by the
// web server profile, so nothing secret sits in this source, in change management, or anywhere a browser can fetch
define('RQS_KIOSK_CONF', dirname(__DIR__) . '/conf/rqskiosk.conf');

if (empty($_SESSION['username'])) {
    $kiosk = is_readable(RQS_KIOSK_CONF) ? parse_ini_file(RQS_KIOSK_CONF) : false;
    if ($kiosk && !empty($kiosk['user']) && isset($kiosk['pass'])) {
        // the same two session fields the sign on page sets, so everything downstream works unchanged
        $_SESSION['username'] = trim($kiosk['user']);
        $_SESSION['password'] = $kiosk['pass'];
    }
}

// on to the entry form either way; with no conf file and no sign on, the framework asks for one, the same as today
header('Location: Requisitions_ctl.php?mode=entry', true, 302);
exit;
?>
