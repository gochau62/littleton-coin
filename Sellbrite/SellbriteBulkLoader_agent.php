<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_agent.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
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
if (!defined('SBL_LOG_FILE')) {
    // the loader's own log, beside the other *_activity logs in the house
    // LCCOnline_logs folder; falls back to the code folder, then /tmp
    $sblLogDir = is_dir(__DIR__ . '/LCCOnline_logs') && is_writable(__DIR__ . '/LCCOnline_logs')
               ? __DIR__ . '/LCCOnline_logs'
               : (is_writable(__DIR__) ? __DIR__ : '/tmp');
    define('SBL_LOG_FILE', $sblLogDir . '/sellbrite_activity.log');
}

function gsLog($msg)
{
    $line = date('Y-m-d H:i:s') . '  ' . $msg . "\r\n";
    // @ - a full disk or a permission problem must never break a lookup
    if (@file_put_contents(SBL_LOG_FILE, $line, FILE_APPEND | LOCK_EX) !== false) { return; }
    // could not write the file: fall back to the LCCOnline logger, then PHP's
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec('Greysheet ' . $msg); }
    else { error_log('Greysheet ' . $msg); }
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

// gsApiGet knows exactly why a call came back empty - say so in the API log
// instead of the bare "nothing returned" that covers every failure alike
function gs_why(array $meta): string
{
    $bits = [];
    if (!empty($meta['error']))  { $bits[] = $meta['error']; }
    if (!empty($meta['status'])) { $bits[] = 'HTTP ' . $meta['status']; }
    if (!empty($meta['note']))   { $bits[] = $meta['note']; }
    return $bits ? '  (' . implode('; ', $bits) . ')' : '  (empty response)';
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
// $think caps Gemini's internal reasoning tokens.  2.5 Flash deliberates by
// default and that deliberation IS most of the latency - pick-from-a-list calls
// run with 0, the listing-writing calls keep a small budget for quality.
function geminiJson($system, $user, &$meta = [], int $think = 0)
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
        'generationConfig'  => ['temperature' => 0.2, 'responseMimeType' => 'application/json',
                                'maxOutputTokens' => 8192,
                                'thinkingConfig' => ['thinkingBudget' => $think]],
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
      $fin = (string) ($resp['candidates'][0]['finishReason'] ?? '');
    if ($fin !== '' && $fin !== 'STOP') { gsLog('gemini finishReason=' . $fin . ' (answer truncated - raise maxOutputTokens?)'); }
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
// "Austrian" finds Austria and "Thalers" finds Thaler: search on the word's
// stem so a grammatical ending never breaks the match.  Short words stay whole.
function gsStem(string $w): string
{
    $n = strlen($w);
    if ($n >= 7) { return substr($w, 0, $n - 2); }
    if ($n >= 5) { return substr($w, 0, $n - 1); }
    return $w;
}

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
    $sql = 'SELECT ref_id, name, path, coin_date FROM ' . SBL_GSMEM_TABLE . " WHERE kind = 'C'";
    $params = [];
    foreach ($words as $w) {
        // require each word, case-insensitively
        $sql .= " AND UPPER(name CONCAT ' ' CONCAT COALESCE(path, '')) LIKE ?";
        $params[] = '%' . strtoupper(gsStem($w)) . '%';
    }
    // alphabetical, capped at limit
    $sql .= ' ORDER BY name FETCH FIRST ' . (int) $limit . ' ROWS ONLY';
    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => $r['name'], 'path' => (string) ($r['path'] ?? ''),
                  'coin_date' => (string) ($r['coin_date'] ?? '')];
    }
    return $out;
}


// replace %, _, \, inside of search name strings
// Closest coins rather than only exact ones.  gsMemSearch demands EVERY word, so
// "Austria Silver 20 Corona" finds nothing when the catalog calls it "Corona,
// Gold" under a path that never says Austria.  Here each word that appears scores
// a point - a word matching the coin's own name counts double, since the path is
// mostly country and series - and the best scoring coins come back first.
// Rows scoring one lone word are dropped: one shared word is a coincidence.
function gsMemBest(string $q, int $limit = 40): array
{
    $words = array_slice(array_unique(array_filter(explode(' ', gsNorm($q)),
                         static fn($w) => strlen($w) > 1)), 0, 8);
    if (!$words) { return []; }

    $score = []; $params = [];
    foreach ($words as $w) {
        $like = '%' . strtoupper(gsLikeEsc(gsStem($w))) . '%';
        $score[] = "(CASE WHEN UPPER(name) LIKE ? ESCAPE '\\' THEN 2 ELSE 0 END)";
        $params[] = $like;
        $score[] = "(CASE WHEN UPPER(COALESCE(path, '')) LIKE ? ESCAPE '\\' THEN 1 ELSE 0 END)";
        $params[] = $like;
    }
    // ordering by the alias keeps the scoring expression - and its parameters - single
    $sql = 'SELECT ref_id, name, path, coin_date, ' . implode(' + ', $score) . ' AS hits FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'C'"
         . ' ORDER BY hits DESC, LENGTH(name), name'
         . ' FETCH FIRST ' . (int) $limit . ' ROWS ONLY';

    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        if ((int) ($r['hits'] ?? 0) < 2) { continue; }
        $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => $r['name'],
                  'path' => (string) ($r['path'] ?? ''), 'coin_date' => (string) ($r['coin_date'] ?? '')];
    }
    return $out;
}

