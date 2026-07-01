<?php
/*    ***************************************************  -->
<!--  * Program Name - greysheet.php                     *  -->
<!--  *                                                 *  -->
<!--  * Standalone GreySheet CDN Public API v2 tester.   *  -->
<!--  * NO dependency on the M-Power stack or any other  *  -->
<!--  * file in this repo - drop in a key and run.       *  -->
<!--  ***************************************************   */

/*
 * WHAT WE LEARNED FROM THE LIVE API (confirmed 2026-07-01)
 * --------------------------------------------------------
 * Hitting the base URL returns GreySheet's own endpoint directory. The REAL
 * operations (there is NO "SearchRequest" - search returns 405 / not
 * implemented, so the catalog is browsed by walking a NODE TREE):
 *
 *   GetNodeRequest            ?NodeId=   -> NodeResponse         (one node)
 *   GetNodeChildrenRequest    ?NodeId=   -> NodeResponse         (child nodes)
 *   GetCollectibleByNodeRequest ?NodeId= -> CollectibleResponse  (coins in a node)
 *   GetCollectibleRequest     ?...       -> CollectibleResponse  (one coin)
 *   GetPricingRequest         ?...       -> GetPricingResponse   (prices)
 *
 * Every response is wrapped: { "Data": [ ... ], "Total", "OpCode", ... }.
 * Node 1 = "U.S. Coins", the root of the tree.
 *
 * MODES
 *   index    - dump GreySheet's endpoint directory (the base-URL response).
 *   node     - GetNodeRequest?NodeId=<node>            (one node's data).
 *   children - GetNodeChildrenRequest?NodeId=<node>    (list child nodes).
 *   coins    - GetCollectibleByNodeRequest?NodeId=<node> (list coins in a node).
 *   crawl    - walk the tree from <node> down, listing every node + coin name.
 *   ping     - connectivity/auth check against the base URL (shows all headers).
 *   probe    - legacy: fire a matrix of candidate routes (kept for diagnostics).
 *
 * HOW TO GIVE IT YOUR KEY (never commit real keys)
 *   Env:    set GS_API_TOKEN / GS_API_KEY  (and optional GS_BASE_URL / GS_API_LEVEL)
 *   Or:     paste into the CONFIG block below (delete before committing)
 *   Or:     paste into the browser form fields (POSTed, not logged in the URL)
 *
 * HOW TO RUN
 *   Localhost (browser): from this folder run  php -S localhost:8000
 *                        then open http://localhost:8000/greysheet.php
 *   CLI:
 *       php greysheet.php index
 *       php greysheet.php node     --node=1
 *       php greysheet.php children --node=1
 *       php greysheet.php coins    --node=17453
 *       php greysheet.php crawl    --node=1 --max=60 --depth=2 --delay=120
 */

/* ----------------------------- CONFIG ---------------------------------- */
$CFG = [
    'base'    => getenv('GS_BASE_URL')  ?: 'https://cpgpublicapiv2.greysheet.com/api',
    'token'   => getenv('GS_API_TOKEN') ?: '',   // <-- or paste here (then delete)
    'key'     => getenv('GS_API_KEY')   ?: '',   // <-- or paste here (then delete)
    'level'   => getenv('GS_API_LEVEL') ?: 'basic',
    'timeout' => 25,
];

/* Legacy probe matrix (search is confirmed NOT to exist; kept for diagnostics). */
$NODE_CANDIDATES = [
    ['path' => 'GetNodeRequest',         'param' => 'NodeId'],
    ['path' => 'GetNodeChildrenRequest', 'param' => 'NodeId'],
];
$SEARCH_CANDIDATES = [
    ['path' => 'GetCollectibleByNodeRequest', 'param' => 'NodeId'],
    ['path' => 'GetCollectibleRequest',       'param' => 'CollectibleId'],
];

$IS_CLI = (PHP_SAPI === 'cli');

/* --------------------------- CORE REQUEST ------------------------------ */
/**
 * One GET against base+path. Captures EVERY response header (this is a
 * diagnostic tool), timing, status, and the raw body. No app dependencies.
 */
