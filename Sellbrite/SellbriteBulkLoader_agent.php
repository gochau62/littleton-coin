<?php
/*
 * GreySheet + Gemini coin agent for the Sellbrite Bulk Loader.
 *
 * WHAT IT DOES
 *   - Coin dropdown: searches the PATH MEMORY (DB2 table SBLMEMORYT) of every
 *     coin this screen has ever seen on GreySheet - name, GsId, node path.
 *     Searching memory costs 0 API calls.  Populate it with the seed crawl
 *     (SellbriteBulkLoader_seed.php) or just let lookups teach it over time.
 *   - Unknown coin: navigates the GreySheet node tree live (string-match the
 *     obvious folders, Gemini picks the ambiguous ones), finds the coin, and
 *     LEARNS: every folder visited and every coin in the leaf is written to
 *     memory, so next time it's in the dropdown instantly.
 *   - Picking a coin calls the API (GetCollectibleRequest + GetPricingRequest)
 *     and auto-fills the form; Gemini maps the data into the right fields.
 *   - Coin not on GreySheet at all: offer to draft the listing with Gemini.
 *
 * ENDPOINTS (CDN Public API v2 - there is no text-search endpoint):
 *   GetNodeChildrenRequest?NodeId=                child folders
 *   GetCollectibleByNodeRequest?NodeId=&ApiLevel= coins in a leaf
 *   GetCollectibleRequest?GsId=&ApiLevel=         one coin, full detail
 *   GetPricingRequest?Gsid=&Grade=&ApiLevel=      prices by grade
 */

require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema / Computer / Validator
require_once __DIR__ . '/SellbriteBulkLoader_model.php';   // sbl_conn / sbl_select (DB2 memory)

/* ----------------------------- SETTINGS --------------------------------- */
/* Edit these lines. Do NOT commit real production keys to git.
 *   BETA (testing) : https://cpgpublicapiv2beta.greysheet.com/api
 *   PROD (live)    : https://cpgpublicapiv2.greysheet.com/api        */
if (!defined('GS_BASE_URL'))   { define('GS_BASE_URL',   'https://cpgpublicapiv2beta.greysheet.com/api'); }
if (!defined('GS_API_TOKEN'))  { define('GS_API_TOKEN',  ''); }                 // x-api-token
if (!defined('GS_API_KEY'))    { define('GS_API_KEY',    ''); }                 // x-api-key
if (!defined('GS_API_LEVEL'))  { define('GS_API_LEVEL',  'basic'); }            // 'basic' | 'advanced'
if (!defined('GS_ROOT_NODE'))  { define('GS_ROOT_NODE',  1); }                  // 1 = "U.S. Coins"
if (!defined('GS_TIMEOUT'))    { define('GS_TIMEOUT',    20); }

if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', ''); }               // aistudio.google.com/apikey
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); }
if (!defined('GEMINI_BASE'))    { define('GEMINI_BASE',    'https://generativelanguage.googleapis.com/v1beta'); }
if (!defined('GEMINI_TIMEOUT')) { define('GEMINI_TIMEOUT', 40); }

/* ------------------------------ LOGGING --------------------------------- */
function gsLog($msg)
{
    $line = 'Sellbrite ' . $msg;
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
    else { error_log($line); }
}