// The closest series NODE whose coins memory has not learned yet (done = 'N').
// Same scoring as gsMemBest; used to decide which series a miss should fetch live.
function gsMemBestNode(string $q): array
{
    $words = array_slice(array_unique(array_filter(explode(' ', gsNorm($q)),
                         static fn($w) => strlen($w) > 1)), 0, 8);
    if (!$words) { return []; }
    $score = []; $params = [];
    foreach ($words as $w) {
        $like = '%' . strtoupper(gsLikeEsc(gsStem($w))) . '%';
        $score[] = "(CASE WHEN UPPER(name) LIKE ? ESCAPE '\\' THEN 2 ELSE 0 END)";
        $params[] = $like;
        $score[] = "(CASE WHEN UPPER(COALESCE(path, '')) LIKE ? ESCAPE '\\' THEN 1 ELSE 0 END)";
        $params[] = $like;
    }
    $sql = 'SELECT ref_id, name, path, ' . implode(' + ', $score) . ' AS hits FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'N' AND coin_count > 0 AND done <> 'Y'"
         . ' ORDER BY hits DESC, LENGTH(name), name FETCH FIRST 1 ROW ONLY';
    $r = gsMemRows($sql, $params)[0] ?? [];
    return ((int) ($r['hits'] ?? 0) >= 2) ? $r : [];
}

// A miss teaches the table: fetch the closest unlearned series live and store its
// coins, exactly as the seeder would have.  One API call per miss, and the node is
// marked done either way, so the same gap is never fetched twice.
function lccLearnSeries(string $q): int
{
    $node = gsMemBestNode($q);
    if (!$node) { return 0; }
    return lcc_fetch_coins((int) $node['ref_id'], (string) ($node['name'] ?? ''),
                           (string) ($node['path'] ?? ''));
}

// The crude word scores only gather candidates - the agent makes the call.  It
// knows Thaler and Taler are one coin, Slv means Silver and Austria is not
// Hungary, so spelling never decides a match.  Returns the 1-based pick, 0 for
// "none of these is the coin", null when the AI could not answer.
function lccJudge(string $desc, array $facts, array $cands): ?array
{
    if (!geminiConfigured() || !$cands) { return null; }
    $list = [];
    foreach (array_slice($cands, 0, 25) as $i => $c) {
        $list[] = ($i + 1) . '. ' . $c['label']
                . (($c['coin_date'] ?? '') !== '' ? ' (' . $c['coin_date'] . ')' : '')
                . (($c['path'] ?? '') !== '' ? '  [' . $c['path'] . ']' : '');
    }
    $sys = 'You match a dealer inventory line to its exact catalog coin. The two sides spell and '
         . 'abbreviate differently - judge by what the coin IS, never by the letters matching. '
         . 'The same coin must agree on country, denomination, metal, date AND mint mark - '
         . '1925-S is not 1925 plain. Prefer the plain business-strike issue over die varieties '
         . '(DDO, overdates, VAMs) unless the description itself names one. Return ONLY JSON '
         . '{"pick": n, "sure": true|false} - "sure" is true ONLY when that entry certainly IS '
         . 'this exact coin, false when it is merely the closest. {"pick": 0} if none fits.';
    $user = "ITEM:\n" . $desc
          . ($facts ? "\nKNOWN FACTS: " . json_encode($facts, JSON_UNESCAPED_SLASHES) : '')
          . "\n\nCANDIDATES:\n" . implode("\n", $list);
    $a = geminiJson($sys, $user, $m);
    if (!is_array($a) || !isset($a['pick'])) { return null; }
    $p = (int) $a['pick'];
    if ($p < 0 || $p > min(count($cands), 25)) { return null; }
    return ['pick' => $p, 'sure' => !empty($a['sure'])];
}

// fetch one series' coins live and remember them - the shared last step of a learn
function lcc_fetch_coins(int $nodeId, string $name, string $path): int
{
    $resp = gsApiGet('GetCollectibleByNodeRequest', ['NodeId' => $nodeId], $m);
    if ($resp === null) { return 0; }
    $coins = gsData($resp);
    gsMemLearnCoins($coins, $path, $nodeId);
    gsMemMarkDone($nodeId);
    gsLog('lccLearn node ' . $nodeId . ' "' . $name . '" +' . count($coins) . ' coins');
    return count($coins);
}

