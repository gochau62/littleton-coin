<?php
/*    ***************************************************  -->
<!--  * Program Name - greysheet.php                     *  -->
<!--  *                                                 *  -->
<!--  * Standalone GreySheet CDN Public API v2 tester.   *  -->
<!--  * NO dependency on the M-Power stack or any other  *  -->
<!--  * file in this repo - drop in a key and run.       *  -->
<!--  ***************************************************   */

/*
 * WHY THIS FILE EXISTS
 * --------------------
 * The real search route + query-param names are still guesses in the agent
 * (GS_SEARCH_PATH / GS_SEARCH_PARAM).  This throwaway tester lets you confirm
 * them against the live API without touching the rest of the app:
 *
 *   - "probe"  fires a MATRIX of candidate routes/params and prints the HTTP
 *              status for each, so you can SEE which ones the server accepts.
 *   - "node"   does the single node lookup (GetNodeRequest?NodeId=...).
 *   - "search" does a single search with whatever route/param you name.
 *   - "ping"   just checks connectivity + auth against the base URL.
 *
 * HOW TO GIVE IT YOUR KEY (never commit real keys)
 * ------------------------------------------------
 *   Option 1 - environment variables (best; nothing to edit):
 *       export GS_API_TOKEN=xxxx
 *       export GS_API_KEY=yyyy
 *       # optional: export GS_BASE_URL=https://cpgpublicapiv2.greysheet.com/api
 *       # optional: export GS_API_LEVEL=advanced
 *   Option 2 - paste into the CONFIG block below (delete before committing).
 *   Option 3 - in the browser form, paste them into the token/key fields (POSTed,
 *              so they are not written to the URL / server access log).
 *
 * HOW TO RUN
 * ----------
 *   Localhost (browser): from this folder run  php -S localhost:8000
 *                        then open http://localhost:8000/greysheet.php
 *   CLI (IBM i PASE / QSH, or anywhere php-cli exists):
 *       php greysheet.php ping
 *       php greysheet.php probe
 *       php greysheet.php probe --node=17453 --term="Morgan Dollar"
 *       php greysheet.php node --node=17453
 *       php greysheet.php search --path=SearchRequest --param=query --term="1909-S VDB"
 */

/* ----------------------------- CONFIG ---------------------------------- */
$CFG = [
    'base'    => getenv('GS_BASE_URL')  ?: 'https://cpgpublicapiv2dev.greysheet.com/api',
    'token'   => getenv('GS_API_TOKEN') ?: '',   // <-- or paste here (then delete)
    'key'     => getenv('GS_API_KEY')   ?: '',   // <-- or paste here (then delete)
    'level'   => getenv('GS_API_LEVEL') ?: 'basic',
    'timeout' => 20,
];

