<?php
/*
 * STANDALONE test page for the Sellbrite Bulk Loader GreySheet agent.
 * Built like greysheet.php (which is proven to run): no require_once on any
 * repo file, keys hardcoded below, direct cURL. Every check is a button so
 * API calls are only spent when clicked.
 *
 *   1. Environment          0 calls   PHP/cURL/writable folder/memory file
 *   2. GreySheet ping       1 call
 *   3. Gemini hello world   1 Gemini call
 *   4. CRAWL a branch       ~7 calls  writes SellbriteBulkLoader_memory.dev.json
 *   5. Memory search/years  0 calls   reads the file the crawl wrote
 *   6. Import one coin      2 calls   collectible + pricing -> mapped fields
 *   7. Agent chain + flow             loads the real app files step by step
 *                                     (shows exactly which file breaks), then
 *                                     runs the full resolve -> import flow
 *
 * The constants below use the same names as SellbriteBulkLoader_agent.php,
 * so when check 7 loads the agent it inherits these keys automatically.
 */

if (!defined('GS_BASE_URL'))    { define('GS_BASE_URL',    'https://cpgpublicapiv2.greysheet.com/api'); }
if (!defined('GS_API_TOKEN'))   { define('GS_API_TOKEN',   'B71FE10C-3B96-41B4-9A9E-A307DBE29B82'); }
if (!defined('GS_API_KEY'))     { define('GS_API_KEY',     '7056764F-B695-4543-994D-6471B64E083A'); }
if (!defined('GS_API_LEVEL'))   { define('GS_API_LEVEL',   'basic'); }
if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', ''); }   // <-- paste your Google key here
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); }

$MEM_FILE = __DIR__ . '/SellbriteBulkLoader_memory.dev.json';
if (function_exists('set_time_limit')) { @set_time_limit(0); }

