<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoaderAdmin_ajax.php*  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 08/13/2026                         *  -->
<!--  ***************************************************  -->
<!--  * The data screen's endpoint: valid values, the   *  -->
<!--  * per-category copy and the market columns all    *  -->
<!--  * read and write LSCDEVLIBP/SBLCONFIGT.           *  -->
<!--  * Project   - 260064                              *  -->
<!--  ***************************************************   */

ob_start();
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
require_once __DIR__ . '/SellbriteBulkLoader_model.php';

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$ok  = static fn($extra = []) => json_encode(array_merge(['returnClass' => 'success'], $extra));
$err = static fn($m) => json_encode(['returnClass' => 'error', 'message' => $m]);

switch ($action) {

    case 'load':
        // everything the screen shows, with the overrides already applied
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
        $mkOv = function_exists('sblCfgAll') ? sblCfgAll('MARKET') : [];
        $cols = [];
        foreach (Exporter::layout() as $c) {
            $c['set'] = strtolower(trim((string) ($mkOv[$c['name']] ?? '')));
            $cols[] = $c;
        }
        $ovV = function_exists('sblCfgAll') ? sblCfgAll('VALUES') : [];
        echo $ok(['fields' => $fields, 'cats' => $cats, 'cols' => $cols,
                  'valueOverrides' => array_keys($ovV)]);
        break;

    case 'saveValues':
        $name  = trim((string) ($_POST['field'] ?? ''));
        $lines = array_values(array_filter(array_map('trim',
                     preg_split('/\r\n|\r|\n/', (string) ($_POST['values'] ?? ''))),
                     static fn($x) => $x !== ''));
        if ($name === '' || !$lines) { echo $err('A field name and at least one value are needed.'); break; }
        echo sblCfgPut('VALUES', $name, json_encode($lines)) ? $ok()
            : $err('Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?');
        break;

    case 'resetValues':
        echo sblCfgDel('VALUES', trim((string) ($_POST['field'] ?? ''))) ? $ok() : $err('Reset failed.');
        break;

    case 'saveCopy':
        $cat = trim((string) ($_POST['category'] ?? ''));
        if ($cat === '') { echo $err('Pick a category.'); break; }
        $v = json_encode(['copy' => (string) ($_POST['copy'] ?? ''),
                          'alt1' => (string) ($_POST['alt1'] ?? ''),
                          'alt2' => (string) ($_POST['alt2'] ?? '')]);
        echo sblCfgPut('COPY', $cat, $v) ? $ok()
            : $err('Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?');
        break;

    case 'resetCopy':
        echo sblCfgDel('COPY', trim((string) ($_POST['category'] ?? ''))) ? $ok() : $err('Reset failed.');
        break;

    case 'saveMarket':
        $col = trim((string) ($_POST['column'] ?? ''));
        $mkt = strtolower(trim((string) ($_POST['market'] ?? '')));
        if ($col === '') { echo $err('Pick a column.'); break; }
        if ($mkt === 'base') {
            echo sblCfgDel('MARKET', $col) ? $ok() : $err('Reset failed.');
        } elseif (in_array($mkt, ['all', 'amazon', 'ebay', 'walmart'], true)) {
            echo sblCfgPut('MARKET', $col, $mkt) ? $ok()
                : $err('Save failed - is LSCDEVLIBP/SBLCONFIGT created and are you signed in?');
        } else { echo $err('Unknown market.'); }
        break;

    default:
        echo $err('Unknown action');
}
