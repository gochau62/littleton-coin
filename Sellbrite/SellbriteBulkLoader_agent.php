<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_agent.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Coin listing agent: GreySheet (facts) + Gemini (the AI that fills the form).
 * Self-contained: the GreySheet API client and the Gemini client are both
 * folded into this one file, below, followed by the agent functions.
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

require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema / Computer / Validator

/* ====================================================================== */
/*  GreySheet CDN Public API v2 client (folded in - this is the one file). */
/*                                                                        */
/*  Sends x-api-token / x-api-key, adds apiLevel, and handles errors       */
/*  (cURL / 401 / 403 / 429 / non-2xx / bad JSON) as a structured result;  */
/*  captures rate-limit / quota headers + timing for usage monitoring.     */
/* ====================================================================== */

/*
 * ----------------------------------------------------------------------------
 *  SETTINGS - edit these lines.  Do NOT commit real production keys to git.
 *     BETA (testing) : https://cpgpublicapiv2beta.greysheet.com/api
 *     PROD (live)    : https://cpgpublicapiv2.greysheet.com/api
 * ----------------------------------------------------------------------------
 *  Real catalog endpoints (there is NO text-search endpoint - the catalog is
 *  a node tree, so free-text search is served from the local cache):
 *     GetNodeRequest?NodeId=            one node
 *     GetNodeChildrenRequest?NodeId=    child nodes
 *     GetCollectibleByNodeRequest?NodeId=&ApiLevel=   coins in a leaf node
 *     GetCollectibleRequest?GsId=&ApiLevel=           one coin (full detail)
 *     GetPricingRequest?Gsid=&Grade=&ApiLevel=        prices by grade
 */
if (!defined('GS_BASE_URL'))  { define('GS_BASE_URL',  'https://cpgpublicapiv2beta.greysheet.com/api'); }
if (!defined('GS_API_TOKEN')) { define('GS_API_TOKEN', ''); }        // paste x-api-token
if (!defined('GS_API_KEY'))   { define('GS_API_KEY',   ''); }        // paste x-api-key
if (!defined('GS_API_LEVEL')) { define('GS_API_LEVEL', 'basic'); }   // 'basic' or 'advanced'
if (!defined('GS_TIMEOUT'))   { define('GS_TIMEOUT',   20); }        // seconds per request

if (!function_exists('gsConfig')) {
    function gsConfig()
    {
        return [
            'base_url'  => GS_BASE_URL,  'api_token' => GS_API_TOKEN, 'api_key' => GS_API_KEY,
            'api_level' => GS_API_LEVEL, 'timeout'   => GS_TIMEOUT,
        ];
    }
}

/** Log a short usage/error line through the LCC logger when present, else error_log. */
if (!function_exists('gsLog')) {
    function gsLog($msg)
    {
        $line = 'Sellbrite ' . $msg;
        if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
        else { error_log($line); }
    }
}

/**
 * GET a path under the configured base URL.
 *  $meta receives: status, error, usage (rate-limit headers), ms, url.
 * Returns the decoded JSON body (array) on success, or null on any failure.
 */
