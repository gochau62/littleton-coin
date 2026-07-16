<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_agent.php    *  -->
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

/*
 *   - Coin dropdown: searches the PATH MEMORY (DB2 table SBLMEMORYT) of every
 *     coin this screen has ever seen on GreySheet - name, GsId, node path.
 *   - Populate it with the seed crawl (SellbriteBulkLoader_seed.php)
 * 
 *   - Picking a coin calls the API (GetCollectibleRequest + GetPricingRequest)
 *     and auto-fills the form; Gemini maps the data into the right fields.
 *
 * ENDPOINTS (CDN Public API v2):
 *   GetNodeChildrenRequest?NodeId=                child folders
 *   GetCollectibleByNodeRequest?NodeId=&ApiLevel= coins in a leaf
 *   GetCollectibleRequest?GsId=&ApiLevel=         one coin, full detail
 *   GetPricingRequest?Gsid=&Grade=&ApiLevel=      prices by grade
 */
require_once __DIR__ . '/SellbriteBulkLoader_logic.php';
require_once __DIR__ . '/SellbriteBulkLoader_model.php';

// Provide Greysheet API key, token, url, and level
if (!defined('GS_BASE_URL'))   { define('GS_BASE_URL',   'https://cpgpublicapiv2.greysheet.com/api'); }
if (!defined('GS_API_TOKEN'))  { define('GS_API_TOKEN',  ''); }
if (!defined('GS_API_KEY'))    { define('GS_API_KEY',    ''); }
if (!defined('GS_API_LEVEL'))  { define('GS_API_LEVEL',  'advanced'); }
if (!defined('GS_ROOT_NODE'))  { define('GS_ROOT_NODE',  1); } 
if (!defined('GS_TIMEOUT'))    { define('GS_TIMEOUT',    200); }

// gemini 2.5 flash model current usage for free testing
if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', ''); }
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); }
if (!defined('GEMINI_BASE'))    { define('GEMINI_BASE',    'https://generativelanguage.googleapis.com/v1beta'); }
if (!defined('GEMINI_TIMEOUT')) { define('GEMINI_TIMEOUT', 400); }

/* =========================================================================
 * HTTP layer for Greysheet and Gemini
 * gsApiGet is the GreySheet caller (headers, timeout, logging);
 * geminiJson is the Gemini caller (JSON-mode, model fallback).
 * ========================================================================= */

// helpful for when trying to find greysheet error messages in debug log
function gsLog($msg)
{
    // prefix every entry so Sellbrite lines are easy to spot in the shared log
    $line = 'Greysheet ' . $msg;
    // Use LCCOnline logger 
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
    // otherwise use PHP error log
    else { error_log($line); }
}

// connection setup for GreySheet: adds the keys, enforces the timeout, records the call.
function gsApiGet($path, array $params = [], &$meta = [])
{
    // reset call report - API log panel on the form is built from this
    $meta = ['status' => 0, 'error' => '', 'ms' => 0, 'url' => ''];
    if (GS_API_TOKEN === '' || GS_API_KEY === '') {
        // no keys configure: dont try, and say why
        $meta['error'] = 'GS_API_TOKEN / GS_API_KEY not set in SellbriteBulkLoader_agent.php';
        gsLog('config: ' . $meta['error']);
        return null;
    }
    // Every GreySheet call needs an API level, default is set to 'advanced'
    if (!isset($params['apiLevel'])) { $params['apiLevel'] = GS_API_LEVEL; }
    // build the full URL for GreySheet production API; base + endpoint + safety params
    $url = rtrim(GS_BASE_URL, '/') . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    $meta['url'] = $url;

    // prepare the HTTP request
    $ch = curl_init($url);
    // return the body as a string, give up after GS_TIMEOUT. send both auth headers Greysheet requires (key + token)
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => GS_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => ['x-api-token: ' . GS_API_TOKEN, 'x-api-key: '   . GS_API_KEY, 'Accept: application/json'],
    ]);

    // microtime starts the time for the call
    $t0   = microtime(true);
    // curl_exec makes the call
    $body = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);
    // false; the request never completed at all, log network error (DNS, timeout, connection refused)
    if ($body === false) {
        $meta['error'] = 'cURL: ' . curl_error($ch);
        curl_close($ch);
        gsLog('network ' . $meta['error'] . ' url=' . $url);
        return null;
    }
    // HTTP status (200 = OK), read before the handle is closed
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // a request reached Greysheet (any HTTP), so it counts and adds to the total amount of calls in the session
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['gs_api_calls'] = (int) ($_SESSION['gs_api_calls'] ?? 0) + 1; }

    // 401/403 = wrong or expired keys
    if ($meta['status'] === 401 || $meta['status'] === 403) { $meta['error'] = 'Auth rejected (HTTP ' . $meta['status'] . ')'; gsLog($meta['error']); return null; }
    // 429 - too many calls too fast; GreySheet is throttling
    if ($meta['status'] === 429) { $meta['error'] = 'Rate limited (429)'; gsLog($meta['error']); return null; }
    // anything outside is a failure
    if ($meta['status'] < 200 || $meta['status'] >= 300) { $meta['error'] = 'HTTP ' . $meta['status']; gsLog($meta['error'] . ' url=' . $url); return null; }

    // parse response from greysheet api and turn it into a readable php array to load information
    $data = json_decode($body, true);
    if (!is_array($data)) { $meta['error'] = 'Bad JSON'; gsLog($meta['error'] . ' url=' . $url); return null; }
    if (isset($data['PermitAccess']) && $data['PermitAccess'] === false) {
        $msg = trim((string) ($data['AccessDeniedMessage'] ?? ''));
        $meta['permit'] = false;
        $meta['note']   = 'PermitAccess=false' . ($msg !== '' ? ': ' . $msg : '') . ' (basic tier - premium fields omitted)';
        gsLog($meta['note'] . ' url=' . $url);
    }
    return $data;
}

// take greysheet json response and read it to get the actual data.
function gsData($resp): array
{
    return (is_array($resp) && isset($resp['Data']) && is_array($resp['Data']))
        ? array_values(array_filter($resp['Data'], 'is_array')) : [];
}

// if no gemini key configured skip
function geminiConfigured() { return GEMINI_API_KEY !== ''; }

// asks for a JSON answer, retries on the backup model when busy.
function geminiJson($system, $user, &$meta = [])
{
    // if not key set return error 
    $meta = ['status' => 0, 'error' => '', 'tokens' => 0, 'ms' => 0];
    if (!geminiConfigured()) { $meta['error'] = 'GEMINI_API_KEY not set'; return null; }

    // The generateContent gemini endpoint, free gemini 2.5 flash model usage
    $url  = rtrim(GEMINI_BASE, '/') . '/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';

    // request using system instructions, user input, and the settings
    $body = json_encode([
        'systemInstruction' => ['parts' => [['text' => (string) $system]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => (string) $user]]]],
        'generationConfig'  => ['temperature' => 0.2, 'responseMimeType' => 'application/json', 'maxOutputTokens' => 2048],
    ], JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => GEMINI_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
    ]);

    // same startup, execute, exit as GreySheet API call
    $t0  = microtime(true);
    $raw = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($raw === false) { $meta['error'] = 'cURL: ' . curl_error($ch); curl_close($ch); gsLog('gemini ' . $meta['error']); return null; }
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // parse geminis response
    $resp = json_decode($raw, true);
    if ($meta['status'] < 200 || $meta['status'] >= 300) {
        $meta['error'] = 'Gemini HTTP ' . $meta['status'] . ': ' . ($resp['error']['message'] ?? '');
        gsLog($meta['error']);
        return null;
    }

    // return token usage data, search through json response for generated description
    $meta['tokens'] = (int) ($resp['usageMetadata']['totalTokenCount'] ?? 0);
    // model response answer sits inside $text
    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // the answer is JSON, we then parse it
    $data = json_decode($text, true);
    if (!is_array($data) && preg_match('/\{.*\}/s', (string) $text, $m)) { $data = json_decode($m[0], true); }
    if (!is_array($data)) { $meta['error'] = 'Gemini returned no usable JSON'; gsLog($meta['error']); return null; }
    gsLog('gemini ok tokens=' . $meta['tokens'] . ' ms=' . $meta['ms']);
    return $data;
}
if (!defined('SBL_GSMEM_TABLE')) { define('SBL_GSMEM_TABLE', 'LSCDEVLIBP.SBLMEMORYT'); }


