<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ajax.php            *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/20/2026                         *  -->
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

require_once __DIR__ . '/Requisitions_model.php';

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json');

function rqsOut($arr) { echo json_encode($arr); exit; }


function rqsOutFail($msg = '') {
    rqsOut(array("ok" => false,
                 "msg" => $msg !== '' ? $msg : ($GLOBALS['rqsErr'] ?: 'Request failed.')));
}

if (!$conn) {
    rqsOutFail("No database connection - sign in to LCC Online first.");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// the two screens are not open to the same people, so neither are the actions behind them
// raising a requisition and the lists the entry form needs to do it sit at the level everyone signed in to LCCOnline has
// everything else belongs to the station screen, where a requisition is authorized, corrected and reported on, and asks for the requisitions group
// this is checked here and not only on the screen, because the screen only decides what is drawn while this decides what can actually be done
$rqEntryActions = array('insert', 'lookups', 'itemlookup', 'itemsearch');
$rqNeeds = in_array($action, $rqEntryActions) ? 10 : 41;

if (function_exists('chkAutUsr') && chkAutUsr($conn, $user, "LCCONLINE", $rqNeeds) != "yes") {
    rqsOutFail("Current user profile is not authorized to use this tool.");
}

switch ($action) {

    // main grid rows, show O open (default), R returned, A all; q searches
    case 'list':
        $rows = rqsGetOpen($conn,
                           $_POST['show'] ?? $_GET['show'] ?? 'O',
                           $_POST['q'] ?? $_GET['q'] ?? '');
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // one requisition, header + lines
    case 'get':
        $rows = rqsGet($conn, intval($_POST['reqNum']));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // the four dropdown lists plus the badge list for the station grid, used only as a fallback when the controller did not preload them onto the page
    // the entry form asks with mode entry and gets no badge list, the same as when the controller preloads it, so the work floor never receives one by either route
    case 'lookups':
        $names  = rqsLookup($conn, "NAMES");
        $codes  = rqsLookup($conn, "AREACODE");
        $types  = rqsLookup($conn, "AREATYPE");
        $auth   = rqsLookup($conn, "AUTHBY");
        if ($names === false || $codes === false || $types === false || $auth === false) {
            rqsOutFail();
        }
        $out = array("ok" => true, "names" => $names, "areaCodes" => $codes,
                     "areaTypes" => $types, "authBy" => $auth);
        if (($_POST['mode'] ?? '') !== 'entry') {
            $badges = rqsLookup($conn, "BADGE");
            if ($badges === false) { rqsOutFail(); }
            $out['badges'] = $badges;
        }
        rqsOut($out);

    // item autofill: most recent description/coin date/cost/retail
    case 'itemlookup':
        $rows = rqsItemLookup($conn, trim($_POST['item'] ?? ''));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "row" => $rows ? $rows[0] : null));

    // the typeahead item search that feeds the entry form item dropdown as the user types
    case 'itemsearch':
        $rows = rqsItemSearch($conn, trim($_POST['q'] ?? ''));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // insert: header + JSON lines; any failure backs the whole requisition out
    case 'insert':
        $payload = json_decode($_POST['payload'], true);
        if (!$payload || empty($payload['lines'])) {
            rqsOutFail("No requisition lines received.");
        }

        // the requestor is whoever the form named, since one person often raises a requisition for another, and the badge that rides with it is that person's
        $me      = rqsWhoAmI($conn, $user);
        $reqName = trim($payload['reqName'] ?? '');
        if ($reqName === '') { $reqName = ($me && $me['name'] !== '') ? $me['name'] : $user; }
        // the badge is worked out here from the name, never sent up by the form, so the entry screen never has to carry a badge and the work floor never sees one
        $badge   = rqsBadgeForName($conn, $reqName);
        if ($badge === '' || !ctype_digit($badge)) { $badge = '0'; }

        // the data entry name records who actually keyed it, taken from the sign on and never from the form, so it stays true even when the requisition is raised for somebody else
        $keyer   = ($me && $me['name'] !== '') ? $me['name'] : $user;
        $first   = trim(explode(' ', trim($keyer))[0]);
        $deName  = $first !== '' ? substr($first, 0, 4) : '';

        $reqNum = rqsInsertHeader($conn,
                      $reqName,
                      $payload['areaCode'],
                      $payload['areaType'],
                      ($payload['rush'] == 'Y' ? 'Y' : 'N'),
                      // the entry form never sets this; a requisition raised there starts unauthorized and is authorized later from the station screen
                      (($payload['mode'] ?? '') === 'entry') ? 'Authorization = None' : ($payload['authBy'] ?? ''),
                      $badge,
                      $payload['comments']);
        if ($reqNum === false) { rqsOutFail(); }

        $lineNum = 0;
        foreach ($payload['lines'] as $line) {
            if (trim($line['item']) == '') { continue; }
            $lineNum++;
            $ok = rqsInsertLine($conn, $reqNum, $lineNum,
                $line['item'], $line['loc'], $line['coinDate'],
                $line['desc'],
                floatval($line['qty']),
                floatval(str_replace(',', '', $line['cost'])),
                floatval(str_replace(',', '', $line['retail'])),
                floatval(str_replace(',', '', $line['addCost'])),
                $deName, $line['skuTo']);
            if (!$ok) {
                $err = $GLOBALS['rqsErr'];
                rqsDeleteRequisition($conn, $reqNum);
                rqsActLog($user, 'BACKOUT', 'req ' . $reqNum . ' - line ' . $lineNum . ' failed');
                rqsOutFail("Line " . $lineNum . " failed (" . $err .
                           ") - nothing was saved. Fix the line and submit again.");
            }
        }

        rqsActLog($user, 'INSERT', 'req ' . $reqNum . ' (' . $lineNum . ' lines)' .
                  ' for ' . $reqName . ' badge ' . $badge);
        rqsOut(array("ok" => true, "reqNum" => $reqNum, "lines" => $lineNum));

    // update a header: missing fields (authBy/comments/badge) stay unchanged
    case 'update':
        $reqNum   = intval($_POST['reqNum']);
        // the badge column only ever takes a number; a legacy name is left exactly as it is rather than rewritten or rejected, so an old requisition can still have its other fields corrected
        $badge    = isset($_POST['badge'])    ? substr(trim($_POST['badge']), 0, 10)    : null;
        if ($badge !== null && $badge !== '' && !ctype_digit($badge)) { $badge = null; }
        $reqName  = isset($_POST['reqName'])  ? substr(trim($_POST['reqName']), 0, 50)  : null;
        $areaCode = isset($_POST['areaCode']) ? substr(trim($_POST['areaCode']), 0, 2)  : null;
        $areaType = isset($_POST['areaType']) ? substr(trim($_POST['areaType']), 0, 25) : null;
        $authBy   = $_POST['authBy'] ?? null;
        if (!rqsUpdateReq($conn, $reqNum, $authBy, $_POST['comments'] ?? null,
                          $badge, $reqName, $areaCode, $areaType)) {
            rqsOutFail();
        }
        // the log names the authorizer that was set, so a later question about who approved something under whose name can be answered from the log alone
        rqsActLog($user, 'UPDATE', 'req ' . $reqNum .
                  ($authBy   !== null ? ' authby ' . trim($authBy) : '') .
                  ($reqName  !== null ? ' name ' . $reqName        : '') .
                  ($badge    !== null ? ' badge ' . $badge         : ''));
        rqsOut(array("ok" => true));

    // correct one line from the grid: only the boxes that were sent are changed
    case 'line':
        $reqNum  = intval($_POST['reqNum']);
        $lineNum = intval($_POST['lineNum']);
        $item = isset($_POST['item']) ? substr(trim($_POST['item']), 0, 16) : null;
        $loc  = isset($_POST['loc'])  ? substr(trim($_POST['loc']), 0, 3)   : null;
        $desc = isset($_POST['desc']) ? substr(trim($_POST['desc']), 0, 50) : null;
        // the data entry name, four letters of a first name and never a number, sent with line 0 to reach every line of the requisition
        $deName = isset($_POST['deName']) ? substr(trim($_POST['deName']), 0, 4) : null;
        // a quantity that is not a whole number above zero is dropped rather than stored, so the grid totals and the monthly report stay sound
        $qty = null;
        if (isset($_POST['qty'])) {
            $q = trim(str_replace(',', '', $_POST['qty']));
            if ($q !== '' && is_numeric($q) && floatval($q) > 0) { $qty = intval(floatval($q)); }
        }
        if (!rqsUpdateLine($conn, $reqNum, $lineNum, $item, $loc, $desc, $qty, $deName)) {
            rqsOutFail();
        }
        rqsActLog($user, 'LINE', 'req ' . $reqNum . ' line ' . $lineNum .
                  ($item   !== null ? ' item ' . $item     : '') .
                  ($loc    !== null ? ' loc ' . $loc       : '') .
                  ($qty    !== null ? ' qty ' . $qty       : '') .
                  ($deName !== null ? ' dename ' . $deName : '') .
                  ($desc   !== null ? ' desc ' . $desc     : ''));
        rqsOut(array("ok" => true));

    // monthly report rows (yyyymm)
    case 'monthly':
        $rows = rqsMonthly($conn, intval($_POST['yyyymm']));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // mark or unmark a line returned (dateRet yyyymmdd, 0 means today)
    case 'returned':
        $reqNum = intval($_POST['reqNum']);
        $lineNum = intval($_POST['lineNum']);
        $flag = ($_POST['flag'] == 'Y' ? 'Y' : 'N');
        $dateRet = intval($_POST['dateRet'] ?? 0);
        if (!rqsSetReturned($conn, $reqNum, $lineNum, $flag, $dateRet)) { rqsOutFail(); }
        rqsActLog($user, $flag == 'Y' ? 'RETURN' : 'UNRETURN',
                  'req ' . $reqNum . ' line ' . $lineNum .
                  ($dateRet > 0 ? ' dated ' . $dateRet : ''));
        rqsOut(array("ok" => true));

    default:
        rqsOutFail("Unknown action.");
}
?>
