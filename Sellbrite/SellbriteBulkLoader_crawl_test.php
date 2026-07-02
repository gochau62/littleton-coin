<?php
/*
 * Standalone GreySheet catalog crawl tester - same shape as greysheet.php
 * (which is proven to run): zero dependency on the M-Power stack, agent,
 * logic, or model files. Hardcoded keys, direct cURL, nothing else required.
 *
 * If SellbriteBulkLoader_seed.php fails to run but this file does, the
 * problem is somewhere in the require_once chain
 * (SellbriteBulkLoader_agent.php -> _logic.php -> _model.php -> Utils/*),
 * not in the GreySheet crawl logic itself or in PHP/cURL/XAMPP.
 *
 * What it does: walks the node tree (GetNodeChildrenRequest /
 * GetCollectibleByNodeRequest) breadth-first from a root node and writes
 * every folder + coin into SellbriteBulkLoader_memory.dev.json - the exact
 * same file and row shape SellbriteBulkLoader_agent.php's dev-mode memory
 * reads, so the coin-search / years tests on the test page pick it up.
 *
 * RUN
 *   Browser: SellbriteBulkLoader_crawl_test.php?root=8243&maxcalls=10&delay=250
 *   CLI:     php SellbriteBulkLoader_crawl_test.php root=8243 maxcalls=10 delay=250
 */

/* ----------------------------- CONFIG ---------------------------------- */
$CFG = [
    'base'  => 'https://cpgpublicapiv2.greysheet.com/api',
    'token' => 'B71FE10C-3B96-41B4-9A9E-A307DBE29B82',
    'key'   => '7056764F-B695-4543-994D-6471B64E083A',
    'level' => 'basic',
];
$MEM_FILE = __DIR__ . '/SellbriteBulkLoader_memory.dev.json';

$IS_CLI = (PHP_SAPI === 'cli');
if (function_exists('set_time_limit')) { @set_time_limit(0); }
if (!$IS_CLI) { header('Content-Type: text/plain; charset=utf-8'); }

/* ---- options (querystring or CLI key=value) ---- */
$opt = ['root' => 1, 'maxcalls' => 10, 'delay' => 250];
$src = $IS_CLI ? array_slice($argv, 1) : [];
foreach ($src as $a) { if (preg_match('/^(\w+)=(\d+)$/', $a, $m)) { $opt[$m[1]] = (int) $m[2]; } }
foreach ($opt as $k => $v) { if (isset($_GET[$k]) && ctype_digit((string) $_GET[$k])) { $opt[$k] = (int) $_GET[$k]; } }

/* --------------------------- CORE REQUEST ------------------------------ */
/** One GET against base+path. Same pattern as greysheet.php's gs_call. */
function gsc_call(array $cfg, string $path, array $params): array
{
    $meta = ['status' => 0, 'ms' => 0, 'url' => '', 'err' => '', 'body' => ''];
    if ($cfg['level'] !== '' && !isset($params['apiLevel'])) { $params['apiLevel'] = $cfg['level']; }
    $url = rtrim($cfg['base'], '/') . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    $meta['url'] = $url;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'x-api-token: ' . $cfg['token'],
            'x-api-key: '   . $cfg['key'],
            'Accept: application/json',
        ],
    ]);
    $t0   = microtime(true);
    $body = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);
    if ($body === false) {
        $meta['err'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
        curl_close($ch);
        return $meta;
    }
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $meta['body']   = (string) $body;
    curl_close($ch);
    return $meta;
}

function gsc_items(array $meta): array
{
    $d = json_decode($meta['body'] ?? '', true);
    if (is_array($d) && isset($d['Data']) && is_array($d['Data'])) {
        return array_values(array_filter($d['Data'], 'is_array'));
    }
    return [];
}

/* -------------------------- DEV MEMORY FILE ----------------------------- */
function gsc_mem_load(string $file): array
{
    if (!is_file($file)) { return []; }
    $d = json_decode((string) file_get_contents($file), true);
    return is_array($d) ? $d : [];
}
function gsc_mem_save(string $file, array $rows): void
{
    @file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}
function gsc_mem_node(array &$rows, int $id, string $name, string $path, int $parent, int $coinCount, string $done = 'N'): void
{
    if ($id <= 0 || $name === '') { return; }
    $rows['N:' . $id] = ['kind' => 'N', 'ref_id' => $id, 'parent_id' => $parent, 'name' => $name,
        'path' => $path, 'coin_date' => '', 'mint_mark' => '', 'coin_count' => $coinCount, 'done' => $done];
}
function gsc_mem_coins(array &$rows, array $coins, string $path, int $parentNodeId): void
{
    foreach ($coins as $c) {
        $id = (int) ($c['Gsid'] ?? 0);
        if ($id <= 0) { continue; }
        $rows['C:' . $id] = ['kind' => 'C', 'ref_id' => $id, 'parent_id' => $parentNodeId,
            'name' => (string) ($c['Name'] ?? ''), 'path' => $path,
            'coin_date' => (string) ($c['CoinDate'] ?? ''), 'mint_mark' => (string) ($c['MintMark'] ?? ''),
            'coin_count' => 0, 'done' => 'N'];
    }
}