/* --------------------------- GREYSHEET CLIENT --------------------------- */
/** GET a GreySheet path; returns decoded JSON or null. $meta gets status/error/ms. */
function gsApiGet($path, array $params = [], &$meta = [])
{
    $meta = ['status' => 0, 'error' => '', 'ms' => 0, 'url' => ''];
    if (GS_API_TOKEN === '' || GS_API_KEY === '') {
        $meta['error'] = 'GS_API_TOKEN / GS_API_KEY not set in SellbriteBulkLoader_agent.php';
        gsLog('config: ' . $meta['error']);
        return null;
    }
    if (!isset($params['apiLevel'])) { $params['apiLevel'] = GS_API_LEVEL; }
    $url = rtrim(GS_BASE_URL, '/') . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    $meta['url'] = $url;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => GS_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'x-api-token: ' . GS_API_TOKEN,
            'x-api-key: '   . GS_API_KEY,
            'Accept: application/json',
        ],
    ]);
    $t0   = microtime(true);
    $body = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($body === false) {
        $meta['error'] = 'cURL: ' . curl_error($ch);
        curl_close($ch);
        gsLog('network ' . $meta['error'] . ' url=' . $url);
        return null;
    }
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($meta['status'] === 401 || $meta['status'] === 403) { $meta['error'] = 'Auth rejected (HTTP ' . $meta['status'] . ')'; gsLog($meta['error']); return null; }
    if ($meta['status'] === 429) { $meta['error'] = 'Rate limited (429)'; gsLog($meta['error']); return null; }
    if ($meta['status'] < 200 || $meta['status'] >= 300) { $meta['error'] = 'HTTP ' . $meta['status']; gsLog($meta['error'] . ' url=' . $url); return null; }

    $data = json_decode($body, true);
    if (!is_array($data)) { $meta['error'] = 'Bad JSON'; gsLog($meta['error'] . ' url=' . $url); return null; }
    if (isset($data['PermitAccess']) && $data['PermitAccess'] === false) {
        $meta['error'] = 'Access denied: ' . ($data['AccessDeniedMessage'] ?? 'check subscription tier');
        gsLog($meta['error']);
        return null;
    }
    return $data;
}

/** The Data[] list from a wrapped response. */
function gsData($resp): array
{
    return (is_array($resp) && isset($resp['Data']) && is_array($resp['Data']))
        ? array_values(array_filter($resp['Data'], 'is_array')) : [];
}

/* ----------------------------- GEMINI CLIENT ---------------------------- */
function geminiConfigured() { return GEMINI_API_KEY !== ''; }

/** Ask Gemini for a JSON object; returns decoded array or null. */
function geminiJson($system, $user, &$meta = [])
{
    $meta = ['status' => 0, 'error' => '', 'tokens' => 0, 'ms' => 0];
    if (!geminiConfigured()) { $meta['error'] = 'GEMINI_API_KEY not set'; return null; }

    $url  = rtrim(GEMINI_BASE, '/') . '/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
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
    $t0  = microtime(true);
    $raw = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($raw === false) { $meta['error'] = 'cURL: ' . curl_error($ch); curl_close($ch); gsLog('gemini ' . $meta['error']); return null; }
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($raw, true);
    if ($meta['status'] < 200 || $meta['status'] >= 300) {
        $meta['error'] = 'Gemini HTTP ' . $meta['status'] . ': ' . ($resp['error']['message'] ?? '');
        gsLog($meta['error']);
        return null;
    }
    $meta['tokens'] = (int) ($resp['usageMetadata']['totalTokenCount'] ?? 0);
    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $data = json_decode($text, true);
    if (!is_array($data) && preg_match('/\{.*\}/s', (string) $text, $m)) { $data = json_decode($m[0], true); }
    if (!is_array($data)) { $meta['error'] = 'Gemini returned no usable JSON'; gsLog($meta['error']); return null; }
    gsLog('gemini ok tokens=' . $meta['tokens'] . ' ms=' . $meta['ms']);
    return $data;
}

/* ------------------------------ PATH MEMORY ----------------------------- */
/*
 * The memory lives in DB2 (SBLMEMORYT): one row per catalog folder
 * (kind 'N', ref_id = NodeId) or coin (kind 'C', ref_id = GsId) learned from
 * GreySheet - by the seed crawl or on first lookup.  The dropdown searches
 * coin rows at 0 API calls; navigation starts from the deepest known folder.
 * Without a DB2 connection (dev) memory is a no-op and lookups still work,
 * they just navigate live every time.
 */
if (!defined('SBL_GSMEM_TABLE')) { define('SBL_GSMEM_TABLE', 'LSCDEVLIBP.SBLMEMORYT'); }

/* Dev fallback (XAMPP / no DB2): memory lives in a local JSON file so the
 * crawl, dropdown search, years and imports can all be tested off the i.
 * On the server with DB2 this file is never touched. */
if (!defined('GS_MEM_DEVFILE')) { define('GS_MEM_DEVFILE', __DIR__ . '/SellbriteBulkLoader_memory.dev.json'); }

/** Is the real DB2 memory available? (cached per request) */
function gsMemDb(): bool
{
    static $ok = null;
    if ($ok === null) { $ok = function_exists('db2_prepare') && function_exists('sbl_conn') && (bool) sbl_conn(); }
    return $ok;
}

