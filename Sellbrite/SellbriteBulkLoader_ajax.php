<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ajax.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/01/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260064                              *  -->
<!--  ***************************************************   */

// AJAX endpoint - buffer from byte 0, one stray byte corrupts an .xlsx download
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

// export streams a file (XLSX when PhpSpreadsheet exists, else CSV) - handled before JSON
if ($action === 'export') {
    $vendor = '/www/seidenphp/htdocs/vendor/autoload.php';
    if (file_exists($vendor)) { require_once $vendor; }
    // a specific market exports its SKUs (All-markets rows included) and only its columns
    $market = strtolower(trim((string) ($_GET['market'] ?? $_POST['market'] ?? 'all')));
    if ($market === '' || !in_array($market, Exporter::markets(), true)) { $market = 'all'; }
    $rows = sblGetAllFull();
    if ($market !== 'all') {
        $rows = array_values(array_filter($rows, static function ($r) use ($market) {
            $m = strtolower(trim((string) ($r['marketplace'] ?? '')));
            return $m === '' || $m === 'all' || $m === $market;
        }));
    }
    $fname = 'sellbrite_products_' . $market . '_' . date('Ymd_His');
    $ss    = Exporter::xlsx($rows, $market);
    // discard anything echoed so far - the download must start at byte 0
    while (ob_get_level() > 0) { ob_end_clean(); }
    if ($ss !== null) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fname . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '.csv"');
    echo Exporter::csv($rows, $market);
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
        // incomplete required fields warn but never block the save
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

    case 'lccSearch':
        // type-ahead list for the LCC SKU box
        echo json_encode(['returnClass' => 'success', 'matches' => lccSearch((string) ($_POST['q'] ?? ''))]);
        break;

    case 'lccLookup':
        // LCC SKU -> item master description/date + the GreySheet coins it matches
        $lcc = lccLookup((string) ($_POST['sku'] ?? ''));
        echo json_encode(['returnClass' => $lcc['ok'] ? 'success' : 'error', 'item' => $lcc['item'],
                          'fields' => $lcc['fields'] ?? [], 'picked' => $lcc['picked'] ?? false, 'sure' => $lcc['sure'] ?? false, 'via' => $lcc['via'] ?? '',
                          'matches' => $lcc['matches'], 'message' => $lcc['error']]);
        break;

    case 'gsImport':
        // autofill by the dropdown pick's gs_id
        $imp = gsImport($_POST);
        $rc  = !$imp['ok'] ? 'error' : (!$imp['found'] ? 'notfound' : ($imp['valid'] ? 'success' : 'warning'));
        echo json_encode(['returnClass' => $rc, 'row' => $imp['row'], 'statuses' => $imp['statuses'],
                          'messages' => $imp['messages'], 'valid' => $imp['valid'],
                          'via' => $imp['via'], 'calls' => $imp['calls'] ?? [], 'raw' => $imp['raw'] ?? null,
                          'preview_image' => $imp['preview_image'] ?? '',
                          'total_calls' => (int) ($_SESSION['gs_api_calls'] ?? 0), 'message' => $imp['error']]);
        break;

    case 'gsListingFill':
        // Gemini writes the EMPTY listing boxes only
        $r = gsListingFill($_POST);
        echo json_encode(['returnClass' => $r['ok'] ? 'success' : 'error',
                          'row' => $r['row'], 'message' => $r['error']]);
        break;

    /* ---- the Sellbrite Data screen (staff-managed overrides in SBLCONFIGT) ---- */

    case 'cfgLoad':
        // everything the data screen shows, with the overrides already applied
        $fields = [];
        foreach (Schema::columns() as $col) {
            $opts = Schema::optionsFor($col);
            if (!$opts) { continue; }
            $fields[] = ['name' => $col['name'], 'label' => $col['label'], 'options' => $opts];
        }
        $cats = [];
        foreach (Schema::optionsFor(['name' => 'category_name', 'dropdown' => 'store_category']) as $c) {
            if (preg_match('/^-{2,}/', $c)) { continue; }
            $cats[] = array_merge(['category' => $c],
                array_merge(['copy' => '', 'alt1' => '', 'alt2' => ''], Schema::categoryCopy($c)));
        }
        $mkOv = sblCfgAll('MARKET');
        $cols = [];
        foreach (Exporter::layout() as $c) {
            $c['set'] = strtolower(trim((string) ($mkOv[$c['name']] ?? '')));
            $cols[] = $c;
        }
        echo json_encode(['returnClass' => 'success', 'fields' => $fields, 'cats' => $cats,
                          'cols' => $cols, 'valueOverrides' => array_keys(sblCfgAll('VALUES'))]);
        break;

    case 'cfgSaveValues':
        $name  = trim((string) ($_POST['field'] ?? ''));
        $lines = array_values(array_filter(array_map('trim',
                     preg_split('/\r\n|\r|\n/', (string) ($_POST['values'] ?? ''))),
                     static fn($x) => $x !== ''));
        if ($name === '' || !$lines) {
            echo json_encode(['returnClass' => 'error', 'message' => 'A field name and at least one value are needed.']); break;
        }
        echo json_encode(sblCfgPut('VALUES', $name, json_encode($lines))
            ? ['returnClass' => 'success']
            : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        break;

    case 'cfgResetValues':
        echo json_encode(sblCfgDel('VALUES', trim((string) ($_POST['field'] ?? '')))
            ? ['returnClass' => 'success'] : ['returnClass' => 'error', 'message' => 'Reset failed.']);
        break;

    case 'cfgSaveCopy':
        $cat = trim((string) ($_POST['category'] ?? ''));
        if ($cat === '') { echo json_encode(['returnClass' => 'error', 'message' => 'Pick a category.']); break; }
        $v = json_encode(['copy' => (string) ($_POST['copy'] ?? ''),
                          'alt1' => (string) ($_POST['alt1'] ?? ''),
                          'alt2' => (string) ($_POST['alt2'] ?? '')]);
        echo json_encode(sblCfgPut('COPY', $cat, $v)
            ? ['returnClass' => 'success']
            : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        break;

    case 'cfgResetCopy':
        echo json_encode(sblCfgDel('COPY', trim((string) ($_POST['category'] ?? '')))
            ? ['returnClass' => 'success'] : ['returnClass' => 'error', 'message' => 'Reset failed.']);
        break;

    case 'cfgSaveMarket':
        $colName = trim((string) ($_POST['column'] ?? ''));
        $mkt = strtolower(trim((string) ($_POST['market'] ?? '')));
        if ($colName === '') { echo json_encode(['returnClass' => 'error', 'message' => 'Pick a column.']); break; }
        if ($mkt === 'base') {
            echo json_encode(sblCfgDel('MARKET', $colName)
                ? ['returnClass' => 'success'] : ['returnClass' => 'error', 'message' => 'Reset failed.']);
        } elseif (in_array($mkt, ['all', 'amazon', 'ebay', 'walmart'], true)) {
            echo json_encode(sblCfgPut('MARKET', $colName, $mkt)
                ? ['returnClass' => 'success']
                : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        } else { echo json_encode(['returnClass' => 'error', 'message' => 'Unknown market.']); }
        break;

    default:
        echo json_encode(['returnClass' => 'error', 'message' => 'Unknown action']);
}