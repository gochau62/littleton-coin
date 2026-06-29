<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteRefData_ajax.php        *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
// AJAX endpoint for the Sellbrite reference-data maintenance screen.
// CRUD over the dropdown lists (SBLVALUET), category defaults (SBLCATDFT)
// and the grade map (SBLGRADET).  Mirrors the JSON contract style of
// SellbriteBulkLoader_ajax.php.
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
require_once __DIR__ . '/SellbriteBulkLoader_model.php';   // shared DB2 access layer

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$ok = static fn($extra = []) => json_encode(['returnClass' => 'success'] + $extra);
$err = static fn($msg) => json_encode(['returnClass' => 'error', 'message' => $msg]);

switch ($action) {

    /* ---- dropdown value lists (SBLVALUET) ---- */
    case 'valueLists':
        echo $ok(['lists' => sblValueLists()]);
        break;
    case 'valueRows':
        echo $ok(['rows' => sblValueRows($_GET['list'] ?? $_POST['list'] ?? '')]);
        break;
    case 'valueAdd':
        $id = sblValueAdd($_POST['list'] ?? '', $_POST['value_text'] ?? '', $_POST['is_header'] ?? 'N');
        echo $id ? $ok(['id' => $id]) : $err('Could not add the option (duplicate or no value).');
        break;
    case 'valueUpdate':
        echo sblValueUpdate((int) ($_POST['id'] ?? 0), $_POST) ? $ok() : $err('Update failed.');
        break;
    case 'valueDelete':
        echo sblValueDelete((int) ($_POST['id'] ?? 0)) ? $ok() : $err('Delete failed.');
        break;

    /* ---- generic auto-fill rules (SBLAUTOFT) ---- */
    case 'autoFields':
        echo $ok(['fields' => sblAutofillFields()]);
        break;
    case 'autoRows':
        echo $ok(['rows' => sblAutofillRows($_GET['when_field'] ?? $_POST['when_field'] ?? '',
                                            $_GET['q'] ?? $_POST['q'] ?? '')]);
        break;
    case 'autoSave':
        $id = sblAutofillSave($_POST);
        echo $id ? $ok(['id' => $id]) : $err('Save failed (when/value/set field required, or duplicate).');
        break;
    case 'autoDelete':
        echo sblAutofillDelete((int) ($_POST['id'] ?? 0)) ? $ok() : $err('Delete failed.');
        break;

    default:
        echo $err('Unknown action');
}