if (!function_exists('gsApiGet')) {
    function gsApiGet($path, array $params = [], array &$meta = [])
    {
        $cfg = gsConfig();
        $meta = ['status' => 0, 'error' => '', 'usage' => [], 'ms' => 0, 'url' => ''];

        if ($cfg['api_token'] === '' || $cfg['api_key'] === '') {
            $meta['error'] = 'GS_API_TOKEN / GS_API_KEY not set in SellbriteBulkLoader_agent.php';
            gsLog('config: ' . $meta['error']);
            return null;
        }

        if (!isset($params['apiLevel']) && $cfg['api_level'] !== '') { $params['apiLevel'] = $cfg['api_level']; }
        $url = rtrim($cfg['base_url'], '/') . '/' . ltrim($path, '/');
        if ($params) { $url .= '?' . http_build_query($params); }
        $meta['url'] = $url;

        $headers = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) $cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'x-api-token: ' . $cfg['api_token'],
                'x-api-key: '   . $cfg['api_key'],
                'Accept: application/json',
            ],
            CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$headers) {
                $p = explode(':', $h, 2);
                if (count($p) === 2) {
                    $name = strtolower(trim($p[0]));
                    if (strpos($name, 'ratelimit') !== false || strpos($name, 'rate-limit') !== false
                        || strpos($name, 'quota') !== false || strpos($name, 'x-api') !== false) {
                        $headers[trim($p[0])] = trim($p[1]);
                    }
                }
                return strlen($h);
            },
        ]);
        $t0   = microtime(true);
        $body = curl_exec($ch);
        $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);

        if ($body === false) {
            $meta['error'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
            curl_close($ch);
            gsLog('network ' . $meta['error'] . ' url=' . $url);
            return null;
        }
        $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $meta['usage']  = $headers;
        curl_close($ch);

        if ($meta['status'] === 401 || $meta['status'] === 403) {
            $meta['error'] = 'Auth rejected (HTTP ' . $meta['status'] . ') — check token/key and subscription tier';
            gsLog($meta['error']); return null;
        }
        if ($meta['status'] === 429) {
            $meta['error'] = 'Rate limited (HTTP 429) — back off; see usage headers';
            gsLog($meta['error'] . ' usage=' . json_encode($headers)); return null;
        }
        if ($meta['status'] < 200 || $meta['status'] >= 300) {
            $meta['error'] = 'HTTP ' . $meta['status'];
            gsLog($meta['error'] . ' url=' . $url . ' body=' . substr((string) $body, 0, 300)); return null;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $meta['error'] = 'Bad JSON: ' . json_last_error_msg();
            gsLog($meta['error'] . ' url=' . $url); return null;
        }
        if ($headers) { gsLog('usage ' . json_encode($headers) . ' ms=' . $meta['ms']); }
        return $data;
    }
}

/** Convenience wrapper returning a flat result array. */
if (!function_exists('gsResult')) {
    function gsResult($path, array $params = [])
    {
        $meta = [];
        try {
            $data = gsApiGet($path, $params, $meta);
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $e->getMessage(),
                    'usage' => [], 'ms' => 0, 'url' => ''];
        }
        return ['ok' => $data !== null, 'status' => $meta['status'], 'data' => $data,
                'error' => $meta['error'], 'usage' => $meta['usage'], 'ms' => $meta['ms'], 'url' => $meta['url']];
    }
}

/* ====================================================================== */
/*  Google Gemini client (folded in - the AI that fills the form).         */
/*                                                                        */
/*  Asks Gemini for JSON (responseMimeType) and returns the decoded object;*/
/*  handles cURL / non-2xx / blocked / bad-JSON errors and logs token use. */
/* ====================================================================== */

/*
 * ----------------------------------------------------------------------------
 *  SETTINGS - edit these lines.  Do NOT commit a real key to git.
 *     Get a key at https://aistudio.google.com/apikey
 * ----------------------------------------------------------------------------
 */
if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', ''); }                 // paste your Gemini API key
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); } // or gemini-2.5-pro
if (!defined('GEMINI_BASE'))    { define('GEMINI_BASE',    'https://generativelanguage.googleapis.com/v1beta'); }
if (!defined('GEMINI_TIMEOUT')) { define('GEMINI_TIMEOUT', 40); }                  // seconds per request

/** Is a Gemini key configured? */
if (!function_exists('geminiConfigured')) {
    function geminiConfigured()
    {
        return defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '';
    }
}

/**
 * Ask Gemini for a JSON object.
 *   $meta receives: status, error, tokens, ms, finish.
 * Returns the model's decoded JSON (array) on success, or null on any failure.
 */
