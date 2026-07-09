<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ajax.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

// AJAX endpoint for the Sellbrite Bulk Loader.
// Buffer from the very start: the shared includes can echo whitespace, and a
// single stray byte in front of an .xlsx download corrupts it for Excel.
ob_start();
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

// Export streams a file, so handle it before JSON. Colour-coded XLSX matching
// Des's product_data workbook when PhpSpreadsheet is on the box (IBM i vendor
// dir, same as GFTCRDCVP); otherwise the identical layout as a plain CSV.
if ($action === 'export') {
    $vendor = '/www/seidenphp/htdocs/vendor/autoload.php';
    if (file_exists($vendor)) { require_once $vendor; }
    $rows = sblGetAllFull();
    $ss   = Exporter::xlsx($rows);
    // Throw away anything echoed so far (include noise, notices, the header
    // comment's newline) - the download must start at byte 0.
    while (ob_get_level() > 0) { ob_end_clean(); }
    if ($ss !== null) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="sellbrite_products_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sellbrite_products_' . date('Ymd_His') . '.csv"');
    echo Exporter::csv($rows);
    exit;
}

// JSON answers get the same treatment - discard any stray include output.
while (ob_get_level() > 0) { ob_end_clean(); }
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
        // Soft gate: incomplete required fields don't block the save - the
        // response carries the list so the UI can warn.
        $missing = [];
        if (!$validation['valid']) {
            $labels = [];
            foreach (Schema::columns() as $col) { $labels[$col['name']] = $col['label']; }
            foreach ($validation['statuses'] as $f => $st) {
                if ($st === 'error') { $missing[] = $labels[$f] ?? $f; }
            }
        }
        $id = sblSave($computed);
        if ($id === false) {
            echo json_encode(['returnClass' => 'error',
                              'message' => 'Save failed - no database connection or a DB error (check you are signed in on the IBM i).']);
            break;
        }
        echo json_encode([
            'returnClass' => $validation['valid'] ? 'success' : 'warning',
            'id'          => $id,
            'sku'         => $computed['sku'] ?? '',
            'valid'       => $validation['valid'],
            'missing'     => $missing,
            'row'         => [
                'id'            => $id,
                'sku'           => $computed['sku'] ?? '',
                'marketplace'   => $computed['marketplace'] ?? '',
                'category_name' => $computed['category_name'] ?? '',
                'name'          => $computed['name'] ?? '',
                'grade'         => $computed['grade'] ?? '',
                'price'         => $computed['price'] ?? '',
                'quantity'      => $computed['quantity'] ?? '',
                'updated_at'    => date('Y-m-d H:i'),
            ],
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
        $ok = $id > 0 ? sblDelete($id) : false;
        echo json_encode(['returnClass' => $ok ? 'success' : 'error', 'id' => $id,
                          'message' => $ok ? '' : 'Delete failed - no database connection or a DB error.']);
        break;

    case 'deleteAll':
        $ok = sblDeleteAll();
        echo json_encode(['returnClass' => $ok ? 'success' : 'error',
                          'message' => $ok ? '' : 'Delete all failed - no database connection or a DB error.']);
        break;

    case 'gsSearch':
        // Coin dropdown: search the learned path memory (0 API calls).
        $s = gsSearch((string) ($_POST['q'] ?? ''));
        echo json_encode(['returnClass' => $s['ok'] ? 'success' : 'error',
                          'matches' => $s['matches'], 'message' => $s['error']]);
        break;

    case 'gsRoots':
        // Drill-down 1: the broad trees (US Coins, US Currency, ...). 0 API calls.
        echo json_encode(['returnClass' => 'success', 'matches' => gsMemRoots()]);
        break;

    case 'gsSeries':
        // Drill-down 2: coin-holding series under a root, searchable. 0 API calls.
        echo json_encode(['returnClass' => 'success',
                          'matches' => gsMemSeries((string) ($_POST['root'] ?? ''), (string) ($_POST['q'] ?? ''))]);
        break;

    case 'gsNodeYears':
        // Year dropdown for a series (deduplicated). 0 API calls.
        echo json_encode(['returnClass' => 'success', 'years' => gsMemYears((string) ($_POST['path'] ?? ''))]);
        break;

    case 'gsCoins':
        // Drill-down 3: coins under the series, optional year filter. 0 API calls.
        echo json_encode(['returnClass' => 'success',
                          'matches' => gsMemCoins((string) ($_POST['path'] ?? ''),
                                                  (string) ($_POST['q'] ?? ''),
                                                  (string) ($_POST['year'] ?? ''))]);
        break;

    case 'gsYears':
        // Dynamic Year dropdown: only the years this series exists for.
        $years = gsYearsFor((string) ($_POST['category'] ?? ''));
        echo json_encode(['returnClass' => 'success', 'years' => $years]);
        break;

    case 'gsImport':
        // Auto-fill from GreySheet: by gs_id (dropdown pick) or by navigating
        // the tree from the form's attributes (learning the path as it goes).
        $imp = gsImport($_POST);
        $rc  = !$imp['ok'] ? 'error' : (!$imp['found'] ? 'notfound' : ($imp['valid'] ? 'success' : 'warning'));
        echo json_encode(['returnClass' => $rc, 'row' => $imp['row'], 'statuses' => $imp['statuses'],
                          'messages' => $imp['messages'], 'valid' => $imp['valid'],
                          'via' => $imp['via'], 'calls' => $imp['calls'] ?? [], 'raw' => $imp['raw'] ?? null,
                          'preview_image' => $imp['preview_image'] ?? '',
                          'total_calls' => (int) ($_SESSION['gs_api_calls'] ?? 0), 'message' => $imp['error']]);
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