/** Dev store: all rows keyed "K:ref_id", shaped like the SQL rows. */
function gsMemDevAll(): array
{
    if (isset($GLOBALS['GS_MEM_DEV'])) { return $GLOBALS['GS_MEM_DEV']; }
    $d = is_file(GS_MEM_DEVFILE) ? json_decode((string) file_get_contents(GS_MEM_DEVFILE), true) : null;
    return $GLOBALS['GS_MEM_DEV'] = (is_array($d) ? $d : []);
}

function gsMemDevSave(array $rows): void
{
    $GLOBALS['GS_MEM_DEV'] = $rows;
    @file_put_contents(GS_MEM_DEVFILE, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/** Normalize a name for matching ("Morgan Dollars, Proof" -> "morgan dollars proof"). */
function gsNorm($s): string
{
    return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', (string) $s))));
}

/** Run a prepared write against the memory table; false when no DB2. */
function gsMemExec(string $sql, array $params): bool
{
    $conn = function_exists('sbl_conn') ? sbl_conn() : false;
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, $sql);
    return $stmt ? (bool) @db2_execute($stmt, $params) : false;
}

/** SELECT rows from the memory table (lowercase keys); [] when no DB2. */
function gsMemRows(string $sql, array $params = []): array
{
    return function_exists('sbl_select') ? sbl_select($sql, $params) : [];
}

/** Insert-or-update one memory row, keyed by (kind, ref_id). */
function gsMemUpsert(string $kind, int $refId, string $name, string $path,
                     string $date = '', string $mm = '', int $parent = 0,
                     int $coinCount = 0, string $done = 'N'): void
{
    if ($refId <= 0 || $name === '') { return; }
    if (!gsMemDb()) {
        $rows = gsMemDevAll();
        $rows[$kind . ':' . $refId] = ['kind' => $kind, 'ref_id' => $refId, 'parent_id' => $parent,
            'name' => $name, 'path' => $path, 'coin_date' => $date, 'mint_mark' => $mm,
            'coin_count' => $coinCount, 'done' => $done];
        gsMemDevSave($rows);
        return;
    }
    $ins = gsMemExec(
        'INSERT INTO ' . SBL_GSMEM_TABLE
      . ' (kind, ref_id, parent_id, name, path, coin_date, mint_mark, coin_count, done)'
      . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$kind, $refId, $parent, $name, $path, $date, $mm, $coinCount, $done]
    );
    if (!$ins) {                                        // duplicate -> refresh it
        gsMemExec(
            'UPDATE ' . SBL_GSMEM_TABLE
          . ' SET parent_id = ?, name = ?, path = ?, coin_date = ?, mint_mark = ?, coin_count = ?, done = ?'
          . ' WHERE kind = ? AND ref_id = ?',
            [$parent, $name, $path, $date, $mm, $coinCount, $done, $kind, $refId]
        );
    }
}

/** Remember a folder. */
function gsMemLearnNode(int $id, string $name, string $path, int $parent = 0,
                        int $coinCount = 0, string $done = 'N'): void
{
    gsMemUpsert('N', $id, $name, $path, '', '', $parent, $coinCount, $done);
}

/** Remember every coin in a leaf (one API call teaches the whole series). */
function gsMemLearnCoins(array $coins, string $path, int $parentNodeId = 0): void
{
    foreach ($coins as $c) {
        $id = (int) ($c['Gsid'] ?? 0);
        if ($id <= 0) { continue; }
        gsMemUpsert('C', $id, (string) ($c['Name'] ?? ''), $path,
                    (string) ($c['CoinDate'] ?? ''), (string) ($c['MintMark'] ?? ''), $parentNodeId);
    }
}

/** Delete one memory row (used by the test page's round trip). */
function gsMemDelete(string $kind, int $refId): void
{
    if (!gsMemDb()) {
        $rows = gsMemDevAll();
        unset($rows[$kind . ':' . $refId]);
        gsMemDevSave($rows);
        return;
    }
    gsMemExec('DELETE FROM ' . SBL_GSMEM_TABLE . ' WHERE kind = ? AND ref_id = ?', [$kind, $refId]);
}

