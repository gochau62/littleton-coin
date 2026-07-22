<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ajax.php            *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station ajax handler.   *  -->
<!--  *   Maps action names to model calls and returns  *  -->
<!--  *   JSON. Every failure returns ok:false with the *  -->
<!--  *   real Db2 message so support can act on what   *  -->
<!--  *   the user reports. A failed insert backs out   *  -->
<!--  *   the partial requisition (REQSTN013S).         *  -->
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

// AJAX endpoint - buffer from byte 0 so stray include output can't corrupt the JSON
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

switch ($action) {

    // main grid rows (open requisitions); first=1 logs the station OPEN
    case 'list':
        $rows = rqsGetOpen($conn, intval($_POST['first'] ?? 0) ? 'Y' : 'N');
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // one requisition, header + lines
    case 'get':
        $rows = rqsGet($conn, intval($_POST['reqNum']));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // dropdown data for the add-request form (fallback when not preloaded)
    case 'lookups':
        $names = rqsLookup($conn, "NAMES");
        $codes = rqsLookup($conn, "AREACODE");
        $types = rqsLookup($conn, "AREATYPE");
        $auth  = rqsLookup($conn, "AUTHBY");
        if ($names === false || $codes === false || $types === false || $auth === false) {
            rqsOutFail();
        }
        rqsOut(array("ok" => true, "names" => $names, "areaCodes" => $codes,
                     "areaTypes" => $types, "authBy" => $auth));

    // item autofill: most recent description/coin date/cost/retail
    case 'itemlookup':
        $rows = rqsItemLookup($conn, trim($_POST['item'] ?? ''));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "row" => $rows ? $rows[0] : null));

    // type-ahead item search for the entry form's dropdown
    case 'itemsearch':
        $rows = rqsItemSearch($conn, trim($_POST['q'] ?? ''));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // insert a requisition: header fields + JSON array of lines.
    // Any line failure backs the whole requisition out - never half-saved.
    case 'insert':
        $payload = json_decode($_POST['payload'], true);
        if (!$payload || empty($payload['lines'])) {
            rqsOutFail("No requisition lines received.");
        }

        $badge = substr($payload['reqName'], 0, 10);
        $reqNum = rqsInsertHeader($conn,
                      $payload['reqName'],
                      $payload['areaCode'],
                      $payload['areaType'],
                      ($payload['rush'] == 'Y' ? 'Y' : 'N'),
                      $payload['authBy'] ?? '',
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
                $badge, $line['skuTo']);
            if (!$ok) {
                $err = $GLOBALS['rqsErr'];
                rqsDeleteRequisition($conn, $reqNum);
                rqsOutFail("Line " . $lineNum . " failed (" . $err .
                           ") - nothing was saved. Fix the line and submit again.");
            }
        }

        rqsOut(array("ok" => true, "reqNum" => $reqNum, "lines" => $lineNum));

    // update a requisition header (authorized-by + comments, legacy Update)
    case 'update':
        $reqNum = intval($_POST['reqNum']);
        if (!rqsUpdateReq($conn, $reqNum, $_POST['authBy'], $_POST['comments'])) {
            rqsOutFail();
        }
        rqsOut(array("ok" => true));

    // monthly report rows (yyyymm)
    case 'monthly':
        $rows = rqsMonthly($conn, intval($_POST['yyyymm']));
        if ($rows === false) { rqsOutFail(); }
        rqsOut(array("ok" => true, "rows" => $rows));

    // mark / unmark a line returned
    case 'returned':
        $reqNum = intval($_POST['reqNum']);
        $lineNum = intval($_POST['lineNum']);
        $flag = ($_POST['flag'] == 'Y' ? 'Y' : 'N');
        if (!rqsSetReturned($conn, $reqNum, $lineNum, $flag)) { rqsOutFail(); }
        rqsOut(array("ok" => true));

    default:
        rqsOutFail("Unknown action.");
}
?>