// The agent walks the catalog tree the way a person would: at each level it sees
// the branches and the item's own facts, picks where the item belongs, and
// descends.  Children come from memory when known and from the live API when not,
// and everything fetched is remembered - each walk makes the next one cheaper.
// Returns the path of the series it lands on, '' when nothing in the tree fits.
function lccAiWalk(string $desc, array $facts = []): string
{
    if ($desc === '' || !geminiConfigured()) { return ''; }
    // remember only where a walk LANDED.  A failure is never cached: the tree
    // keeps getting learned and the walker keeps improving, so a description
    // that found nothing an hour ago deserves a fresh walk now.
    $wk = md5($desc);
    if (!empty($_SESSION['sbl_lcc_walk'][$wk])) { return $_SESSION['sbl_lcc_walk'][$wk]; }

    $nodes = array_map(static fn($r) => ['id' => (int) $r['node_id'], 'name' => $r['name'],
                                         'path' => $r['path'], 'coins' => 0, 'done' => 'N'],
                       gsMemRoots());
    $sys = 'You are placing an item in the GreySheet coin catalog tree. Given the item and a '
         . 'numbered list of branches, return ONLY JSON {"pick": n} for the branch the item '
         . 'belongs under, or {"pick": 0} if none fits or the item is not a collectible.';

    for ($depth = 0; $depth < 6 && $nodes; $depth++) {
        $list = [];
        foreach ($nodes as $i => $n) { $list[] = ($i + 1) . '. ' . $n['name']; }
        $user = "ITEM:\n" . $desc
              . ($facts ? "\nKNOWN FACTS: " . json_encode($facts, JSON_UNESCAPED_SLASHES) : '')
              . "\n\nBRANCHES:\n" . implode("\n", $list);
        $a    = geminiJson($sys, $user, $m);
        $pick = is_array($a) ? (int) ($a['pick'] ?? 0) : 0;
        if ($pick < 1 || $pick > count($nodes)) {
            gsLog('lccWalk "' . $desc . '" stopped at depth ' . $depth . ' - no branch fits');
            return '';
        }
        $cur = $nodes[$pick - 1];

        // a series with coins: make sure they are remembered, then land here
        if ((int) $cur['coins'] > 0) {
            if (($cur['done'] ?? 'N') !== 'Y') {
                lcc_fetch_coins((int) $cur['id'], $cur['name'], $cur['path']);
            }
            gsLog('lccWalk "' . $desc . '" landed on "' . $cur['path'] . '"');
            return $_SESSION['sbl_lcc_walk'][$wk] = (string) $cur['path'];
        }

        // a folder: children from memory, or fetched live and remembered
        $kids = gsMemNodeChildren((int) $cur['id']);
        if (!$kids) {
            $resp = gsApiGet('GetNodeChildrenRequest', ['NodeId' => (int) $cur['id']], $mm);
            if ($resp === null) { return ''; }
            foreach (gsData($resp) as $c) {
                gsMemLearnNode((int) ($c['Id'] ?? 0), (string) ($c['Name'] ?? ''),
                               $cur['path'] . ' > ' . (string) ($c['Name'] ?? ''), (int) $cur['id'],
                               (int) ($c['CollectibleChildrenCountLive'] ?? 0));
            }
            $kids = gsMemNodeChildren((int) $cur['id']);
            gsLog('lccWalk learned ' . count($kids) . ' branches under "' . $cur['name'] . '"');
        }
        $nodes = array_map(static fn($r) => ['id' => (int) $r['ref_id'], 'name' => (string) $r['name'],
                                             'path' => (string) ($r['path'] ?? ''),
                                             'coins' => (int) ($r['coin_count'] ?? 0),
                                             'done' => (string) ($r['done'] ?? 'N')], $kids);
    }
    return '';
}

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
    // each word's STEM must appear in the series name or its path
    foreach (array_filter(explode(' ', gsNorm($q))) as $w) {
        $sql .= " AND UPPER(name CONCAT ' ' CONCAT COALESCE(path, '')) LIKE ?";
        $params[] = '%' . strtoupper(gsStem($w)) . '%';
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

    // search narrows by word stems in the coin name
    foreach (array_filter(explode(' ', gsNorm($q))) as $w) {
        $sql .= " AND UPPER(name) LIKE ?";
        $params[] = '%' . strtoupper(gsStem($w)) . '%';
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
    // GreySheet takes only the number, so "VG 8" / "XF-40" / "MS65RD" send their
    // digits - without this the grade was dropped and pricing fell to the lowest row
    if ($grade !== null && preg_match('/(\d{1,2})/', (string) $grade, $gm)) { $params['Grade'] = (int) $gm[1]; }
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
        'paper_money_grade_designation' => ['desc' => 'the slab qualifier: EPQ/PPQ (original paper) or Apparent/Net (problem note) - certified notes only; leave EMPTY, the operator reads it off the holder'],
        'paper_money_series_designation' => ['src' => 'CoinDate letter suffix', 'desc' => 'U.S. notes ONLY: the series letter after the year ("1934A" -> "A"); empty when the date has no letter; blank for coins'],
        'country_of_manufacture' => ['src' => 'CatalogPath CountryName', 'desc' => 'full country name', 'const' => 'United States'],
        'certification'  => ['opts' => $cert, 'desc' => 'OPERATOR-PICKED from the valid values (grading service, or Uncertified) - leave EMPTY; do not guess'],
        'title_suffix'   => ['desc' => 'operator catch-all appended to the title (grade details, error details, packaging, slab-label text) - leave BLANK; "Coin Collectible" is added to the title automatically'],
        'precious_metal_content' => ['src' => 'WeightOunces', 'desc' => 'per-coin metal, e.g. "1 oz","0.859 oz"; blank for base metal'],
        'total_precious_metal_content' => ['src' => 'WeightOunces x Fineness', 'desc' => 'troy oz of pure precious metal, blank for base-metal coins'],
        'description'    => ['desc' => 'A natural sentence built from the ACTUAL field values, house shape: '
            . '"A genuine {year} {mint mark} {variety} {series/type} {metal} {denomination IN WORDS - Quarter, Half Dollar, Cent Penny} '
            . '{strike if special} Coin[, from {brand} when not U.S. Mint]'
            . '[, in {grade} Condition -OR- , graded and certified {grade} {designation} by {certification} when slabbed]'
            . '[, {special feature clause, e.g. privy mark}]. [Contains {content} {fineness} {Metal}. - precious metals only]" '
            . 'Example using every criteria: "A genuine 2025 W American Eagle Silver Dollar Proof Bullion Coin, '
            . 'graded and certified PR 70 DCAM by PCGS, with the special privy mark honoring the 250th anniversary '
            . 'of the United States Army. Contains 1 oz 0.999 Silver." Plain raw grade examples: '
            . '"A genuine 1943 Lincoln Wheat Steel Cent Penny Coin, in AU About Uncirculated Condition." No hype.'],
        'diameter'       => ['src' => 'Diameter', 'desc' => 'millimeters, number only'],
        'weight'         => ['src' => 'WeightOunces', 'desc' => 'coin weight in troy ounces, number only'],
        'search_terms'   => ['desc' => '8-15 lowercase space-separated keywords: metal, type, denomination, mint, theme, "numismatics", "coin"'],
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
    if ($isPaper) {
        // the letter after the year is the Series Designation ("1934A" -> "A")
        $row['composition'] = 'Paper';
        if (preg_match('/^\s*\d{4}\s*-?\s*([A-Za-z])\b/', $g('CoinDate'), $m)) { $row['paper_money_series_designation'] = strtoupper($m[1]); }
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
    // Brand stays blank for the operator - the GreySheet image attribution was wrong for it
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
             'Designer','Edge','Mintage','Rarity','CoinShape', 'FeaturedImageAttribution', 'PcgsNumber','IsSet','IsType','CpgVal','GreyVal',
            'FriedbergNumber','BnBNumber','PickNumber','NoteColor','NoteDimension','Watermark','Printer',
             'NotePaperType','BnbSignatureName1','BnbSignatureName2','ObsoleteStateName','ObsoleteCityName','ObsoleteBankName',
             'GeneralNotes','ObverseDescription','ReverseDescription','ObverseLettering','ReverseLettering',
             'PriceLow','PriceHigh'];
    $out = [];
    foreach ($keys as $k) {
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
         . "work them into the description and extended_description. For U.S. notes coin_variety_2 may carry "
         . "the Friedberg number (\"FR2307\") when it is otherwise empty.\n"
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
    $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m, 512));
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
    // GreySheet's notes and design text are copyrighted - they never land in
    // the listing boxes. Expanded Description and the COLLECTOR'S NOTE stay
    // empty until the Generate-with-AI button writes them in its own words.
    $row['extended_description'] = '';
    $row['feature_4'] = '';
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
        $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m, 512));
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
    // exact first - every word present is the best answer there is - then closest
    $m = gsMemSearch($q);
    if (!$m) { $m = gsMemBest($q); }
    return ['ok' => true, 'matches' => $m, 'error' => ''];
}