/* --------------------------- HTTP helpers ------------------------------- */
/** GET a GreySheet path. Returns ['status','ms','err','data'=>decoded Data[]]. */
function t_gs(string $path, array $params): array
{
    $r = ['status' => 0, 'ms' => 0, 'err' => '', 'data' => [], 'raw' => ''];
    if (!isset($params['apiLevel'])) { $params['apiLevel'] = GS_API_LEVEL; }
    $url = rtrim(GS_BASE_URL, '/') . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['x-api-token: ' . GS_API_TOKEN, 'x-api-key: ' . GS_API_KEY, 'Accept: application/json'],
    ]);
    $t0 = microtime(true);
    $body = curl_exec($ch);
    $r['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($body === false) { $r['err'] = 'cURL: ' . curl_error($ch); curl_close($ch); return $r; }
    $r['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $r['raw'] = (string) $body;
    $d = json_decode($body, true);
    if (is_array($d) && isset($d['Data']) && is_array($d['Data'])) {
        $r['data'] = array_values(array_filter($d['Data'], 'is_array'));
    }
    if ($r['status'] !== 200 && $r['err'] === '') { $r['err'] = 'HTTP ' . $r['status'] . ' ' . substr($body, 0, 200); }
    return $r;
}

/** Ask Gemini for a JSON object. Returns ['ok','ms','err','json']. */
function t_gemini(string $system, string $user): array
{
    $r = ['ok' => false, 'ms' => 0, 'err' => '', 'json' => null];
    if (GEMINI_API_KEY === '') { $r['err'] = 'GEMINI_API_KEY is empty - paste it at the top of this file'; return $r; }
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
    $body = json_encode([
        'systemInstruction' => ['parts' => [['text' => $system]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
        'generationConfig' => ['temperature' => 0.2, 'responseMimeType' => 'application/json', 'maxOutputTokens' => 512],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
    ]);
    $t0 = microtime(true);
    $raw = curl_exec($ch);
    $r['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($raw === false) { $r['err'] = 'cURL: ' . curl_error($ch); curl_close($ch); return $r; }
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $resp = json_decode($raw, true);
    if ($status !== 200) { $r['err'] = 'HTTP ' . $status . ': ' . ($resp['error']['message'] ?? substr($raw, 0, 200)); return $r; }
    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    $j = json_decode($text, true);
    if (!is_array($j) && preg_match('/\{.*\}/s', $text, $m)) { $j = json_decode($m[0], true); }
    if (!is_array($j)) { $r['err'] = 'no usable JSON in response'; return $r; }
    $r['ok'] = true;
    $r['json'] = $j;
    return $r;
}

/* --------------------- dev memory file (agent-compatible) --------------- */
function t_mem_load(string $f): array
{
    if (!is_file($f)) { return []; }
    $d = json_decode((string) file_get_contents($f), true);
    return is_array($d) ? $d : [];
}
function t_mem_save(string $f, array $rows): void
{
    @file_put_contents($f, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function t_norm(string $s): string
{
    return trim(preg_replace('/\s+/', ' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', $s))));
}
function t_mem_search(array $rows, string $q, int $limit = 20): array
{
    $words = array_filter(explode(' ', t_norm($q)));
    $out = [];
    foreach ($rows as $r) {
        if (($r['kind'] ?? '') !== 'C') { continue; }
        $hay = t_norm(($r['name'] ?? '') . ' ' . ($r['path'] ?? ''));
        foreach ($words as $w) { if (strpos($hay, $w) === false) { continue 2; } }
        $out[] = $r;
        if (count($out) >= $limit) { break; }
    }
    return $out;
}

/* ------------------------------ dispatch -------------------------------- */
$check = $_POST['check'] ?? $_GET['check'] ?? '';

/* ---- 4. CRAWL: streams plain text and exits (like greysheet.php crawl) -- */
if ($check === 'crawl') {
    header('Content-Type: text/plain; charset=utf-8');
    $root  = ctype_digit((string) ($_POST['root'] ?? '')) ? (int) $_POST['root'] : 8243;
    $max   = ctype_digit((string) ($_POST['maxcalls'] ?? '')) ? (int) $_POST['maxcalls'] : 10;
    $delay = ctype_digit((string) ($_POST['delay'] ?? '')) ? (int) $_POST['delay'] : 250;

    echo "CRAWL  root={$root}  maxcalls={$max}  delay={$delay}ms  base=" . GS_BASE_URL . "\n";
    echo str_repeat('-', 60) . "\n";
    @ob_flush(); @flush();

    $mem = t_mem_load($MEM_FILE);
    $done = [];
    foreach ($mem as $r) { if (($r['kind'] ?? '') === 'N' && ($r['done'] ?? 'N') === 'Y') { $done[(int) $r['ref_id']] = true; } }

    $queue = [['id' => $root, 'name' => '(root ' . $root . ')', 'path' => 'U.S. Coins', 'coins' => 0, 'parent' => 0]];
    $seen = []; $calls = 0; $coinsTotal = 0; $skipped = 0; $stopped = '';

    while ($queue) {
        if ($calls >= $max) { $stopped = 'call budget reached - run again to resume'; break; }
        $n = array_shift($queue);
        $id = (int) $n['id'];
        if ($id <= 0 || isset($seen[$id])) { continue; }
        $seen[$id] = true;

        if (isset($done[$id])) {                     // resume: children from file, no API
            $skipped++;
            foreach ($mem as $r) {
                if (($r['kind'] ?? '') === 'N' && (int) ($r['parent_id'] ?? 0) === $id) {
                    $queue[] = ['id' => (int) $r['ref_id'], 'name' => $r['name'], 'path' => $r['path'],
                                'coins' => (int) $r['coin_count'], 'parent' => $id];
                }
            }
            continue;
        }

        if ((int) $n['coins'] > 0) {                 // leaf: one call stores the whole series
            $m = t_gs('GetCollectibleByNodeRequest', ['NodeId' => $id]);
            $calls++;
            if ($m['err']) { $stopped = 'GetCollectibleByNodeRequest: ' . $m['err']; break; }
            foreach ($m['data'] as $c) {
                $gid = (int) ($c['Gsid'] ?? 0);
                if ($gid <= 0) { continue; }
                $mem['C:' . $gid] = ['kind' => 'C', 'ref_id' => $gid, 'parent_id' => $id,
                    'name' => (string) ($c['Name'] ?? ''), 'path' => $n['path'],
                    'coin_date' => (string) ($c['CoinDate'] ?? ''), 'mint_mark' => (string) ($c['MintMark'] ?? ''),
                    'coin_count' => 0, 'done' => 'N'];
            }
            $mem['N:' . $id] = ['kind' => 'N', 'ref_id' => $id, 'parent_id' => (int) $n['parent'], 'name' => $n['name'],
                'path' => $n['path'], 'coin_date' => '', 'mint_mark' => '', 'coin_count' => (int) $n['coins'], 'done' => 'Y'];
            $coinsTotal += count($m['data']);
            echo "LEAF [{$id}] {$n['name']}  +" . count($m['data']) . " coins  (call {$calls}, {$m['ms']}ms)\n";
            if ($m['data']) { $s = $m['data'][0]; echo '      e.g. GsId=' . ($s['Gsid'] ?? '?') . '  "' . ($s['Name'] ?? '?') . '"  date=' . ($s['CoinDate'] ?? '?') . "\n"; }
        } else {                                     // folder: list + queue children
            $m = t_gs('GetNodeChildrenRequest', ['NodeId' => $id]);
            $calls++;
            if ($m['err']) { $stopped = 'GetNodeChildrenRequest: ' . $m['err']; break; }
            foreach ($m['data'] as $c) {
                $kid = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                        'path' => $n['path'] . ' > ' . (string) ($c['Name'] ?? ''),
                        'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0), 'parent' => $id];
                $mem['N:' . $kid['id']] = ['kind' => 'N', 'ref_id' => $kid['id'], 'parent_id' => $id, 'name' => $kid['name'],
                    'path' => $kid['path'], 'coin_date' => '', 'mint_mark' => '', 'coin_count' => $kid['coins'], 'done' => 'N'];
                $queue[] = $kid;
            }
            $mem['N:' . $id] = ['kind' => 'N', 'ref_id' => $id, 'parent_id' => (int) $n['parent'], 'name' => $n['name'],
                'path' => $n['path'], 'coin_date' => '', 'mint_mark' => '', 'coin_count' => 0, 'done' => 'Y'];
            echo "NODE [{$id}] {$n['name']}  +" . count($m['data']) . " folders  (call {$calls}, {$m['ms']}ms)\n";
        }
        t_mem_save($MEM_FILE, $mem);
        @ob_flush(); @flush();
        if ($delay) { usleep($delay * 1000); }
    }
    echo str_repeat('-', 60) . "\n";
    echo "DONE: {$calls} API calls, {$coinsTotal} coins stored ({$skipped} nodes resumed from file).\n";
    if ($stopped) { echo 'Stopped: ' . $stopped . "\n"; }
    echo 'Memory file: ' . basename($MEM_FILE) . ' (' . count($mem) . " rows total)\n";
    echo $queue ? count($queue) . " nodes still queued - run crawl again to continue.\n" : "Branch complete.\n";
    exit;
}

/* ---- 7. agent chain + full flow: streams so a fatal shows WHERE ---- */
if ($check === 'agent') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Loading the real app files one by one - if this output stops mid-line,\n";
    echo "the LAST file named is the one that breaks on this machine.\n";
    echo str_repeat('-', 60) . "\n";
    foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
        echo str_pad($f, 42) . (file_exists($f) ? '' : 'not present (fine off the i)') . "\n";
        @ob_flush(); @flush();
        if (file_exists($f)) { require_once $f; echo str_pad('', 42) . "loaded OK\n"; }
    }
    foreach (['SellbriteBulkLoader_data.php'  => 'require',
              'SellbriteBulkLoader_logic.php' => 'require',
              'SellbriteBulkLoader_model.php' => 'require',
              'SellbriteBulkLoader_agent.php' => 'require'] as $f => $_) {
        echo str_pad($f, 42) . '... ';
        @ob_flush(); @flush();
        if (!is_file(__DIR__ . '/' . $f)) { echo "MISSING\n"; exit; }
        if ($f === 'SellbriteBulkLoader_data.php') { $d = require __DIR__ . '/' . $f; echo 'OK (' . count($d['schema'] ?? []) . " columns)\n"; continue; }
        require_once __DIR__ . '/' . $f;
        echo "OK\n";
        @ob_flush(); @flush();
    }
    echo str_repeat('-', 60) . "\n";
    echo "Whole chain loaded. Running the FULL FLOW through the agent:\n\n";
    @ob_flush(); @flush();

    $attrs = ['category_name' => trim((string) ($_POST['cat'] ?? 'Draped Bust Half Cent')),
              'year' => trim((string) ($_POST['year'] ?? '1804')),
              'mint_mark' => trim((string) ($_POST['mm'] ?? '')),
              'grade' => trim((string) ($_POST['grade'] ?? ''))];
    $trace = [];
    $gsId = gsResolve($attrs, $trace);
    echo 'resolve "' . implode(' ', array_filter($attrs)) . '"  ->  '
       . ($gsId > 0 ? "GsId {$gsId}" : 'NOT FOUND') . '   via   ' . implode('  >  ', $trace) . "\n\n";
    if ($gsId > 0) {
        $imp = gsImport(['gs_id' => $gsId, 'grade' => $attrs['grade']]);
        echo 'import: ' . ($imp['found'] ? 'OK via ' . $imp['via'] : 'FAILED ' . $imp['error']) . "\n\n";
        foreach ($imp['row'] as $k => $v) { if ((string) $v !== '') { echo str_pad($k, 28) . ' = ' . $v . "\n"; } }
    }
    exit;
}

/* ---- all other checks build $out and render in the HTML page ---- */
$out = [];
$pass = static function ($label, $ok, $detail = '') use (&$out) {
    $out[] = ($ok ? 'PASS  ' : 'FAIL  ') . $label . ($detail !== '' ? '  -  ' . $detail : '');
    return $ok;
};

switch ($check) {

    case 'env':    /* ---- 1. environment (0 calls) ---- */
        $pass('PHP ' . PHP_VERSION . ' (need 8.0+)', PHP_MAJOR_VERSION >= 8);
        $pass('cURL extension', function_exists('curl_init'), function_exists('curl_init') ? '' : 'enable extension=curl in php.ini');
        $pass('GreySheet keys hardcoded', GS_API_TOKEN !== '' && GS_API_KEY !== '', 'base=' . GS_BASE_URL);
        $pass('Gemini key set', GEMINI_API_KEY !== '', GEMINI_API_KEY !== '' ? 'model=' . GEMINI_MODEL : 'paste it at the top of this file');
        $w = @file_put_contents(__DIR__ . '/.write_test', 'x') !== false;
        @unlink(__DIR__ . '/.write_test');
        $pass('folder writable (for the memory file)', $w);
        $mem = t_mem_load($MEM_FILE);
        $c = count(array_filter($mem, static fn($r) => ($r['kind'] ?? '') === 'C'));
        $n = count($mem) - $c;
        $pass('memory file', true, is_file($MEM_FILE) ? basename($MEM_FILE) . ": {$n} folders, {$c} coins" : 'not created yet - run the crawl');
        break;

    case 'gs':     /* ---- 2. GreySheet ping (1 call) ---- */
        $m = t_gs('GetNodeRequest', ['NodeId' => 1]);
        $node = $m['data'][0] ?? [];
        $pass('GetNodeRequest?NodeId=1', $m['status'] === 200,
              $m['status'] === 200
                ? 'HTTP 200 in ' . $m['ms'] . 'ms - "' . ($node['Name'] ?? '?') . '" children=' . ($node['NodeChildrenCountLive'] ?? '?')
                : $m['err']);
        break;

    case 'gemini': /* ---- 3. Gemini hello world (1 call) ---- */
        $g = t_gemini('Return ONLY a JSON object shaped {"message": "..."}.',
                      'Say hello world and confirm in one short sentence that you are ready to help fill coin listings.');
        $pass('Gemini generateContent (' . GEMINI_MODEL . ')', $g['ok'], $g['ok'] ? $g['ms'] . 'ms' : $g['err']);
        if ($g['ok']) { $out[] = '      Gemini says: "' . ($g['json']['message'] ?? '?') . '"'; }
        break;

    case 'search': /* ---- 5a. memory search (0 calls) ---- */
        $q = trim((string) ($_POST['q'] ?? ''));
        $hits = t_mem_search(t_mem_load($MEM_FILE), $q);
        $pass('memory search "' . $q . '"', (bool) $hits, count($hits) . ' hit(s)' . ($hits ? '' : ' - run the crawl first'));
        foreach ($hits as $h) { $out[] = '      [' . $h['ref_id'] . '] ' . $h['name'] . '   (' . $h['path'] . ')'; }
        break;

    case 'years':  /* ---- 5b. years for a category (0 calls) ---- */
        $cat = t_norm((string) ($_POST['cat'] ?? ''));
        $years = [];
        foreach (t_mem_load($MEM_FILE) as $r) {
            if (($r['kind'] ?? '') !== 'C') { continue; }
            if ($cat === '' || strpos(t_norm(($r['path'] ?? '') . ' ' . ($r['name'] ?? '')), $cat) === false) { continue; }
            if (preg_match('/\d{4}/', (string) ($r['coin_date'] ?? ''), $m)) { $years[$m[0]] = true; }
        }
        ksort($years);
        $pass('years for "' . ($_POST['cat'] ?? '') . '"', (bool) $years,
              $years ? implode(', ', array_keys($years)) : 'none in memory - crawl that branch first');
        break;

    case 'import': /* ---- 6. one coin import, standalone (2 calls) ---- */
        $gsId = (int) ($_POST['gs_id'] ?? 0);
        $m = t_gs('GetCollectibleRequest', ['GsId' => $gsId]);
        $coin = $m['data'][0] ?? [];
        if (!$pass('GetCollectibleRequest GsId=' . $gsId, (bool) $coin, $coin ? ($coin['Name'] ?? '') : $m['err'])) { break; }
        $p = t_gs('GetPricingRequest', ['Gsid' => $gsId] + (ctype_digit((string) ($_POST['grade'] ?? '')) ? ['Grade' => (int) $_POST['grade']] : []));
        $price = $p['data'][0]['PricingData'][0] ?? [];
        $pass('GetPricingRequest', $p['status'] === 200, $price ? ('grade ' . ($price['GradeLabel'] ?? '?') . '  CPG=' . ($price['CpgVal'] ?? '-') . '  Grey=' . ($price['GreyVal'] ?? '-')) : 'no pricing rows');
        $map = ['CoinDate' => 'year', 'MintMark' => 'mint_mark', 'DenominationShort' => 'denomination',
                'Variety' => 'coin_variety_1', 'Variety2' => 'coin_variety_2', 'Composition' => 'composition',
                'Fineness' => 'fineness', 'StrikeType' => 'strike_type', 'Mintage' => '(mintage)', 'Designer' => '(designer)'];
        $out[] = '';
        foreach ($map as $k => $f) {
            if (isset($coin[$k]) && is_scalar($coin[$k]) && trim((string) $coin[$k]) !== '') { $out[] = '      ' . str_pad($f, 22) . ' = ' . $coin[$k]; }
        }
        if (!empty($coin['WeightOunces'])) { $out[] = '      ' . str_pad('package_weight (lb)', 22) . ' = ' . round(((float) $coin['WeightOunces']) / 16, 4); }
        if (!empty($coin['CatalogPath']) && is_array($coin['CatalogPath'])) {
            $last = end($coin['CatalogPath']);
            if (!empty($last['Name'])) { $out[] = '      ' . str_pad('category_name (seed)', 22) . ' = ' . $last['Name']; }
        }
        break;
}

$h = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Bulk Loader pre-flight tests</title>
<style>
 body{font-family:Arial,Helvetica,sans-serif;background:#CCFFCC;color:#222;margin:0;padding:24px;}
 h1{color:#1C4532;font-size:1.2rem;} h2{color:#1C4532;font-size:0.95rem;margin:0 0 8px;}
 .card{background:#fff;border:1px solid #b4b4b4;border-radius:8px;padding:14px;margin-bottom:14px;}
 .row{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:6px 0;}
 button{background:#2e8b57;color:#fff;border:none;border-radius:50px;padding:9px 20px;font-weight:700;cursor:pointer;}
 button:hover{background:#1e6e43;} button.blue{background:#007bff;} button.blue:hover{background:#0056b3;}
 input{border:1px solid #b4b4b4;border-radius:4px;padding:8px 10px;font:inherit;}
 pre{background:#0f1f16;color:#d7ffe0;padding:14px;border-radius:6px;overflow:auto;font-size:13px;}
 pre .ok{color:#7CFC98;} .muted{color:#5f6b62;font-size:12px;}
</style></head><body>
<h1>Sellbrite Bulk Loader &mdash; pre-flight tests <span class="muted">(standalone, no app dependencies)</span></h1>

<div class="card"><h2>1. Environment <span class="muted">(0 API calls)</span></h2>
  <form method="post"><button name="check" value="env">Run environment checks</button></form>
</div>

<div class="card"><h2>2. GreySheet ping <span class="muted">(1 API call)</span></h2>
  <form method="post"><button class="blue" name="check" value="gs">Ping GreySheet</button></form>
</div>

<div class="card"><h2>3. Gemini hello world <span class="muted">(1 Gemini call &mdash; prints what it generates)</span></h2>
  <form method="post"><button class="blue" name="check" value="gemini">Generate hello world</button></form>
</div>

<div class="card"><h2>4. Crawl a branch <span class="muted">(default: Half Cents, ~7 calls &mdash; streams progress, resumable, writes the memory file)</span></h2>
  <form method="post" class="row">
    <input name="root" value="8243" size="7" title="root node id (1 = all of U.S. Coins)">
    <input name="maxcalls" value="10" size="5" title="call budget">
    <input name="delay" value="250" size="5" title="delay ms">
    <button class="blue" name="check" value="crawl">Run crawl</button></form>
</div>

<div class="card"><h2>5. Memory search &amp; dynamic years <span class="muted">(0 API calls &mdash; reads what the crawl stored)</span></h2>
  <form method="post" class="row"><input name="q" value="1804 half cent" size="24">
    <button name="check" value="search">Search memory</button></form>
  <form method="post" class="row"><input name="cat" value="Draped Bust Half Cent" size="24">
    <button name="check" value="years">Years for category</button></form>
</div>

<div class="card"><h2>6. Import one coin <span class="muted">(2 API calls &mdash; take a GsId from a search hit above)</span></h2>
  <form method="post" class="row"><input name="gs_id" placeholder="GsId" size="10"><input name="grade" placeholder="grade # (opt)" size="9">
    <button class="blue" name="check" value="import">Import &amp; map</button></form>
</div>

<div class="card"><h2>7. App chain + FULL FLOW <span class="muted">(loads the real agent files one by one &mdash; if the page dies, the last file printed is the broken one &mdash; then resolves + imports through the agent)</span></h2>
  <form method="post" class="row">
    <input name="cat" value="Draped Bust Half Cent" size="20"><input name="year" value="1804" size="6">
    <input name="mm" value="" size="4" placeholder="mint"><input name="grade" value="" size="8" placeholder="grade">
    <button class="blue" name="check" value="agent">Load chain &amp; run full flow</button></form>
</div>

<?php if ($out): ?>
<div class="card"><h2>Result &mdash; <?= $h($check) ?></h2>
<pre><?php foreach ($out as $line) { echo str_starts_with($line, 'PASS') ? '<span class="ok">' . $h($line) . '</span>' . "\n" : $h($line) . "\n"; } ?></pre></div>
<?php endif; ?>

<p class="muted">Order: 1 &rarr; 2 &rarr; 3 &rarr; 4 (crawl) &rarr; 5 &rarr; 6 &rarr; 7. Checks 1-6 have zero dependencies on the app files; only check 7 touches them. Delete this file when everything is green.</p>
</body></html>