if (!function_exists('geminiJson')) {
    function geminiJson($system, $user, array &$meta = [])
    {
        $meta = ['status' => 0, 'error' => '', 'tokens' => 0, 'ms' => 0, 'finish' => ''];
        if (!geminiConfigured()) {
            $meta['error'] = 'GEMINI_API_KEY not set in SellbriteBulkLoader_agent.php';
            gsLog('config: ' . $meta['error']);
            return null;
        }

        $url  = rtrim(GEMINI_BASE, '/') . '/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
        $body = json_encode([
            'systemInstruction' => ['parts' => [['text' => (string) $system]]],
            'contents'          => [['role' => 'user', 'parts' => [['text' => (string) $user]]]],
            'generationConfig'  => [
                'temperature'      => 0.2,
                'responseMimeType' => 'application/json',
                'maxOutputTokens'  => 2048,
            ],
        ], JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => (int) GEMINI_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . GEMINI_API_KEY,
            ],
        ]);
        $t0  = microtime(true);
        $raw = curl_exec($ch);
        $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);

        if ($raw === false) {
            $meta['error'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
            curl_close($ch);
            gsLog('gemini network ' . $meta['error']);
            return null;
        }
        $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resp = json_decode($raw, true);
        if ($meta['status'] < 200 || $meta['status'] >= 300) {
            $meta['error'] = 'Gemini HTTP ' . $meta['status'] . ': '
                           . ($resp['error']['message'] ?? substr((string) $raw, 0, 200));
            gsLog($meta['error']);
            return null;
        }

        $meta['tokens'] = (int) ($resp['usageMetadata']['totalTokenCount'] ?? 0);
        $meta['finish'] = (string) ($resp['candidates'][0]['finishReason'] ?? '');
        $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') {
            $meta['error'] = 'Gemini returned no content (finish=' . $meta['finish'] . ')';
            gsLog($meta['error']);
            return null;
        }

        $data = json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Occasionally the model wraps JSON in prose/fences — salvage the object.
            if (preg_match('/\{.*\}/s', $text, $m) && ($data = json_decode($m[0], true)) !== null) {
                // recovered
            } else {
                $meta['error'] = 'Gemini JSON parse: ' . json_last_error_msg();
                gsLog($meta['error']);
                return null;
            }
        }
        gsLog('gemini ok tokens=' . $meta['tokens'] . ' ms=' . $meta['ms']);
        return is_array($data) ? $data : null;
    }
}

/* ---- Deterministic fallback map (used only when no Gemini key) ----------
 * Left = real GetCollectibleRequest field, right = Sellbrite column. */
if (!isset($GLOBALS['GS_FIELD_MAP'])) {
    $GLOBALS['GS_FIELD_MAP'] = [
        'CoinDate'          => 'year',
        'MintMark'          => 'mint_mark',
        'DenominationShort' => 'denomination',
        'Variety'           => 'coin_variety_1',
        'Variety2'          => 'coin_variety_2',
        'Composition'       => 'composition',
        'Fineness'          => 'fineness',
        'StrikeType'        => 'strike_type',
    ];
}

