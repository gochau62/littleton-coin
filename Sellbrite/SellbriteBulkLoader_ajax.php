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
require_once __DIR__ . '/SellbriteBulkLoader_model.php';        // also pulls in the logic file
require_once __DIR__ . '/SellbriteBulkLoader_admin_model.php';  // reference-data (values/lookups/rules)

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

// Use the DB reference tables when they exist; otherwise the static data file.
sblLoadReferenceOverrides();

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

    /* ---- reference-data admin (Manage Lists / Categories) ---- */

    case 'ref_grid':
        echo json_encode([
            'returnClass' => 'success',
            'values'      => sblValuesGrid($_POST['list'] ?? ''),
            'lookups'     => sblLookupsGrid($_POST['lookup'] ?? ''),
            'rules'       => sblRulesGrid(),
            'lists'       => array_keys(Schema::values()),
            'fields'      => array_column(Schema::columns(), 'name'),
        ]);
        break;

    case 'value_add':
        $ok = sblValueAdd($_POST['list_name'] ?? '', $_POST['option_value'] ?? '', $_POST['sort_order'] ?? 0);
        echo json_encode(['returnClass' => $ok ? 'success' : 'error']);
        break;
    case 'value_delete':
        echo json_encode(['returnClass' => sblValueDelete($_POST['id'] ?? 0) ? 'success' : 'error']);
        break;

    case 'lookup_add':
        $ok = sblLookupAdd($_POST['lookup_name'] ?? '', $_POST['lookup_key'] ?? '',
                           $_POST['attr_name'] ?? '', $_POST['attr_value'] ?? '');
        echo json_encode(['returnClass' => $ok ? 'success' : 'error']);
        break;
    case 'lookup_delete':
        echo json_encode(['returnClass' => sblLookupDelete($_POST['id'] ?? 0) ? 'success' : 'error']);
        break;

    case 'rule_add':
        $ok = sblRuleAdd($_POST['field_name'] ?? '', $_POST['rule_type'] ?? 'error',
                         $_POST['message'] ?? '', $_POST['condition_json'] ?? '');
        echo json_encode(['returnClass' => $ok ? 'success' : 'error',
                           'message' => $ok ? '' : 'Add failed (check the condition JSON).']);
        break;
    case 'rule_delete':
        echo json_encode(['returnClass' => sblRuleDelete($_POST['id'] ?? 0) ? 'success' : 'error']);
        break;

    case 'ref_seed':
        echo json_encode(['returnClass' => 'success'] + sblRefSeedFromFile());
        break;

    default:
        echo json_encode(['returnClass' => 'error', 'message' => 'Unknown action']);
}