function gs_call(array $cfg, string $path, array $params): array
{
    $meta = ['status' => 0, 'ms' => 0, 'url' => '', 'err' => '', 'headers' => [], 'body' => ''];

    if ($cfg['level'] !== '' && !isset($params['apiLevel'])) { $params['apiLevel'] = $cfg['level']; }
    $url = rtrim($cfg['base'], '/') . '/' . ltrim($path, '/');
    if ($params) { $url .= '?' . http_build_query($params); }
    $meta['url'] = $url;

    $headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => (int) $cfg['timeout'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'x-api-token: ' . $cfg['token'],
            'x-api-key: '   . $cfg['key'],
            'Accept: application/json',
        ],
        CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$headers) {
            $p = explode(':', $h, 2);
            if (count($p) === 2) { $headers[trim($p[0])] = trim($p[1]); }
            return strlen($h);
        },
    ]);
    $t0   = microtime(true);
    $body = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);

    if ($body === false) {
        $meta['err'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
        curl_close($ch);
        return $meta;
    }
    $meta['status']  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $meta['headers'] = $headers;
    $meta['body']    = (string) $body;
    curl_close($ch);
    return $meta;
}

/** Short human hint for a status code. */
function gs_hint(int $status): string
{
    switch (true) {
        case $status === 0:                    return 'no response (network/DNS/TLS — see err)';
        case $status === 200:                  return 'OK';
        case $status === 400:                  return 'bad request (route ok, but wrong/missing param?)';
        case $status === 401:                  return 'unauthorized (token/key wrong or missing)';
        case $status === 403:                  return 'forbidden (key inactive, wrong tier, or gateway block)';
        case $status === 404:                  return 'not found (route or id does not exist)';
        case $status === 405:                  return 'operation does not exist for this service (405)';
        case $status === 429:                  return 'rate limited (back off; see RateLimit-* headers)';
        case $status >= 200 && $status < 300:  return 'success';
        case $status >= 500:                   return 'server error';
        default:                               return 'unexpected';
    }
}

/** Pretty JSON if the body parses; otherwise the raw text (optionally clipped). */
function gs_pretty(string $body, int $clip = 0): string
{
    $data = json_decode($body, true);
    $out  = (json_last_error() === JSON_ERROR_NONE)
        ? json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : $body;
    if ($clip > 0 && strlen($out) > $clip) { $out = substr($out, 0, $clip) . "\n... [clipped]"; }
    return $out;
}

/** Mask a secret for display: keep first 4 chars, star the rest. */
function gs_mask(string $s): string
{
    if ($s === '') { return '(empty)'; }
    return substr($s, 0, 4) . str_repeat('*', max(3, strlen($s) - 4)) . ' (len ' . strlen($s) . ')';
}

/* ------------------------- RESPONSE HELPERS ---------------------------- */
/** Decode a gs_call result's body to an array (empty array on failure). */
function gs_json(array $meta): array
{
    $d = json_decode($meta['body'] ?? '', true);
    return is_array($d) ? $d : [];
}

/** The list of item objects in a wrapped response: the "Data" array. */
function gs_items(array $resp): array
{
    if (isset($resp['Data']) && is_array($resp['Data'])) {
        return array_values(array_filter($resp['Data'], 'is_array'));
    }
    foreach ($resp as $v) {                        // fallback: first array-of-objects
        if (is_array($v) && isset($v[0]) && is_array($v[0])) { return $v; }
    }
    return [];
}

/** Best human name field on an item (node or coin). */
function gs_name(array $item): string
{
    foreach (['Name', 'FullName', 'CollectibleName', 'Title', 'DisplayName', 'Description'] as $k) {
        if (isset($item[$k]) && is_scalar($item[$k]) && trim((string) $item[$k]) !== '') {
            return trim((string) $item[$k]);
        }
    }
    return '(unnamed)';
}

/** Best id field on an item. */
function gs_id(array $item): string
{
    foreach (['Id', 'NodeId', 'CollectibleId', 'Node_Id', 'Collectible_Id'] as $k) {
        if (isset($item[$k]) && is_scalar($item[$k])) { return (string) $item[$k]; }
    }
    return '?';
}

/* ------------------------------- CRAWL --------------------------------- */
/**
 * Breadth-first walk from a root node: list every node and the coin names
 * under it. Bounded by $maxNodes and $maxDepth; polite $delayMs between calls;
 * stops cleanly on 429. Child metadata comes from the parent's children list,
 * so each node costs at most one coins call + one children call.
 */