/* =========================================================================
 * Used to fill the SBLMEMORYT with node and coin ids
 * Everything saved in memory from GreySheet is upserted so lookups cost 0 API calls
 * ========================================================================= */

// format strings to standardized form
function gsNorm($s): string
{
    // "Morgan-Dollars (1878) = 'morgan dollars 1878'
    return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', (string) $s))));
}

// rerun db2 connection to ensure valid user to be able to read and write
function gsMemExec(string $sql, array $params): bool
{
    // reuse the model layer DB connection, without report failure
    $conn = function_exists('sbl_conn') ? sbl_conn() : false;
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, $sql);
    return $stmt ? (bool) @db2_execute($stmt, $params) : false;
}

// select rows from memory table to put into dropdown menu
function gsMemRows(string $sql, array $params = []): array
{
    return function_exists('sbl_select') ? sbl_select($sql, $params) : [];
}

// insert one node or coin row into the dropdown menu, refresh if already there
function gsMemUpsert(string $kind, int $refId, string $name, string $path,
                     string $date = '', string $mm = '', int $parent = 0,
                     int $coinCount = 0, string $done = 'N'): void
{
    // Use name and ID to fill dropdown menus from memory
    if ($refId <= 0 || $name === '') { return; }
    $ins = gsMemExec(
        'INSERT INTO ' . SBL_GSMEM_TABLE
      . ' (kind, ref_id, parent_id, name, path, coin_date, mint_mark, coin_count, done)'
      . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$kind, $refId, $parent, $name, $path, $date, $mm, $coinCount, $done]
    );
    if (!$ins) {
        gsMemExec(
            'UPDATE ' . SBL_GSMEM_TABLE
          . ' SET parent_id = ?, name = ?, path = ?, coin_date = ?, mint_mark = ?, coin_count = ?, done = ?'
          . ' WHERE kind = ? AND ref_id = ?',
            [$parent, $name, $path, $date, $mm, $coinCount, $done, $kind, $refId]
        );
    }
}


// recorded only runs during seeding
function gsMemLearnNode(int $id, string $name, string $path, int $parent = 0,
                        int $coinCount = 0, string $done = 'N'): void
{
    gsMemUpsert('N', $id, $name, $path, '', '', $parent, $coinCount, $done);
}

// record a leaf's coins same rare paths as above.
function gsMemLearnCoins(array $coins, string $path, int $parentNodeId = 0): void
{
    foreach ($coins as $c) {
        $id = (int) ($c['Gsid'] ?? 0);
        if ($id <= 0) { continue; }
        gsMemUpsert('C', $id, (string) ($c['Name'] ?? ''), $path,
                    (string) ($c['CoinDate'] ?? ''), (string) ($c['MintMark'] ?? ''), $parentNodeId);
    }
}

// Marks a node as fully crawled.
function gsMemMarkDone(int $nodeId): void
{
    gsMemExec('UPDATE ' . SBL_GSMEM_TABLE . " SET done = 'Y' WHERE kind = 'N' AND ref_id = ?", [$nodeId]);
}

// return all node rows (used by seeder)
function gsMemNodes(): array
{
    return gsMemRows('SELECT ref_id, parent_id, name, path, coin_count, done FROM '
                   . SBL_GSMEM_TABLE . " WHERE kind = 'N'");
}

// return child node rows (used by seeder)
function gsMemNodeChildren(int $parentId): array
{
    return gsMemRows('SELECT ref_id, name, path, coin_count, done FROM ' . SBL_GSMEM_TABLE
                   . " WHERE kind = 'N' AND parent_id = ?", [$parentId]);
}

/* =========================================================================
 * dropdown menus read memory path 
 * gsMemRoots -> gsMemSeries -> gsMemYears/gsMemCoins
 * ========================================================================= */
function gsMemSearch(string $q, int $limit = 40): array
{
    // Split the search into words - EVERY word must appear somewhere in the coin's name or path.
    $words = array_filter(explode(' ', gsNorm($q)));
    if (!$words) { return []; }
    // Start from all coins ('C' rows)...
    $sql = 'SELECT ref_id, name, path FROM ' . SBL_GSMEM_TABLE . " WHERE kind = 'C'";
    $params = [];
    foreach ($words as $w) {
        // require each word, case-insensitively
        $sql .= " AND UPPER(name CONCAT ' ' CONCAT COALESCE(path, '')) LIKE ?";
        $params[] = '%' . strtoupper($w) . '%';
    }
    // alphabetical, capped at limit
    $sql .= ' ORDER BY name FETCH FIRST ' . (int) $limit . ' ROWS ONLY';
    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => $r['name'], 'path' => (string) ($r['path'] ?? '')];
    }
    return $out;
}


// replace %, _, \, inside of search name strings
function gsLikeEsc(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}


// broad trees present in memory (parent_id = 0): U.S. Coins, U.S. Currency, World Coins, World Currency
function gsMemRoots(): array
{
    $out = [];
    // no parent ids means they are the top nodes
    foreach (gsMemRows('SELECT ref_id, name, path FROM ' . SBL_GSMEM_TABLE
                     . " WHERE kind = 'N' AND parent_id = 0 ORDER BY name") as $r) {
        $out[] = ['node_id' => (int) $r['ref_id'], 'name' => (string) $r['name'],
                  'path' => (string) (($r['path'] ?? '') !== '' ? $r['path'] : $r['name'])];
    }
    return $out;
}


// the coin-holding series (leaf nodes) under a chosen root, matched by catalog path for (2. Series) menu
function gsMemSeries(string $rootPath, string $q = '', int $limit = 10000): array
{
    // only select from folders that hold coins, must have a collectable coin coint return
    $sql = 'SELECT ref_id, name, path, coin_count FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'N' AND coin_count > 0";
    $params = [];
    $rootPath = trim($rootPath);
    if ($rootPath !== '') {
        $sql .= " AND (path = ? OR path LIKE ? ESCAPE '\\')";
        $params[] = $rootPath;
        $params[] = gsLikeEsc($rootPath) . ' > %';
    }
    // words must appear in the series name or in its path
    foreach (array_filter(explode(' ', gsNorm($q))) as $w) {
        $sql .= " AND UPPER(name CONCAT ' ' CONCAT COALESCE(path, '')) LIKE ?";
        $params[] = '%' . strtoupper($w) . '%';
    }
    $sql .= ' ORDER BY name FETCH FIRST ' . (int) $limit . ' ROWS ONLY';
    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        $out[] = ['node_id' => (int) $r['ref_id'], 'name' => (string) $r['name'],
                  'path' => (string) ($r['path'] ?? ''), 'count' => (int) $r['coin_count']];
    }
    return $out;
}