// LCC SKU lookup: read the item master, then offer the GreySheet coins its description matches
function lccLookup(string $sku): array
{
    $sku = trim($sku);
    if ($sku === '') { return ['ok' => false, 'item' => [], 'matches' => [], 'error' => 'Type an LCC SKU.']; }
    $row = function_exists('sblLccItem') ? sblLccItem($sku) : false;
    // false = the CALL failed; [] = it ran and the SKU is not there
    if ($row === false) {
        return ['ok' => false, 'item' => [], 'matches' => [],
                'error' => 'Item lookup unavailable - check that SBLITEM001S is created on this system.'];
    }
    if (!$row) { return ['ok' => false, 'item' => [], 'matches' => [], 'error' => 'SKU ' . $sku . ' is not on the LCC item master.']; }

    $desc = trim((string) ($row['item_desc'] ?? ''));
    [$year, $dateMint] = lccDate((string) ($row['item_date'] ?? ''));
    // LCC's own grade codes are 2 characters and do not match the Sellbrite grade list,
    // so they ride along as a hint for the operator rather than filling the Grade box
    $hint = trim(trim((string) ($row['item_grade'] ?? '')) . ' ' . trim((string) ($row['item_grade2'] ?? '')));
    $note = trim((string) ($row['item_comment'] ?? ''));
    // money and counts come back raw from DB2; blank a zero so it never fills a box
    $money = function ($v) { $n = (float) $v; return $n > 0 ? number_format($n, 2, '.', '') : ''; };
    $count = function ($v) { $n = (int) $v; return $n > 0 ? (string) $n : ''; };
    $retail = $money($row['item_retail'] ?? 0);
    $cost   = $money($row['item_cost'] ?? 0);
    $qoh    = $count($row['item_qoh'] ?? 0);
    // Read the description first: gsMemSearch needs EVERY word to appear, and the
    // catalog writes "Silver" where the master writes "Slv", so the AI's spelt-out
    // phrase is the one worth searching. The raw line is only the fallback.
    $read   = lccParse($desc, trim((string) ($row['item_grade'] ?? '')));
    $parsed = $read['fields'];
    if ($year !== '')     { $parsed['year'] = $year; }
    if ($dateMint !== '') { $parsed['mint_mark'] = $dateMint; }
    // the judge and the walk see the raw coin date too - a range like 1922-1925
    // rules candidates in or out even when no single year exists
    $facts = $parsed;
    $rawItemDate = trim((string) ($row['item_date'] ?? ''));
    if ($rawItemDate !== '') { $facts['lcc_coin_date'] = $rawItemDate; }

    // Cast a wide net: the exact and closest passes only GATHER candidates.  The
    // words never decide the match - the agent judges the pool, because spelling
    // and abbreviations differ between the master and the catalog on every coin.
    $pool = [];
    $add  = static function (array $rows) use (&$pool) {
        foreach ($rows as $r) { $pool[(int) $r['gs_id']] = $r; }
    };
    if ($read['search'] !== '') { $add(gsMemSearch($read['search'])); }
    if ($desc !== '')           { $add(gsMemSearch($desc)); }
    if (count($pool) < 8 && $read['search'] !== '') { $add(gsMemBest($read['search'])); }
    if (count($pool) < 8 && $desc !== '')           { $add(gsMemBest($desc)); }
    $matches = array_values($pool);
    $picked  = false;
    $sure    = false;
    if (count($matches) > 1) {
        $j = lccJudge($desc, $facts, $matches);
        if ($j !== null && $j['pick'] > 0) {
            $one = array_splice($matches, $j['pick'] - 1, 1);
            array_unshift($matches, $one[0]);
            $picked = true;
            $sure   = $j['sure'];
            gsLog('lccJudge picked "' . $one[0]['label'] . '" of ' . count($matches)
                . ($j['sure'] ? ' (sure)' : ' (closest only)'));
        } elseif ($j !== null && $j['pick'] === 0) {
            // the agent says none of these IS the coin: try learning and the walk,
            // but keep the pool - closest suggestions beat an empty screen, they
            // just never auto-import
            gsLog('lccJudge: none of ' . count($matches) . ' candidates is this coin');
            $rejected = $matches;
            $matches  = [];
        }
    }
    // Still nothing: memory does not know the series yet.  First the cheap learn -
    // an unlearned node whose name overlaps the search - then retry the scoring.
    if (!$matches && lccLearnSeries($read['search'] !== '' ? $read['search'] : $desc) > 0) {
        $matches = $read['search'] !== '' ? gsMemBest($read['search']) : [];
        if (!$matches && $desc !== '') { $matches = gsMemBest($desc); }
    }
    // Last resort: the agent walks the catalog tree from the SKU's own facts, and
    // wherever it lands, that series' coins ARE the candidates - narrowed by the
    // coin date when there is one, no matter how differently LCC words the coin.
    $via = '';
    if (!$matches) {
        $wpath = lccAiWalk($desc, $facts);
        if ($wpath !== '') {
            $rows = gsMemCoins($wpath, '', $year);
            // a ranged coin date (1892-1907, 1966-72) is still a filter: keep the
            // shelf's coins whose own year falls inside it
            $rawDate = strtoupper(trim((string) ($row['item_date'] ?? '')));
            if (!$rows && $year === '' && preg_match('/^(\d{4})\s*-\s*(\d{2,4})$/', $rawDate, $rm)) {
                $y1 = (int) $rm[1];
                $y2 = (int) $rm[2];
                if ($y2 < 100) { $y2 += (int) (floor($y1 / 100) * 100); }
                foreach (gsMemCoins($wpath) as $c) {
                    if (preg_match('/\d{4}/', $c['coin_date'] . ' ' . $c['label'], $ym)
                        && (int) $ym[0] >= $y1 && (int) $ym[0] <= $y2) { $rows[] = $c; }
                }
            }
            if (!$rows && $year !== '') {
                // the shelf is right but no coin there carries this year: the
                // catalog likely does not hold the coin, so what IS there shows
                // as suggestions only - no drill, nothing automatic
                $rows = gsMemCoins($wpath);
                $via  = 'suggest';
                gsLog('lccLookup ' . $sku . ': no ' . $year . ' coin under "' . $wpath . '"');
            }
            foreach (array_slice($rows, 0, 40) as $r) {
                $matches[] = ['gs_id' => $r['gs_id'], 'label' => $r['label'], 'path' => $wpath,
                              'coin_date' => (string) ($r['coin_date'] ?? '')];
            }
            // the shelf is right but the exact coin still needs choosing - the
            // judge puts it first, so the right denomination leads the list
            if ($via === '' && count($matches) > 1) {
                $j = lccJudge($desc, $facts, $matches);
                if ($j !== null && $j['pick'] > 0) {
                    $one = array_splice($matches, $j['pick'] - 1, 1);
                    array_unshift($matches, $one[0]);
                    $picked = true;
                    $sure   = $j['sure'];
                    gsLog('lccJudge picked "' . $one[0]['label'] . '" from the landed shelf'
                        . ($j['sure'] ? ' (sure)' : ' (closest only)'));
                }
            }
        }
    }
    // nothing certain anywhere: offer the closest candidates as SUGGESTIONS - the
    // operator picks, nothing fills or imports on its own
    if (!$matches && !empty($rejected)) { $matches = $rejected; $via = 'suggest'; }
    gsLog('lccLookup ' . $sku . ' -> ' . count($matches) . ' matches'
        . ($matches ? ' (top: ' . $matches[0]['label'] . ' | ' . $matches[0]['path'] . ')' : ''));

    return ['ok' => true, 'error' => '', 'fields' => $parsed,
            'item' => ['sku' => (string) ($row['item_sku'] ?? $sku), 'description' => $desc, 'year' => $year,
                       'date' => trim((string) ($row['item_date'] ?? '')),
                       'grade' => trim((string) ($row['item_grade'] ?? '')),
                       'grade2' => trim((string) ($row['item_grade2'] ?? '')),
                       'grade_hint' => $hint, 'comment' => $note,
                       'root' => trim((string) ($row['item_root'] ?? '')),
                       'link' => trim((string) ($row['item_link'] ?? '')),
                       'roll' => $count($row['item_roll'] ?? 0),
                       'retail' => $retail, 'cost' => $cost, 'quantity' => $qoh],
            'matches' => $matches, 'picked' => $picked, 'sure' => $sure, 'via' => $via];
}


