<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGreysheet_agent.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Coin listing agent: GreySheet (facts) + Gemini (the AI that fills the form).
 *
 *  Flow:
 *    1. gsImport()  looks the coin up in GreySheet.
 *       - Found    -> Gemini reads the GreySheet data and inserts it into the
 *                     right SBLPRODUCT fields (picking valid dropdown values),
 *                     then Computer + Validator run for human review.
 *       - Not found -> returns found=false so the screen can ask
 *                     "GreySheet doesn't have this coin - generate with AI?".
 *    2. gsGenerate() (on the user's yes) has Gemini draft the whole listing
 *       from its own knowledge for one-off / foreign coins.
 *
 *  If no Gemini key is set, mapping falls back to a simple deterministic map
 *  so the GreySheet import still works (just without the AI smarts).
 *
 *  Nothing is ever auto-saved: every result is a draft the human reviews.
 */

require_once __DIR__ . '/SellbriteGreysheet_client.php';   // gsResult (GreySheet)
require_once __DIR__ . '/SellbriteGemini_client.php';      // geminiJson  (AI)
require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema / Computer / Validator

/* ---- Deterministic fallback map (used only when no Gemini key) ---------- */
if (!isset($GLOBALS['GS_FIELD_MAP'])) {
    $GLOBALS['GS_FIELD_MAP'] = [
        'Year' => 'year', 'MintMark' => 'mint_mark', 'Mint' => 'mint_location',
        'Denomination' => 'denomination', 'Series' => 'category_name',
        'Variety' => 'coin_variety_1', 'Grade' => 'grade',
        'Composition' => 'composition', 'Metal' => 'composition',
        'CpgVal' => 'price', 'GreyVal1' => 'cost',
    ];
}

/** Recursively find the first scalar value for $key (case-insensitive) in a decoded response. */
function gs_dig($data, $key)
{
    $key = strtolower($key);
    if (!is_array($data)) { return null; }
    foreach ($data as $k => $v) {
        if (is_string($k) && strtolower($k) === $key && (is_scalar($v) || $v === null)) { return $v; }
    }
    foreach ($data as $v) {
        if (is_array($v)) { $hit = gs_dig($v, $key); if ($hit !== null && $hit !== '') { return $hit; } }
    }
    return null;
}

/** Deterministic fallback: map a GreySheet item onto a partial row with the static map. */
function gsMapToProduct(array $gsItem): array
{
    $row = [];
    foreach ($GLOBALS['GS_FIELD_MAP'] as $gsKey => $sblField) {
        $val = gs_dig($gsItem, $gsKey);
        if ($val === null || $val === '') { continue; }
        if (!isset($row[$sblField]) || $row[$sblField] === '') {
            $row[$sblField] = is_scalar($val) ? trim((string) $val) : $val;
        }
    }
    return $row;
}

/**
 * Compact spec of the listing fields for the AI prompt: name, label, required,
 * and the valid dropdown options (so the model only picks allowed values).
 * Built from the coin-relevant field groups (skips watch/stamp/advent extras).
 */
function sbl_field_spec(): string
{
    static $spec = null;
    if ($spec !== null) { return $spec; }
    $byName = Schema::byName();
    $lines  = [];
    foreach (Schema::groups() as $group => $names) {
        foreach ($names as $n) {
            if (!isset($byName[$n])) { continue; }
            $col = $byName[$n];
            $line = '- ' . $n . ' (' . $col['label'] . ')' . (!empty($col['required']) ? ' [required]' : '');
            $opts = Schema::optionsFor($col);
            if ($opts) {
                $opts = array_values(array_filter($opts, static fn($o) => strpos((string) $o, '---') !== 0));
                $line .= ' options: ' . implode(' | ', $opts);
            }
            $lines[] = $line;
        }
    }
    return $spec = implode("\n", $lines);
}

/** Keep only known field keys from an AI result, trimmed (defends against stray keys). */
function sbl_clean_ai_row($data): array
{
    if (!is_array($data)) { return []; }
    $valid = array_flip(array_column(Schema::columns(), 'name'));
    $row = [];
    foreach ($data as $k => $v) {
        if (isset($valid[$k]) && (is_scalar($v) || $v === null)) {
            $row[$k] = trim((string) $v);
        }
    }
    return $row;
}

/** Gemini reads GreySheet data and fills the fields (falls back to the static map). */
function gsAiMap(array $gsData, array &$meta = []): array
{
    if (!geminiConfigured()) { return gsMapToProduct($gsData); }
    $system = 'You are a data-entry assistant for Littleton Coin Company\'s Sellbrite listing tool. '
            . 'You are given raw data for one coin from the GreySheet price guide and a list of target '
            . 'fields. Put each piece of data into the correct field. For fields that list "options:", you '
            . 'MUST choose one of those exact options (or leave the field empty). Do not invent facts that '
            . 'are not supported by the GreySheet data. Return ONLY a JSON object whose keys are the field '
            . 'machine-names and whose values are strings.';
    $user = "TARGET FIELDS:\n" . sbl_field_spec()
          . "\n\nGREYSHEET DATA (JSON):\n" . json_encode($gsData, JSON_UNESCAPED_SLASHES);
    $out = geminiJson($system, $user, $meta);
    $row = sbl_clean_ai_row($out);
    return $row ?: gsMapToProduct($gsData);   // fall back if the AI gave nothing usable
}

/** Gemini drafts a full listing for a one-off / foreign coin from its own knowledge. */
function gsAiGenerate(string $hint, array &$meta = []): array
{
    if (!geminiConfigured()) { return []; }
    $system = 'You are a numismatic listing expert for Littleton Coin Company. GreySheet has no entry for '
            . 'this coin, so draft a complete Sellbrite listing from your own knowledge. For fields that list '
            . '"options:", choose one of those exact options. Write accurate, professional marketing copy for '
            . 'the description, features and search terms. If a fact is uncertain, leave that field empty '
            . 'rather than guessing. Return ONLY a JSON object keyed by field machine-name.';
    $user = "TARGET FIELDS:\n" . sbl_field_spec()
          . "\n\nCOIN TO LIST (what the user typed - SKU and/or description):\n" . $hint;
    return sbl_clean_ai_row(geminiJson($system, $user, $meta));
}

/** First scalar value under any of $keys (case-insensitive) directly on an assoc array. */
function gs_pick(array $assoc, array $keys)
{
    foreach ($keys as $want) {
        foreach ($assoc as $k => $v) {
            if (is_string($k) && strtolower($k) === strtolower($want) && is_scalar($v) && $v !== '') { return $v; }
        }
    }
    return null;
}

/** Recursively collect {id,label} pairs from a search response (tolerant of shape). */
function gs_collect_matches($data, array &$out): void
{
    if (!is_array($data)) { return; }
    $id    = gs_pick($data, ['id', 'nodeId', 'collectibleId', 'catalogId']);
    $label = gs_pick($data, ['name', 'title', 'displayName', 'fullName', 'description']);
    if ($id !== null && $label !== null) { $out[(string) $id] = (string) $label; }
    foreach ($data as $v) { if (is_array($v)) { gs_collect_matches($v, $out); } }
}

/**
 * Search the GreySheet catalog for a coin by free text.
 * Returns ['ok','matches'=>[['id','label'],...],'error'].  The endpoint/param
 * are configurable (GS_SEARCH_PATH / GS_SEARCH_PARAM) - confirm them in Swagger.
 */
function gsSearch(string $q): array
{
    $q = trim($q);
    if ($q === '') { return ['ok' => false, 'matches' => [], 'error' => 'Enter something to search for.']; }
    $res = gsResult(GS_SEARCH_PATH, [GS_SEARCH_PARAM => $q]);
    if (!$res['ok']) { return ['ok' => false, 'matches' => [], 'error' => $res['error'] ?: ('HTTP ' . $res['status'])]; }
    $pairs = [];
    gs_collect_matches($res['data'], $pairs);
    $matches = [];
    foreach ($pairs as $id => $label) { $matches[] = ['id' => $id, 'label' => $label]; }
    return ['ok' => true, 'matches' => array_slice($matches, 0, 50), 'error' => ''];
}

/** Deterministic GreySheet lookup. Returns ['found','data','error','status']. */
function gsLookup(array $params): array
{
    if (!empty($params['path'])) {
        $path  = preg_replace('/[^A-Za-z0-9_\/]/', '', (string) $params['path']);
        $query = array_diff_key($params, ['path' => 1, 'action' => 1]);
        $res   = gsResult($path, $query);
    } else {
        $nodeId = (int) ($params['node_id'] ?? 0);
        if ($nodeId <= 0) { return ['found' => false, 'data' => null, 'error' => 'Provide a GreySheet node_id (or path).', 'status' => 0]; }
        $res = gsResult('GetNodeRequest', ['NodeId' => $nodeId]);
    }
    // 404 (or an OK-but-empty body) means GreySheet has no such coin.
    if (!$res['ok'] && $res['status'] === 404) { return ['found' => false, 'data' => null, 'error' => '', 'status' => 404]; }
    if (!$res['ok']) { return ['found' => false, 'data' => null, 'error' => $res['error'] ?: ('HTTP ' . $res['status']), 'status' => $res['status']]; }
    $empty = !is_array($res['data']) || count($res['data']) === 0;
    return ['found' => !$empty, 'data' => $res['data'], 'error' => '', 'status' => $res['status']];
}

/** Finalize a draft row: run Computer + Validator and shape the response. */
function gs_finalize(array $row, $source, string $via): array
{
    $row   = Computer::apply($row);
    $check = Validator::check($row);
    return ['ok' => true, 'found' => true, 'row' => $row, 'statuses' => $check['statuses'],
            'messages' => $check['messages'], 'valid' => $check['valid'], 'source' => $source,
            'error' => '', 'via' => $via];
}

/**
 * Import a coin: GreySheet lookup -> Gemini fills the fields.
 * Returns found=false (no error) when GreySheet has no entry, so the caller can
 * offer to generate it with AI.
 */
function gsImport(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => ''];
    $look = gsLookup($params);
    if ($look['error'] !== '') { return array_merge($base, ['error' => $look['error']]); }
    if (!$look['found'])       { return array_merge($base, ['ok' => true]); }   // ok, but not found

    $row = gsAiMap(is_array($look['data']) ? $look['data'] : []);
    if (!$row) { return array_merge($base, ['error' => 'Could not map the GreySheet data to any field.']); }
    return gs_finalize($row, $look['data'], geminiConfigured() ? 'greysheet+ai' : 'greysheet-map');
}

/** Generate a one-off coin's listing with AI (used after the user confirms). */
function gsGenerate(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => ''];
    if (!geminiConfigured()) { return array_merge($base, ['error' => 'AI generation needs a Gemini key (GEMINI_API_KEY).']); }
    $hint = trim((string) ($params['hint'] ?? $params['sku'] ?? $params['node_id'] ?? ''));
    if ($hint === '') { return array_merge($base, ['error' => 'Describe the coin (SKU and/or text) to generate.']); }
    $row = gsAiGenerate($hint, $meta);
    if (!$row) { return array_merge($base, ['error' => 'The AI did not return a usable listing.']); }
    return gs_finalize($row, null, 'ai-generated');
}