// created dropdown that return the years that exist for that coin under a node (3. Years) menu
function gsMemYears(string $nodePath): array
{
    $nodePath = trim($nodePath);
    if ($nodePath === '') { return []; }
    // all coins in that series coin returns year range or specific year 1991 or (1871,-)
    $rows = gsMemRows('SELECT DISTINCT coin_date, name FROM ' . SBL_GSMEM_TABLE
                    . " WHERE kind = 'C' AND (path = ? OR path LIKE ? ESCAPE '\\')",
                    [$nodePath, gsLikeEsc($nodePath) . ' > %']);
    $years = [];
    foreach ($rows as $r) {
        // look for 4-digit year in the date column first, then check the name
        $src = ((string) ($r['coin_date'] ?? '')) . ' ' . ((string) ($r['name'] ?? ''));
        // Accept 1300-2099 storing as array keys making duplicates collapse
        if (preg_match('/\b(1[3-9]\d{2}|20\d{2})\b/', $src, $m)) { $years[$m[0]] = true; }
    }
    $out = array_keys($years);
    // sort by oldest year first when displaying dropdown
    sort($out);
    return $out;
}


// return every coin in the series, $year applied narrows the search, return full names, stripping shared prefixes (4. Coin) menu
function gsMemCoins(string $nodePath, string $q = '', string $year = '', int $limit = 50000): array
{
    $nodePath = trim($nodePath);
    if ($nodePath === '') { return []; }
    $sql = 'SELECT ref_id, name, coin_date, mint_mark FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'C' AND (path = ? OR path LIKE ? ESCAPE '\\')";
    $params = [$nodePath, gsLikeEsc($nodePath) . ' > %'];
    $year = trim($year);
    
    // year filter, must match the year inside the name hide others
    if ($year !== '') {
        $sql .= " AND (coin_date = ? OR UPPER(name) LIKE ?)";
        $params[] = $year;
        $params[] = '%' . strtoupper(gsLikeEsc($year)) . '%';
    }

    // search narrows by words in the coin name
    foreach (array_filter(explode(' ', gsNorm($q))) as $w) {
        $sql .= " AND UPPER(name) LIKE ?";
        $params[] = '%' . strtoupper($w) . '%';
    }
    $sql .= ' ORDER BY name FETCH FIRST ' . (int) $limit . ' ROWS ONLY';
    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => (string) $r['name'],
                  'coin_date' => (string) ($r['coin_date'] ?? ''), 'mint_mark' => (string) ($r['mint_mark'] ?? '')];
    }
    return $out;
}


// years for a typed category: memory first; 
function gsYearsFor(string $category, bool $liveLookup = true): array
{
    $ck = gsNorm($category);
    if ($ck === '') { return []; }

    $years = [];
    $like  = '%' . strtoupper($ck) . '%';
    $rows  = gsMemRows('SELECT DISTINCT coin_date FROM ' . SBL_GSMEM_TABLE
                     . " WHERE kind = 'C' AND UPPER(COALESCE(path, '') CONCAT ' ' CONCAT name) LIKE ?", [$like]);
    foreach ($rows as $r) {
        if (preg_match('/\d{4}/', (string) ($r['coin_date'] ?? ''), $m)) { $years[$m[0]] = true; }
    }
    $out = array_keys($years);
    sort($out);
    return $out;
}


// Fetches one coin's full fact sheet.
function gsCollectible(int $gsId, &$meta = []): array
{
    $meta = [];
    if ($gsId <= 0) { return []; }
    $resp = gsApiGet('GetCollectibleRequest', ['GsId' => $gsId], $meta);
    return gsData($resp)[0] ?? [];
}


// the coin's full catalog path from its own memory row ("World Coins > Australia > ...")
function gsMemPath(int $gsId): string
{
    return (string) (gsMemRows('SELECT path FROM ' . SBL_GSMEM_TABLE
                  . " WHERE kind = 'C' AND ref_id = ?", [$gsId])[0]['path'] ?? '');
}

// Fetches one coin's price row (often empty for world coins - GreySheet pricing is US-centric).
function gsPricing(int $gsId, $grade = null, &$meta = []): array
{
    $meta = [];
    if ($gsId <= 0) { return []; }
    $params = ['Gsid' => $gsId];
    // only pass grade when its a plain number - GreySheet rejects text here
    if ($grade !== null && ctype_digit((string) $grade)) { $params['Grade'] = (int) $grade; }
    $resp  = gsApiGet('GetPricingRequest', $params, $meta);
    // the actual price row is nested one level down, inside PricingData
    $first = gsData($resp)[0] ?? [];
    return $first['PricingData'][0] ?? [];
}


// Cleans a price string into a plain number.
function gsPriceNum($v): string
{
    $v = preg_replace('/[^0-9.]/', '', (string) $v);
    return is_numeric($v) ? $v : '';
}


/* =========================================================================
 * field normalizers (composition, category date-strip,"90% silver; 10% copper" 
 * becomes the one metal word ("Silver"), mint location, dropdown snapping)
 * ========================================================================= */
function sbl_norm_composition(string $c): string
{
    // Normalize a free-text GreySheet composition (e.g. "99.99% gold" -> "Gold", "Copper-Nickel Clad" stays).
    $l = strtolower($c);
    $pairs = [
        'copper-nickel clad' => 'Copper-Nickel Clad', 'copper-nickel' => 'Copper-Nickel',
        'copper-plated zinc' => 'Copper-Plated Zinc', 'silver clad' => 'Silver Clad',
        'manganese' => 'Manganese-Brass', 'bronze' => 'Bronze', 'sterling' => 'Sterling Silver',
        'gold' => 'Gold', 'silver' => 'Silver', 'platinum' => 'Platinum', 'palladium' => 'Palladium',
        'nickel' => 'Copper-Nickel', 'copper' => 'Copper', 'brass' => 'Brass', 'steel' => 'Zinc-Coated Steel',
        'zinc' => 'Copper-Plated Zinc', 'aluminum' => 'Aluminum-Bronze', 'bi-metallic' => 'Bi-Metallic',
        'pewter' => 'Pewter', 'titanium' => 'Titanium', 'paper' => 'Paper',
    ];
    foreach ($pairs as $needle => $val) { if (strpos($l, $needle) !== false) { return $val; } }
    // nothing matches return the original GreySheet composition
    return trim($c);
}

// strip "(2022-2025)" style date ranges off a series name
function sbl_norm_category(string $gs): string
{
    // Remove any (...) grouping containing a 4-digit year: '(2022-2025)'
    $clean = preg_replace("/\\((?:[^)]*\\d{4}[^)]*)\\)/u", " ", $gs);
    // Also remove any bare ranges '1878', '1946-Present'
    $clean = preg_replace("/\\b\\d{4}\\s*[-\\x{2013}]\\s*(?:\\d{2,4}|present|date)\\b/iu", " ", $clean);
    // Trim remaining space and stray dashes
    $clean = trim(preg_replace("/\\s+/", " ", $clean), " -\t");
    return $clean !== "" ? $clean : trim($gs);
}

// Mint mark letter to city ("D" -> "Denver, Colorado").
function sbl_mint_location(string $mm): string
{
    $mm = trim($mm);
    // No mint mark = no location claim - leave it for the operator).
    if ($mm === '' || strcasecmp($mm, 'No Mint Mark') === 0) { return ''; }
    $map = ['C' => 'Charlotte', 'CC' => 'Carson City', 'D' => 'Denver', 'O' => 'New Orleans',
            'P' => 'Philadelphia', 'S' => 'San Francisco', 'W' => 'West Point',
            'M' => 'Manila', 'MO' => 'Mexico City'];
    return $map[strtoupper($mm)] ?? '';
}

// Snaps an almost-right value onto the exact valid option.
function sbl_snap(string $v, array $opts): string
{
    $v = trim($v);
    if ($v === '') { return ''; }
    foreach ($opts as $o) { if ($o === $v) { return $o; } }
    foreach ($opts as $o) { if (strcasecmp($o, $v) === 0) { return $o; } }
    return $v;
}


/* =========================================================================
 * the AI writing brief - per-field guides, option lists, prompt spec, response cleanup
 * ========================================================================= */
