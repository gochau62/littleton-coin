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

    /* ---- category defaults (SBLCATDFT) ---- */
    case 'catRows':
        echo $ok(['rows' => sblCatRows()]);
        break;
    case 'catFind':
        $row = sblCatFind((int) ($_POST['id'] ?? $_GET['id'] ?? 0));
        echo $row ? $ok(['row' => $row]) : $err('Category not found.');
        break;
    case 'catSave':
        $id = sblCatSave($_POST);
        echo $id ? $ok(['id' => $id]) : $err('Save failed (category name required / duplicate).');
        break;
    case 'catDelete':
        echo sblCatDelete((int) ($_POST['id'] ?? 0)) ? $ok() : $err('Delete failed.');
        break;

    /* ---- grade map (SBLGRADET) ---- */
    case 'gradeRows':
        echo $ok(['rows' => sblGradeRows()]);
        break;
    case 'gradeSave':
        $id = sblGradeSave($_POST);
        echo $id ? $ok(['id' => $id]) : $err('Save failed (grade required / duplicate).');
        break;
    case 'gradeDelete':
        echo sblGradeDelete((int) ($_POST['id'] ?? 0)) ? $ok() : $err('Delete failed.');
        break;

    default:
        echo $err('Unknown action');
}