function gs_crawl(array $cfg, string $rootId, int $maxNodes, int $maxDepth, int $delayMs, array &$stats): array
{
    if (function_exists('set_time_limit')) { @set_time_limit(0); }
    $lines = [];
    $stats = ['nodes' => 0, 'coins' => 0, 'calls' => 0, 'last_headers' => [], 'stopped' => ''];

    $seed = gs_call($cfg, 'GetNodeRequest', ['NodeId' => $rootId]);
    $stats['calls']++;
    $stats['last_headers'] = $seed['headers'] ?: $stats['last_headers'];
    if ($seed['status'] !== 200) {
        $stats['stopped'] = 'root GetNodeRequest returned HTTP ' . $seed['status'] . ' (' . gs_hint($seed['status']) . ')';
        return $lines;
    }
    $root  = gs_items(gs_json($seed))[0] ?? [];
    $queue = [[
        'id'    => (gs_id($root) !== '?') ? gs_id($root) : $rootId,
        'name'  => gs_name($root),
        'depth' => 0,
        'child' => (int) ($root['NodeChildrenCountLive'] ?? 0),
        'coll'  => (int) ($root['CollectibleChildrenCountLive'] ?? 0),
    ]];

    while ($queue && $stats['nodes'] < $maxNodes) {
        $n = array_shift($queue);
        $stats['nodes']++;
        $pad = str_repeat('  ', $n['depth']);
        $lines[] = $pad . sprintf('# [%s] %s  (children=%d, coins=%d)', $n['id'], $n['name'], $n['child'], $n['coll']);

        if ($n['coll'] > 0) {
            $cm = gs_call($cfg, 'GetCollectibleByNodeRequest', ['NodeId' => $n['id']]);
            $stats['calls']++;
            $stats['last_headers'] = $cm['headers'] ?: $stats['last_headers'];
            if ($cm['status'] === 429) { $stats['stopped'] = 'rate limited (429) while listing coins'; break; }
            if ($cm['status'] !== 200) {
                $lines[] = $pad . '  (coins call HTTP ' . $cm['status'] . ' - ' . gs_hint($cm['status']) . ')';
            } else {
                foreach (gs_items(gs_json($cm)) as $coin) {
                    $lines[] = $pad . '  - ' . gs_name($coin) . ' [' . gs_id($coin) . ']';
                    $stats['coins']++;
                }
            }
            usleep($delayMs * 1000);
        }

        if ($n['depth'] < $maxDepth && $n['child'] > 0 && $stats['nodes'] < $maxNodes) {
            $chm = gs_call($cfg, 'GetNodeChildrenRequest', ['NodeId' => $n['id']]);
            $stats['calls']++;
            $stats['last_headers'] = $chm['headers'] ?: $stats['last_headers'];
            if ($chm['status'] === 429) { $stats['stopped'] = 'rate limited (429) while listing children'; break; }
            if ($chm['status'] !== 200) {
                $lines[] = $pad . '  (children call HTTP ' . $chm['status'] . ' - ' . gs_hint($chm['status']) . ')';
            } else {
                foreach (gs_items(gs_json($chm)) as $child) {
                    $queue[] = [
                        'id'    => gs_id($child),
                        'name'  => gs_name($child),
                        'depth' => $n['depth'] + 1,
                        'child' => (int) ($child['NodeChildrenCountLive'] ?? 0),
                        'coll'  => (int) ($child['CollectibleChildrenCountLive'] ?? 0),
                    ];
                }
            }
            usleep($delayMs * 1000);
        }
    }
    if (!$stats['stopped'] && $queue && $stats['nodes'] >= $maxNodes) {
        $stats['stopped'] = 'hit --max=' . $maxNodes . ' node cap (raise --max to go further)';
    }
    return $lines;
}

/* --------------------------- LEGACY PROBE ------------------------------ */
function gs_probe(array $cfg, $nodeId, string $term, array $nodeCands, array $searchCands): array
{
    $rows = [];
    foreach ($nodeCands as $c) {
        $m = gs_call($cfg, $c['path'], [$c['param'] => $nodeId]);
        $rows[] = ['kind' => 'node', 'try' => $c['path'] . '?' . $c['param'] . '=', 'meta' => $m];
    }
    foreach ($searchCands as $c) {
        $m = gs_call($cfg, $c['path'], [$c['param'] => $nodeId]);
        $rows[] = ['kind' => 'coin', 'try' => $c['path'] . '?' . $c['param'] . '=', 'meta' => $m];
    }
    return $rows;
}

