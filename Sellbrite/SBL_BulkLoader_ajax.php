<?php
/*    ***************************************************  -->
<!--  * Program Name - SBL_BulkLoader_ajax.php          *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
// AJAX endpoint for the Sellbrite Bulk Loader.
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
require_once __DIR__ . '/SBL_BulkLoader_model.php';   // also pulls in the logic file

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CSV export streams a file, so handle it before JSON 
if ($action === 'export') {
    $csv = Exporter::csv(sblGetAll());
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sellbrite_export_' . date('Ymd_His') . '.csv"');
    echo $csv;
    exit;
}

header('Content-Type: application/json');

switch ($action) {

    case 'compute':
        $computed   = Computer::apply($_POST);
        $validation = Validator::check($computed);
        echo json_encode([
            'returnClass' => 'success',
            'fields'      => $computed,
            'statuses'    => $validation['statuses'],
            'messages'    => $validation['messages'],
            'valid'       => $validation['valid'],
        ]);
        break;

    case 'save':
        $computed   = Computer::apply($_POST);
        $validation = Validator::check($computed);
        $id = sblSave($computed);
        echo json_encode([
            'returnClass' => $validation['valid'] ? 'success' : 'warning',
            'id'          => $id,
            'sku'         => $computed['sku'] ?? '',
            'valid'       => $validation['valid'],
        ]);
        break;

    case 'find':
        $row = sblFind((int) ($_POST['id'] ?? 0));
        echo json_encode([
            'returnClass' => $row ? 'success' : 'error',
            'row'         => $row,
        ]);
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) { sblDelete($id); }
        echo json_encode(['returnClass' => 'success', 'id' => $id]);
        break;

    default:
        echo json_encode(['returnClass' => 'error', 'message' => 'Unknown action']);
}
