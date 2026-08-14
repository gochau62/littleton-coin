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
        // staff-added fields always list, even before any values are saved
        foreach (Schema::customFields() as $col) {
            $fields[] = ['name' => $col['name'], 'label' => $col['label'],
                         'options' => Schema::optionsFor($col), 'custom' => true];
        }
        // the Descriptions tab lists Des's copy categories plus any staff-added ones
        $catNames = array_keys(Schema::categoryCopyAll());
        foreach (array_keys(sblCfgAll('COPY')) as $k) {
            if (!in_array($k, $catNames, true)) { $catNames[] = $k; }
        }
        sort($catNames, SORT_NATURAL | SORT_FLAG_CASE);
        $baseCats = Schema::categoryCopyAll();
        $cats = [];
        foreach ($catNames as $c) {
            $cats[] = array_merge(['category' => $c, 'base' => isset($baseCats[$c])],
                array_merge(['copy' => '', 'alt1' => '', 'alt2' => ''], Schema::categoryCopy($c)));
        }
        $mkOv = sblCfgAll('MARKET');
        $cols = [];
        foreach (Exporter::layout() as $c) {
            $c['set'] = strtolower(trim((string) ($mkOv[$c['name']] ?? '')));
            $cols[] = $c;
        }
        $custom = [];
        foreach (sblCfgAll('COL') as $k => $j) {
            $c = json_decode((string) $j, true) ?: [];
            $custom[] = ['name' => (string) $k, 'label' => (string) ($c['label'] ?? $k),
                         'market' => (string) ($c['market'] ?? 'all'), 'value' => (string) ($c['value'] ?? '')];
        }
        echo json_encode(['returnClass' => 'success', 'fields' => $fields, 'cats' => $cats,
                          'cols' => $cols, 'custom' => $custom,
                          'valueOverrides' => array_keys(sblCfgAll('VALUES'))]);
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
        } elseif (in_array($mkt, ['all', 'amazon', 'ebay', 'walmart', 'none'], true)) {
            echo json_encode(sblCfgPut('MARKET', $colName, $mkt)
                ? ['returnClass' => 'success']
                : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        } else { echo json_encode(['returnClass' => 'error', 'message' => 'Unknown market.']); }
        break;

    case 'cfgAddCol':
        // a staff-added export column: header label, market, optional fixed text for every row
        $label = trim((string) ($_POST['label'] ?? ''));
        $mkt   = strtolower(trim((string) ($_POST['market'] ?? 'all')));
        $val   = trim((string) ($_POST['value'] ?? ''));
        $name  = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label), '_'));
        if ($label === '' || $name === '') {
            echo json_encode(['returnClass' => 'error', 'message' => 'A column header is needed.']); break;
        }
        $clash = false;
        foreach (Exporter::layout() as $c) { if ($c['name'] === $name) { $clash = true; break; } }
        if ($clash) {
            echo json_encode(['returnClass' => 'error', 'message' => 'That column is already in the standard layout.']); break;
        }
        if (!in_array($mkt, ['all', 'amazon', 'ebay', 'walmart'], true)) { $mkt = 'all'; }
        echo json_encode(sblCfgPut('COL', $name, json_encode(['label' => $label, 'market' => $mkt, 'value' => $val]))
            ? ['returnClass' => 'success']
            : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        break;

    case 'cfgDelCol':
        echo json_encode(sblCfgDel('COL', trim((string) ($_POST['column'] ?? '')))
            ? ['returnClass' => 'success'] : ['returnClass' => 'error', 'message' => 'Remove failed.']);
        break;

    case 'cfgAddField':
        // a staff-added dropdown: a box on the loader form AND a column in the export
        $label = trim((string) ($_POST['label'] ?? ''));
        $name  = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $label), '_'));
        if ($label === '' || $name === '') {
            echo json_encode(['returnClass' => 'error', 'message' => 'A header name is needed.']); break;
        }
        $clash = isset(Schema::byName()[$name]);
        foreach (Exporter::layout() as $c) { if ($c['name'] === $name) { $clash = true; break; } }
        if ($clash) {
            echo json_encode(['returnClass' => 'error', 'message' => 'That header already exists.']); break;
        }
        echo json_encode(sblCfgPut('FIELD', $name, json_encode(['label' => $label]))
            ? ['returnClass' => 'success']
            : ['returnClass' => 'error', 'message' => 'Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?']);
        break;

    case 'cfgDelField':
        $name = trim((string) ($_POST['field'] ?? ''));
        if ($name === '') { echo json_encode(['returnClass' => 'error', 'message' => 'Pick a header.']); break; }
        // the field, its value list and any market override go together
        $ok = sblCfgDel('FIELD', $name);
        sblCfgDel('VALUES', $name);
        sblCfgDel('MARKET', $name);
        echo json_encode($ok ? ['returnClass' => 'success']
                             : ['returnClass' => 'error', 'message' => 'Delete failed.']);
        break;

    default:
        echo json_encode(['returnClass' => 'error', 'message' => 'Unknown action']);
}