/* ---------------------------- REQUEST IN ------------------------------- */
$mode = 'form';
$opt  = ['node' => '1', 'term' => 'Morgan Dollar', 'path' => '', 'param' => '',
         'base' => '', 'level' => '', 'max' => '60', 'depth' => '2', 'delay' => '120'];

if ($IS_CLI) {
    $mode = $argv[1] ?? 'index';
    foreach (array_slice($argv, 2) as $a) {
        if (preg_match('/^--([a-z]+)=(.*)$/', $a, $mm)) { $opt[$mm[1]] = $mm[2]; }
    }
} else {
    $src  = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $mode = $src['mode'] ?? 'form';
    foreach ($opt as $k => $_) { if (isset($src[$k]) && $src[$k] !== '') { $opt[$k] = (string) $src[$k]; } }
    if (!empty($src['token'])) { $CFG['token'] = (string) $src['token']; }
    if (!empty($src['key']))   { $CFG['key']   = (string) $src['key']; }
}
if ($opt['base']  !== '') { $CFG['base']  = $opt['base']; }
if ($opt['level'] !== '') { $CFG['level'] = $opt['level']; }

$keysMissing = ($CFG['token'] === '' || $CFG['key'] === '');

/* ------------------------------ RUN ------------------------------------ */
function gs_run(string $mode, array $cfg, array $opt, array $nodeCands, array $searchCands): string
{
    $L = [];
    $L[] = 'Base URL    : ' . $cfg['base'];
    $L[] = 'apiLevel    : ' . $cfg['level'];
    $L[] = 'x-api-token : ' . gs_mask($cfg['token']);
    $L[] = 'x-api-key   : ' . gs_mask($cfg['key']);
    $L[] = str_repeat('-', 68);

    if (($cfg['token'] === '' || $cfg['key'] === '') && $mode !== 'ping') {
        $L[] = 'NO KEY SET. Set GS_API_TOKEN / GS_API_KEY (env), edit CONFIG, or';
        $L[] = 'paste into the browser form. Requests will 403 without them.';
        return implode("\n", $L);
    }

    switch ($mode) {

        case 'ping':
            $m = gs_call($cfg, '', []);
            $L[] = sprintf('PING -> HTTP %d  (%s)  %d ms', $m['status'], gs_hint($m['status']), $m['ms']);
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = 'Response headers (all):';
            foreach ($m['headers'] as $k => $v) { $L[] = '  ' . $k . ': ' . $v; }
            $L[] = '';
            $L[] = 'Body:';
            $L[] = gs_pretty($m['body'], 1500);
            break;

        case 'index':
            $m   = gs_call($cfg, '', []);
            $L[] = sprintf('INDEX -> HTTP %d  (%s)  %d ms', $m['status'], gs_hint($m['status']), $m['ms']);
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = "GreySheet's own endpoint directory:";
            foreach (gs_json($m) as $group => $ops) {
                if (!is_array($ops)) { continue; }
                $L[] = '  ' . $group . ':';
                foreach ($ops as $op) {
                    if (is_array($op) && isset($op['Name'])) {
                        $L[] = '    - ' . $op['Name'] . (isset($op['Returns']) ? '  -> ' . $op['Returns'] : '');
                    }
                }
            }
            break;

        case 'node':
            $m = gs_call($cfg, 'GetNodeRequest', ['NodeId' => $opt['node']]);
            $L[] = sprintf('NODE %s -> HTTP %d  (%s)  %d ms', $opt['node'], $m['status'], gs_hint($m['status']), $m['ms']);
            $L[] = '  ' . $m['url'];
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = gs_pretty($m['body']);
            break;

        case 'children':
            $m = gs_call($cfg, 'GetNodeChildrenRequest', ['NodeId' => $opt['node']]);
            $L[] = sprintf('CHILDREN of node %s -> HTTP %d  (%s)  %d ms', $opt['node'], $m['status'], gs_hint($m['status']), $m['ms']);
            $L[] = '  ' . $m['url'];
            $items = gs_items(gs_json($m));
            $L[] = 'child nodes: ' . count($items);
            foreach ($items as $it) {
                $L[] = sprintf('  [%s] %s  (children=%d, coins=%d)', gs_id($it), gs_name($it),
                    (int) ($it['NodeChildrenCountLive'] ?? 0), (int) ($it['CollectibleChildrenCountLive'] ?? 0));
            }
            if (!$items) { $L[] = ''; $L[] = gs_pretty($m['body'], 1500); }
            break;

        case 'coins':
            $m = gs_call($cfg, 'GetCollectibleByNodeRequest', ['NodeId' => $opt['node']]);
            $L[] = sprintf('COINS under node %s -> HTTP %d  (%s)  %d ms', $opt['node'], $m['status'], gs_hint($m['status']), $m['ms']);
            $L[] = '  ' . $m['url'];
            $items = gs_items(gs_json($m));
            $L[] = 'coins: ' . count($items);
            foreach ($items as $it) { $L[] = '  - ' . gs_name($it) . ' [' . gs_id($it) . ']'; }
            if (!$items) { $L[] = ''; $L[] = gs_pretty($m['body'], 1500); }
            break;

        case 'crawl':
            $root  = $opt['node'] !== '' ? $opt['node'] : '1';
            $max   = max(1, (int) $opt['max']);
            $depth = max(0, (int) $opt['depth']);
            $delay = max(0, (int) $opt['delay']);
            $L[] = sprintf('CRAWL from node %s  (max=%d nodes, depth=%d, delay=%dms)', $root, $max, $depth, $delay);
            $L[] = 'Walks GetNodeChildrenRequest down the tree; lists coins via';
            $L[] = 'GetCollectibleByNodeRequest.  "#" = node,  "-" = coin.';
            $L[] = '';
            $stats = [];
            $L = array_merge($L, gs_crawl($cfg, $root, $max, $depth, $delay, $stats));
            $L[] = '';
            $L[] = sprintf('DONE: %d nodes, %d coins, %d API calls.', $stats['nodes'], $stats['coins'], $stats['calls']);
            if ($stats['stopped']) { $L[] = 'Stopped early: ' . $stats['stopped']; }
            $rl = [];
            foreach ($stats['last_headers'] as $k => $v) {
                $lk = strtolower($k);
                if (strpos($lk, 'ratelimit') !== false || strpos($lk, 'rate-limit') !== false
                    || strpos($lk, 'quota') !== false || strpos($lk, 'remaining') !== false) { $rl[] = '  ' . $k . ': ' . $v; }
            }
            if ($rl) { $L[] = ''; $L[] = 'Rate-limit / quota headers (last call):'; $L = array_merge($L, $rl); }
            break;

        case 'probe':
            $L[] = 'PROBE  id="' . $opt['node'] . '"';
            $rows = gs_probe($cfg, $opt['node'], $opt['term'], $nodeCands, $searchCands);
            $L[] = sprintf('  %-6s %-36s %-6s %-6s %s', 'KIND', 'TRY', 'HTTP', 'ms', 'HINT');
            foreach ($rows as $r) {
                $m = $r['meta'];
                $L[] = sprintf('  %-6s %-36s %-6s %-6s %s', $r['kind'], $r['try'], $m['status'] ?: '-', $m['ms'], gs_hint($m['status']));
            }
            break;

        default:
            $L[] = 'Unknown mode "' . $mode . '". Use: index | node | children | coins | crawl | ping | probe';
    }
    return implode("\n", $L);
}