// the fields an inventory description can honestly support - the rest of the
// listing still comes from GreySheet, so the AI is never asked to invent them
const LCC_PARSE_FIELDS = ['year', 'coin_type', 'denomination', 'country_of_manufacture',
                          'composition', 'fineness', 'grade', 'mint_mark', 'mint_location',
                          'coin_variety_1', 'coin_variety_2', 'circulated_or_uncirculated',
                          'strike_type', 'single_coin_or_set', 'paper_money_type'];

// IICDAT is free text and holds several shapes: "1868", "1940-S" (year and mint
// mark), "1863B", "ND(1919)", and ranges like "1892-1907" or "247-145BC".  Only
// a single issue year may fill the Year box - a range is not a year, so it is
// left for the operator.  Returns [year, mint mark].
function lccDate(string $raw): array
{
    $d = strtoupper(trim($raw));
    if ($d === '') { return ['', '']; }
    // no-date issues carry the real year in brackets
    if (preg_match('/^ND\s*\((\d{4})\)$/', $d, $m))      { return [$m[1], '']; }
    if (preg_match('/^(\d{4})$/', $d, $m))               { return [$m[1], '']; }
    // "1940-S" is a year and a mint mark; "1892-1907" is a range and is not
    if (preg_match('/^(\d{4})-([A-Z]{1,2})$/', $d, $m))  { return [$m[1], $m[2]]; }
    if (preg_match('/^(\d{4})([A-Z])$/', $d, $m))        { return [$m[1], $m[2]]; }
    // a year followed by variety text ("1878 7TF", "1878 7/8TF") is still a year -
    // never a range, which the patterns above have already claimed
    if (preg_match('/^(\d{4})\s+\S/', $d, $m)) { return [$m[1], '']; }
    return ['', ''];
}

