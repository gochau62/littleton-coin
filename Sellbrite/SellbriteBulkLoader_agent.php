<?php
/*
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
require_once __DIR__ . '/SellbriteBulkLoader_logic.php';
require_once __DIR__ . '/SellbriteBulkLoader_model.php';

// Local, UNCOMMITTED secrets (the Gemini key). Create this file ONCE on each
// machine - it is git-ignored, so it survives every pull and never reaches
// GitHub (whose secret scanning blocks Google keys):
//     Sellbrite/SellbriteBulkLoader_secrets.php
//     <?php  define('GEMINI_API_KEY', 'AQ.your-key-here');
if (is_file(__DIR__ . '/SellbriteBulkLoader_secrets.php')) { require_once __DIR__ . '/SellbriteBulkLoader_secrets.php'; }

if (!defined('GS_BASE_URL'))   { define('GS_BASE_URL',   'https://cpgpublicapiv2.greysheet.com/api'); }
if (!defined('GS_API_TOKEN'))  { define('GS_API_TOKEN',  'B71FE10C-3B96-41B4-9A9E-A307DBE29B82'); }
if (!defined('GS_API_KEY'))    { define('GS_API_KEY',    '7056764F-B695-4543-994D-6471B64E083A'); }
if (!defined('GS_API_LEVEL'))  { define('GS_API_LEVEL',  'advanced'); }
if (!defined('GS_ROOT_NODE'))  { define('GS_ROOT_NODE',  1); }   // default TREE only - see gsRoots()
if (!defined('GS_TIMEOUT'))    { define('GS_TIMEOUT',    20); }

// Key comes from the secrets file above, or the GEMINI_API_KEY env var, else blank.
if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: ''); }
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); }
if (!defined('GEMINI_BASE'))    { define('GEMINI_BASE',    'https://generativelanguage.googleapis.com/v1beta'); }
if (!defined('GEMINI_TIMEOUT')) { define('GEMINI_TIMEOUT', 40); }
// (SBL_ABOUT_SELLER / SBL_EXACT_IMAGE_DEFAULT constants live in the logic file.)

function gsLog($msg)
{
    $line = 'Sellbrite ' . $msg;
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
    else { error_log($line); }
}

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
    // A request reached GreySheet (any HTTP status), so it counts against the quota.
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['gs_api_calls'] = (int) ($_SESSION['gs_api_calls'] ?? 0) + 1; }

    if ($meta['status'] === 401 || $meta['status'] === 403) { $meta['error'] = 'Auth rejected (HTTP ' . $meta['status'] . ')'; gsLog($meta['error']); return null; }
    if ($meta['status'] === 429) { $meta['error'] = 'Rate limited (429)'; gsLog($meta['error']); return null; }
    if ($meta['status'] < 200 || $meta['status'] >= 300) { $meta['error'] = 'HTTP ' . $meta['status']; gsLog($meta['error'] . ' url=' . $url); return null; }

    $data = json_decode($body, true);
    if (!is_array($data)) { $meta['error'] = 'Bad JSON'; gsLog($meta['error'] . ' url=' . $url); return null; }
    // PermitAccess=false does NOT mean the call failed. On the basic tier it
    // flags that PREMIUM fields (advanced pricing such as GreyVal) are gated -
    // the node tree and basic collectible Data still come back in this same
    // response. The proven greysheet.php crawler ignores this flag and reads
    // Data regardless, which is why it walks the whole catalog fine. So treat
    // it as a note, never a failure: keep whatever Data we were handed.
    if (isset($data['PermitAccess']) && $data['PermitAccess'] === false) {
        $msg = trim((string) ($data['AccessDeniedMessage'] ?? ''));
        $meta['permit'] = false;
        $meta['note']   = 'PermitAccess=false' . ($msg !== '' ? ': ' . $msg : '') . ' (basic tier - premium fields omitted)';
        gsLog($meta['note'] . ' url=' . $url);
    }
    return $data;
}

function gsData($resp): array
{
    return (is_array($resp) && isset($resp['Data']) && is_array($resp['Data']))
        ? array_values(array_filter($resp['Data'], 'is_array')) : [];
}

function geminiConfigured() { return GEMINI_API_KEY !== ''; }
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

if (!defined('SBL_GSMEM_TABLE')) { define('SBL_GSMEM_TABLE', 'LSCDEVLIBP.SBLMEMORYT'); }

function gsNorm($s): string
{
    return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', (string) $s))));
}
function gsMemExec(string $sql, array $params): bool
{
    $conn = function_exists('sbl_conn') ? sbl_conn() : false;
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, $sql);
    return $stmt ? (bool) @db2_execute($stmt, $params) : false;
}
function gsMemRows(string $sql, array $params = []): array
{
    return function_exists('sbl_select') ? sbl_select($sql, $params) : [];
}
function gsMemUpsert(string $kind, int $refId, string $name, string $path,
                     string $date = '', string $mm = '', int $parent = 0,
                     int $coinCount = 0, string $done = 'N'): void
{
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
function gsMemLearnNode(int $id, string $name, string $path, int $parent = 0,
                        int $coinCount = 0, string $done = 'N'): void
{
    gsMemUpsert('N', $id, $name, $path, '', '', $parent, $coinCount, $done);
}
function gsMemLearnCoins(array $coins, string $path, int $parentNodeId = 0): void
{
    foreach ($coins as $c) {
        $id = (int) ($c['Gsid'] ?? 0);
        if ($id <= 0) { continue; }
        gsMemUpsert('C', $id, (string) ($c['Name'] ?? ''), $path,
                    (string) ($c['CoinDate'] ?? ''), (string) ($c['MintMark'] ?? ''), $parentNodeId);
    }
}
function gsMemMarkDone(int $nodeId): void
{
    gsMemExec('UPDATE ' . SBL_GSMEM_TABLE . " SET done = 'Y' WHERE kind = 'N' AND ref_id = ?", [$nodeId]);
}
function gsMemNodes(): array
{
    return gsMemRows('SELECT ref_id, parent_id, name, path, coin_count, done FROM '
                   . SBL_GSMEM_TABLE . " WHERE kind = 'N'");
}
function gsMemNodeChildren(int $parentId): array
{
    return gsMemRows('SELECT ref_id, name, path, coin_count, done FROM ' . SBL_GSMEM_TABLE
                   . " WHERE kind = 'N' AND parent_id = ?", [$parentId]);
}
function gsMemSearch(string $q, int $limit = 40): array
{
    $words = array_filter(explode(' ', gsNorm($q)));
    if (!$words) { return []; }
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
/* Escape the LIKE metacharacters in a literal so a path/name used as a prefix
 * can't act as a wildcard. Pair with  ... LIKE ? ESCAPE '\'  in the SQL. */