/* --------------------------------- RUN ---------------------------------- */
$maxCalls = max(1, (int) $opt['maxcalls']);
$delayUs  = max(0, (int) $opt['delay']) * 1000;

echo "STANDALONE CRAWL TEST  root={$opt['root']}  maxcalls={$maxCalls}  delay={$opt['delay']}ms\n";
echo "base={$CFG['base']}  token=" . substr($CFG['token'], 0, 4) . '...'
   . '  key=' . substr($CFG['key'], 0, 4) . "...\n";
echo str_repeat('-', 60) . "\n";
if (!$IS_CLI) { @ob_flush(); @flush(); }

if (!function_exists('curl_init')) { exit("STOP: PHP cURL extension is not enabled (check php.ini for extension=curl).\n"); }

$mem = gsc_mem_load($MEM_FILE);
$doneNodes = [];
foreach ($mem as $r) { if (($r['kind'] ?? '') === 'N' && ($r['done'] ?? 'N') === 'Y') { $doneNodes[(int) $r['ref_id']] = true; } }

$queue = [['id' => (int) $opt['root'], 'name' => 'U.S. Coins', 'path' => 'U.S. Coins', 'coins' => 0]];
$seen  = [];
$calls = 0; $nodes = 0; $coinsTotal = 0; $skipped = 0; $stopped = '';

while ($queue) {
    if ($calls >= $maxCalls) { $stopped = 'call budget reached (run again to resume)'; break; }
    $n  = array_shift($queue);
    $id = (int) $n['id'];
    if ($id <= 0 || isset($seen[$id])) { continue; }
    $seen[$id] = true;
    $nodes++;

    if (isset($doneNodes[$id])) {
        $skipped++;
        foreach ($mem as $r) {
            if (($r['kind'] ?? '') === 'N' && (int) ($r['parent_id'] ?? 0) === $id) {
                $queue[] = ['id' => (int) $r['ref_id'], 'name' => $r['name'], 'path' => $r['path'], 'coins' => (int) $r['coin_count']];
            }
        }
        continue;
    }

    if ((int) $n['coins'] > 0) {
        $m = gsc_call($CFG, 'GetCollectibleByNodeRequest', ['NodeId' => $id]);
        $calls++;
        if ($m['status'] !== 200) { $stopped = "HTTP {$m['status']} on GetCollectibleByNodeRequest: " . ($m['err'] ?: $m['body']); break; }
        $coins = gsc_items($m);
        gsc_mem_coins($mem, $coins, $n['path'], $id);
        gsc_mem_node($mem, $id, $n['name'], $n['path'], (int) ($n['parent'] ?? 0), (int) $n['coins'], 'Y');
        $coinsTotal += count($coins);
        echo "LEAF [{$id}] {$n['name']}  +" . count($coins) . " coins  (call {$calls}, {$m['ms']}ms)\n";
        if ($coins) { $s = $coins[0]; echo '      e.g. GsId=' . ($s['Gsid'] ?? '?') . ' "' . ($s['Name'] ?? '?') . '"  date=' . ($s['CoinDate'] ?? '?') . "\n"; }
    } else {
        $m = gsc_call($CFG, 'GetNodeChildrenRequest', ['NodeId' => $id]);
        $calls++;
        if ($m['status'] !== 200) { $stopped = "HTTP {$m['status']} on GetNodeChildrenRequest: " . ($m['err'] ?: $m['body']); break; }
        $kids = gsc_items($m);
        foreach ($kids as $c) {
            $kid = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                    'path' => $n['path'] . ' > ' . (string) ($c['Name'] ?? ''),
                    'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0), 'parent' => $id];
            gsc_mem_node($mem, $kid['id'], $kid['name'], $kid['path'], $id, $kid['coins']);
            $queue[] = $kid;
        }
        gsc_mem_node($mem, $id, $n['name'], $n['path'], (int) ($n['parent'] ?? 0), 0, 'Y');
        echo "NODE [{$id}] {$n['name']}  +" . count($kids) . " folders  (call {$calls}, {$m['ms']}ms)\n";
    }
    gsc_mem_save($MEM_FILE, $mem);
    if (!$IS_CLI) { @ob_flush(); @flush(); }
    if ($delayUs) { usleep($delayUs); }
}

echo str_repeat('-', 60) . "\n";
echo "DONE: {$calls} API calls, {$nodes} nodes visited ({$skipped} resumed), {$coinsTotal} coins stored.\n";
if ($stopped) { echo 'Stopped: ' . $stopped . "\n"; }
echo 'Memory file: ' . $MEM_FILE . '  (' . count($mem) . " total rows)\n";
if ($queue) { echo count($queue) . " nodes still queued - run again to continue.\n"; }
else        { echo "Crawl complete for this branch.\n"; }
