<?php
/*
 * Pre-flight test page for the Sellbrite Bulk Loader GreySheet agent.
 *
 * Run this BEFORE the seed crawl. Each button runs one check and shows
 * PASS/FAIL with the reason, so API calls are only spent when you click:
 *
 *   1. Config + DB2 + memory round-trip   0 API calls
 *   2. GreySheet ping                     1 API call
 *   3. Gemini ping                        1 Gemini call
 *   4. Memory search / years              0 API calls (run after a crawl)
 *   5. Import one coin by GsId            2 API calls (+ Gemini)
 *
 * Open from a browser session signed in to LCCOnline. Safe to delete
 * once everything passes.
 */

foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require_once __DIR__ . '/SellbriteBulkLoader_agent.php';

$check = $_POST['check'] ?? '';
$out   = [];
$pass  = static function ($label, $ok, $detail = '') use (&$out) {
    $out[] = ($ok ? 'PASS  ' : 'FAIL  ') . $label . ($detail !== '' ? '  -  ' . $detail : '');
    return $ok;
};

switch ($check) {

    case 'safe':   /* ---- 1. config + DB2 + memory round trip (0 API calls) ---- */
        $pass('GreySheet keys set (GS_API_TOKEN / GS_API_KEY)', GS_API_TOKEN !== '' && GS_API_KEY !== '',
              GS_API_TOKEN === '' ? 'edit the constants at the top of SellbriteBulkLoader_agent.php' : 'base=' . GS_BASE_URL);
        $pass('Gemini key set (GEMINI_API_KEY)', geminiConfigured(),
              geminiConfigured() ? 'model=' . GEMINI_MODEL : 'AI mapping/generation will fall back to the basic map');
        $pass('db2 extension loaded', function_exists('db2_prepare'));
        $pass('getDB2PConn available (Utils loaded)', function_exists('getDB2PConn'));
        $conn = function_exists('sbl_conn') ? sbl_conn() : false;
        if (!$pass('DB2 connection (signed-in session credentials)', (bool) $conn,
                   $conn ? '' : 'open this page while signed in to LCCOnline')) { break; }

        $rows = sbl_select('SELECT COUNT(*) AS N FROM ' . SBL_GSMEM_TABLE);
        $pass('memory table exists (' . SBL_GSMEM_TABLE . ')', isset($rows[0]['n']),
              isset($rows[0]['n']) ? $rows[0]['n'] . ' rows' : 'run RUNSQLSTM SRCMBR(SBLMEMORYT) first');

        $rows = sbl_select('SELECT COUNT(*) AS N FROM ' . SBL_TABLE);
        $pass('product table exists (' . SBL_TABLE . ')', isset($rows[0]['n']),
              isset($rows[0]['n']) ? $rows[0]['n'] . ' rows' : 'run RUNSQLSTM SRCMBR(SBLPRODUCT) first');

        // Write -> search -> delete a marker row; proves the whole memory path.
        gsMemUpsert('C', 999999999, 'TEST COIN 1881-Z', 'TEST > PATH', '1881', 'Z');
        $hits = gsMemSearch('test coin 1881');
        $found = false;
        foreach ($hits as $h) { if ((int) $h['gs_id'] === 999999999) { $found = true; } }
        gsMemExec('DELETE FROM ' . SBL_GSMEM_TABLE . " WHERE kind = 'C' AND ref_id = 999999999", []);
        $pass('memory write -> search -> delete round trip', $found,
              $found ? 'insert, word-search and cleanup all worked' : 'row not found back - check table authorities');
        break;

    case 'gs':     /* ---- 2. GreySheet ping (1 API call) ---- */
        $resp = gsApiGet('GetNodeRequest', ['NodeId' => 1], $meta);
        $node = gsData($resp)[0] ?? [];
        $pass('GetNodeRequest?NodeId=1', $resp !== null,
              $resp !== null
                ? 'HTTP ' . $meta['status'] . ' in ' . $meta['ms'] . 'ms - node "' . ($node['Name'] ?? '?')
                  . '" children=' . ($node['NodeChildrenCountLive'] ?? '?')
                : $meta['error'] . '  (if Auth rejected on the beta host, switch GS_BASE_URL to prod)');
        break;

    case 'gemini': /* ---- 3. Gemini ping (1 Gemini call) ---- */
        $r = geminiJson('Return ONLY the JSON {"ok": true}.', 'ping', $meta);
        $pass('Gemini generateContent', is_array($r) && !empty($r['ok']),
              is_array($r) ? 'tokens=' . $meta['tokens'] . ' in ' . $meta['ms'] . 'ms' : $meta['error']);
        break;

    case 'search': /* ---- 4a. memory search (0 API calls) ---- */
        $q = trim((string) ($_POST['q'] ?? ''));
        $hits = gsMemSearch($q);
        $pass('memory search "' . $q . '"', (bool) $hits, count($hits) . ' hit(s)'
              . ($hits ? '' : ' - run the mini seed crawl first (root=8243&maxcalls=10)'));
        foreach (array_slice($hits, 0, 15) as $h) { $out[] = '      [' . $h['gs_id'] . '] ' . $h['label'] . '   (' . $h['path'] . ')'; }
        break;

    case 'years':  /* ---- 4b. years for a category (0 API calls from memory) ---- */
        $cat = trim((string) ($_POST['cat'] ?? ''));
        $years = gsYearsFor($cat, false);   // memory only - no live navigation from the test page
        $pass('years for "' . $cat . '" (memory only)', (bool) $years,
              $years ? implode(', ', $years) : 'none in memory yet - crawl that branch first');
        break;

    case 'import': /* ---- 5. one-coin import (2 API calls + Gemini) ---- */
        $gsId = (int) ($_POST['gs_id'] ?? 0);
        $imp  = gsImport(['gs_id' => $gsId, 'grade' => trim((string) ($_POST['grade'] ?? ''))]);
        $pass('gsImport GsId=' . $gsId, $imp['ok'] && $imp['found'],
              !$imp['ok'] ? $imp['error'] : (!$imp['found'] ? 'coin not found on GreySheet' : 'via ' . $imp['via']));
        if ($imp['found']) {
            foreach ($imp['row'] as $k => $v) { if ((string) $v !== '') { $out[] = '      ' . str_pad($k, 28) . ' = ' . $v; } }
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
<h1>Sellbrite Bulk Loader &mdash; pre-flight tests</h1>

<div class="card"><h2>1. Safe checks &mdash; config, DB2, memory round trip <span class="muted">(0 API calls)</span></h2>
  <form method="post"><button name="check" value="safe">Run safe checks</button></form>
</div>

<div class="card"><h2>2. GreySheet ping <span class="muted">(1 API call)</span></h2>
  <form method="post"><button class="blue" name="check" value="gs">Ping GreySheet</button></form>
</div>

<div class="card"><h2>3. Gemini ping <span class="muted">(1 Gemini call)</span></h2>
  <form method="post"><button class="blue" name="check" value="gemini">Ping Gemini</button></form>
</div>

<div class="card"><h2>4. Memory search &amp; dynamic years <span class="muted">(0 API calls &mdash; run the mini crawl first: <code>SellbriteBulkLoader_seed.php?root=8243&amp;maxcalls=10&amp;delay=250</code>)</span></h2>
  <form method="post" class="row"><input name="q" value="1804 half cent" size="24">
    <button name="check" value="search">Search memory</button></form>
  <form method="post" class="row"><input name="cat" value="Draped Bust Half Cent" size="24">
    <button name="check" value="years">Years for category</button></form>
</div>

<div class="card"><h2>5. Import one coin <span class="muted">(2 API calls + Gemini &mdash; take a GsId from a search hit above)</span></h2>
  <form method="post" class="row"><input name="gs_id" placeholder="GsId" size="10"><input name="grade" placeholder="grade (opt)" size="8">
    <button class="blue" name="check" value="import">Import &amp; map</button></form>
</div>

<?php if ($out): ?>
<div class="card"><h2>Result &mdash; <?= $h($check) ?></h2>
<pre><?php foreach ($out as $line) { echo str_starts_with($line, 'PASS') ? '<span class="ok">' . $h($line) . '</span>' . "\n" : $h($line) . "\n"; } ?></pre></div>
<?php endif; ?>

<p class="muted">Order: 1 &rarr; 2 &rarr; 3, then run the mini seed crawl, then 4 &rarr; 5. All green = safe to run the full crawl. Delete this file when done.</p>
</body></html>
