<?php
/*
 * One-time seed crawl: populate the GreySheet catalog memory (SBLGSMEMT).
 *
 * Walks the node tree from GS_ROOT_NODE breadth-first, storing every folder
 * and every coin (name, GsId, path, date, mint mark) into the memory table.
 * After this runs, the coin dropdown and the dynamic Year list work for the
 * whole catalog with 0 API calls; only the final data pull per coin is live.
 *
 * COST: calls scale with the number of NODES, not coins (one call lists a
 * whole leaf).  A full U.S. Coins run is roughly 1,000-2,500 calls.  The
 * crawl is budget-capped and RESUMABLE: nodes already marked done in the
 * table are skipped (their children/coins are read from the table), so you
 * can run it in slices - just run it again and it continues where it left off.
 *
 * RUN (on the server that has the keys + DB2):
 *   Browser:  SellbriteBulkLoader_seed.php?maxcalls=1200&delay=150
 *   CLI:      php SellbriteBulkLoader_seed.php maxcalls=1200 delay=150
 * Requires SBLGSMEMT to exist and GS_API_TOKEN / GS_API_KEY set in the agent.
 */

require_once __DIR__ . '/SellbriteBulkLoader_agent.php';   // client + memory helpers

if (function_exists('set_time_limit')) { @set_time_limit(0); }
header('Content-Type: text/plain; charset=utf-8');

/* ---- options (querystring or CLI key=value) ---- */
$opt = ['maxcalls' => 1200, 'delay' => 150, 'root' => GS_ROOT_NODE];
$src = (PHP_SAPI === 'cli') ? array_slice($_SERVER['argv'] ?? [], 1) : [];
foreach ($src as $a) { if (preg_match('/^(\w+)=(\d+)$/', $a, $m)) { $opt[$m[1]] = (int) $m[2]; } }
foreach ($opt as $k => $v) { if (isset($_GET[$k]) && ctype_digit((string) $_GET[$k])) { $opt[$k] = (int) $_GET[$k]; } }

$maxCalls = max(1, $opt['maxcalls']);
$delayUs  = max(0, $opt['delay']) * 1000;

echo "SEED CRAWL  root={$opt['root']}  maxcalls={$maxCalls}  delay={$opt['delay']}ms\n";
echo str_repeat('-', 60) . "\n";
@ob_flush(); @flush();

if (GS_API_TOKEN === '' || GS_API_KEY === '') { exit("STOP: GS_API_TOKEN / GS_API_KEY not set in the agent file.\n"); }
if (!(function_exists('sbl_conn') && sbl_conn())) { exit("STOP: no DB2 connection - create SBLGSMEMT and run this on the server.\n"); }

/* ---- known state from the table (resume support) ---- */
$doneNodes = [];
foreach (gsMemNodes() as $n) { if (($n['done'] ?? 'N') === 'Y') { $doneNodes[(int) $n['ref_id']] = true; } }

$queue = [['id' => (int) $opt['root'], 'name' => 'U.S. Coins', 'path' => 'U.S. Coins', 'coins' => 0]];
$seen  = [];
$stat  = ['calls' => 0, 'nodes' => 0, 'coins' => 0, 'skipped' => 0, 'stopped' => ''];

while ($queue) {
    if ($stat['calls'] >= $maxCalls) { $stat['stopped'] = 'call budget reached (run again to resume)'; break; }
    $n  = array_shift($queue);
    $id = (int) $n['id'];
    if ($id <= 0 || isset($seen[$id])) { continue; }
    $seen[$id] = true;
    $stat['nodes']++;

    // Resume: a done node's children are already in the table - no API needed.
    if (isset($doneNodes[$id])) {
        $stat['skipped']++;
        foreach (gsMemNodeChildren($id) as $kid) {
            $queue[] = ['id' => (int) $kid['ref_id'], 'name' => $kid['name'],
                        'path' => (string) ($kid['path'] ?? ''), 'coins' => (int) $kid['coin_count']];
        }
        continue;
    }

    if ((int) $n['coins'] > 0) {
        // Leaf: one call stores every coin in the series.
        $resp = gsApiGet('GetCollectibleByNodeRequest', ['NodeId' => $id], $meta);
        $stat['calls']++;
        if ($resp === null) { $stat['stopped'] = 'API error: ' . $meta['error']; break; }
        $coins = gsData($resp);
        gsMemLearnCoins($coins, $n['path'], $id);
        gsMemMarkDone($id);
        $stat['coins'] += count($coins);
        echo "LEAF [{$id}] {$n['name']}  +" . count($coins) . " coins  (call {$stat['calls']})\n";
    } else {
        // Folder: list children, store them, queue them.
        $resp = gsApiGet('GetNodeChildrenRequest', ['NodeId' => $id], $meta);
        $stat['calls']++;
        if ($resp === null) { $stat['stopped'] = 'API error: ' . $meta['error']; break; }
        $kids = gsData($resp);
        foreach ($kids as $c) {
            $kid = ['id' => (int) ($c['Id'] ?? 0), 'name' => (string) ($c['Name'] ?? ''),
                    'path' => $n['path'] . ' > ' . (string) ($c['Name'] ?? ''),
                    'coins' => (int) ($c['CollectibleChildrenCountLive'] ?? 0)];
            gsMemLearnNode($kid['id'], $kid['name'], $kid['path'], $id, $kid['coins']);
            $queue[] = $kid;
        }
        gsMemLearnNode($id, $n['name'], $n['path'], 0, 0, 'Y');   // folder itself is done
        echo "NODE [{$id}] {$n['name']}  +" . count($kids) . " folders  (call {$stat['calls']})\n";
    }
    @ob_flush(); @flush();
    if ($delayUs) { usleep($delayUs); }
}

echo str_repeat('-', 60) . "\n";
echo "DONE: {$stat['calls']} API calls, {$stat['nodes']} nodes visited "
   . "({$stat['skipped']} resumed from table), {$stat['coins']} coins stored.\n";
if ($stat['stopped']) { echo 'Stopped: ' . $stat['stopped'] . "\n"; }
if ($queue)           { echo count($queue) . " nodes still queued - run again to continue.\n"; }
else                  { echo "Crawl complete - the whole tree is in memory.\n"; }