// Grade and Coin Type carry hundreds of options - too many for a prompt, and
// sending none leaves the AI guessing.  Score them against the words in the
// description (and the Sheldon code for grade) and show only what could fit.
function lcc_shortlist(string $field, string $desc, string $gradeCode = '', int $cap = 40): array
{
    $opts = sbl_field_options($field);
    if (count($opts) <= $cap) { return $opts; }
    // The grade abbreviation sits after a run of spaces at the end. Only Grade
    // should score against it - otherwise "UNC" drags in every "Uncirculated
    // Coin Set" and buries the series the description actually names.
    $text  = $field === 'grade' ? $desc : preg_split('/\s{2,}/', trim($desc))[0];
    $words = array_filter(preg_split('/[^a-z0-9]+/i', strtolower($text)),
                          static fn($w) => strlen($w) > 2 && !ctype_digit($w));
    $code  = ltrim($gradeCode, '0');
    $hits  = [];
    foreach ($opts as $o) {
        $lo = strtolower((string) $o);
        $score = 0;
        foreach ($words as $w) { if (strpos($lo, $w) !== false) { $score += 2; } }
        // "12" has to match as its own number so it cannot hit "120"
        if ($code !== '' && preg_match('/(?<!\d)' . preg_quote($code, '/') . '(?!\d)/', $lo)) { $score += 3; }
        if ($score > 0) { $hits[(string) $o] = $score; }
    }
    arsort($hits);
    return array_slice(array_keys($hits), 0, $cap);
}