function gsLikeEsc(string $s): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
}
/* Drill-down level 1: the broad trees present in memory (parent_id = 0):
 * U.S. Coins, U.S. Currency, World Coins, World Currency. 0 API calls. */
function gsMemRoots(): array
{
    $out = [];
    foreach (gsMemRows('SELECT ref_id, name, path FROM ' . SBL_GSMEM_TABLE
                     . " WHERE kind = 'N' AND parent_id = 0 ORDER BY name") as $r) {
        $out[] = ['node_id' => (int) $r['ref_id'], 'name' => (string) $r['name'],
                  'path' => (string) (($r['path'] ?? '') !== '' ? $r['path'] : $r['name'])];
    }
    return $out;
}
/* Drill-down level 2: the coin-holding series (leaf nodes) under a chosen root,
 * matched by catalog path so intermediate folders are flattened away. The user
 * goes root -> series -> coin. Searchable. 0 API calls. */
function gsMemSeries(string $rootPath, string $q = '', int $limit = 10000): array
{
    $sql = 'SELECT ref_id, name, path, coin_count FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'N' AND coin_count > 0";
    $params = [];
    $rootPath = trim($rootPath);
    if ($rootPath !== '') {
        $sql .= " AND (path = ? OR path LIKE ? ESCAPE '\\')";
        $params[] = $rootPath;
        $params[] = gsLikeEsc($rootPath) . ' > %';
    }
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
/* Distinct years for the coins under a node (its own dropdown, deduplicated). */
function gsMemYears(string $nodePath): array
{
    $nodePath = trim($nodePath);
    if ($nodePath === '') { return []; }
    $rows = gsMemRows('SELECT DISTINCT coin_date, name FROM ' . SBL_GSMEM_TABLE
                    . " WHERE kind = 'C' AND (path = ? OR path LIKE ? ESCAPE '\\')",
                    [$nodePath, gsLikeEsc($nodePath) . ' > %']);
    $years = [];
    foreach ($rows as $r) {
        $src = ((string) ($r['coin_date'] ?? '')) . ' ' . ((string) ($r['name'] ?? ''));
        if (preg_match('/\b(1[6-9]\d{2}|20\d{2})\b/', $src, $m)) { $years[$m[0]] = true; }
    }
    $out = array_keys($years);
    sort($out);
    return $out;
}
/* Coins under a node (any level), by catalog path. Optional $year narrows to one
 * year, optional $q narrows by coin name. Returns full names; the front-end
 * strips the shared prefix so only the distinguishing part shows. 0 API calls.
 * Limit is high so big series (Morgan Dollars 1878-1921 with all VAMs) list in
 * full; the Year dropdown is the quick way to narrow them. */
function gsMemCoins(string $nodePath, string $q = '', string $year = '', int $limit = 50000): array
{
    $nodePath = trim($nodePath);
    if ($nodePath === '') { return []; }
    $sql = 'SELECT ref_id, name, coin_date, mint_mark FROM ' . SBL_GSMEM_TABLE
         . " WHERE kind = 'C' AND (path = ? OR path LIKE ? ESCAPE '\\')";
    $params = [$nodePath, gsLikeEsc($nodePath) . ' > %'];
    $year = trim($year);
    if ($year !== '') {
        $sql .= " AND (coin_date = ? OR UPPER(name) LIKE ?)";
        $params[] = $year;
        $params[] = '%' . strtoupper(gsLikeEsc($year)) . '%';
    }
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

/* The catalog has NO single root node. Four trees sit side by side at the top:
 *   1 = U.S. Coins    2 = U.S. Currency    6 = World Coins    12 = World Currency
 * Every node under them has either child nodes OR collectibles, never both.
 * GS_ROOT_NODE is only the default tree; gsPickRoot() chooses per item. */
function gsRoots(): array
{
    return [1 => 'U.S. Coins', 2 => 'U.S. Currency', 6 => 'World Coins', 12 => 'World Currency'];
}
function gsPickRoot(array $attrs, string $desc): array
{
    $roots   = gsRoots();
    $country = strtolower(trim((string) ($attrs['country_of_manufacture'] ?? '')));
    $text    = strtolower($desc . ' ' . (string) ($attrs['paper_money_type'] ?? ''));
    $paper   = trim((string) ($attrs['paper_money_type'] ?? '')) !== ''
            || trim((string) ($attrs['paper_money_grade_designation'] ?? '')) !== ''
            || preg_match('/\b(note|banknote|currency|paper money)\b/', $text);
    $world   = ($country !== '' && !in_array($country, ['united states', 'united states of america', 'usa', 'us'], true))
            || preg_match('/\b(world|foreign)\b/', $text);
    $id = $world ? ($paper ? 12 : 6) : ($paper ? 2 : (int) GS_ROOT_NODE);
    return ['id' => $id, 'name' => $roots[$id] ?? ('(root ' . $id . ')')];
}

function gsResolveLeaf(array $attrs, array &$trace = []): array
{
    $none = ['coins' => [], 'path' => ''];
    $category = trim((string) ($attrs['category_name'] ?? ''));
    $desc = trim(implode(' ', array_filter([$attrs['year'] ?? '', $attrs['mint_mark'] ?? '', $category,
                $attrs['denomination'] ?? '', $attrs['grade'] ?? '', $attrs['strike_type'] ?? ''])));
    if ($category === '' && $desc === '') { return $none; }
    $target = $category !== '' ? $category : $desc;

    $root   = gsPickRoot($attrs, $desc);
    $nodeId = (int) $root['id'];
    $path   = $root['name'];
    $trace[] = 'root:' . $root['name'];
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
        if (!$children) {
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
        if ($pick['coins'] > 0) {
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

function gsResolve(array $attrs, array &$trace = []): int
{
    $leaf = gsResolveLeaf($attrs, $trace);
    return $leaf['coins'] ? gsPickCoin($leaf['coins'], $attrs) : 0;
}

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

function gsCollectible(int $gsId, &$meta = []): array
{
    $meta = [];
    if ($gsId <= 0) { return []; }
    $resp = gsApiGet('GetCollectibleRequest', ['GsId' => $gsId], $meta);
    return gsData($resp)[0] ?? [];
}
function gsPricing(int $gsId, $grade = null, &$meta = []): array
{
    $meta = [];
    if ($gsId <= 0) { return []; }
    $params = ['Gsid' => $gsId];
    if ($grade !== null && ctype_digit((string) $grade)) { $params['Grade'] = (int) $grade; }
    $resp  = gsApiGet('GetPricingRequest', $params, $meta);
    $first = gsData($resp)[0] ?? [];
    return $first['PricingData'][0] ?? [];
}
function gsPriceNum($v): string
{
    $v = preg_replace('/[^0-9.]/', '', (string) $v);
    return is_numeric($v) ? $v : '';
}
/* Normalize a free-text GreySheet composition to an ODS "Valid Values" option
 * (e.g. "99.99% gold" -> "Gold", "Copper-Nickel Clad" stays). */
function sbl_norm_composition(string $c): string
{
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
    return trim($c);
}
/* Normalize a GreySheet series name ("Lincoln Cents - Wheat Reverse
 * (1909-1958)", "Morgan Dollars") to the PCC STORE CATEGORY from the ODS
 * VLOOKUP sheet ("Lincoln Wheat Small Cent", "Morgan Dollar"). These store
 * categories are what Sellbrite's "SKU of Parent Product" carries. Returns the
 * store category, or the input unchanged if nothing matches well. */
function sbl_norm_category(string $gs): string
{
    // Known renames the token matcher can't infer (GreySheet name -> store category).
    $l = strtolower($gs);
    if (preg_match('/silver eagle/', $l))              { return 'Silver Bullion Coin'; }
    if (preg_match('/gold (eagle|buffalo)/', $l))      { return 'Gold Bullion Coin'; }
    if (preg_match('/platinum eagle/', $l))            { return 'Platinum Bullion Coin'; }
    if (preg_match('/palladium eagle/', $l))           { return 'Palladium Bullion Coin'; }
    if (preg_match('/america the beautiful.*5 oz/', $l)) { return 'Silver Bullion Coin'; }
    // Store categories never carry dates: whatever we return, strip the
    // "(2022-2025)" ranges and bare years GreySheet appends to series names.
    $clean = preg_replace('/\((?:[^)]*\d{4}[^)]*)\)/u', ' ', $gs);          // "(1909-1958)", "(2022-Present)"
    $clean = preg_replace('/\b\d{4}\s*[-\x{2013}]\s*(?:\d{2,4}|present|date)\b/iu', ' ', $clean);
    $clean = trim(preg_replace('/\s+/', ' ', $clean), " -\t");
    if ($clean === '') { $clean = trim($gs); }
    $cats = array_keys(Schema::lookups()['category_copy'] ?? []);
    if (!$cats || trim($gs) === '') { return $clean; }
    $tok = static function (string $s): array {
        $s = strtolower(preg_replace('/\([^)]*\)/', ' ', $s));       // drop "(1909-1958)"
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        $words = [];
        foreach (preg_split('/\s+/', $s, -1, PREG_SPLIT_NO_EMPTY) as $w) {
            if (preg_match('/^\d{3,4}$/', $w)) { continue; }          // bare years
            if (strlen($w) > 3 && substr($w, -1) === 's') { $w = substr($w, 0, -1); }  // dollars->dollar
            $words[$w] = true;
        }
        return $words;
    };
    $g = $tok($gs);
    if (!$g) { return $clean; }
    $best = ''; $bestScore = 0.0;
    foreach ($cats as $cat) {
        $c = $tok($cat);
        if (!$c) { continue; }
        $inter = count(array_intersect_key($g, $c));
        if ($inter === 0) { continue; }
        // Jaccard, weighted toward covering the STORE category's words.
        $score = ($inter / count($c)) * 0.7 + ($inter / count($g)) * 0.3;
        if ($score > $bestScore) { $bestScore = $score; $best = $cat; }
    }
    return $bestScore >= 0.7 ? $best : $clean;
}
/* Mint letter -> mint city (from the ODS Mint Location logic). */
function sbl_mint_location(string $mm): string
{
    $mm = trim($mm);
    // No mint mark = no location claim (early Philadelphia coins carry none,
    // but so do many others - leave it for the operator).
    if ($mm === '' || strcasecmp($mm, 'No Mint Mark') === 0) { return ''; }
    $map = ['C' => 'Charlotte', 'CC' => 'Carson City', 'D' => 'Denver', 'O' => 'New Orleans',
            'P' => 'Philadelphia', 'S' => 'San Francisco', 'W' => 'West Point',
            'M' => 'Manila', 'MO' => 'Mexico City'];
    return $map[strtoupper($mm)] ?? '';
}
/* Snap a value to the closest allowed option (exact, then case-insensitive). */
function sbl_snap(string $v, array $opts): string
{
    $v = trim($v);
    if ($v === '') { return ''; }
    foreach ($opts as $o) { if ($o === $v) { return $o; } }
    foreach ($opts as $o) { if (strcasecmp($o, $v) === 0) { return $o; } }
    return $v;   // leave as-is; the human can correct it
}
/* The ODS-derived field guide: for each Sellbrite field, where its value comes
 * from, how to fill it, the allowed options (from the ODS "Valid Values" sheet)
 * and any hardcoded constant. Drives BOTH the deterministic map and the Gemini
 * prompt. Only the fields the autofill is responsible for are listed. */
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
        'coin_type'      => ['desc' => 'OPERATOR-PICKED from the valid values - leave EMPTY; do not guess'],
        'year'           => ['src' => 'CoinDate', 'desc' => '4-digit issue year only'],
        'mint_mark'      => ['src' => 'MintMark', 'desc' => 'mint letter (S,D,CC,O,P,W...) or exactly "No Mint Mark" if none'],
        'mint_location'  => ['src' => 'from mint_mark', 'desc' => 'CC=Carson City, D=Denver, O=New Orleans, S=San Francisco, W=West Point, P/none=Philadelphia'],
        'denomination'   => ['src' => 'DenominationShort (US) / DenominationLong (world)', 'desc' => 'face value, e.g. 1C, 50C, $1 for US; "5 Euros" spoken form for world coins'],
        'coin_variety_1' => ['src' => 'Variety'],
        'coin_variety_2' => ['src' => 'Variety2'],
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
        'certification'  => ['opts' => $cert, 'const' => 'Uncertified', 'desc' => 'Uncertified unless slabbed'],
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
        'extended_description' => ['desc' => 'EXPANDED DESCRIPTION for the whole category: 2-4 factual sentences built from YOUR description PLUS the GreySheet GeneralNotes/Obverse/Reverse text (composition, design, designer, history). Write it so the SAME text fits every coin in this category - no grade, date, mint or price.'],
        'feature_4'      => ['desc' => 'a COLLECTOR\'S NOTE about the SERIES (history, design, collector appeal) - category-level, 2-4 sentences, no this-coin grade/date/price. Do NOT add the "COLLECTOR\'S NOTE:" prefix; the system adds it.'],
        'diameter'       => ['src' => 'Diameter', 'desc' => 'millimeters, number only'],
        'weight'         => ['src' => 'WeightOunces', 'desc' => 'coin weight in troy ounces, number only'],
        'search_terms'   => ['desc' => '8-15 lowercase space-separated keywords: metal, type, denomination, mint, theme, "numismatics", "coin"'],
        'price'          => ['src' => 'pricing CpgVal', 'req' => true, 'desc' => 'CPG retail; the operator confirms it'],
        'cost'           => ['src' => 'pricing GreyVal', 'req' => true, 'desc' => 'wholesale (advanced tier); the operator confirms it'],
    ];
}
/* Deterministic mapping: fills every field it reliably can straight from the
 * GreySheet data + the ODS constants. This is the trustworthy base; Gemini only
 * fills the gaps it leaves (coin_type, refinements). */
function gsMapToProduct(array $c): array
{
    $g = static fn(string $k): string => (isset($c[$k]) && is_scalar($c[$k])) ? trim((string) $c[$k]) : '';
    // Paper money (U.S./World Currency trees): the coin-only fields (mint mark
    // and location) are never stamped onto a note.
    $isPaper = false; $isWorld = false;
    if (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) {
        $rootName = strtolower((string) (($c['CatalogPath'][0]['Name'] ?? '')));
        $isPaper  = strpos($rootName, 'currency') !== false;
        $isWorld  = strpos($rootName, 'world') !== false;
    }
    $row = [];
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
    // Designation abbreviation (Des): the special strike/color designation only -
    // color RD/RB/BN, cameo CAM/DCAM/UCAM, proof-like PL/DMPL, full-detail
    // FB/FBL/FS/5FS/FT/FH. GreySheet stores THIS in "Other" (e.g. "DCAM","FB",
    // "RD","RD DCAM"). GreySheet "Desg" is the grade TYPE (MS/PR/SP) - it drives
    // circulated/uncirculated below, and must NOT be used as a designation.
    if ($g('Other') !== '')             { $row['designation_abbrivation'] = $g('Other'); }
    if ($g('Composition') !== '')       { $row['composition'] = sbl_norm_composition($g('Composition')); }
    if ($g('Fineness')    !== '')       { $row['fineness']    = $g('Fineness'); }
    // Added per Des: diameter (mm) and weight (troy oz) straight from GreySheet.
    if ($g('Diameter') !== '')          { $row['diameter']    = $g('Diameter'); }
    // GreySheet CoinShape = Sellbrite Bullion Shape.
    if ($g('CoinShape') !== '')         { $row['bullion_shape'] = $g('CoinShape'); }
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces'])) {
        $row['weight'] = rtrim(rtrim(number_format((float) $c['WeightOunces'], 4, '.', ''), '0'), '.');
    }

    $strike    = $g('StrikeType');
    $gradeType = strtoupper($g('Desg'));   // MS / PR / PF / SP / SMS - the grade type
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

    if (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) {
        $last = end($c['CatalogPath']);
        if (is_array($last) && !empty($last['Name'])) {
            // Normalize the GreySheet series to the PCC STORE CATEGORY (the ODS
            // VLOOKUP "Store Category" = Sellbrite "SKU of Parent Product").
            $row['category_name'] = sbl_norm_category(trim((string) $last['Name']));
        }
        foreach ($c['CatalogPath'] as $node) {
            if (!empty($node['CountryName'])) { $row['country_of_manufacture'] = trim((string) $node['CountryName']); break; }
        }
        // World trees name the country as the path's second node even when the
        // CountryName attribute is blank: "World Coins > Austria > ...".
        if (($row['country_of_manufacture'] ?? '') === '' && $isWorld && count($c['CatalogPath']) > 1) {
            $n = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', (string) ($c['CatalogPath'][1]['Name'] ?? '')));
            if ($n !== '') { $row['country_of_manufacture'] = $n; }
        }
        // Coin Type: TRY to autofill by best-matching the tree's valid values
        // against the series/category wording ("Morgan Dollars" -> "Morgan",
        // "Lincoln Cents - Wheat Reverse" -> "Lincoln Wheat"). No stored
        // mapping - longest option whose every word appears wins; no match
        // leaves the dropdown to the operator.
        if (($row['coin_type'] ?? '') === '') {
            $poolKey = ($isWorld ? 'world' : 'us') . '_' . ($isPaper ? 'currency' : 'coins');
            $hay = strtolower(($row['category_name'] ?? '') . ' '
                 . implode(' ', array_map(static fn($n) => is_array($n) ? (string) ($n['Name'] ?? '') : '', $c['CatalogPath'])));
            $best = '';
            // GreySheet says "Silver Eagles"; the valid value is "American Eagle".
            if (preg_match('/(silver|gold|platinum|palladium) eagle/', $hay)) { $best = 'American Eagle'; }
            elseif (strpos($hay, 'gold buffalo') !== false) { $best = 'American Buffalo'; }
            else {
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
    // No per-category facts anywhere: GreySheet provides denomination,
    // composition, fineness and weight with the coin; the category_copy KEYS
    // only exist to normalize the series name into the store category.
    // Precious-metal content = metal weight x fineness (troy oz), precious metals only.
    $fin = (float) preg_replace('/[^0-9.]/', '', $g('Fineness'));
    if (!empty($c['WeightOunces']) && is_numeric($c['WeightOunces']) && $fin > 0 && $fin <= 1) {
        $comp = strtolower($g('Composition'));
        if (preg_match('/silver|gold|platinum|palladium/', $comp)) {
            $row['total_precious_metal_content'] = rtrim(rtrim(number_format((float) $c['WeightOunces'] * $fin, 4, '.', ''), '0'), '.') . ' oz';
        }
    }
    // ODS constants. (Features 1/2/3/5 are derived by Computer per Des's layout:
    // 1+2 = description split, 3 = exact-image line, 5 = PCC blurb. "Coin
    // Collectible" is appended to the TITLE by Computer, not stored here -
    // title_suffix is left blank for the operator's grade/error/packaging notes.)
    $row['certification'] = 'Uncertified';
    $row['exact_image']   = SBL_EXACT_IMAGE_DEFAULT;
    // United States only for the U.S. trees; a world coin whose path didn't
    // name a country stays blank for the operator's dropdown pick.
    if (($row['country_of_manufacture'] ?? '') === '' && !$isWorld) {
        $row['country_of_manufacture'] = 'United States';
    }
    return array_filter($row, static fn($v) => $v !== '' && $v !== null);
}
/* Compact, populated-only view of the coin for the AI prompt (drops the ~55
 * empty / currency-only keys so the model isn't reading noise). */
function gs_coin_facts(array $c): array
{
    $keys = ['Name','CoinDate','MintMark','DenominationShort','DenominationLong','Variety','Variety2',
             'Desg','Other','Prefix','Composition','Fineness','StrikeType','WeightOunces','WeightGrams','Diameter',
             'Designer','Edge','Mintage','Rarity','CoinShape','PcgsNumber','IsSet','IsType','CpgVal','GreyVal',
             'GeneralNotes','ObverseDescription','ReverseDescription','ObverseLettering','ReverseLettering',
             'PriceLow','PriceHigh'];
    $out = [];
    foreach ($keys as $k) {
        if (isset($c[$k]) && $c[$k] !== '' && $c[$k] !== null && $c[$k] !== 0 && $c[$k] !== '0') { $out[$k] = $c[$k]; }
    }
    if (!empty($c['CatalogPath']) && is_array($c['CatalogPath'])) {
        $out['CatalogPath'] = implode(' > ', array_map(static fn($n) => (string) ($n['Name'] ?? ''), $c['CatalogPath']));
    }
    return $out;
}
/* The allowed values for a field, straight from the ODS "Valid Values" sheet
 * (Schema::values), with group separators / hint rows removed. This is the ONE
 * source of truth the agent must conform to, both in the prompt and when
 * snapping its answer. */
function sbl_field_options(string $name): array
{
    $col = Schema::byName()[$name] ?? null;
    $opts = $col ? Schema::optionsFor($col) : [];
    if (!$opts) { $opts = sbl_field_guide()[$name]['opts'] ?? []; }
    return array_values(array_filter($opts, static fn($o) => !preg_match('/^\s*(-{2,}|\*{3})/', (string) $o)));
}
function sbl_field_spec(): string
{
    static $spec = null;
    if ($spec !== null) { return $spec; }
    $byName = Schema::byName();
    $lines  = [];
    foreach (sbl_field_guide() as $name => $gd) {
        $label = $byName[$name]['label'] ?? $name;
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
/* Snap every controlled field in a row to its ODS "Valid Values" list. Runs on
 * the final row so both the deterministic base and the AI output conform. */
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
/* Gemini ALWAYS writes the category-level copy fresh from the GreySheet
 * facts (GeneralNotes / obverse / reverse) - no saved-listing template. */
function gsAiMap(array $coin): array
{
    $base = gsMapToProduct($coin);   // trustworthy deterministic fields + ODS constants
    if (!geminiConfigured()) { return sbl_snap_row($base); }

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
         . "4. feature_4 is a COLLECTOR'S NOTE about the series (why collectors want it), also category-level "
         . "and distinct from extended_description; do NOT add the \"COLLECTOR'S NOTE:\" label - the system adds it.\n"
         . "5. Do NOT fill feature_1, feature_2, feature_3 or feature_5 - the system derives them from the "
         . "description, condition, image line and company blurb.\n"
         . "Return ONLY a JSON object keyed by field machine-name.";
    $user = "TARGET FIELDS:\n" . sbl_field_spec() . "\n\nGREYSHEET COIN FACTS:\n"
          . json_encode(gs_coin_facts($coin), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m));

    // Deterministic base wins; the AI only fills the gaps it left (e.g. coin_type).
    $row = $base;
    foreach ($ai as $k => $v) { if ($v !== '' && ($base[$k] ?? '') === '') { $row[$k] = $v; } }

    // Expanded Description fallback: if the AI failed (e.g. 429), use the
    // GreySheet GeneralNotes cleaned up.
    if (trim((string) ($row['extended_description'] ?? '')) === '' && !empty($coin['GeneralNotes'])) {
        $notes = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' ', (string) $coin['GeneralNotes'])));
        $notes = trim(preg_replace('/\s+/', ' ', $notes));
        if ($notes !== '') { $row['extended_description'] = mb_substr($notes, 0, 1900); }
    }
    // COLLECTOR'S NOTE fallback: when the AI didn't write one, reuse the
    // category-level expanded copy so feature 4 is never blank (Computer adds
    // the "COLLECTOR'S NOTE:" label).
    if (trim((string) ($row['feature_4'] ?? '')) === '' && trim((string) ($row['extended_description'] ?? '')) !== '') {
        $row['feature_4'] = mb_substr(trim((string) $row['extended_description']), 0, 1400);
    }
    return sbl_snap_row($row);
}
/* Listing-content gap fill: Gemini writes ONLY the empty ones among
 * description / extended_description / feature_4 (collector's note), in the
 * exact same house layout and rules as the autofill writer. Product Name,
 * features 1/2/3/5 and Search Terms stay formula-derived, and anything the
 * operator already typed is never overwritten. Works from the form's own
 * facts, so it also covers non-GreySheet products (watches, calendars...). */
function gsListingFill(array $post): array
{
    $want = [];
    foreach (['description', 'extended_description', 'feature_4'] as $f) {
        $v = trim((string) ($post[$f] ?? ''));
        if ($v === '' || strncmp($v, '***', 3) === 0) { $want[] = $f; }
    }
    if (!$want) { return ['ok' => true, 'row' => [], 'via' => 'nothing empty', 'error' => '']; }

    $row = [];
    // Gemini writes everything fresh - no saved-listing template.
    if ($want && geminiConfigured()) {
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
             . "4. feature_4 is a COLLECTOR'S NOTE about the series (why collectors want it), category-level and "
             . "distinct from extended_description; do NOT add the \"COLLECTOR'S NOTE:\" label - the system adds it.\n"
             . "5. Return ONLY a JSON object with EXACTLY the requested field names - no other fields.";
        $user = "FIELDS TO WRITE (only these):\n" . $spec
              . "\nPRODUCT FACTS (from the entry form):\n"
              . json_encode($facts, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $ai = sbl_clean_ai_row(geminiJson($sys, $user, $m));
        foreach ($want as $f) {
            if (trim((string) ($ai[$f] ?? '')) !== '') { $row[$f] = trim((string) $ai[$f]); }
        }
    }
    // COLLECTOR'S NOTE fallback: reuse the expanded copy (form's or just written).
    if (in_array('feature_4', $want, true) && trim((string) ($row['feature_4'] ?? '')) === '') {
        $src = trim((string) ($row['extended_description'] ?? '')) ?: trim((string) ($post['extended_description'] ?? ''));
        if ($src !== '' && strncmp($src, '***', 3) !== 0) { $row['feature_4'] = mb_substr($src, 0, 1400); }
    }
    $err = !$row && !geminiConfigured() ? 'GEMINI_API_KEY not set (add the secrets file).' : '';
    return ['ok' => $err === '', 'row' => $row, 'via' => 'listing gap fill', 'error' => $err];
}
function gsSearch(string $q): array
{
    $q = trim($q);
    if ($q === '') { return ['ok' => false, 'matches' => [], 'error' => 'Type something to search for.']; }
    return ['ok' => true, 'matches' => gsMemSearch($q), 'error' => ''];
}
function gs_finalize(array $row, $source, string $via, array $calls = []): array
{
    $row   = Computer::apply($row);
    $check = Validator::check($row);
    return ['ok' => true, 'found' => true, 'row' => $row, 'statuses' => $check['statuses'],
            'messages' => $check['messages'], 'valid' => $check['valid'], 'source' => $source,
            'error' => '', 'via' => $via, 'calls' => $calls];
}
function gsImport(array $params): array
{
    $base = ['ok' => false, 'found' => false, 'row' => [], 'statuses' => [], 'messages' => [],
             'valid' => false, 'source' => null, 'error' => '', 'via' => '', 'calls' => []];
    $calls = [];

    $gsId = (int) ($params['gs_id'] ?? 0);
    if ($gsId <= 0) {
        $trace = [];
        $gsId  = gsResolve($params, $trace);
        if ($trace) { $calls[] = ['call' => 'Navigate GreySheet tree', 'got' => implode(' > ', $trace)]; }
        if ($gsId <= 0) { return array_merge($base, ['ok' => true, 'calls' => $calls]); }
        gsLog('resolved "' . ($params['category_name'] ?? '') . '" -> GsId ' . $gsId . ' via ' . implode(' > ', $trace));
    }

    $coin = gsCollectible($gsId, $mCol);
    $calls[] = ['call' => 'GetCollectibleRequest?GsId=' . $gsId, 'ms' => (int) ($mCol['ms'] ?? 0),
                'got' => $coin ? ('"' . ($coin['Name'] ?? '?') . '"  (' . count($coin) . ' fields)') : 'nothing returned'];
    if (!$coin) { return array_merge($base, ['ok' => true, 'calls' => $calls]); }
    $rawCoin = $coin;   // untouched collectible, for the readout box

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
    // every time - no saved-listing template.
    $row = gsAiMap($coin);
    if (geminiConfigured()) { $calls[] = ['call' => 'Gemini map (' . GEMINI_MODEL . ')', 'got' => count($row) . ' fields filled']; }
    if (($coin['CpgVal'] ?? '') !== '' && ($row['price'] ?? '') === '') { $row['price'] = gsPriceNum($coin['CpgVal']); }
    if (($coin['GreyVal'] ?? '') !== '' && ($row['cost'] ?? '') === '') { $row['cost'] = gsPriceNum($coin['GreyVal']); }
    // Pricing names the grade it priced (GradeLabel): autofill Grade with it,
    // normalized to the house "MS 65" shape, unless the operator already chose.
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