/* Candidate routes to probe. First entry in each is the agent's current guess. */
$NODE_CANDIDATES = [
    ['path' => 'GetNodeRequest', 'param' => 'NodeId'],
    ['path' => 'GetNodeRequest', 'param' => 'nodeId'],
    ['path' => 'GetNode',        'param' => 'NodeId'],
    ['path' => 'GetNode',        'param' => 'nodeId'],
    ['path' => 'Node',           'param' => 'id'],
    ['path' => 'nodes',          'param' => null],   // REST style: nodes/{id}
];
$SEARCH_CANDIDATES = [
    ['path' => 'SearchRequest', 'param' => 'query'],   // current guess
    ['path' => 'SearchRequest', 'param' => 'term'],
    ['path' => 'SearchRequest', 'param' => 'q'],
    ['path' => 'SearchRequest', 'param' => 'keyword'],
    ['path' => 'Search',        'param' => 'query'],
    ['path' => 'search',        'param' => 'q'],
    ['path' => 'search',        'param' => 'query'],
    ['path' => 'GetSearchRequest', 'param' => 'query'],
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

/** Short human hint for a status code (helps read 401/403/404/429 fast). */
function gs_hint(int $status): string
{
    switch (true) {
        case $status === 0:                    return 'no response (network/DNS/TLS — see err)';
        case $status === 200:                  return 'OK';
        case $status === 400:                  return 'bad request (route ok, but wrong/missing param?)';
        case $status === 401:                  return 'unauthorized (token/key wrong or missing)';
        case $status === 403:                  return 'forbidden (key inactive, wrong tier, or gateway block)';
        case $status === 404:                  return 'not found (route or id does not exist)';
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
    if (json_last_error() === JSON_ERROR_NONE) {
        $out = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
        $out = $body;
    }
    if ($clip > 0 && strlen($out) > $clip) { $out = substr($out, 0, $clip) . "\n... [clipped]"; }
    return $out;
}

/** Mask a secret for display: keep first 4 chars, star the rest. */
function gs_mask(string $s): string
{
    if ($s === '') { return '(empty)'; }
    $keep = substr($s, 0, 4);
    return $keep . str_repeat('*', max(3, strlen($s) - 4)) . ' (len ' . strlen($s) . ')';
}

/* ------------------------------ MODES ---------------------------------- */
/** Try every node/search candidate with a sample id/term; collect a report. */
function gs_probe(array $cfg, $nodeId, string $term, array $nodeCands, array $searchCands): array
{
    $rows = [];
    foreach ($nodeCands as $c) {
        if ($c['param'] === null) {                       // REST: nodes/{id}
            $m = gs_call($cfg, $c['path'] . '/' . rawurlencode((string) $nodeId), []);
            $label = $c['path'] . '/{id}';
        } else {
            $m = gs_call($cfg, $c['path'], [$c['param'] => $nodeId]);
            $label = $c['path'] . '?' . $c['param'] . '=';
        }
        $rows[] = ['kind' => 'node', 'try' => $label, 'meta' => $m];
    }
    foreach ($searchCands as $c) {
        $m = gs_call($cfg, $c['path'], [$c['param'] => $term]);
        $rows[] = ['kind' => 'search', 'try' => $c['path'] . '?' . $c['param'] . '=', 'meta' => $m];
    }
    return $rows;
}

/* ---------------------------- REQUEST IN ------------------------------- */
/* Pull mode + options from CLI argv or the web request. */
$mode = 'form';
$opt  = ['node' => '1', 'term' => 'Morgan Dollar', 'path' => '', 'param' => '', 'base' => '', 'level' => ''];

if ($IS_CLI) {
    $mode = $argv[1] ?? 'ping';
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
/** Do the work for a non-form mode; returns a plain-text report block. */
function gs_run(string $mode, array $cfg, array $opt, array $nodeCands, array $searchCands): string
{
    $L = [];
    $L[] = 'Base URL : ' . $cfg['base'];
    $L[] = 'apiLevel : ' . $cfg['level'];
    $L[] = 'x-api-token : ' . gs_mask($cfg['token']);
    $L[] = 'x-api-key   : ' . gs_mask($cfg['key']);
    $L[] = str_repeat('-', 68);

    if ($cfg['token'] === '' || $cfg['key'] === '') {
        $L[] = 'NO KEY SET. Set GS_API_TOKEN and GS_API_KEY (env), or edit the';
        $L[] = 'CONFIG block, or paste them into the browser form. See the header';
        $L[] = 'comment for all three ways. Requests will 403 without them.';
        if ($mode === 'ping') { /* still let ping run to show the raw 403 */ }
        else { return implode("\n", $L); }
    }

    switch ($mode) {
        case 'ping':
            $m = gs_call($cfg, '', []);
            $L[] = sprintf('PING  -> HTTP %d  (%s)  %d ms', $m['status'], gs_hint($m['status']), $m['ms']);
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = 'Response headers:';
            foreach ($m['headers'] as $k => $v) { $L[] = '  ' . $k . ': ' . $v; }
            $L[] = '';
            $L[] = 'Body:';
            $L[] = gs_pretty($m['body'], 1500);
            break;

        case 'probe':
            $L[] = 'PROBE  node id="' . $opt['node'] . '"  term="' . $opt['term'] . '"';
            $L[] = 'A route that returns 200/400/404 EXISTS (401/403 = auth; 404 on';
            $L[] = 'a search term usually means the route is wrong, not the term).';
            $L[] = '';
            $rows = gs_probe($cfg, $opt['node'], $opt['term'], $nodeCands, $searchCands);
            $L[] = sprintf('  %-6s %-30s %-6s %-6s %s', 'KIND', 'TRY', 'HTTP', 'ms', 'HINT');
            foreach ($rows as $r) {
                $m = $r['meta'];
                $L[] = sprintf('  %-6s %-30s %-6s %-6s %s',
                    $r['kind'], $r['try'], $m['status'] ?: '-', $m['ms'], gs_hint($m['status']));
            }
            $L[] = '';
            $L[] = 'Tip: re-run with a REAL node id you know exists to tell a working';
            $L[] = 'route (200) from a merely-valid one (404). Example:';
            $L[] = '  php greysheet.php probe --node=17453 --term="Morgan Dollar"';
            break;

        case 'node':
            $m = gs_call($cfg, 'GetNodeRequest', ['NodeId' => $opt['node']]);
            $L[] = sprintf('NODE %s -> HTTP %d  (%s)  %d ms', $opt['node'], $m['status'], gs_hint($m['status']), $m['ms']);
            $L[] = '  ' . $m['url'];
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = gs_pretty($m['body']);
            break;

        case 'search':
            $path  = $opt['path']  !== '' ? $opt['path']  : 'SearchRequest';
            $param = $opt['param'] !== '' ? $opt['param'] : 'query';
            $m = gs_call($cfg, $path, [$param => $opt['term']]);
            $L[] = sprintf('SEARCH "%s" via %s?%s= -> HTTP %d  (%s)  %d ms',
                $opt['term'], $path, $param, $m['status'], gs_hint($m['status']), $m['ms']);
            $L[] = '  ' . $m['url'];
            if ($m['err']) { $L[] = 'ERROR: ' . $m['err']; }
            $L[] = '';
            $L[] = gs_pretty($m['body']);
            break;

        default:
            $L[] = 'Unknown mode "' . $mode . '". Use: ping | probe | node | search';
    }
    return implode("\n", $L);
}

/* ------------------------------ OUTPUT --------------------------------- */
if ($IS_CLI) {
    echo gs_run($mode, $CFG, $opt, $NODE_CANDIDATES, $SEARCH_CANDIDATES), "\n";
    exit(0);
}

/* Web: form + (optional) results. Simple LCC-green styling, no framework. */
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
  .row{margin:4px 0}
  button{background:#2e7d32;color:#fff;border:0;border-radius:6px;padding:9px 16px;font:inherit;cursor:pointer}
  button:hover{background:#256528}
  pre{background:#0f1b12;color:#c9f5cf;padding:14px;border-radius:8px;overflow:auto;white-space:pre-wrap}
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
          <option value="https://cpgpublicapiv2dev.greysheet.com/api" <?= strpos($CFG['base'],'dev')!==false?'selected':'' ?>>DEV — cpgpublicapiv2dev</option>
          <option value="https://cpgpublicapiv2.greysheet.com/api"    <?= strpos($CFG['base'],'dev')===false?'selected':'' ?>>PROD — cpgpublicapiv2</option>
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
      <legend>What to run</legend>
      <div class="row"><label>Node id</label><input type="text" name="node" value="<?= $h($opt['node']) ?>" style="width:140px">
        <label>Search term</label><input type="text" name="term" value="<?= $h($opt['term']) ?>"></div>
      <div class="row"><label>Search path</label><input type="text" name="path" value="<?= $h($opt['path']) ?>" placeholder="SearchRequest" style="width:160px">
        <label>param</label><input type="text" name="param" value="<?= $h($opt['param']) ?>" placeholder="query" style="width:120px"></div>
      <div class="row" style="margin-top:8px">
        <button name="mode" value="ping">Ping</button>
        <button name="mode" value="probe">Probe routes</button>
        <button name="mode" value="node">Node lookup</button>
        <button name="mode" value="search">Search</button>
      </div>
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