// Read an LCC inventory description into form fields.  "1868 Austria Silver 10
// Kreuzer VG" carries the year, country, metal, denomination and grade; a person
// reads that at a glance, so the AI does the same rather than a lookup table.
// The grade code rides along because it is the Sheldon number ("08" = VG-8).
function lccParse(string $desc, string $gradeCode = ''): array
{
    $desc = trim($desc);
    if ($desc === '') { return ['fields' => [], 'search' => '']; }
    if (!geminiConfigured()) {
        gsLog('lccParse skipped - GEMINI_API_KEY not set');
        return ['fields' => [], 'search' => ''];
    }

    // one AI call per description per session; the master does not change under us.
    // an entry without 'fields' is from an older session format - re-read it
    $key = md5($desc . '|' . $gradeCode);
    $hit = $_SESSION['sbl_lcc_parse'][$key] ?? null;
    if (is_array($hit) && isset($hit['fields'])) {
        gsLog('lccParse "' . $desc . '" (cached) -> search "' . ($hit['search'] ?? '') . '"');
        return $hit;
    }

    // Build the field list for THIS description.  The house field guide is written
    // for the GreySheet import ("from GreySheet CoinDate"), which means nothing when
    // the source is a line of dealer shorthand, so the guidance is written here.
    $notes = [
        'year'                       => '4-digit issue year',
        'country_of_manufacture'     => 'the country named in the description',
        'composition'                => 'the metal named in the description',
        'denomination'               => 'face value as written, e.g. "10 Kreuzer"',
        'coin_type'                  => 'the series, ONLY if one option genuinely matches; otherwise leave it out',
        'grade'                      => 'match the grade abbreviation at the end of the description',
        'circulated_or_uncirculated' => 'Uncirculated only for a mint-state grade',
    ];
    $byName = Schema::byName();
    $spec   = [];
    foreach (LCC_PARSE_FIELDS as $f) {
        $line = '- ' . $f . ' (' . ($byName[$f]['label'] ?? $f) . ')';
        if (isset($notes[$f])) { $line .= ': ' . $notes[$f]; }
        $opts = lcc_shortlist($f, $desc, $gradeCode);
        if ($opts) { $line .= '  MUST be one of: ' . implode(' | ', $opts); }
        $spec[] = $line;
    }

    $sys = 'You read Littleton Coin Company inventory descriptions and turn them into Sellbrite '
         . 'listing fields. These are terse dealer lines, normally year, country, metal, '
         . 'denomination, then an abbreviated grade, and they are heavily abbreviated: Slv=Silver, '
         . 'Cu=Copper, Clrzd=Colorized, Cmpct=Compact, "St(7)"=a set of 7, "w/Orig Bag Frag"=with '
         . 'original bag fragment. Expand an abbreviation only when it is unambiguous. Not every '
         . 'item is a coin - bars, notes, ornaments and gift items appear too; when the line is not '
         . 'a coin, return only the fields that still apply. Fill ONLY what the description states. '
         . 'Never infer, never guess, and leave a field out entirely rather than filling it from '
         . 'general knowledge of the series. For fields with "options:", use one of those exact '
         . 'options. Return ONLY a JSON object keyed by field machine-name.';
    $user = "TARGET FIELDS:\n" . implode("\n", $spec) . "\n\nINVENTORY DESCRIPTION:\n" . $desc;
    if ($gradeCode !== '' && ltrim($gradeCode, '0') !== '') {
        // 04-70 are Sheldon numbers; anything higher, or with a letter, is an LCC
        // house condition code, and only the description says what it means
        $n = ctype_digit($gradeCode) ? (int) $gradeCode : 0;
        $user .= "\n\nGRADE CODE: " . $gradeCode . ($n >= 1 && $n <= 70
               ? ' - the numeric Sheldon grade (08 is VG-8, 40 is XF-40, 65 is MS-65). Use it with '
                 . 'the abbreviation in the description to pick the grade option.'
               : ' - an LCC house condition code, NOT a Sheldon number. Ignore the code and go by '
                 . 'the abbreviation at the end of the description.');
    }

    $user .= "\n\nALSO RETURN \"search_phrase\": the coin written out the way a catalog would "
           . 'name it - country, denomination and series in full words, abbreviations expanded, '
           . 'no year, no grade, no packaging. Keep it short; every word in it is required to '
           . 'match, so include only words a catalog would certainly use.';

    $ai  = geminiJson($sys, $user, $m);
    $row = sbl_snap_row(sbl_clean_ai_row($ai));
    // keep only the whitelist - a stray field would fill a box the description never mentioned
    $out = [];
    foreach (LCC_PARSE_FIELDS as $f) {
        if (isset($row[$f]) && trim((string) $row[$f]) !== '') { $out[$f] = trim((string) $row[$f]); }
    }
    // search_phrase is not a form field, so it is read before the schema clean drops it
    $res = ['fields' => $out, 'search' => trim((string) (is_array($ai) ? ($ai['search_phrase'] ?? '') : ''))];
    // only a real answer is worth keeping - caching an empty one would make a
    // single failed call permanent for the rest of the session
    if ($out || $res['search'] !== '') { $_SESSION['sbl_lcc_parse'][$key] = $res; }
    gsLog('lccParse "' . $desc . '" -> ' . ($out ? implode(', ', array_keys($out)) : 'no fields')
        . ($res['search'] !== '' ? ' | search "' . $res['search'] . '"' : ''));
    return $res;
}

