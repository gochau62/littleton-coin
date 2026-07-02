<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ajax.php     *  -->
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
require_once __DIR__ . '/SellbriteBulkLoader_model.php';   // also pulls in the logic file
require_once __DIR__ . '/SellbriteBulkLoader_agent.php';   // GreySheet + Gemini coin agent

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

    case 'gsSearch':
        // Coin dropdown: search the learned path memory (0 API calls).
        $s = gsSearch((string) ($_POST['q'] ?? ''));
        echo json_encode(['returnClass' => $s['ok'] ? 'success' : 'error',
                          'matches' => $s['matches'], 'message' => $s['error']]);
        break;

    case 'gsImport':
        // Auto-fill from GreySheet: by gs_id (dropdown pick) or by navigating
        // the tree from the form's attributes (learning the path as it goes).
        $imp = gsImport($_POST);
        $rc  = !$imp['ok'] ? 'error' : (!$imp['found'] ? 'notfound' : ($imp['valid'] ? 'success' : 'warning'));
        echo json_encode(['returnClass' => $rc, 'row' => $imp['row'], 'statuses' => $imp['statuses'],
                          'messages' => $imp['messages'], 'valid' => $imp['valid'],
                          'via' => $imp['via'], 'message' => $imp['error']]);
        break;

    case 'gsGenerate':
        // Coin GreySheet doesn't carry: Gemini drafts the whole listing.
        $gen = gsGenerate($_POST);
        echo json_encode(['returnClass' => !$gen['ok'] ? 'error' : ($gen['valid'] ? 'success' : 'warning'),
                          'row' => $gen['row'], 'statuses' => $gen['statuses'],
                          'messages' => $gen['messages'], 'valid' => $gen['valid'],
                          'via' => $gen['via'], 'message' => $gen['error']]);
        break;

    default:
        echo json_encode(['returnClass' => 'error', 'message' => 'Unknown action']);
}
