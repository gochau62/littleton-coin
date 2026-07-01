<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGreysheet_agent.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * GreySheet -> Sellbrite product agent.
 *
 * Pulls a coin from the GreySheet catalog and maps its fields onto the 85
 * SBLPRODUCT columns, then runs the existing Computer (derived fields) and
 * Validator (red/amber checks) so a human can review and edit before saving
 * and exporting the Sellbrite CSV.
 *
 * The field mapping lives in one array ($GS_FIELD_MAP) so it is easy to tune
 * once we confirm the exact GreySheet response keys from the test page.
 *
 * Public surface:
 *   gsImport($params) -> ['ok','row','statuses','messages','valid','source','error']
 */

require_once __DIR__ . '/SellbriteGreysheet_client.php';   // gsResult / gsApiGet
require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Computer / Validator / Schema

/*
 * GreySheet field  ->  Sellbrite column.
 * Left side = the key as it appears in the GreySheet JSON (best guesses until
 * we see a live response; the mapper matches case-insensitively and searches
 * nested objects, so minor naming differences still resolve).  Adjust freely.
 */
if (!isset($GLOBALS['GS_FIELD_MAP'])) {
    $GLOBALS['GS_FIELD_MAP'] = [
        'Year'         => 'year',
        'MintMark'     => 'mint_mark',
        'Mint'         => 'mint_location',
        'Denomination' => 'denomination',
        'Series'       => 'category_name',      // rough Sellbrite category seed
        'Variety'      => 'coin_variety_1',
        'Grade'        => 'grade',
        'Composition'  => 'composition',
        'Metal'        => 'composition',
        'Designation'  => 'designation_abbrivation',
        // Pricing: GreySheet is wholesale/guide, so treat as suggestions.
        'CpgVal'       => 'price',              // collector price guide -> retail suggestion
        'GreyVal1'     => 'cost',              // greysheet bid -> cost suggestion
    ];
}

/** Recursively find the first scalar value for $key (case-insensitive) in a decoded response. */
function gs_dig($data, $key)
{
    $key = strtolower($key);
    if (!is_array($data)) { return null; }
    foreach ($data as $k => $v) {
        if (is_string($k) && strtolower($k) === $key && (is_scalar($v) || $v === null)) {
            return $v;
        }
    }
    foreach ($data as $v) {
        if (is_array($v)) {
            $hit = gs_dig($v, $key);
            if ($hit !== null && $hit !== '') { return $hit; }
        }
    }
    return null;
}

/** Map one decoded GreySheet item onto a partial SBLPRODUCT row (only fields we can fill). */
function gsMapToProduct(array $gsItem): array
{
    $row = [];
    foreach ($GLOBALS['GS_FIELD_MAP'] as $gsKey => $sblField) {
        $val = gs_dig($gsItem, $gsKey);
        if ($val === null || $val === '') { continue; }
        // First non-empty source wins (e.g. Composition before Metal).
        if (!isset($row[$sblField]) || $row[$sblField] === '') {
            $row[$sblField] = is_scalar($val) ? trim((string) $val) : $val;
        }
    }
    return $row;
}

/**
 * High-level import: fetch a GreySheet item, map it, and run Computer + Validator.
 *   $params: ['node_id' => N]  (GetNodeRequest) or ['path' => '...', ...extra query]
 * Returns a structure the AJAX endpoint hands straight to the form.
 */
function gsImport(array $params): array
{
    $out = ['ok' => false, 'row' => [], 'statuses' => [], 'messages' => [],
            'valid' => false, 'source' => null, 'error' => ''];

    // Decide which endpoint to call.
    if (!empty($params['path'])) {
        $path  = preg_replace('/[^A-Za-z0-9_\/]/', '', (string) $params['path']);
        $query = array_diff_key($params, ['path' => 1, 'action' => 1]);
        $res   = gsResult($path, $query);
    } else {
        $nodeId = (int) ($params['node_id'] ?? 0);
        if ($nodeId <= 0) { $out['error'] = 'Provide a GreySheet node_id (or a path).'; return $out; }
        $res = gsResult('GetNodeRequest', ['NodeId' => $nodeId]);
    }

    if (!$res['ok']) { $out['error'] = $res['error'] ?: ('GreySheet HTTP ' . $res['status']); return $out; }
    $out['source'] = $res['data'];

    // Map -> compute -> validate, reusing the screen's own logic.
    $row = gsMapToProduct(is_array($res['data']) ? $res['data'] : []);
    if (!$row) { $out['error'] = 'No mappable fields in the GreySheet response (check GS_FIELD_MAP).'; return $out; }

    $row = Computer::apply($row);
    $check = Validator::check($row);
    $out['ok']       = true;
    $out['row']      = $row;
    $out['statuses'] = $check['statuses'];
    $out['messages'] = $check['messages'];
    $out['valid']    = $check['valid'];
    return $out;
}