/* ------------------------------ OUTPUT --------------------------------- */
if ($IS_CLI) {
    // Only auto-run when invoked directly (so the file can be include()d for tests).
    $invoked = $_SERVER['argv'][0] ?? '';
    if (@realpath($invoked) === __FILE__) {
        echo gs_run($mode, $CFG, $opt, $NODE_CANDIDATES, $SEARCH_CANDIDATES), "\n";
    }
    return;
}

$report = ($mode !== 'form') ? gs_run($mode, $CFG, $opt, $NODE_CANDIDATES, $SEARCH_CANDIDATES) : '';
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GreySheet API tester</title>
<style>
  body{font:14px/1.5 -apple-system,Segoe UI,Arial,sans-serif;margin:0;background:#f4f7f4;color:#123}
  header{background:#2e7d32;color:#fff;padding:14px 20px;font-weight:600}
  .wrap{max-width:1000px;margin:18px auto;padding:0 16px}
  form{background:#fff;border:1px solid #cfe3cf;border-radius:8px;padding:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
  fieldset{border:1px solid #dbe7db;border-radius:6px;margin:0 0 12px}
  legend{padding:0 6px;color:#2e7d32;font-weight:600}
  label{display:inline-block;min-width:92px;color:#345}
  input,select{padding:6px 8px;border:1px solid #b9cdb9;border-radius:5px;margin:4px 6px 4px 0;font:inherit}
  input[type=text],input[type=password]{width:320px}
  input.sm{width:90px}
  .row{margin:4px 0}
  button{background:#2e7d32;color:#fff;border:0;border-radius:6px;padding:9px 14px;font:inherit;cursor:pointer;margin:3px 4px 3px 0}
  button:hover{background:#256528}
  pre{background:#0f1b12;color:#c9f5cf;padding:14px;border-radius:8px;overflow:auto;white-space:pre-wrap;max-height:70vh}
  .note{color:#666;font-size:12px}
  .warn{background:#fff3cd;border:1px solid #ffe08a;color:#664d03;padding:8px 12px;border-radius:6px;margin:10px 0}
</style>
</head>
<body>
<header>GreySheet CDN Public API v2 — standalone tester</header>
<div class="wrap">

  <?php if ($keysMissing): ?>
    <div class="warn">No API key set. Paste your <b>x-api-token</b> and <b>x-api-key</b> below
    (or set <code>GS_API_TOKEN</code> / <code>GS_API_KEY</code> env vars). Without them every call is 403.</div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <fieldset>
      <legend>Credentials &amp; server</legend>
      <div class="row"><label>x-api-token</label>
        <input type="password" name="token" placeholder="<?= $keysMissing ? 'paste token' : 'set via env/config' ?>"></div>
      <div class="row"><label>x-api-key</label>
        <input type="password" name="key" placeholder="<?= $keysMissing ? 'paste key' : 'set via env/config' ?>"></div>
      <div class="row"><label>Base URL</label>
        <select name="base">
          <option value="https://cpgpublicapiv2.greysheet.com/api"    <?= strpos($CFG['base'],'dev')===false?'selected':'' ?>>PROD — cpgpublicapiv2</option>
          <option value="https://cpgpublicapiv2dev.greysheet.com/api" <?= strpos($CFG['base'],'dev')!==false?'selected':'' ?>>DEV — cpgpublicapiv2dev</option>
        </select>
        <label>apiLevel</label>
        <select name="level">
          <option value="basic"    <?= $CFG['level']==='basic'?'selected':'' ?>>basic</option>
          <option value="advanced" <?= $CFG['level']==='advanced'?'selected':'' ?>>advanced</option>
        </select>
      </div>
      <p class="note">Token/key are POSTed (not put in the URL), so they don't land in the access log.</p>
    </fieldset>

    <fieldset>
      <legend>Target &amp; crawl limits</legend>
      <div class="row"><label>Node id</label><input class="sm" type="text" name="node" value="<?= $h($opt['node']) ?>">
        <span class="note">1 = "U.S. Coins" (root)</span></div>
      <div class="row"><label>Crawl max</label><input class="sm" type="text" name="max" value="<?= $h($opt['max']) ?>">
        <label style="min-width:50px">depth</label><input class="sm" type="text" name="depth" value="<?= $h($opt['depth']) ?>">
        <label style="min-width:60px">delay ms</label><input class="sm" type="text" name="delay" value="<?= $h($opt['delay']) ?>"></div>
    </fieldset>

    <fieldset>
      <legend>Run</legend>
      <div class="row">
        <button name="mode" value="index">Index (all endpoints)</button>
        <button name="mode" value="node">Node</button>
        <button name="mode" value="children">Children</button>
        <button name="mode" value="coins">Coins</button>
        <button name="mode" value="crawl">Crawl (nodes + coins)</button>
      </div>
      <div class="row">
        <button name="mode" value="ping">Ping</button>
        <button name="mode" value="probe">Probe</button>
      </div>
      <p class="note">Crawl walks the tree from Node id down to "depth", up to "max" nodes,
      pausing "delay ms" between calls. Start small (e.g. depth 2, max 60) to respect rate limits.</p>
    </fieldset>
  </form>

  <?php if ($report !== ''): ?>
    <h3>Result — <?= $h($mode) ?></h3>
    <pre><?= $h($report) ?></pre>
  <?php endif; ?>

  <p class="note">Standalone diagnostic. Not part of the M-Power app; safe to delete when done.</p>
</div>
</body>
</html>