// per-field guide: source, allowed options, house examples - drives both the
// deterministic map and the Gemini prompt; edit this wording to change how the AI writes
function sbl_field_guide(): array
{
    static $g = null;
    if ($g !== null) { return $g; }
    $strike = ['Business','Burnished','Enhanced Uncirculated','Matte','Proof-Like','Satin','Specimen','Proof','Brilliant Proof','Reverse Proof','Satin Proof'];
    $style  = ['Circulated','Uncirculated','Mint','Cleaned','Damaged','Error','Proof','Classic Commemorative','Modern Commemorative','Pattern','Over Date','Repunched Date'];
    $comp   = ['Bronze','Copper','Copper Alloy','Copper-Nickel','Copper-Nickel Clad','Copper-Plated Zinc','Gold','Manganese-Brass','Palladium','Platinum','Silver','Silver Alloy','Silver Clad','Zinc-Coated Steel','Aluminum-Bronze','Bi-Metallic','Billon','Brass','Nickel-Plated Steel','Nickel-Silver','Paper','Pewter','Sterling Silver','Titanium'];
    $cert   = ['Uncertified','ANACS','CAC','ICG','NGC','NGC & CAC','PCGS','PCGS & CAC','U.S. Mint','PCGS Banknote Grading','PCGS Currency','PMG','Legacy Currency Grading'];
    return $g = [
        'category_name'  => ['src' => 'CatalogPath (last node)', 'desc' => 'the PCC STORE CATEGORY, singular, e.g. "Lincoln Wheat Small Cent","Morgan Dollar","Silver Bullion Coin","Small Size Federal Reserve Note" - the system normalizes this; keep whatever it provides'],
        'coin_type'      => ['desc' => 'pick the ONE option from the COIN TYPE OPTIONS list (sent with the facts) that matches the series/path - names may differ slightly (path "Australia > \$2 Kookaburra" -> option "Australian Kookaburra"); copy the option EXACTLY; leave EMPTY if none fits'],
        'year'           => ['src' => 'CoinDate', 'desc' => '4-digit issue year only'],
        'mint_mark'      => ['src' => 'MintMark', 'desc' => 'mint letter (S,D,CC,O,P,W...) or exactly "No Mint Mark" if none'],
        'mint_location'  => ['src' => 'from mint_mark', 'desc' => 'CC=Carson City, D=Denver, O=New Orleans, S=San Francisco, W=West Point, P/none=Philadelphia'],
        'denomination'   => ['src' => 'DenominationShort (US) / DenominationLong (world)', 'desc' => 'face value, e.g. 1C, 50C, $1 for US; "5 Euros" spoken form for world coins'],
         'coin_variety_1' => ['src' => 'Variety', 'desc' => 'REWRITE so it keeps ONLY what category_name does not already say, judged by MEANING not spelling - "Kookaburra" inside "\$1 Kookaburra, 1 Ounce Silver" adds nothing, return ""; never add words that were not in the original'],
        'coin_variety_2' => ['src' => 'Variety2', 'desc' => 'same rule: keep only the new part - "1oz Silver, 35th Anniversary" next to "\$1 Kookaburra, 1 Ounce Silver" -> "35th Anniversary" ("1oz Silver" = "1 Ounce Silver")'],
        'designation_abbrivation' => ['src' => 'Other (NOT Desg)', 'desc' => 'the SPECIAL strike/color designation only - color RD/RB/BN, cameo CAM/DCAM/UCAM, proof-like PL/DMPL, full-detail FB/FBL/FS/5FS/FT/FH. GreySheet puts it in "Other". "Desg" (MS/PR) is the grade TYPE, NOT this - leave blank when the coin has no special designation'],
        'grade'          => ['src' => 'pricing GradeLabel', 'desc' => 'autofilled from the pricing call\'s GradeLabel; the operator can override'],
        'strike_type'    => ['src' => 'StrikeType', 'opts' => $strike],
        'circulated_or_uncirculated' => ['desc' => 'Uncirculated for MS/PR/proof/BU/mint-state, Circulated otherwise', 'opts' => ['Circulated','Uncirculated']],
        'composition'    => ['src' => 'Composition', 'opts' => $comp],
        'fineness'       => ['src' => 'Fineness', 'desc' => 'decimal purity, e.g. 0.9, 0.999'],
        'single_coin_or_set' => ['src' => 'IsSet', 'opts' => ['Single Coin','Set'], 'const' => 'Single Coin'],
        'set_count'      => ['desc' => 'number of coins ONLY when a set; blank for single coins'],
        'bullion_shape'  => ['src' => 'CoinShape', 'opts' => ['Bar','Round'], 'desc' => 'GreySheet CoinShape; bullion categories only, blank otherwise'],
        'coin_design'    => ['opts' => ['Shield-Type Cob','Pillars-Type Cob','Milled-Pillar Type','Milled-Bust Type'],
                             'desc' => 'Spanish colonial cob/milled coinage only; blank otherwise'],
        'paper_money_type' => ['src' => 'catalog path', 'desc' => 'paper money ONLY (e.g. Banknotes, Replacement Notes); blank for coins',
                               'opts' => ['Banknotes','Bond Certificates','Cancelled Currency','Collections & Lots','Commemorative Issue',
                                          'Emergency Issue','Errors','Hawaii Overprint Note','Hologram','Military Currency',
                                          'North Africa Note','Notgeld','Polymer Notes','Replacement Notes','Specimens',
                                          'Uncut Sheets','Wartime Occupation']],
        'paper_money_grade_designation' => ['desc' => 'paper money ONLY, e.g. EPQ, PPQ, Star; blank for coins'],
        'country_of_manufacture' => ['src' => 'CatalogPath CountryName', 'desc' => 'full country name', 'const' => 'United States'],
        'certification'  => ['opts' => $cert, 'desc' => 'OPERATOR-PICKED from the valid values (grading service, or Uncertified) - leave EMPTY; do not guess'],
        'title_suffix'   => ['desc' => 'operator catch-all appended to the title (grade details, error details, packaging, slab-label text) - leave BLANK; "Coin Collectible" is added to the title automatically'],
        'precious_metal_content' => ['src' => 'WeightOunces', 'desc' => 'per-coin metal, e.g. "1 oz","0.859 oz"; blank for base metal'],
        'total_precious_metal_content' => ['src' => 'WeightOunces x Fineness', 'desc' => 'troy oz of pure precious metal, blank for base-metal coins'],
        'brand'          => ['desc' => '"U.S. Mint" for modern U.S. Mint issues (proof/mint sets, bullion, modern commems); otherwise leave blank'],
        'description'    => ['desc' => 'A natural sentence built from the ACTUAL field values, house shape: '
            . '"A genuine {year} {mint mark} {variety} {series/type} {metal} {denomination IN WORDS - Quarter, Half Dollar, Cent Penny} '
            . '{strike if special} Coin[, from {brand} when not U.S. Mint]'
            . '[, in {grade} Condition -OR- , graded and certified {grade} {designation} by {certification} when slabbed]'
            . '[, {special feature clause, e.g. privy mark}]. [Contains {content} {fineness} {Metal}. - precious metals only]" '
            . 'Example using every criteria: "A genuine 2025 W American Eagle Silver Dollar Proof Bullion Coin, '
            . 'graded and certified PR 70 DCAM by PCGS, with the special privy mark honoring the 250th anniversary '
            . 'of the United States Army. Contains 1 oz 0.999 Silver." Plain raw grade examples: '
            . '"A genuine 1943 Lincoln Wheat Steel Cent Penny Coin, in AU About Uncirculated Condition." No hype.'],
        'extended_description' => ['desc' => 'EXPANDED DESCRIPTION for the whole category: 2-4 factual sentences built from YOUR description PLUS the GreySheet GeneralNotes/Obverse/Reverse text (composition, design, designer, history). Write it so the SAME text fits every coin in this category - no grade, date, mint or price. House example: "In 1943, the U.S. Mint struck Lincoln cents in zinc-coated steel to save copper for munitions and other military materials in World War II. Each unique one-year-only issue bears Victor D. Brenner\'s Lincoln portrait obverse and Wheat Ears reverse designs."'],
        'feature_4'      => ['desc' => 'a COLLECTOR\'S NOTE about the SERIES (history, design, collector appeal) - category-level, 2-4 sentences, no this-coin grade/date/price. REWRITE the facts in YOUR OWN words: it must NOT copy or lightly rephrase the GeneralNotes or the extended_description - no shared sentences. House example: "Lincoln cents with the original Wheat Ears reverse were introduced in 1909 on the 100th anniversary of Abraham Lincoln\'s birth and were struck until 1958. These bronze cents were the first circulating U.S. coins to feature a portrait of a historical figure. Over its decades-long circulation, the Lincoln Wheat Cent only underwent one composition change. In 1943, the composition was changed from bronze to zinc-coated steel to save copper during the war." Do NOT add the "COLLECTOR\'S NOTE:" prefix; the system adds it.'],
        'diameter'       => ['src' => 'Diameter', 'desc' => 'millimeters, number only'],
        'weight'         => ['src' => 'WeightOunces', 'desc' => 'coin weight in troy ounces, number only'],
        'search_terms'   => ['desc' => '8-15 lowercase space-separated keywords: metal, type, denomination, mint, theme, "numismatics", "coin"'],
        'price'          => ['src' => 'pricing CpgVal', 'req' => true, 'desc' => 'CPG retail; the operator confirms it'],
        'cost'           => ['src' => 'pricing GreyVal', 'req' => true, 'desc' => 'wholesale (advanced tier); the operator confirms it'],
    ];
}


