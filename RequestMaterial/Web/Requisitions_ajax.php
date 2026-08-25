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
// an entry action passes on either level, because whoever runs the station screen must be able to raise a requisition too, and the two levels are separate grants rather than a ladder
$rqEntryActions = array('insert', 'lookups', 'itemlookup', 'itemsearch');
$rqOk = "no";
if (function_exists('chkAutUsr')) {
    if (in_array($action, $rqEntryActions)) {
        $rqOk = (chkAutUsr($conn, $user, "LCCONLINE", 10) == "yes" ||
                 chkAutUsr($conn, $user, "LCCONLINE", 41) == "yes") ? "yes" : "no";
    } else {
        $rqOk = chkAutUsr($conn, $user, "LCCONLINE", 41);
    }
} else {
    $rqOk = "yes";
}

if ($rqOk != "yes") {
    rqsOutFail("Current user profile is not authorized to use this tool.");
}

// the typed number cells of a line, checked here instead of reaching Db2 as a silent zero; a cell
// that was not sent at all comes back null, which the procedures read as leave this column alone
function rqsLineNums($lineNum) {
    $out    = array('qty' => null, 'cost' => null, 'retail' => null, 'addCost' => null);
    $labels = array('qty' => 'quantity', 'cost' => 'cost',
                    'retail' => 'retail', 'addCost' => 'additional cost');
    foreach ($labels as $fld => $label) {
        if (!array_key_exists($fld, $_POST)) { continue; }
        $raw = str_replace(',', '', trim($_POST[$fld]));
        if ($raw === '') { $raw = '0'; }
        if (!is_numeric($raw)) {
            rqsOutFail("The " . $label . " on line " . $lineNum . " is not a number.");
        }
        if (floatval($raw) < 0) {
            rqsOutFail("The " . $label . " on line " . $lineNum . " cannot be less than zero.");
        }
        $out[$fld] = floatval($raw);
    }
    return $out;
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

        // the requestor is whoever the form named, since one person often raises a requisition for another
        $me      = rqsWhoAmI($conn, $user);
        $reqName = trim($payload['reqName'] ?? '');
        if ($reqName === '') { $reqName = ($me && $me['name'] !== '') ? $me['name'] : $user; }
        // new requisitions start with badge 0; whoever handles the requisition enters theirs afterwards from the station grid, which is why the entry form never asks for one
        $badge   = '0';

        // Entered By is whoever is signed on, the person actually raising the requisition, and it is taken from the sign on rather than the form so it stays true when a manager raises one under somebody else's name
        // the requestor beside it is who the requisition is for, so between them the record says who asked and who entered
        $keyer   = ($me && $me['name'] !== '') ? $me['name'] : $user;
        $deName  = substr(trim($keyer), 0, 50);

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

        rqsActLog($user, 'INSERT', 'req ' . $reqNum . ' (' . $lineNum . ' lines) for ' . $reqName);
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
        $comments = $_POST['comments'] ?? null;
        if (!rqsUpdateReq($conn, $reqNum, $authBy, $comments,
                          $badge, $reqName, $areaCode, $areaType)) {
            rqsOutFail();
        }
        // every field the request carried is named, so a question later about who changed what on which requisition is answered by the log by itself
        // the authorizer and the requestor matter most: between them and the sign on name at the front of the line, a requisition authorized or raised under somebody else's name shows up plainly
        rqsActLog($user, 'UPDATE', 'req ' . $reqNum .
                  ($authBy   !== null ? ' authby ' . trim($authBy)   : '') .
                  ($reqName  !== null ? ' name ' . $reqName          : '') .
                  ($badge    !== null ? ' badge ' . $badge           : '') .
                  ($areaCode !== null ? ' areacode ' . $areaCode     : '') .
                  ($areaType !== null ? ' areatype ' . $areaType     : '') .
                  ($comments !== null ? ' comments "' .
                       substr(trim($comments), 0, 60) . '"'          : ''));
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

    // correct one line of an existing requisition from the maintenance screen's line sheet
    case 'updateline':
        $reqNum  = intval($_POST['reqNum'] ?? 0);
        $lineNum = intval($_POST['lineNum'] ?? 0);
        if ($reqNum <= 0 || $lineNum <= 0) { rqsOutFail("No requisition line was named."); }

        // the whole line arrives as it stands on screen; anything absent all the same is left as it is on the file
        $item     = array_key_exists('item', $_POST)     ? substr(trim($_POST['item']), 0, 16)     : null;
        $loc      = array_key_exists('loc', $_POST)      ? substr(trim($_POST['loc']), 0, 3)       : null;
        $coinDate = array_key_exists('coinDate', $_POST) ? substr(trim($_POST['coinDate']), 0, 10) : null;
        $desc     = array_key_exists('desc', $_POST)     ? substr(trim($_POST['desc']), 0, 50)     : null;
        $skuTo    = array_key_exists('skuTo', $_POST)    ? substr(trim($_POST['skuTo']), 0, 16)    : null;

        // the item number is what the line is, so it can be corrected but not emptied
        if ($item !== null && $item === '') { rqsOutFail("Line " . $lineNum . " needs an item number."); }

        $nums = rqsLineNums($lineNum);

        if (!rqsUpdateLine($conn, $reqNum, $lineNum, $item, $loc, $coinDate, $desc,
                           $nums['qty'], $nums['cost'], $nums['retail'],
                           $nums['addCost'], $skuTo)) {
            rqsOutFail();
        }

        // every field the correction carried is named, so a question later about a changed item number or price is answered by the log by itself
        rqsActLog($user, 'UPDATELINE', 'req ' . $reqNum . ' line ' . $lineNum .
                  ($item            !== null ? ' item ' . $item              : '') .
                  ($loc             !== null ? ' loc ' . $loc                : '') .
                  ($coinDate        !== null ? ' coindate ' . $coinDate      : '') .
                  ($desc            !== null ? ' desc "' . $desc . '"'       : '') .
                  ($nums['qty']     !== null ? ' qty ' . $nums['qty']        : '') .
                  ($nums['cost']    !== null ? ' cost ' . $nums['cost']      : '') .
                  ($nums['retail']  !== null ? ' retail ' . $nums['retail']  : '') .
                  ($nums['addCost'] !== null ? ' addcost ' . $nums['addCost'] : '') .
                  ($skuTo           !== null ? ' skuto ' . $skuTo            : ''));
        rqsOut(array("ok" => true));

    // add a line to a requisition already raised, from the maintenance screen's line sheet
    case 'addline':
        $reqNum = intval($_POST['reqNum'] ?? 0);
        if ($reqNum <= 0) { rqsOutFail("No requisition was named."); }

        $item = substr(trim($_POST['item'] ?? ''), 0, 16);
        if ($item === '') { rqsOutFail("A new line needs an item number."); }

        $loc      = substr(trim($_POST['loc'] ?? ''), 0, 3);
        $coinDate = substr(trim($_POST['coinDate'] ?? ''), 0, 10);
        $desc     = substr(trim($_POST['desc'] ?? ''), 0, 50);
        $skuTo    = substr(trim($_POST['skuTo'] ?? ''), 0, 16);
        $nums     = rqsLineNums('being added');

        // Entered By is whoever is signed on, the same as a line keyed on the entry form
        $me     = rqsWhoAmI($conn, $user);
        $deName = substr(trim(($me && $me['name'] !== '') ? $me['name'] : $user), 0, 50);

        $newLine = rqsAddLine($conn, $reqNum, $item, $loc, $coinDate, $desc,
                              $nums['qty'], $nums['cost'], $nums['retail'],
                              $nums['addCost'], $deName, $skuTo);
        if ($newLine === false) { rqsOutFail(); }

        rqsActLog($user, 'ADDLINE', 'req ' . $reqNum . ' line ' . $newLine .
                  ' item ' . $item . ' qty ' . $nums['qty'] .
                  ' cost ' . $nums['cost'] . ' retail ' . $nums['retail']);
        rqsOut(array("ok" => true, "lineNum" => $newLine));

    // take one line off a requisition; the header and every other line stay as they are
    case 'deleteline':
        $reqNum  = intval($_POST['reqNum'] ?? 0);
        $lineNum = intval($_POST['lineNum'] ?? 0);
        if ($reqNum <= 0 || $lineNum <= 0) { rqsOutFail("No requisition line was named."); }

        if (!rqsDeleteLine($conn, $reqNum, $lineNum)) { rqsOutFail(); }

        rqsActLog($user, 'DELETELINE', 'req ' . $reqNum . ' line ' . $lineNum);
        rqsOut(array("ok" => true));

    default:
        rqsOutFail("Unknown action.");
}
?>