/** Mark a node fully fetched (its coins/children are all in the table). */
function gsMemMarkDone(int $nodeId): void
{
    if (!gsMemDb()) {
        $rows = gsMemDevAll();
        if (isset($rows['N:' . $nodeId])) { $rows['N:' . $nodeId]['done'] = 'Y'; gsMemDevSave($rows); }
        return;
    }
    gsMemExec('UPDATE ' . SBL_GSMEM_TABLE . " SET done = 'Y' WHERE kind = 'N' AND ref_id = ?", [$nodeId]);
}

/** All remembered folders (for the navigation shortcut). */
function gsMemNodes(): array
{
    if (!gsMemDb()) {
        return array_values(array_filter(gsMemDevAll(), static fn($r) => ($r['kind'] ?? '') === 'N'));
    }
    return gsMemRows('SELECT ref_id, parent_id, name, path, coin_count, done FROM '
                   . SBL_GSMEM_TABLE . " WHERE kind = 'N'");
}

/** Remembered child folders of one node (lets the seed crawl resume without API calls). */
function gsMemNodeChildren(int $parentId): array
{
    if (!gsMemDb()) {
        return array_values(array_filter(gsMemDevAll(), static fn($r) =>
            ($r['kind'] ?? '') === 'N' && (int) ($r['parent_id'] ?? 0) === $parentId));
    }
    return gsMemRows('SELECT ref_id, name, path, coin_count, done FROM ' . SBL_GSMEM_TABLE
                   . " WHERE kind = 'N' AND parent_id = ?", [$parentId]);
}

/** Dropdown search: remembered coins matching every word of $q. 0 API calls. */
function gsMemSearch(string $q, int $limit = 40): array
{
    $words = array_filter(explode(' ', gsNorm($q)));
    if (!$words) { return []; }

    if (!gsMemDb()) {
        $out = [];
        foreach (gsMemDevAll() as $r) {
            if (($r['kind'] ?? '') !== 'C') { continue; }
            $hay = gsNorm(($r['name'] ?? '') . ' ' . ($r['path'] ?? ''));
            foreach ($words as $w) { if (strpos($hay, $w) === false) { continue 2; } }
            $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => $r['name'], 'path' => (string) ($r['path'] ?? '')];
            if (count($out) >= $limit) { break; }
        }
        return $out;
    }

    $sql = 'SELECT ref_id, name, path FROM ' . SBL_GSMEM_TABLE . " WHERE kind = 'C'";
    $params = [];
    foreach ($words as $w) {
        $sql .= " AND UPPER(name CONCAT ' ' CONCAT COALESCE(path, '')) LIKE ?";
        $params[] = '%' . strtoupper($w) . '%';
    }
    $sql .= ' ORDER BY name FETCH FIRST ' . (int) $limit . ' ROWS ONLY';
    $out = [];
    foreach (gsMemRows($sql, $params) as $r) {
        $out[] = ['gs_id' => (int) $r['ref_id'], 'label' => $r['name'], 'path' => (string) ($r['path'] ?? '')];
    }
    return $out;
}

/* --------------------------- TREE NAVIGATION ---------------------------- */
/** A node's child folders. */
function gsChildren(int $nodeId): array
{
    $resp = gsApiGet('GetNodeChildrenRequest', ['NodeId' => $nodeId], $m);
    $out  = [];
    foreach (gsData($resp) as $c) {
        $out[] = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                  'nodes' => (int) ($c['NodeChildrenCountLive'] ?? 0),
                  'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0)];
    }
    return $out;
}

/** Choose the child folder leading to the target: string-match, else Gemini. */
function gsNavPick(array $children, string $target, string $context)
{
    $t = gsNorm($target);
    $hits = [];
    foreach ($children as $c) {
        $n = gsNorm($c['name']);
        if ($n !== '' && (strpos($t, $n) !== false || strpos($n, $t) !== false)) { $hits[] = $c; }
    }
    if (count($hits) === 1) { return $hits[0]; }

    if (geminiConfigured()) {
        $list = [];
        foreach ($children as $c) { $list[] = $c['id'] . ' = ' . $c['name']; }
        $sys = 'You navigate a coin catalog tree. Given a target coin and a list of folders ("id = name"), '
             . 'pick the ONE folder that leads to that coin. Use the grade to tell proof from business '
             . 'strike. Return ONLY JSON {"id": <id>}.';
        $out = geminiJson($sys, "TARGET COIN: $context\n\nFOLDERS:\n" . implode("\n", $list), $m);
        $id  = (int) ($out['id'] ?? 0);
        foreach ($children as $c) { if ($c['id'] === $id) { return $c; } }
    }
    return $hits[0] ?? null;
}

