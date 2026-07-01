<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGreysheet_test.php      *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Stand-alone tester for the GreySheet CDN Public API v2.
 *
 *  WHAT IT DOES
 *  ------------
 *  Open this page in a browser on the server that has your keys + network
 *  access.  It fires one request to the configured (DEV) base URL and shows
 *  the HTTP status, timing, usage / rate-limit headers, any error, and the
 *  raw JSON the API returns - so we can see every field it gives us and map
 *  them onto the Sellbrite product columns.
 *
 *  - Uses the testing credentials in SellbriteGreysheet_config.php only.
 *  - Never prints your token/key.
 *  - Add ?format=json to get the raw result for the front-end / an agent.
 *
 *  Try different calls with the form, e.g.:
 *     path = GetNodeRequest      param NodeId = 1     (top of the catalog tree)
 */
require_once __DIR__ . '/SellbriteGreysheet_client.php';

$path   = isset($_GET['path']) ? preg_replace('/[^A-Za-z0-9_\/]/', '', $_GET['path']) : 'GetNodeRequest';
$pname  = isset($_GET['pname']) ? preg_replace('/[^A-Za-z0-9_]/', '', $_GET['pname']) : 'NodeId';
$pval   = isset($_GET['pval']) ? trim((string) $_GET['pval']) : '1';

$params = ($pname !== '' && $pval !== '') ? [$pname => $pval] : [];
$res    = gsResult($path, $params);

// Machine-readable mode for the front-end / agent.
if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json');
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$h = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>GreySheet API Test</title>
<style>
 body{font-family:Arial,Helvetica,sans-serif;background:#CCFFCC;color:#222;margin:0;padding:24px;}
 h1{color:#1C4532;font-size:1.2rem;}
 form{background:#fff;border:1px solid #b4b4b4;border-radius:8px;padding:14px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:end;}
 label{font-size:12px;font-weight:700;color:#5f6b62;display:block;}
 input{border:1px solid #b4b4b4;border-radius:4px;padding:8px 10px;font:inherit;}
 button{background:#2e8b57;color:#fff;border:none;border-radius:50px;padding:9px 22px;font-weight:700;cursor:pointer;}
 .card{background:#fff;border:1px solid #b4b4b4;border-radius:8px;padding:14px;margin-bottom:14px;}
 .ok{color:#1d7a37;font-weight:700;} .err{color:#cd0a0a;font-weight:700;}
 table.kv{border-collapse:collapse;} .kv td{padding:3px 12px 3px 0;font-size:13px;vertical-align:top;}
 pre{background:#0f1f16;color:#d7ffe0;padding:14px;border-radius:6px;overflow:auto;max-height:60vh;font-size:12.5px;}
 .muted{color:#5f6b62;font-size:12px;}
</style></head><body>
<h1>GreySheet CDN Public API v2 &mdash; Tester</h1>
<form method="get">
  <div><label>Path</label><input name="path" value="<?= $h($path) ?>" size="22"></div>
  <div><label>Param name</label><input name="pname" value="<?= $h($pname) ?>" size="12"></div>
  <div><label>Param value</label><input name="pval" value="<?= $h($pval) ?>" size="12"></div>
  <button type="submit">Send test request</button>
  <span class="muted">Hitting the DEV base URL from your config. Add <code>&amp;format=json</code> for raw JSON.</span>
</form>

<div class="card">
  <table class="kv">
    <tr><td>Result</td><td><?= $res['ok'] ? '<span class="ok">OK</span>' : '<span class="err">FAILED</span>' ?></td></tr>
    <tr><td>HTTP status</td><td><?= $h($res['status']) ?></td></tr>
    <tr><td>Time</td><td><?= $h($res['ms']) ?> ms</td></tr>
    <tr><td>URL</td><td><?= $h($res['url']) ?></td></tr>
    <?php if ($res['error']): ?><tr><td>Error</td><td class="err"><?= $h($res['error']) ?></td></tr><?php endif; ?>
    <tr><td>Usage headers</td><td><?= $res['usage'] ? $h(json_encode($res['usage'])) : '<span class="muted">none returned</span>' ?></td></tr>
  </table>
</div>

<div class="card">
  <div class="muted" style="margin-bottom:6px">Response body (every field the API returns for this call):</div>
  <pre><?= $h($res['data'] === null ? '(no body)' : json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
</div>

<?php if (!$res['ok'] && $res['status'] === 0): ?>
<div class="card muted">
  Could not connect. Check that this server can reach the GreySheet host and that
  <code>GS_API_TOKEN</code> / <code>GS_API_KEY</code> are set at the top of
  <code>SellbriteGreysheet_client.php</code>.
</div>
<?php endif; ?>
</body></html>