/* =========================================================================
 * GreySheet facts -> product row
 * gsMapToProduct = deterministic mapping, gsAiMap = mapping + Gemini copy,
 * gsListingFill = Gemini gap-fill for the Listing Content boxes only
 * ========================================================================= */
// deterministic mapping, no AI - the place that decides which fact lands in
// which box; Gemini only fills the gaps it leaves
function gsMapToProduct(array $c): array
{
    $g = static fn(string $k): string => (isset($c[$k]) && is_scalar($c[$k])) ? trim((string) $c[$k]) : '';
    $gsPathNodes = (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) ? $c['CatalogPath'] : [];
    $gsRootNames = [1 => 'u.s. coins', 2 => 'u.s. currency', 6 => 'world coins', 12 => 'world currency'];
    $gsRootName  = strtolower(trim((string) ($gsPathNodes[0]['Name'] ?? ($gsRootNames[(int) ($c['RootNode_Id'] ?? 0)] ?? ''))));
    $gsLast      = $gsPathNodes ? end($gsPathNodes) : null;
    $gsSeriesName = trim((string) (is_array($gsLast) ? ($gsLast['Name'] ?? '') : ($c['ParentNodeName'] ?? '')));
    $gsPathText  = $gsPathNodes
        ? implode(' ', array_map(static fn($n) => is_array($n) ? (string) ($n['Name'] ?? '') : '', $gsPathNodes))
        : trim($gsRootName . ' ' . $gsSeriesName);
    // Paper money (U.S./World Currency trees): the coin-only fields (mint mark and location) are never stamped onto a note.
    $isPaper = strpos($gsRootName, 'currency') !== false;
    $isWorld = strpos($gsRootName, 'world') !== false;
    $row = [];
    // "2022-D" -> year 2022 (the first 4-digit number in the coin date).
    if (preg_match('/\d{4}/', $g('CoinDate'), $m)) { $row['year'] = $m[0]; }
    if (!$isPaper) {
        $mm = $g('MintMark');
        $row['mint_mark']     = $mm !== '' ? $mm : 'No Mint Mark';
        $row['mint_location'] = sbl_mint_location($mm);
    }

    // World coins list the spoken face value ("5 Euros") - the short form's
    // leading S/G/P is a metal prefix ("S€5" = silver €5), not the value.
    // U.S. coins keep the house short form ("1C", "50C", "$1").
    if ($isWorld && $g('DenominationLong') !== '') { $row['denomination'] = $g('DenominationLong'); }
    elseif ($g('DenominationShort') !== '')        { $row['denomination'] = $g('DenominationShort'); }
    if ($g('Variety')  !== '')          { $row['coin_variety_1'] = $g('Variety'); }
    if ($g('Variety2') !== '')          { $row['coin_variety_2'] = $g('Variety2'); }

    // Designation abbreviation; color RD/RB/BN, cameo CAM/DCAM/UCAM, proof-like PL/DMPL, full-detail FB/FBL/FS/5FS/FT/FH.
    // GreySheet stores THIS in "Other" (e.g. "DCAM","FB","RD","RD DCAM"). 
    // GreySheet "Desg" is the grade TYPE (MS/PR/SP)
    if ($g('Other') !== '')             { $row['designation_abbrivation'] = $g('Other'); }
    if ($g('Composition') !== '')       { $row['composition'] = sbl_norm_composition($g('Composition')); }
    if ($g('Fineness')    !== '')       { $row['fineness']    = $g('Fineness'); }

    // diameter (mm) and weight (troy oz) straight from GreySheet.
    if ($g('Diameter') !== '')          { $row['diameter']    = $g('Diameter'); }

    // GreySheet CoinShape = Sellbrite Bullion Shape.
    if ($g('CoinShape') !== '')         { $row['bullion_shape'] = $g('CoinShape'); }
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces'])) {
        $row['weight'] = rtrim(rtrim(number_format((float) $c['WeightOunces'], 4, '.', ''), '0'), '.');
    }

    $strike    = $g('StrikeType');
    // MS / PR / PF / SP / SMS - the grade type
    $gradeType = strtoupper($g('Desg')); 
    $isProof   = stripos($strike, 'proof') !== false || stripos($g('Name'), 'proof') !== false
              || in_array($gradeType, ['PR', 'PF'], true);
    if ($strike !== '') { $row['strike_type'] = $strike; }
    
    // Mint State / Proof / Specimen are all uncirculated; circulated coins have
    // a circulated Desg or none, so leave those for the grade/operator.
    if ($isProof || in_array($gradeType, ['MS', 'PR', 'PF', 'SP', 'SMS'], true)) {
        $row['circulated_or_uncirculated'] = 'Uncirculated';
    }
    $row['single_coin_or_set'] = !empty($c['IsSet']) ? 'Set' : 'Single Coin';

    // Per-coin precious-metal content, e.g. "1 oz" (precious metals only).
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces'])
        && preg_match('/silver|gold|platinum|palladium/', strtolower($g('Composition')))) {
        $row['precious_metal_content'] = rtrim(rtrim(number_format((float) $c['WeightOunces'], 4, '.', ''), '0'), '.') . ' oz';
    }

   if ($gsSeriesName !== '' || $gsPathNodes) {
        if ($gsSeriesName !== '') {
            // SKU of Parent Product = the series name, date range stripped.
            $row['category_name'] = sbl_norm_category($gsSeriesName);
        }

        // Country: only the full CatalogPath (when present) can name it directly.
        foreach ($gsPathNodes as $node) {
            if (!empty($node['CountryName'])) { $row['country_of_manufacture'] = trim((string) $node['CountryName']); break; }
        }
        
        // World trees name the country as the path's second node even when the CountryName attribute is blank: "World Coins > Austria > ...".
        if (($row['country_of_manufacture'] ?? '') === '' && $isWorld && count($gsPathNodes) > 1) {
            $n = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($gsPathNodes[1]['Name'] ?? '')));
            if ($n !== '') { $row['country_of_manufacture'] = $n; }
        }
        // TRY to autofill coin type by using ("Morgan Dollars" -> "Morgan", "Lincoln Cents - Wheat Reverse" -> "Lincoln Wheat"). 
        if (($row['coin_type'] ?? '') === '') {
            $poolKey = ($isWorld ? 'world' : 'us') . '_' . ($isPaper ? 'currency' : 'coins');
            $hay = strtolower(($row['category_name'] ?? '') . ' ' . $gsPathText);
            $best = '';
            // GreySheet says "Silver Eagles"; the valid value is "American Eagle".
            if (preg_match('/(silver|gold|platinum|palladium) eagle/', $hay)) { $best = 'American Eagle'; }
            elseif (strpos($hay, 'gold buffalo') !== false) { $best = 'American Buffalo'; }
            else {
                // gets the tree's option list and resolves those (gsAiMap).
                foreach (Schema::coinTypePools()[$poolKey] ?? [] as $opt) {
                    $all = true;
                    foreach (preg_split('/\s+/', strtolower($opt)) as $tk) {
                        if ($tk !== '' && strpos($hay, $tk) === false) { $all = false; break; }
                    }
                    if ($all && strlen($opt) > strlen($best)) { $best = $opt; }
                }
            }
            if ($best !== '') { $row['coin_type'] = $best; }
        }
    }


    // GreySheet provides denomination, composition, fineness and weight with the coin; nothing per-category is stored;
    // the parent SKU is the series name itself (dates stripped).
    // Precious-metal content = metal weight x fineness (troy oz), precious metals only.
    $fin = (float) preg_replace('/[^0-9.]/', '', $g('Fineness'));
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces']) && $fin > 0 && $fin <= 1) {
        $comp = strtolower($g('Composition'));
        if (preg_match('/silver|gold|platinum|palladium/', $comp)) {
            $row['total_precious_metal_content'] = rtrim(rtrim(number_format((float) $c['WeightOunces'] * $fin, 4, '.', ''), '0'), '.') . ' oz';
        }
    }

    // Features 1/2/3/5 are derived by Computer
    // title_suffix is left blank for the operator's grade/error/packaging notes.)
    $row['exact_image']   = SBL_EXACT_IMAGE_DEFAULT;
    // Brand from GreySheet's image attribution when it carries one;
    if ($g('FeaturedImageAttribution') !== '') { $row['brand'] = $g('FeaturedImageAttribution'); }
    // United States ONLY when the path root is explicitly a U.S. tree; any other/unknown root leaves the country alone 
    if (($row['country_of_manufacture'] ?? '') === '' && preg_match('/^u\.?s\.?\b|united states/', $gsRootName)) {
        $row['country_of_manufacture'] = 'United States';
    }
    return array_filter($row, static fn($v) => $v !== '' && $v !== null);
}