// type-ahead over the LCC item master: what the SKU box lists as the operator types
function lccSearch(string $q): array
{
    if (!function_exists('sblLccSearch')) { return []; }
    $rows = sblLccSearch(trim($q));
    if ($rows === false) { return []; }
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['sku' => trim((string) ($r['item_sku'] ?? '')),
                  'description' => trim((string) ($r['item_desc'] ?? '')),
                  'date' => trim((string) ($r['item_date'] ?? ''))];
    }
    return $out;
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
                'got' => $coin ? ('"' . ($coin['Name'] ?? '?') . '"  (' . count($coin) . ' fields)')
                              : ('nothing returned' . gs_why($mCol))];
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
                                : ('no pricing' . gs_why($mPr))];
    if ($price) {
        $coin['CpgVal']     = $price['CpgVal'] ?? '';
        $coin['GreyVal']    = $price['GreyVal'] ?? '';
        $coin['GradeLabel'] = $price['GradeLabel'] ?? '';
    }

    // Gemini writes the category-level copy fresh from the GreySheet notes
    $row = gsAiMap($coin);
    if (geminiConfigured()) { $calls[] = ['call' => 'Gemini map (' . GEMINI_MODEL . ')', 'got' => count($row) . ' fields filled']; }
    // strip commas etc. from whatever landed in price/cost
    foreach (['price', 'cost'] as $pf) { if (($row[$pf] ?? '') !== '') { $row[$pf] = gsPriceNum($row[$pf]); } }
    $row['price'] = '';   // the Retail box stays empty - the operator types the price
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
    $row = sbl_clean_ai_row(geminiJson($sys, "TARGET FIELDS:\n" . sbl_field_spec() . "\n\nCOIN TO LIST:\n" . $hint, $m, 512));
    if (!$row) { return array_merge($base, ['error' => 'The AI did not return a usable listing.']); }
    return gs_finalize($row, null, 'ai-generated');
}
