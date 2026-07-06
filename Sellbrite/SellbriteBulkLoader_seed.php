<?php
/*
 * One-time seed crawl: populate the GreySheet catalog memory.
 *
 * The catalog has NO single root node - four trees sit side by side at the top:
 *   1 = U.S. Coins    2 = U.S. Currency    6 = World Coins    12 = World Currency
 * Walks each requested tree breadth-first, storing every folder and every coin
 * (name, GsId, path, date, mint mark). Where it stores:
 *
 *   - DB2 available (the IBM i, signed in to LCCOnline): writes SBLMEMORYT
 *     through the agent's memory functions. THIS is the production run.
 *   - No DB2 (XAMPP): writes SellbriteBulkLoader_memory.dev.json itself, the
 *     same file/shape the standalone test page reads - so the crawl can be
 *     tested locally even though the DB2 screen won't use that file.
 *
 * COST: calls scale with the number of NODES, not coins (one call lists a
 * whole leaf). Full U.S. Coins run is roughly 1,000-2,500 calls. The crawl
 * is budget-capped and RESUMABLE: nodes already marked done are re-expanded
 * from storage at 0 API calls, so just run it again to continue.
 *
 * RUN
 *   Browser: SellbriteBulkLoader_seed.php?maxcalls=1200&delay=150
 *            (on the i: be signed in to LCCOnline in the same browser)
 *   Default tree is root=1 (U.S. Coins). The other trees when you want them:
 *     ?root=2   U.S. Currency      ?root=6    World Coins
 *     ?root=12  World Currency     ?root=1,2  several in one run
 *   Mini test first: ?root=8243&maxcalls=10&delay=250   (Half Cents, ~7 calls)
 */

// Framework helpers (getDB2PConn) + the signed-in user's DB2 credentials,
// same as the AJAX endpoint. Both are optional off the i.
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') { session_start(); }

require_once __DIR__ . '/SellbriteBulkLoader_agent.php';   // gsApiGet/gsData + DB2 memory fns

if (function_exists('set_time_limit')) { @set_time_limit(0); }
header('Content-Type: text/plain; charset=utf-8');

/* ---- options (querystring or CLI key=value; root takes a comma list) ---- */
$opt = ['maxcalls' => '1200', 'delay' => '150', 'root' => (string) GS_ROOT_NODE];
$src = (PHP_SAPI === 'cli') ? array_slice($_SERVER['argv'] ?? [], 1) : [];
foreach ($src as $a) { if (preg_match('/^(\w+)=([\d,]+)$/', $a, $m)) { $opt[$m[1]] = $m[2]; } }
foreach ($opt as $k => $v) { if (isset($_GET[$k]) && preg_match('/^[\d,]+$/', (string) $_GET[$k])) { $opt[$k] = (string) $_GET[$k]; } }

$maxCalls = max(1, (int) $opt['maxcalls']);
$delayUs  = max(0, (int) $opt['delay']) * 1000;
$roots    = array_values(array_unique(array_filter(array_map('intval', explode(',', $opt['root'])))));
if (!$roots) { $roots = [GS_ROOT_NODE]; }

echo 'SEED CRAWL  root=' . implode(',', $roots) . "  maxcalls={$maxCalls}  delay=" . (int) $opt['delay'] . "ms\n";
echo str_repeat('-', 60) . "\n";
@ob_flush(); @flush();

if (GS_API_TOKEN === '' || GS_API_KEY === '') { exit("STOP: GS_API_TOKEN / GS_API_KEY not set in the agent file.\n"); }

/* ---- storage: DB2 (production) or the seed's own JSON (local testing) ---- */
$HAS_DB2   = function_exists('sbl_conn') && sbl_conn();
$JSON_FILE = __DIR__ . '/SellbriteBulkLoader_memory.dev.json';
$JSON      = [];

if ($HAS_DB2) {
    // Make sure the table actually exists, otherwise every write silently no-ops.
    $probe = sbl_select('SELECT COUNT(*) AS N FROM ' . SBL_GSMEM_TABLE);
    if (!isset($probe[0]['n'])) {
        exit("STOP: DB2 is connected but " . SBL_GSMEM_TABLE . " is missing or not readable.\n"
           . "Create it first:  RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC) SRCMBR(SBLMEMORYT) COMMIT(*NONE)\n");
    }
    echo 'DB2 MODE: storing to ' . SBL_GSMEM_TABLE . ' (' . $probe[0]['n'] . " rows already)\n";
} else {
    $d = is_file($JSON_FILE) ? json_decode((string) file_get_contents($JSON_FILE), true) : null;
    $JSON = is_array($d) ? $d : [];
    echo 'JSON MODE (no DB2): storing to ' . basename($JSON_FILE) . ' (' . count($JSON) . " rows already)\n";
    echo "Note: the standalone test page reads this file; the DB2 screen does not.\n";
}
echo str_repeat('-', 60) . "\n";
@ob_flush(); @flush();