// compact, populated view of the coin information sent to the agent for insertion
function gs_coin_facts(array $c): array
{
    $keys = ['Name','CoinDate','MintMark','DenominationShort','DenominationLong','Variety','Variety2',
             'Desg','Other','Prefix','Composition','Fineness','StrikeType','WeightOunces','WeightGrams','Diameter',
             'Designer','Edge','Mintage','Rarity','CoinShape','PcgsNumber','IsSet','IsType','CpgVal','GreyVal',
             'FriedbergNumber','BnBNumber','PickNumber','HaxbyNumber','Krause','NoteColor','NoteDimension',
             'Watermark','Printer','NoteSecurityThread','NotePaperType','BnbSignatureName1','BnbSignatureName2',
             'BnbSignatureName3','ObsoleteStateName','ObsoleteCityName','ObsoleteBankName','IssueYear','Variant',
             'KeyComment1','KeyComment2','KeyComment3','ArtComment1','ArtComment2','ArtComment3',
             'ObverseDesigner','ReverseDesigner','GeneralCoinLettering','IsRedbook',
             'GeneralNotes','ObverseDescription','ReverseDescription','ObverseLettering','ReverseLettering',
             'PriceLow','PriceHigh'];
    $out = [];
    foreach ($keys as $k) {
        // trim strings - GreySheet ships stray \r ("Morgenthau\r"); blanks/zeros stay out of the prompt
        $v = isset($c[$k]) && is_string($c[$k]) ? trim($c[$k]) : ($c[$k] ?? null);
        if ($v !== '' && $v !== null && $v !== 0 && $v !== '0') { $out[$k] = $v; }
    }
    if (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) {
        $out['CatalogPath'] = implode(' > ', array_map(static fn($n) => (string) ($n['Name'] ?? ''), $c['CatalogPath']));
            } elseif (!empty($c['ParentNodeName'])) {
        // series name sent to the agent
        $out['CatalogPath'] = trim((string) $c['ParentNodeName']);
    }
    return $out;
}

// one field's allowed options from the "Valid Values" sheet
function sbl_field_options(string $name): array
{
    $col = Schema::byName()[$name] ?? null;
    $opts = $col ? Schema::optionsFor($col) : [];
    if (!$opts) { $opts = sbl_field_guide()[$name]['opts'] ?? []; }
    return array_values(array_filter($opts, static fn($o) => !preg_match('/^\s*(-{2,}|\*{3})/', (string) $o)));
}

// turn the field guide into the prompt's TARGET FIELDS text
function sbl_field_spec(): string
{
    static $spec = null;
    if ($spec !== null) { return $spec; }
    $byName = Schema::byName();
    $lines  = [];
    foreach (sbl_field_guide() as $name => $gd) {
        $label = $byName[$name]['label'] ?? $name;
        // One prompt line per field: name (label) [required]: guidance [source] [default] [allowed values].
        $line  = '- ' . $name . ' (' . $label . ')' . (!empty($gd['req']) ? ' [required]' : '');
        if (!empty($gd['desc']))  { $line .= ': ' . $gd['desc']; }
        if (!empty($gd['src']))   { $line .= '  [from GreySheet ' . $gd['src'] . ']'; }
        if (!empty($gd['const'])) { $line .= '  [default "' . $gd['const'] . '"]'; }
        $opts = sbl_field_options($name);
        if ($opts) {
            // Big lists (grade, country, designation) would swamp the prompt;
            // still enforced by snapping, so just point at the list there.
            $line .= count($opts) <= 80
                ? '  MUST be one of: ' . implode(' | ', $opts)
                : '  MUST be a valid Sellbrite "' . $label . '" value (snapped to the house list)';
        }
        $lines[] = $line;
    }
    return $spec = implode("\n", $lines);
}

// Tidies the AI's answer (drops invented fields, trims strings).
function sbl_clean_ai_row($data): array
{
    if (!is_array($data)) { return []; }
    $valid = array_flip(array_column(Schema::columns(), 'name'));
    $row = [];
    foreach ($data as $k => $v) {
        if (isset($valid[$k]) && (is_scalar($v) || $v === null)) { $row[$k] = trim((string) $v); }
    }
    return $row;
}

// Snaps every AI value onto the exact valid options.
function sbl_snap_row(array $row): array
{
    if (isset($row['composition']) && $row['composition'] !== '') {
        $row['composition'] = sbl_norm_composition($row['composition']);   // "99.99% gold" -> "Gold"
    }
    foreach (array_keys(sbl_field_guide()) as $f) {
        $opts = sbl_field_options($f);
        if ($opts && isset($row[$f]) && $row[$f] !== '') { $row[$f] = sbl_snap($row[$f], $opts); }
    }
    return $row;
}