/** In a leaf's coin list, pick the exact coin by year / mint / grade. */
function gsPickCoin(array $coins, array $attrs): int
{
    $year = trim((string) ($attrs['year'] ?? ''));
    $mm   = trim((string) ($attrs['mint_mark'] ?? ''));

    $cands = $coins;
    if ($year !== '') {
        $byDate = array_values(array_filter($cands, static fn($c) =>
            (string) ($c['CoinDate'] ?? '') === $year || strpos((string) ($c['Name'] ?? ''), $year) !== false));
        if ($byDate) { $cands = $byDate; }
    }
    if ($mm !== '' && strcasecmp($mm, 'No Mint Mark') !== 0) {
        $byMm = array_values(array_filter($cands, static fn($c) => strcasecmp((string) ($c['MintMark'] ?? ''), $mm) === 0));
        if ($byMm) { $cands = $byMm; }
    }
    if (count($cands) === 1) { return (int) ($cands[0]['Gsid'] ?? 0); }

    if (count($cands) > 1 && geminiConfigured()) {
        $list = [];
        foreach ($cands as $c) { $list[] = (int) ($c['Gsid'] ?? 0) . ' = ' . (string) ($c['Name'] ?? ''); }
        $desc = trim(implode(' ', array_filter([$attrs['year'] ?? '', $attrs['mint_mark'] ?? '',
                     $attrs['category_name'] ?? '', $attrs['grade'] ?? '', $attrs['strike_type'] ?? ''])));
        $sys  = 'Pick the ONE catalog coin best matching the target (grade colour BN/RB/RD, proof vs '
              . 'business, variety). Return ONLY JSON {"id": <GsId>}.';
        $out  = geminiJson($sys, "TARGET: $desc\n\nCOINS:\n" . implode("\n", array_slice($list, 0, 120)), $m);
        $id   = (int) ($out['id'] ?? 0);
        foreach ($cands as $c) { if ((int) ($c['Gsid'] ?? 0) === $id) { return $id; } }
    }
    return (int) ($cands[0]['Gsid'] ?? 0);
}

/**
 * Navigate to a coin's leaf node from its attributes, walking the tree live.
 * Starts from the deepest folder memory already knows, learns every folder
 * visited and every coin in the leaf.  Returns ['coins' => [...], 'path' => ''].
 */
function gsResolveLeaf(array $attrs, array &$trace = []): array
{
    $none = ['coins' => [], 'path' => ''];
    $category = trim((string) ($attrs['category_name'] ?? ''));
    $desc = trim(implode(' ', array_filter([$attrs['year'] ?? '', $attrs['mint_mark'] ?? '', $category,
                $attrs['denomination'] ?? '', $attrs['grade'] ?? '', $attrs['strike_type'] ?? ''])));
    if ($category === '' && $desc === '') { return $none; }
    $target = $category !== '' ? $category : $desc;

    $nodeId = GS_ROOT_NODE;
    $path   = 'U.S. Coins';

    // Memory shortcut: jump straight to a known folder whose name matches the category.
    $tk = gsNorm($target);
    foreach (gsMemNodes() as $n) {
        $k = gsNorm((string) $n['name']);
        if ($k !== '' && (strpos($tk, $k) !== false || strpos($k, $tk) !== false)) {
            $nodeId = (int) $n['ref_id'];
            $path   = (string) ($n['path'] ?? $path);
            $trace[] = 'memory:' . $n['name'];
            break;
        }
    }

    for ($depth = 0; $depth < 8; $depth++) {
        $children = gsChildren($nodeId);
        if (!$children) {                               // no child folders: treat as leaf
            $resp  = gsApiGet('GetCollectibleByNodeRequest', ['NodeId' => $nodeId], $m);
            $coins = gsData($resp);
            if (!$coins) { return $none; }
            gsMemLearnCoins($coins, $path, $nodeId);
            gsMemMarkDone($nodeId);
            return ['coins' => $coins, 'path' => $path];
        }
        $pick = gsNavPick($children, $target, $desc);
        if (!$pick) { return $none; }
        $parent = $nodeId;
        $path  .= ' > ' . $pick['name'];
        $trace[] = $pick['id'] . ':' . $pick['name'];
        gsMemLearnNode($pick['id'], $pick['name'], $path, $parent, $pick['coins']);

        if ($pick['coins'] > 0) {                       // leaf: list + learn
            $resp  = gsApiGet('GetCollectibleByNodeRequest', ['NodeId' => $pick['id']], $m);
            $coins = gsData($resp);
            gsMemLearnCoins($coins, $path, $pick['id']);
            if ($coins) { gsMemMarkDone($pick['id']); }
            return ['coins' => $coins, 'path' => $path];
        }
        $nodeId = $pick['id'];
    }
    return $none;
}