/** Pull the collectible object out of a wrapped GreySheet response ({ "Data":[ {...} ] }). */
function gs_data_first($resp)
{
    if (is_array($resp) && isset($resp['Data'][0]) && is_array($resp['Data'][0])) { return $resp['Data'][0]; }
    return is_array($resp) ? $resp : [];
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

/** Deterministic fallback: map one GreySheet collectible onto a partial row.
 * Reads the documented fields directly (no deep recursion, so CatalogPath node
 * fields are not mistaken for coin fields). */
function gsMapToProduct(array $gsItem): array
{
    $row = [];
    foreach ($GLOBALS['GS_FIELD_MAP'] as $gsKey => $sblField) {
        $val = $gsItem[$gsKey] ?? null;
        if (is_scalar($val) && trim((string) $val) !== '') { $row[$sblField] = trim((string) $val); }
    }
    // Coin weight (ounces) -> package weight (pounds), a starting point for review.
    if (isset($gsItem['WeightOunces']) && is_numeric($gsItem['WeightOunces']) && (float) $gsItem['WeightOunces'] > 0) {
        $row['package_weight'] = (string) round((float) $gsItem['WeightOunces'] / 16, 4);
    }
    // Deepest node in the catalog path is the closest thing to a category seed.
    if (!empty($gsItem['CatalogPath']) && is_array($gsItem['CatalogPath'])) {
        $last = end($gsItem['CatalogPath']);
        if (is_array($last) && !empty($last['Name'])) { $row['category_name'] = trim((string) $last['Name']); }
    }
    return $row;
}

/** Fetch one collectible's full detail by GreySheet id (GetCollectibleRequest). */
function gsCollectible($gsId): array
{
    $gsId = (int) $gsId;
    if ($gsId <= 0) { return []; }
    $res = gsResult('GetCollectibleRequest', ['GsId' => $gsId]);
    return $res['ok'] ? gs_data_first($res['data']) : [];
}

/** Fetch pricing for a coin (GetPricingRequest); optionally at one numeric grade. */
function gsPricing($gsId, $grade = null): array
{
    $gsId = (int) $gsId;
    if ($gsId <= 0) { return []; }
    $params = ['Gsid' => $gsId];
    if ($grade !== null && $grade !== '' && ctype_digit((string) $grade)) { $params['Grade'] = (int) $grade; }
    $res = gsResult('GetPricingRequest', $params);
    if (!$res['ok']) { return []; }
    $first = gs_data_first($res['data']);
    return (is_array($first) && isset($first['PricingData'][0])) ? $first['PricingData'][0] : [];
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
 * Search the coin catalog by free text.
 *
 * GreySheet has NO text-search endpoint (the catalog is a node tree), so this
 * searches the local catalog cache once it exists (SBLGSCATT, populated by a
 * scheduled crawl of GetNodeChildrenRequest / GetCollectibleByNodeRequest).
 * Until that table is built it returns a clear message instead of guessing.
 */
function gsSearch(string $q): array
{
    $q = trim($q);
    if ($q === '') { return ['ok' => false, 'matches' => [], 'error' => 'Enter something to search for.']; }
    if (function_exists('sblGsCatalogSearch')) {          // provided once the cache is built
        return ['ok' => true, 'matches' => sblGsCatalogSearch($q), 'error' => ''];
    }
    return ['ok' => false, 'matches' => [],
            'error' => 'Catalog search is not available yet (the GreySheet cache table has not been built).'];
}

/** Deterministic GreySheet lookup. Returns ['found','data','error','status']. */
function gsLookup(array $params): array
{
    $nodeId = (int) ($params['node_id'] ?? 0);
    $q      = trim((string) ($params['q'] ?? ''));
    if (!empty($params['path'])) {
        $path  = preg_replace('/[^A-Za-z0-9_\/]/', '', (string) $params['path']);
        $query = array_diff_key($params, ['path' => 1, 'action' => 1, 'q' => 1]);
        $res   = gsResult($path, $query);
    } elseif ($nodeId > 0) {
        $res = gsResult('GetNodeRequest', ['NodeId' => $nodeId]);
    } elseif ($q !== '') {
        // Free text (the coin typed in the form): search, then take the best match.
        $search = gsSearch($q);
        if (!$search['ok'])      { return ['found' => false, 'data' => null, 'error' => $search['error'], 'status' => 0]; }
        if (!$search['matches']) { return ['found' => false, 'data' => null, 'error' => '', 'status' => 404]; }  // nothing -> offer generate
        $res = gsResult('GetNodeRequest', ['NodeId' => (int) $search['matches'][0]['id']]);
    } else {
        return ['found' => false, 'data' => null, 'error' => 'Enter a coin (category / year) or a node id.', 'status' => 0];
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

/** Clean a GreySheet price string ("$1,234.50") to a plain number, or '' . */
function gs_price_num($v): string
{
    $v = preg_replace('/[^0-9.]/', '', (string) $v);
    return is_numeric($v) ? $v : '';
}

/* ------------------------------------------------------------------ */
/*  Live tree navigation - find a coin's GsId without knowing the path */
/*                                                                     */
/*  No cache: walk the node tree from the US Coins root, letting the   */
/*  coin's own attributes (category / year / mint / grade) choose each */
/*  turn.  String-match when a folder is obvious, ask Gemini when it   */
/*  is not (e.g. it knows a Morgan lives under "Dollars").  ~4-6 calls. */
/* ------------------------------------------------------------------ */

if (!defined('GS_ROOT_NODE')) { define('GS_ROOT_NODE', 1); }   // "U.S. Coins"

/** Normalize a name for loose matching. */
function gs_norm($s)
{
    return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', (string) $s))));
}

/** A node's children as [ ['id','name','nodes','coins'], ... ]. */
function gsChildren($nodeId): array
{
    $res = gsResult('GetNodeChildrenRequest', ['NodeId' => (int) $nodeId]);
    if (!$res['ok'] || !isset($res['data']['Data'])) { return []; }
    $out = [];
    foreach ($res['data']['Data'] as $c) {
        if (!is_array($c)) { continue; }
        $out[] = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                  'nodes' => (int) ($c['NodeChildrenCountLive'] ?? 0),
                  'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0)];
    }
    return $out;
}

/** Choose the child folder that leads toward the target (string-match, then Gemini). */
function gsNavPick(array $children, string $target, string $context)
{
    $t = gs_norm($target);
    $hits = [];
    foreach ($children as $c) {
        $n = gs_norm($c['name']);
        if ($n !== '' && (strpos($t, $n) !== false || strpos($n, $t) !== false)) { $hits[] = $c; }
    }
    if (count($hits) === 1) { return $hits[0]; }                    // unambiguous

    if (geminiConfigured()) {
        $list = [];
        foreach ($children as $c) { $list[] = $c['id'] . ' = ' . $c['name']; }
        $sys = 'You navigate a coin catalog tree. Given a target coin and a list of catalog folders '
             . '(each "id = name"), pick the ONE folder that leads to that coin. Consider proof vs '
             . 'business strike from the grade. Return ONLY JSON {"id": <id>}.';
        $out = geminiJson($sys, "TARGET COIN: $context\n\nFOLDERS:\n" . implode("\n", $list), $m);
        $id  = (int) ($out['id'] ?? 0);
        foreach ($children as $c) { if ($c['id'] === $id) { return $c; } }
    }
    return $hits[0] ?? null;                                        // best string hit, or give up
}

/** At a leaf node, pick the exact coin's GsId by year / mint / grade. */
function gsPickCoinFromLeaf(int $nodeId, array $coin): int
{
    $res = gsResult('GetCollectibleByNodeRequest', ['NodeId' => $nodeId, 'apiLevel' => GS_API_LEVEL]);
    if (!$res['ok'] || !isset($res['data']['Data'])) { return 0; }
    $coins = array_values(array_filter($res['data']['Data'], 'is_array'));
    $year  = trim((string) ($coin['year'] ?? ''));
    $mm    = trim((string) ($coin['mint_mark'] ?? ''));

    // Narrow to the right date first (leaf lists can be hundreds of coins).
    $cands = [];
    foreach ($coins as $c) {
        if ($year !== '' && (string) ($c['CoinDate'] ?? '') !== $year && strpos((string) ($c['Name'] ?? ''), $year) === false) { continue; }
        $cands[] = $c;
    }
    if (!$cands) { $cands = $coins; }
    if (count($cands) === 1) { return (int) ($cands[0]['Gsid'] ?? 0); }

    // Prefer a matching mint mark when we have one.
    if ($mm !== '' && strcasecmp($mm, 'No Mint Mark') !== 0) {
        $mmHits = array_values(array_filter($cands, static fn($c) => strcasecmp((string) ($c['MintMark'] ?? ''), $mm) === 0));
        if ($mmHits) { $cands = $mmHits; }
    }
    if (count($cands) === 1) { return (int) ($cands[0]['Gsid'] ?? 0); }

    // Still ambiguous (grade colour / variety): let Gemini pick, else take the first.
    if (geminiConfigured()) {
        $list = []; foreach ($cands as $c) { $list[] = (int) ($c['Gsid'] ?? 0) . ' = ' . (string) ($c['Name'] ?? ''); }
        $desc = trim(implode(' ', array_filter([$coin['year'] ?? '', $coin['mint_mark'] ?? '',
                    $coin['category_name'] ?? '', $coin['grade'] ?? '', $coin['strike_type'] ?? ''])));
        $sys = 'Pick the ONE catalog coin that best matches the target (match grade colour BN/RB/RD, '
             . 'proof/business, and variety). Return ONLY JSON {"id": <GsId>}.';
        $out = geminiJson($sys, "TARGET: $desc\n\nCOINS:\n" . implode("\n", $list), $m);
        $id  = (int) ($out['id'] ?? 0);
        foreach ($cands as $c) { if ((int) ($c['Gsid'] ?? 0) === $id) { return $id; } }
    }
    return (int) ($cands[0]['Gsid'] ?? 0);
}

/**
 * Resolve a coin (from the form's category / year / mint / grade) to a GreySheet
 * GsId by walking the tree live.  Returns 0 if it cannot be found.
 */
function gsResolveGsId(array $coin, array &$trace = []): int
{
    $category = trim((string) ($coin['category_name'] ?? ''));
    $desc = trim(implode(' ', array_filter([$coin['year'] ?? '', $coin['mint_mark'] ?? '', $category,
                $coin['denomination'] ?? '', $coin['grade'] ?? '', $coin['strike_type'] ?? ''])));
    if ($category === '' && $desc === '') { return 0; }
    $target = $category !== '' ? $category : $desc;

    $nodeId = GS_ROOT_NODE;
    for ($depth = 0; $depth < 8; $depth++) {
        $children = gsChildren($nodeId);
        if (!$children) { return 0; }
        $pick = gsNavPick($children, $target, $desc);
        if (!$pick) { return 0; }
        $trace[] = $pick['id'] . ':' . $pick['name'];
        if ($pick['coins'] > 0) { return gsPickCoinFromLeaf($pick['id'], $coin); }   // leaf reached
        $nodeId = $pick['id'];
    }
    return 0;
}

/**
 * Import a coin the user picked (by GreySheet id): fetch its full detail +
 * pricing with the real endpoints, map, then Gemini fills the rest.
 *   $params['gs_id']  the collectible's GsId (from the catalog picker)
 *   $params['grade']  optional numeric grade to price at
 * Falls back to a free-text lookup (needs the cache) when no gs_id is given.
 */
function gsImport(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => ''];

    // Either the caller already knows the GsId, or we navigate the tree to find it.
    $gsId = (int) ($params['gs_id'] ?? 0);
    if ($gsId <= 0) {
        $trace = [];
        $gsId  = gsResolveGsId($params, $trace);
        if ($gsId <= 0) { return array_merge($base, ['ok' => true]); }   // not found -> offer to generate
        gsLog('resolved ' . ($params['category_name'] ?? '') . ' -> GsId ' . $gsId . ' via ' . implode(' > ', $trace));
    }
    $coin = gsCollectible($gsId);
    if (!$coin) { return array_merge($base, ['ok' => true]); }

    // Live pricing for this coin (CpgVal -> retail suggestion, GreyVal -> cost).
    if (($gsId > 0) || isset($coin['Gsid'])) {
        $price = gsPricing($gsId ?: (int) ($coin['Gsid'] ?? 0), $params['grade'] ?? null);
        if ($price) {
            $coin['CpgVal'] = $price['CpgVal'] ?? ($coin['CpgVal'] ?? '');
            $coin['GreyVal'] = $price['GreyVal'] ?? ($coin['GreyVal'] ?? '');
        }
    }

    $row = gsAiMap($coin);
    if (($coin['CpgVal'] ?? '') !== '' && ($row['price'] ?? '') === '')  { $row['price'] = gs_price_num($coin['CpgVal']); }
    if (($coin['GreyVal'] ?? '') !== '' && ($row['cost'] ?? '') === '')  { $row['cost']  = gs_price_num($coin['GreyVal']); }
    if (!$row) { return array_merge($base, ['error' => 'Could not map the GreySheet data to any field.']); }
    return gs_finalize($row, $coin, geminiConfigured() ? 'greysheet+ai' : 'greysheet-map');
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
