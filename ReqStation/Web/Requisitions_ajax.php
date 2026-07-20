<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_ajax.php                  *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station ajax handler.   *  -->
<!--  *   Maps action names to model calls and returns  *  -->
<!--  *   JSON. Session is re-established the same way  *  -->
<!--  *   InvPrt_Print_Invoices_ajax.php does it.       *  -->
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

session_name(SESSION_NAME);
session_start();

include("Requisitions_model.php");

$user = $_SESSION['username'];
$password = $_SESSION['password'];

$conn = getDB2PConn();

header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {

    // main grid rows (open requisitions)
    case 'list':
        echo json_encode(array("ok" => true,
                               "rows" => rqsGetOpen($conn)));
        break;

    // one requisition, header + lines
    case 'get':
        $reqNum = intval($_POST['reqNum']);
        echo json_encode(array("ok" => true,
                               "rows" => rqsGet($conn, $reqNum)));
        break;

    // dropdown data for the add-request form
    case 'lookups':
        echo json_encode(array(
            "ok"        => true,
            "names"     => rqsLookup($conn, "REQSTN007S"),
            "areaCodes" => rqsLookup($conn, "REQSTN008S"),
            "areaTypes" => rqsLookup($conn, "REQSTN009S"),
            "authBy"    => rqsLookup($conn, "REQSTN010S")));
        break;

    // insert a requisition: header fields + JSON array of lines
    case 'insert':
        $payload = json_decode($_POST['payload'], true);
        if (!$payload || empty($payload['lines'])) {
            echo json_encode(array("ok" => false,
                                   "msg" => "No requisition lines received."));
            break;
        }

        $badge = substr($payload['reqName'], 0, 10);
        $reqNum = rqsInsertHeader($conn,
                      $payload['reqName'],
                      $payload['areaCode'],
                      $payload['areaType'],
                      ($payload['rush'] == 'Y' ? 'Y' : 'N'),
                      $badge,
                      $payload['comments']);

        $lineNum = 0;
        foreach ($payload['lines'] as $line) {
            if (trim($line['item']) == '') { continue; }
            $lineNum++;
            rqsInsertLine($conn, $reqNum, $lineNum,
                $line['item'], $line['loc'], $line['coinDate'],
                $line['desc'],
                floatval($line['qty']),
                floatval(str_replace(',', '', $line['cost'])),
                floatval(str_replace(',', '', $line['retail'])),
                floatval(str_replace(',', '', $line['addCost'])),
                $badge, $line['skuTo']);
        }

        echo json_encode(array("ok" => true,
                               "reqNum" => $reqNum,
                               "lines" => $lineNum));
        break;

    // authorize a requisition
    case 'authorize':
        $reqNum = intval($_POST['reqNum']);
        rqsAuthorize($conn, $reqNum,
                             $_POST['authBy'], $_POST['comments']);
        echo json_encode(array("ok" => true));
        break;

    // mark / unmark a line returned
    case 'returned':
        $reqNum = intval($_POST['reqNum']);
        $lineNum = intval($_POST['lineNum']);
        $flag = ($_POST['flag'] == 'Y' ? 'Y' : 'N');
        rqsSetReturned($conn, $reqNum, $lineNum, $flag);
        echo json_encode(array("ok" => true));
        break;

    default:
        echo json_encode(array("ok" => false, "msg" => "Unknown action."));
}
?>