/** Find a coin's GsId from its attributes (0 = not found). */
function gsResolve(array $attrs, array &$trace = []): int
{
    $leaf = gsResolveLeaf($attrs, $trace);
    return $leaf['coins'] ? gsPickCoin($leaf['coins'], $attrs) : 0;
}

/**
 * The years a series actually exists for (drives the dynamic Year dropdown).
 * Memory-first (0 API calls once a series is learned); unknown series are
 * looked up live, which also teaches memory the whole leaf.
 */
function gsYearsFor(string $category, bool $liveLookup = true): array
{
    $ck = gsNorm($category);
    if ($ck === '') { return []; }

    $years = [];
    if (!gsMemDb()) {
        foreach (gsMemDevAll() as $r) {
            if (($r['kind'] ?? '') !== 'C') { continue; }
            if (strpos(gsNorm(($r['path'] ?? '') . ' ' . ($r['name'] ?? '')), $ck) === false) { continue; }
            if (preg_match('/\d{4}/', (string) ($r['coin_date'] ?? ''), $m)) { $years[$m[0]] = true; }
        }
    } else {
        $like = '%' . strtoupper($ck) . '%';
        $rows = gsMemRows('SELECT DISTINCT coin_date FROM ' . SBL_GSMEM_TABLE
                        . " WHERE kind = 'C' AND UPPER(COALESCE(path, '') CONCAT ' ' CONCAT name) LIKE ?", [$like]);
        foreach ($rows as $r) {
            if (preg_match('/\d{4}/', (string) ($r['coin_date'] ?? ''), $m)) { $years[$m[0]] = true; }
        }
    }
    if (!$years && $liveLookup) {
        $t = [];
        foreach (gsResolveLeaf(['category_name' => $category], $t)['coins'] as $c) {
            if (preg_match('/\d{4}/', (string) ($c['CoinDate'] ?? ''), $m)) { $years[$m[0]] = true; }
        }
    }
    $out = array_keys($years);
    sort($out);
    return $out;
}

/* ----------------------- COIN DATA -> FORM FIELDS ------------------------ */
/** One coin's full detail (GetCollectibleRequest). */
function gsCollectible(int $gsId): array
{
    if ($gsId <= 0) { return []; }
    $resp = gsApiGet('GetCollectibleRequest', ['GsId' => $gsId], $m);
    return gsData($resp)[0] ?? [];
}

/** Pricing for a coin, optionally at a numeric grade (GetPricingRequest). */
function gsPricing(int $gsId, $grade = null): array
{
    if ($gsId <= 0) { return []; }
    $params = ['Gsid' => $gsId];
    if ($grade !== null && ctype_digit((string) $grade)) { $params['Grade'] = (int) $grade; }
    $resp  = gsApiGet('GetPricingRequest', $params, $m);
    $first = gsData($resp)[0] ?? [];
    return $first['PricingData'][0] ?? [];
}

/** "$1,234.50" -> "1234.50" (or ''). */
function gsPriceNum($v): string
{
    $v = preg_replace('/[^0-9.]/', '', (string) $v);
    return is_numeric($v) ? $v : '';
}

/** Deterministic map of documented collectible fields (fallback when no Gemini). */
function gsMapToProduct(array $c): array
{
    $map = ['CoinDate' => 'year', 'MintMark' => 'mint_mark', 'DenominationShort' => 'denomination',
            'Variety' => 'coin_variety_1', 'Variety2' => 'coin_variety_2',
            'Composition' => 'composition', 'Fineness' => 'fineness', 'StrikeType' => 'strike_type'];
    $row = [];
    foreach ($map as $k => $f) {
        if (isset($c[$k]) && is_scalar($c[$k]) && trim((string) $c[$k]) !== '') { $row[$f] = trim((string) $c[$k]); }
    }
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces'])) {
        $row['package_weight'] = (string) round((float) $c['WeightOunces'] / 16, 4);
    }
    if (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) {
        $last = end($c['CatalogPath']);
        if (is_array($last) && !empty($last['Name'])) { $row['category_name'] = trim((string) $last['Name']); }
    }
    return $row;
}