// The full autofill writer: facts first 
function gsAiMap(array $coin): array
{
    $base = gsMapToProduct($coin);
    if (!geminiConfigured()) { return sbl_snap_row($base); }
    
    // The writing brief - the numbered RULES are the whole contract with the model.
    $sys = "You are the listing writer for Littleton Coin Company's Sellbrite coin listings. From the "
         . "GreySheet coin facts (name, dates, mint, composition, designer, mintage, and especially "
         . "GeneralNotes / ObverseDescription / ReverseDescription) produce the catalog fields AND the "
         . "listing copy.\n"
         . "RULES:\n"
         . "1. For any field with a \"MUST be one of\" list, copy one option EXACTLY or leave it empty. "
         . "Never invent facts - leave a field empty if the data does not support it.\n"
         . "2. WRITE THE DESCRIPTION FIRST: one natural sentence that works the ACTUAL field values into the "
         . "house shape shown in its guide (follow its full-criteria example - certified vs raw grade wording, "
         . "brand clause, privy/feature clause, then the 'Contains ...' metal sentence for precious metals). "
         . "Everything else builds on it.\n"
         . "3. extended_description is the EXPANDED DESCRIPTION for the whole category/series: write 2-4 "
         . "factual sentences by combining your description with the GreySheet GeneralNotes / obverse / "
         . "reverse text (history, composition, design, designer). It must read so the SAME text fits "
         . "EVERY coin in this category - do not mention this coin's grade, date, mint or price.\n"
         . "4. feature_4 is a COLLECTOR'S NOTE about the series (why collectors want it), also category-level. "
         . "Write it in YOUR OWN words: it must not repeat or lightly rephrase any sentence from GeneralNotes "
         . "or from extended_description - pick a different angle (series history, design lineage, collecting "
         . "appeal, key changes over the years). Do NOT add the \"COLLECTOR'S NOTE:\" label - the system adds it.\n"
         . "5. Do NOT fill feature_1, feature_2, feature_3 or feature_5 - the system derives them from the "
         . "description, condition, image line and company blurb.\n"
         . "6. DE-DUPLICATE the varieties - ALWAYS return coin_variety_1 and coin_variety_2. REWRITE each so it "
         . "keeps ONLY what category_name does not already say, judged by MEANING not spelling "
         . "(\"1oz Silver, 35th Anniversary\" next to \"\$1 Kookaburra, 1 Ounce Silver\" -> \"35th Anniversary\"; "
         . "\"Kookaburra\" alone adds nothing -> \"\"). Never add words that were not in the original variety, and "
         . "use the CLEANED varieties in the description and search terms. The title is built from these fields - "
         . "duplicated wording there reads as an error to buyers.\n"
         . "7. coin_type: pick from the COIN TYPE OPTIONS list sent after the facts. The option's wording often "
         . "differs from the path (country vs demonym: \"Australia > \$1 Kookaburra\" -> \"Australian Kookaburra\"; "
         . "singular vs plural) - still pick it when it clearly names this series. Copy it EXACTLY; leave it empty "
         . "ONLY when no option describes the coin.\n"
         . "8. Paper money: the note facts (FriedbergNumber, Printer, BnbSignatureName1/2 - the Treasury "
         . "signature pair, Watermark, NotePaperType, NoteDimension in mm, PickNumber) are real catalog data - "
         . "work them into the description and extended_description. IsRedbook true = listed in the Red Book "
         . "(good collector's-note material). PcgsNumber / Ngc / NgcId / Krause are CATALOG numbers, NEVER a "
         . "certification - do not treat them as grading.\n"
         . "Return ONLY a JSON object keyed by field machine-name.";
    // Pool root: path root name, else the reply's RootNode_Id (live replies carry no CatalogPath).
    $ctRoot = strtolower((string) ($coin['CatalogPath'][0]['Name'] ?? ''));
    if ($ctRoot === '') { $ctRoot = [1 => 'u.s. coins', 2 => 'u.s. currency', 6 => 'world coins', 12 => 'world currency'][(int) ($coin['RootNode_Id'] ?? 0)] ?? ''; }
    $ctKey  = (strpos($ctRoot, 'world') !== false ? 'world' : 'us') . '_'
            . (strpos($ctRoot, 'currency') !== false ? 'currency' : 'coins');
    $ctOpts = Schema::coinTypePools()[$ctKey] ?? [];
    // What to fill (the field spec) + what is true (the curated facts packet).
    $user = "TARGET FIELDS:\n" . sbl_field_spec() . "\n\nGREYSHEET COIN FACTS:\n"
          . json_encode(gs_coin_facts($coin), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
          . ($ctOpts ? "\n\nCOIN TYPE OPTIONS (pick ONE exactly, or leave coin_type empty):\n" . implode(' | ', $ctOpts) : '');
    // Ask Gemini; keep only real schema fields from the answer.
    $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m));
    $row = $base;
    foreach ($ai as $k => $v) { if ($v !== '' && ($base[$k] ?? '') === '') { $row[$k] = $v; } }
    // The guard only accepts words already in the original, so the AI can remove but never invent.
    foreach (['coin_variety_1', 'coin_variety_2'] as $vf) {
        if (!array_key_exists($vf, $ai) || ($base[$vf] ?? '') === '') { continue; }
        $aiV = trim((string) $ai[$vf]);
        if ($aiV === '') { $row[$vf] = ''; continue; }
        $have = preg_split('/[^a-z0-9]+/', strtolower($base[$vf]), -1, PREG_SPLIT_NO_EMPTY);
        $want = preg_split('/[^a-z0-9]+/', strtolower($aiV), -1, PREG_SPLIT_NO_EMPTY);
        if (!array_diff($want, $have)) { $row[$vf] = $aiV; }
    }
    // GeneralNotes stays the Expanded Description; 
    // the obverse + reverse design text becomes the COLLECTOR'S NOTE
    $gsClean = static function ($s): string {
        $s = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' ', (string) $s)));
        return trim((string) preg_replace('/\s+/', ' ', $s));
    };
    $gsNotes  = $gsClean($coin['GeneralNotes'] ?? '');
    $gsDesign = trim($gsClean($coin['ObverseDescription'] ?? '') . ' ' . $gsClean($coin['ReverseDescription'] ?? ''));
    if (trim((string) ($row['extended_description'] ?? '')) === '') {
        $src = $gsNotes !== '' ? $gsNotes : $gsDesign;
        if ($src !== '') { $row['extended_description'] = mb_substr($src, 0, 1900); }
    }
    if (trim((string) ($row['feature_4'] ?? '')) === '') {
        $src = $gsDesign !== '' ? $gsDesign : trim((string) ($row['extended_description'] ?? ''));
        if ($src !== '') { $row['feature_4'] = mb_substr($src, 0, 1400); }
    }
    return sbl_snap_row($row);
}