/* Small adapter so the crawl below is identical in both modes. */
function seed_json_save(): void
{
    global $HAS_DB2, $JSON, $JSON_FILE;
    if (!$HAS_DB2) { @file_put_contents($JSON_FILE, json_encode($JSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX); }
}
function seed_node(int $id, string $name, string $path, int $parent, int $coins, string $done = 'N'): void
{
    global $HAS_DB2, $JSON;
    if ($HAS_DB2) { gsMemLearnNode($id, $name, $path, $parent, $coins, $done); return; }
    $JSON['N:' . $id] = ['kind' => 'N', 'ref_id' => $id, 'parent_id' => $parent, 'name' => $name,
        'path' => $path, 'coin_date' => '', 'mint_mark' => '', 'coin_count' => $coins, 'done' => $done];
}
function seed_coins(array $coins, string $path, int $parent): void
{
    global $HAS_DB2, $JSON;
    if ($HAS_DB2) { gsMemLearnCoins($coins, $path, $parent); return; }
    foreach ($coins as $c) {
        $id = (int) ($c['Gsid'] ?? 0);
        if ($id <= 0) { continue; }
        $JSON['C:' . $id] = ['kind' => 'C', 'ref_id' => $id, 'parent_id' => $parent,
            'name' => (string) ($c['Name'] ?? ''), 'path' => $path,
            'coin_date' => (string) ($c['CoinDate'] ?? ''), 'mint_mark' => (string) ($c['MintMark'] ?? ''),
            'coin_count' => 0, 'done' => 'N'];
    }
}
function seed_done(int $id): void
{
    global $HAS_DB2, $JSON;
    if ($HAS_DB2) { gsMemMarkDone($id); return; }
    if (isset($JSON['N:' . $id])) { $JSON['N:' . $id]['done'] = 'Y'; }
}
function seed_done_map(): array
{
    global $HAS_DB2, $JSON;
    $out = [];
    if ($HAS_DB2) {
        foreach (gsMemNodes() as $n) { if (($n['done'] ?? 'N') === 'Y') { $out[(int) $n['ref_id']] = true; } }
    } else {
        foreach ($JSON as $r) { if (($r['kind'] ?? '') === 'N' && ($r['done'] ?? 'N') === 'Y') { $out[(int) $r['ref_id']] = true; } }
    }
    return $out;
}
function seed_children_of(int $id): array
{
    global $HAS_DB2, $JSON;
    $out = [];
    if ($HAS_DB2) {
        foreach (gsMemNodeChildren($id) as $k) {
            $out[] = ['id' => (int) $k['ref_id'], 'name' => $k['name'], 'path' => (string) ($k['path'] ?? ''),
                      'coins' => (int) $k['coin_count'], 'parent' => $id];
        }
    } else {
        foreach ($JSON as $r) {
            if (($r['kind'] ?? '') === 'N' && (int) ($r['parent_id'] ?? 0) === $id) {
                $out[] = ['id' => (int) $r['ref_id'], 'name' => $r['name'], 'path' => $r['path'],
                          'coins' => (int) $r['coin_count'], 'parent' => $id];
            }
        }
    }
    return $out;
}
/* Real name/path for a starting node, so stored paths label the right tree
 * (U.S. Coins vs World Currency...). Known top-level roots cost 0 calls;
 * a node we've crawled before comes from storage; anything else (e.g. a
 * mini-test sub-tree) spends ONE GetNodeRequest call on its name. */
function seed_root_entry(int $id, array &$stat): array
{
    global $HAS_DB2, $JSON;
    $tops = function_exists('gsRoots') ? gsRoots()
          : [1 => 'U.S. Coins', 2 => 'U.S. Currency', 6 => 'World Coins', 12 => 'World Currency'];
    $name = (string) ($tops[$id] ?? '');
    $path = $name;
    if ($name === '') {
        if ($HAS_DB2) {
            $r = gsMemRows('SELECT name, path FROM ' . SBL_GSMEM_TABLE
                         . " WHERE kind = 'N' AND ref_id = ?", [$id]);
            $name = (string) ($r[0]['name'] ?? '');
            $path = (string) (($r[0]['path'] ?? '') !== '' ? $r[0]['path'] : $name);
        } elseif (isset($JSON['N:' . $id])) {
            $name = (string) $JSON['N:' . $id]['name'];
            $path = (string) (($JSON['N:' . $id]['path'] ?? '') !== '' ? $JSON['N:' . $id]['path'] : $name);
        }
    }
    if ($name === '') {
        $resp = gsApiGet('GetNodeRequest', ['NodeId' => $id], $m);
        $stat['calls']++;
        $d    = is_array($resp) ? ($resp['Data'] ?? []) : [];
        $name = (string) ($d['Name'] ?? ($d[0]['Name'] ?? ''));
        if ($name === '') { $name = '(root ' . $id . ')'; }
        $path = $name;
    }
    return ['id' => $id, 'name' => $name, 'path' => $path, 'coins' => 0, 'parent' => 0];
}

/* ------------------------------ the crawl ------------------------------- */
$doneNodes = seed_done_map();
$stat  = ['calls' => 0, 'nodes' => 0, 'coins' => 0, 'skipped' => 0, 'stopped' => ''];
$queue = [];
foreach ($roots as $rid) { $queue[] = seed_root_entry($rid, $stat); }
$seen  = [];
$permitWarned = false;   // show the basic-tier note at most once

while ($queue) {
    if ($stat['calls'] >= $maxCalls) { $stat['stopped'] = 'call budget reached (run again to resume)'; break; }
    $n  = array_shift($queue);
    $id = (int) $n['id'];
    if ($id <= 0 || isset($seen[$id])) { continue; }
    $seen[$id] = true;
    $stat['nodes']++;

    // Resume: a done node's children come from storage, 0 API calls.
    if (isset($doneNodes[$id])) {
        $stat['skipped']++;
        foreach (seed_children_of($id) as $kid) { $queue[] = $kid; }
        continue;
    }

    if ((int) $n['coins'] > 0) {
        // Leaf: one call stores every coin in the series.
        $resp = gsApiGet('GetCollectibleByNodeRequest', ['NodeId' => $id], $meta);
        $stat['calls']++;
        if ($resp === null) { $stat['stopped'] = 'API error: ' . $meta['error']; break; }
        $coins = gsData($resp);
        seed_coins($coins, $n['path'], $id);
        seed_done($id);
        $stat['coins'] += count($coins);
        echo "LEAF [{$id}] {$n['name']}  +" . count($coins) . " coins  (call {$stat['calls']}, {$meta['ms']}ms)\n";
        if ($coins) { $s = $coins[0]; echo '      e.g. GsId=' . ($s['Gsid'] ?? '?') . '  "' . ($s['Name'] ?? '?') . '"  date=' . ($s['CoinDate'] ?? '?') . "\n"; }
    } else {
        // Folder: list children, store them, queue them, mark this folder done.
        $resp = gsApiGet('GetNodeChildrenRequest', ['NodeId' => $id], $meta);
        $stat['calls']++;
        if ($resp === null) { $stat['stopped'] = 'API error: ' . $meta['error']; break; }
        $kids = gsData($resp);
        foreach ($kids as $c) {
            $kid = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                    'path' => $n['path'] . ' > ' . (string) ($c['Name'] ?? ''),
                    'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0), 'parent' => $id];
            seed_node($kid['id'], $kid['name'], $kid['path'], $id, $kid['coins']);
            $queue[] = $kid;
        }
        seed_node($id, $n['name'], $n['path'], (int) $n['parent'], 0, 'Y');
        echo "NODE [{$id}] {$n['name']}  +" . count($kids) . " folders  (call {$stat['calls']}, {$meta['ms']}ms)\n";
    }
    if (!$permitWarned && !($meta['permit'] ?? true)) {
        echo '      note: ' . ($meta['note'] ?? 'PermitAccess=false') . " - navigation Data still stored.\n";
        $permitWarned = true;
    }
    seed_json_save();
    @ob_flush(); @flush();
    if ($delayUs) { usleep($delayUs); }
}

echo str_repeat('-', 60) . "\n";
echo "DONE: {$stat['calls']} API calls, {$stat['nodes']} nodes visited "
   . "({$stat['skipped']} resumed from storage), {$stat['coins']} coins stored.\n";
if ($stat['stopped']) { echo 'Stopped: ' . $stat['stopped'] . "\n"; }
if ($queue)           { echo count($queue) . " nodes still queued - run again to continue.\n"; }
else                  { echo "Crawl complete - the whole tree is in memory.\n"; }