/** Field spec for the AI prompt: names, labels, and valid dropdown options. */
function sbl_field_spec(): string
{
    static $spec = null;
    if ($spec !== null) { return $spec; }
    $byName = Schema::byName();
    $lines  = [];
    foreach (Schema::groups() as $names) {
        foreach ($names as $n) {
            if (!isset($byName[$n])) { continue; }
            $col  = $byName[$n];
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

/** Keep only real field keys from an AI answer. */
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

/** Gemini reads the GreySheet coin data and fills the fields (static map fallback). */
function gsAiMap(array $coin): array
{
    if (!geminiConfigured()) { return gsMapToProduct($coin); }
    $sys = 'You are a data-entry assistant for Littleton Coin Company\'s Sellbrite listing tool. Given raw '
         . 'GreySheet data for one coin and the target fields, put each piece of data into the correct '
         . 'field. For fields with "options:", you MUST use one of those exact options (or leave empty). '
         . 'Do not invent facts. Return ONLY a JSON object keyed by field machine-name.';
    $user = "TARGET FIELDS:\n" . sbl_field_spec() . "\n\nGREYSHEET DATA (JSON):\n"
          . json_encode($coin, JSON_UNESCAPED_SLASHES);
    $row = sbl_clean_ai_row(geminiJson($sys, $user, $m));
    return $row ?: gsMapToProduct($coin);
}

/* ------------------------------ PUBLIC API ------------------------------ */
/** Dropdown search over the path memory (0 API calls). */
function gsSearch(string $q): array
{
    $q = trim($q);
    if ($q === '') { return ['ok' => false, 'matches' => [], 'error' => 'Type something to search for.']; }
    return ['ok' => true, 'matches' => gsMemSearch($q), 'error' => ''];
}

function gs_finalize(array $row, $source, string $via): array
{
    $row   = Computer::apply($row);
    $check = Validator::check($row);
    return ['ok' => true, 'found' => true, 'row' => $row, 'statuses' => $check['statuses'],
            'messages' => $check['messages'], 'valid' => $check['valid'], 'source' => $source,
            'error' => '', 'via' => $via];
}

/**
 * Import a coin and auto-fill the form.
 *   gs_id set  -> straight to the data pull (the dropdown pick).
 *   otherwise  -> resolve via memory + live tree navigation, learning as we go.
 * found=false with ok=true means "not on GreySheet" -> offer AI generate.
 */
function gsImport(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => ''];

    $gsId = (int) ($params['gs_id'] ?? 0);
    if ($gsId <= 0) {
        $trace = [];
        $gsId  = gsResolve($params, $trace);
        if ($gsId <= 0) { return array_merge($base, ['ok' => true]); }
        gsLog('resolved "' . ($params['category_name'] ?? '') . '" -> GsId ' . $gsId . ' via ' . implode(' > ', $trace));
    }

    $coin = gsCollectible($gsId);
    if (!$coin) { return array_merge($base, ['ok' => true]); }

    $price = gsPricing($gsId, $params['grade'] ?? null);
    if ($price) {
        $coin['CpgVal']  = $price['CpgVal'] ?? '';
        $coin['GreyVal'] = $price['GreyVal'] ?? '';
    }

    $row = gsAiMap($coin);
    if (($coin['CpgVal'] ?? '') !== '' && ($row['price'] ?? '') === '') { $row['price'] = gsPriceNum($coin['CpgVal']); }
    if (($coin['GreyVal'] ?? '') !== '' && ($row['cost'] ?? '') === '') { $row['cost'] = gsPriceNum($coin['GreyVal']); }
    if (!$row) { return array_merge($base, ['error' => 'Could not map the GreySheet data to any field.']); }
    return gs_finalize($row, $coin, geminiConfigured() ? 'greysheet+ai' : 'greysheet-map');
}

/** One-off / foreign coin GreySheet doesn't have: Gemini drafts the listing. */
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
