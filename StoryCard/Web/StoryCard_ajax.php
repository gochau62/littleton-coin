<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCard_ajax.php                *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/27/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

// AJAX endpoint, buffer from byte 0 so stray include output can't corrupt the JSON
ob_start();
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

require_once __DIR__ . '/StoryCard_model.php';

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json');

function stcOut($arr) { echo json_encode($arr); exit; }


function stcOutFail($msg = '') {
    stcOut(array("ok" => false,
                 "msg" => $msg !== '' ? $msg : ($GLOBALS['stcErr'] ?: 'Request failed.')));
}

if (!$conn) {
    stcOutFail("No database connection - sign in to LCC Online first.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // the SKU list the Access combo carried: SKUs that already have a card,
    // narrowed by what has been typed so far
    case 'skusearch':
        $rows = stcSkuSearch($conn, $_POST['q'] ?? $_GET['q'] ?? '');
        if ($rows === false) { stcOutFail(); }
        stcOut(array("ok" => true, "rows" => $rows));

    // one card: the item description, both sides and the search key. A SKU that
    // is not on the item master is refused here, the way CheckSku refused it
    case 'card':
        $sku  = stcCleanSku($_POST['sku'] ?? $_GET['sku'] ?? '');
        if ($sku === '') { stcOutFail("Enter a SKU."); }

        $item = stcGetSku($conn, $sku);
        if ($item === false) { stcOutFail(); }
        if ($item === null)  { stcOutFail("SKU Not in Master File"); }

        $rows = stcGetCard($conn, $sku);
        if ($rows === false) { stcOutFail(); }

        $card = stcCardToSides($rows);
        $card['sku']   = rtrim($item['SCSKU']);
        $card['desc']  = rtrim($item['SCDESC']);
        $card['isNew'] = (count($rows) === 0);

        stcOut(array("ok" => true, "card" => $card));

    // the footer for one key, plus the keys there are to choose from. The key
    // list is the Access FooterSelect query the FootMaintenance combo used
    case 'footer':
        $sky  = intval($_POST['sky'] ?? $_GET['sky'] ?? STC_FOOT_KEY);
        $rows = stcGetFooter($conn, $sky);
        if ($rows === false) { stcOutFail(); }

        // a failed key list is logged but does not take the footer down with
        // it - FooterSelect can only ever return key 1 anyway
        $keys = stcFooterKeys($conn);
        $keyList = array();
        if ($keys === false) {
            error_log('StoryCard footer keys unavailable (' . $GLOBALS['stcErr'] .
                      ') - is STYCRD001S built with the KEYS type?');
            $keyList[] = $sky;
        } else {
            foreach ($keys as $k) { $keyList[] = intval($k['SCFSKY']); }
        }

        $foot = array();
        foreach ($rows as $r) { $foot[] = rtrim($r['SCFTXT']); }

        stcOut(array("ok" => true, "sky" => $sky,
                     "footer" => $foot, "keys" => $keyList));

    // save a whole card in one call: both sides plus the search key. Any line
    // that fails puts the card back the way it was and reports which one
    case 'savecard':
        $payload = json_decode($_POST['payload'] ?? '', true);
        if (!is_array($payload)) { stcOutFail("Nothing to save."); }

        $sku = stcCleanSku($payload['sku'] ?? '');
        if ($sku === '') { stcOutFail("Pick a SKU first."); }

        $item = stcGetSku($conn, $sku);
        if ($item === false) { stcOutFail(); }
        if ($item === null)  { stcOutFail("SKU Not in Master File"); }

        $side1 = is_array($payload['side1'] ?? null) ? $payload['side1'] : array();
        $side2 = is_array($payload['side2'] ?? null) ? $payload['side2'] : array();
        $srk   = substr(trim((string)($payload['searchKey'] ?? '')), 0, STC_SRK_LEN);

        // the screen sends a full set of boxes; anything past the printed side
        // is dropped here rather than trusted
        $side1 = array_slice(array_values($side1), 0, STC_S1_LAST - STC_S1_FIRST + 1);
        $side2 = array_slice(array_values($side2), 0, STC_S2_LAST - STC_S2_FIRST + 1);

        $before = stcGetCard($conn, $sku);
        if ($before === false) { stcOutFail(); }
        $wasNew = (count($before) === 0);

        $lines = stcSaveCard($conn, $sku, $side1, $side2, $srk);
        if ($lines === false) { stcOutFail(); }

        stcActLog($user, $wasNew ? 'INSERT' : 'UPDATE',
                  'card ' . $sku . ' (' . $lines . ' lines)');
        stcOut(array("ok" => true, "sku" => $sku, "lines" => $lines, "isNew" => $wasNew));

    // save the shared footer. It prints on every story card, so this one is
    // rolled back the same way a card is
    case 'savefooter':
        $payload = json_decode($_POST['payload'] ?? '', true);
        if (!is_array($payload) || !is_array($payload['footer'] ?? null)) {
            stcOutFail("Nothing to save.");
        }

        $sky   = intval($payload['sky'] ?? STC_FOOT_KEY);
        $lines = stcSaveFooter($conn, array_values($payload['footer']), $sky);
        if ($lines === false) { stcOutFail(); }

        stcActLog($user, 'FOOTER', 'key ' . $sky . ' (' . $lines . ' lines)');
        stcOut(array("ok" => true, "sky" => $sky, "lines" => $lines));

    default:
        stcOutFail("Unknown action.");
}
?>