// The "Generate Product details with AI" button: writes ONLY the empty Listing Content boxes from what's on the form.
function gsListingFill(array $post): array
{
    $want = [];
    foreach (['description', 'extended_description', 'feature_4'] as $f) {
        $v = trim((string) ($post[$f] ?? ''));
        if ($v === '' || strncmp($v, '***', 3) === 0) { $want[] = $f; }
    }
    if (!$want) { return ['ok' => true, 'row' => [], 'via' => 'nothing empty', 'error' => '']; }

    $row = [];
    if ($want && geminiConfigured()) {
        // The facts = whatever is typed on the form (skipping *** placeholders) - no GreySheet needed, so watches work too.
        $facts = [];
        foreach (['sku','category_name','name','coin_type','denomination','year','mint_mark','mint_location',
                  'grade','circulated_or_uncirculated','strike_type','certification','composition','fineness',
                  'precious_metal_content','single_coin_or_set','set_count','country_of_manufacture','brand',
                  'coin_design','coin_variety_1','coin_variety_2','paper_money_type','title_suffix',
                  'description','extended_description'] as $f) {
            $v = trim((string) ($post[$f] ?? ''));
            if ($v !== '' && strncmp($v, '***', 3) !== 0) { $facts[$f] = $v; }
        }
        $guide = sbl_field_guide();
        // Only the wanted fields' guides go into the prompt.
        $spec  = '';
        foreach ($want as $f) { $spec .= '- ' . $f . ': ' . ($guide[$f]['desc'] ?? '') . "\n"; }
        $sys = "You are the listing writer for Littleton Coin Company's Sellbrite listings. Write ONLY the "
             . "requested listing-copy fields from the product facts given.\n"
             . "RULES:\n"
             . "1. Never invent facts - build only on the facts provided.\n"
             . "2. description keeps its exact one-sentence house shape.\n"
             . "3. extended_description is the EXPANDED DESCRIPTION for the whole category/series: 2-4 factual "
             . "sentences (history, composition, design) written so the SAME text fits EVERY item in this "
             . "category - no grade, date, mint or price.\n"
             . "4. feature_4 is a COLLECTOR'S NOTE about the series (why collectors want it), category-level. "
             . "Write it in YOUR OWN words: it must not repeat or lightly rephrase any sentence from the "
             . "extended_description - pick a different angle (series history, design lineage, collecting "
             . "appeal). Do NOT add the \"COLLECTOR'S NOTE:\" label - the system adds it.\n"
             . "5. Return ONLY a JSON object with EXACTLY the requested field names - no other fields.";
        $user = "FIELDS TO WRITE (only these):\n" . $spec
              . "\nPRODUCT FACTS (from the entry form):\n"
              . json_encode($facts, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m));
        foreach ($want as $f) {
            if (trim((string) ($ai[$f] ?? '')) !== '') { $row[$f] = trim((string) $ai[$f]); }
        }
    }
    $err = !$row && !geminiConfigured() ? 'GEMINI_API_KEY not set (add the secrets file).' : '';
    return ['ok' => $err === '', 'row' => $row, 'via' => 'listing gap fill', 'error' => $err];
}


/* =========================================================================
 * ajax entry points (called from _ajax.php)
 * gsSearch / gsImport / gsGenerate / gs_finalize
 * ========================================================================= */

// free-text coin search for the page
function gsSearch(string $q): array
{
    $q = trim($q);
    if ($q === '') { return ['ok' => false, 'matches' => [], 'error' => 'Type something to search for.']; }
    return ['ok' => true, 'matches' => gsMemSearch($q), 'error' => ''];
}

// last step of any import: run the computed fields, then the validator
function gs_finalize(array $row, $source, string $via, array $calls = []): array
{
    $row   = Computer::apply($row);
    $check = Validator::check($row);
    return ['ok' => true, 'found' => true, 'row' => $row, 'statuses' => $check['statuses'],
            'messages' => $check['messages'], 'valid' => $check['valid'], 'source' => $source,
            'error' => '', 'via' => $via, 'calls' => $calls];
}

// the Autofill: fetch the coin + its price, map the facts, write the copy, finalize
function gsImport(array $params): array
{
    // empty response shape every early exit fills in
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => '', 'calls' => []];
    $calls = [];

    // Picked from the dropdown = we already have the id;
    $gsId = (int) ($params['gs_id'] ?? 0);
    if ($gsId <= 0) { return array_merge($base, ['ok' => true, 'calls' => $calls]); }
    $coin = gsCollectible($gsId, $mCol);
    $calls[] = ['call' => 'GetCollectibleRequest?GsId=' . $gsId, 'ms' => (int) ($mCol['ms'] ?? 0),
                'got' => $coin ? ('"' . ($coin['Name'] ?? '?') . '"  (' . count($coin) . ' fields)') : 'nothing returned'];
    if (!$coin) { return array_merge($base, ['ok' => true, 'calls' => $calls]); }

    // picked from stores the full path ("World Coins > Austria > ...").
    if (empty($coin['CatalogPath'])) {
        $memPath = gsMemPath($gsId);
        if ($memPath !== '') {
            $coin['CatalogPath'] = array_map(static fn($n) => ['Name' => trim($n)],
                                             array_filter(explode('>', $memPath), 'trim'));
            $calls[] = ['call' => 'gsMemPath?GsId=' . $gsId, 'got' => $memPath];
        }
    }
    $rawCoin = $coin;
    $price = gsPricing($gsId, $params['grade'] ?? null, $mPr);
    $calls[] = ['call' => 'GetPricingRequest?Gsid=' . $gsId . (isset($params['grade']) && $params['grade'] !== '' ? '&Grade=' . $params['grade'] : ''),
                'ms' => (int) ($mPr['ms'] ?? 0),
                'got' => $price ? ('CpgVal=' . ($price['CpgVal'] ?? '-') . '  GreyVal=' . ($price['GreyVal'] ?? '-')
                                   . ($price['GradeLabel'] ?? '' ? '  (' . $price['GradeLabel'] . ')' : ''))
                                : 'no pricing (basic tier or none)'];
    if ($price) {
        $coin['CpgVal']     = $price['CpgVal'] ?? '';
        $coin['GreyVal']    = $price['GreyVal'] ?? '';
        $coin['GradeLabel'] = $price['GradeLabel'] ?? '';
    }

    // Gemini writes the category-level copy fresh from the GreySheet notes
    $row = gsAiMap($coin);
    if (geminiConfigured()) { $calls[] = ['call' => 'Gemini map (' . GEMINI_MODEL . ')', 'got' => count($row) . ' fields filled']; }
    if (($coin['CpgVal'] ?? '') !== '' && ($row['price'] ?? '') === '') { $row['price'] = gsPriceNum($coin['CpgVal']); }
    if (($coin['GreyVal'] ?? '') !== '' && ($row['cost'] ?? '') === '') { $row['cost'] = gsPriceNum($coin['GreyVal']); }

    // Pricing names the grade it priced (GradeLabel): autofill Grade with it,
    if (($coin['GradeLabel'] ?? '') !== '' && ($row['grade'] ?? '') === '') {
        $row['grade'] = preg_replace('/^([A-Za-z]{1,4})\s*-?\s*(\d)/', '$1 $2', trim((string) $coin['GradeLabel']));
    }

    if (!$row) { return array_merge($base, ['error' => 'Could not map the GreySheet data to any field.', 'calls' => $calls]); }
    $out = gs_finalize($row, $coin, geminiConfigured() ? 'greysheet+ai' : 'greysheet-map', $calls);
    $out['raw'] = ['collectible' => $rawCoin, 'pricing' => $price, 'facts_sent_to_ai' => gs_coin_facts($rawCoin)];

    // Display-only reference image from GreySheet; NOT written to product_image_*.
    $out['preview_image'] = (string) ($rawCoin['FeaturedImageUrl'] ?? '');
    return $out;
}

// Find-and-import in one go, for coins described rather than picked.
function gsGenerate(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => ''];
    if (!geminiConfigured()) { return array_merge($base, ['error' => 'AI generation needs a Gemini key (GEMINI_API_KEY).']); }
    $hint = trim((string) ($params['hint'] ?? ''));
    if ($hint === '') { return array_merge($base, ['error' => 'Describe the coin to generate.']); }

    $sys = 'You are a numismatic listing expert for Littleton Coin Company. GreySheet has no entry for '
         . 'this coin; draft a complete Sellbrite listing from your own knowledge. For fields with '
         . '"options:", use one of those exact options. Write accurate professional copy for description, '
         . 'features and search terms. Leave uncertain facts empty rather than guessing. '
         . 'Return ONLY a JSON object keyed by field machine-name.';
    $row = sbl_clean_ai_row(geminiJson($sys, "TARGET FIELDS:\n" . sbl_field_spec() . "\n\nCOIN TO LIST:\n" . $hint, $m));
    if (!$row) { return array_merge($base, ['error' => 'The AI did not return a usable listing.']); }
    return gs_finalize($row, null, 'ai-generated');
}
