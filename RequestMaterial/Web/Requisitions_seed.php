<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_seed.php            *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (function_exists('set_time_limit')) { @set_time_limit(0); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }

// the same level the station screen asks for, so whoever looks after the requisitions can load them
$authorized = "yes";
if ($conn && function_exists('chkAutUsr')) {
    $authorized = chkAutUsr($conn, $user, "LCCONLINE", 41);
}
if (!$conn || $authorized != "yes") {
    exit('Not authorized (sign in to LCC Online first).');
}

// each target table mapped to its column list in the exact order the CSV columns appear, since the exported CSV files carry no header row
$TABLES = array(
    'RQSREQHDRT' => array('RHREQ#','RHNAME','RHRQDT','RHRQTM','RHARCD','RHARTY',
                          'RHRUSH','RHAUTF','RHAUTB','RHBDGE','RHCMNT'),
    'RQSREQDTLT' => array('RDREQ#','RDLIN#','RDITEM','RDLOC','RDCNDT','RDDESC',
                          'RDQTY','RDCOST','RDRETL','RDACST','RDBDGE','RDSKUT',
                          'RDRTNF','RDRTDT'),
    'RQSCODEFLT' => array('CDTYPE','CDCODE','CDDESC','CDACTV'),
);

// all three files can be picked at once; they are sorted into load order here, header then detail then codes, because the detail lines hang off the headers
function rqsLoadOrder($files, $TABLES) {
    $order = array_keys($TABLES);
    $out = array();
    foreach ($files as $f) {
        foreach ($TABLES as $t => $c) {
            if (stripos($f['name'], $t) !== false) { $f['table'] = $t; break; }
        }
        $out[] = $f;
    }
    usort($out, function ($a, $b) use ($order) {
        $ia = array_search($a['table'] ?? '', $order, true);
        $ib = array_search($b['table'] ?? '', $order, true);
        return (($ia === false) ? 99 : $ia) - (($ib === false) ? 99 : $ib);
    });
    return $out;
}

// the load runs when files are posted: each is streamed row by row into its table with batched commits for speed on the journaled tables
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['csv'])) {
    header('Content-Type: text/plain; charset=utf-8');

    // one file or several arrive in the same shape, so they are flattened into a plain list first
    $picked = array();
    if (is_array($_FILES['csv']['name'])) {
        foreach ($_FILES['csv']['name'] as $i => $nm) {
            if ($nm === '') { continue; }
            $picked[] = array('name' => $nm, 'tmp_name' => $_FILES['csv']['tmp_name'][$i],
                              'size' => $_FILES['csv']['size'][$i],
                              'error' => $_FILES['csv']['error'][$i]);
        }
    } else {
        $picked[] = $_FILES['csv'];
    }
    if (!count($picked)) { exit("No files were chosen.\n"); }

    $forced = strtoupper(trim($_POST['table'] ?? ''));
    $picked = rqsLoadOrder($picked, $TABLES);

    echo "SEED LOAD  " . count($picked) . " file(s)\n";
    echo str_repeat('=', 60) . "\n\n";
    @ob_flush(); @flush();

foreach ($picked as $up) {
    if ($up['error'] !== UPLOAD_ERR_OK) {
        echo "{$up['name']}: upload failed (code {$up['error']}). If this is the detail CSV (~5 MB),\n" .
             "check php.ini upload_max_filesize / post_max_size - currently " .
             ini_get('upload_max_filesize') . " / " . ini_get('post_max_size') . ".\n\n";
        continue;
    }

    // which table: an explicit choice wins when only one file was picked, otherwise it comes from the filename
    $table = (count($picked) === 1 && $forced !== '' && $forced !== 'AUTO')
           ? $forced : ($up['table'] ?? '');
    if (!isset($TABLES[$table])) {
        echo "{$up['name']}: could not tell which table this belongs to - name it after its table.\n\n";
        continue;
    }
    $cols = $TABLES[$table];
    $ncol = count($cols);

    echo "{$up['name']}  ->  {$table}  (" . number_format($up['size']) . " bytes)\n";
    echo str_repeat('-', 60) . "\n";
    @ob_flush(); @flush();

    if (!empty($_POST['clear'])) {
        if (!db2_exec($conn, "DELETE FROM {$table}")) {
            exit("Clear failed: " . db2_stmt_errormsg() . "\n");
        }
        echo "cleared {$table}\n"; @ob_flush(); @flush();
    }

    $sql  = "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES (" .
            rtrim(str_repeat('?,', $ncol), ',') . ")";
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { exit("Prepare failed: " . db2_stmt_errormsg() . "\n"); }

    // batch commits: much faster on journaled tables
    @db2_autocommit($conn, DB2_AUTOCOMMIT_OFF);

    $fh = fopen($up['tmp_name'], 'r');
    $row = 0; $ok = 0; $bad = 0; $t0 = microtime(true);
    while (($f = fgetcsv($fh)) !== false) {
        // skip a fully blank line, which fgetcsv hands back as a single null field
        if ($f === array(null)) { continue; }
        $row++;
        if (count($f) !== $ncol) {
            $bad++;
            if ($bad <= 10) { echo "row {$row}: expected {$ncol} columns, got " . count($f) . " - skipped\n"; }
            continue;
        }
        if (db2_execute($stmt, $f)) {
            $ok++;
        } else {
            $bad++;
            if ($bad <= 10) { echo "row {$row}: " . db2_stmt_errormsg() . "\n"; }
        }
        if ($ok % 2000 === 0 && $ok > 0) { @db2_commit($conn); }
        if ($row % 5000 === 0) {
            echo "  ... {$row} rows (" . round(microtime(true) - $t0, 1) . "s)\n";
            @ob_flush(); @flush();
        }
    }
    fclose($fh);
    @db2_commit($conn);
    @db2_autocommit($conn, DB2_AUTOCOMMIT_ON);

    echo str_repeat('-', 60) . "\n";
    echo "done: {$ok} inserted, {$bad} skipped, " . round(microtime(true) - $t0, 1) . "s\n\n";

    // header load: restart the identity so new reqs continue the sequence
    if ($table === 'RQSREQHDRT' && $ok > 0) {
        $r = db2_exec($conn, "SELECT MAX(RHREQ#) AS MX FROM RQSREQHDRT");
        $mx = ($r && ($x = db2_fetch_assoc($r))) ? intval($x['MX']) : 0;
        $next = $mx + 1;
        if (db2_exec($conn, "ALTER TABLE RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH {$next}")) {
            echo "identity restarted: next req# = {$next}\n\n";
        } else {
            echo "IDENTITY RESTART FAILED - run manually:\n" .
                 "  ALTER TABLE RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH {$next}\n" .
                 "  (" . db2_stmt_errormsg() . ")\n\n";
        }
    }

    // validation for this table
    $checks = array(
        'RQSREQHDRT' => array(
            "COUNT(*)  [expect 14,073]"          => "SELECT COUNT(*) AS V FROM RQSREQHDRT",
            "authorized=Y  [expect 46]"          => "SELECT COUNT(*) AS V FROM RQSREQHDRT WHERE RHAUTF='Y'",
            "rush=Y  [expect 2,349]"             => "SELECT COUNT(*) AS V FROM RQSREQHDRT WHERE RHRUSH='Y'",
            "MAX(req#)  [expect 17,178]"         => "SELECT MAX(RHREQ#) AS V FROM RQSREQHDRT",
        ),
        'RQSREQDTLT' => array(
            "COUNT(*)  [expect 50,063]"          => "SELECT COUNT(*) AS V FROM RQSREQDTLT",
            "open lines  [expect 741]"           => "SELECT COUNT(*) AS V FROM RQSREQDTLT WHERE RDRTNF='N'",
            "SUM(qty)  [expect 33,464,119]"      => "SELECT SUM(RDQTY) AS V FROM RQSREQDTLT",
        ),
        'RQSCODEFLT' => array(
            "COUNT(*)  [expect 90]"              => "SELECT COUNT(*) AS V FROM RQSCODEFLT",
            "by type  [expect 4 types]"          => "SELECT COUNT(DISTINCT CDTYPE) AS V FROM RQSCODEFLT",
        ),
    );
    echo "validation:\n";
    foreach ($checks[$table] as $label => $q) {
        $r = db2_exec($conn, $q);
        $v = ($r && ($x = db2_fetch_assoc($r))) ? $x['V'] : '(query failed)';
        echo "  " . str_pad($label, 38) . " = " . number_format((float)$v) . "\n";
    }
    echo "\n" . str_repeat('=', 60) . "\n\n";
    @ob_flush(); @flush();
}

    echo "All done. Open Requisitions_ctl.php to see the grid.\n";
    exit;
}

// the upload form shown on a plain GET request, listing the CSV files to pick and the order they load in
?>
<!DOCTYPE html>
<html><head><title>Requisitions Seeder</title></head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f8f8f8;margin:0;">
<div style="background:#1C4532;color:#fff;padding:.7rem 1.25rem;font-weight:600;">
  Requisitions Data Seeder <span style="opacity:.7;font-weight:400;">- dev load tool</span>
</div>
<div style="max-width:620px;margin:1.5rem auto;background:#fff;border:1px solid #dfe6e1;border-radius:8px;padding:1.25rem;">
  <p style="color:#5f6b62;margin-top:0;">
    Pick the <code>RequestMaterial/Data/*.csv</code> files - <b>all three at once</b> if you
    like. Each one's table comes from its filename, and they are loaded in the right order
    whatever order you pick them in:
    <b>RQSREQHDRT &rarr; RQSREQDTLT &rarr; RQSCODEFLT</b>.
    The header file restarts the requisition numbering on its own when it loads.
  </p>
  <form method="post" enctype="multipart/form-data">
    <p><input type="file" name="csv[]" accept=".csv" multiple required></p>
    <p>
      <label style="color:#5f6b62;">Table (only used when one file is picked):
        <select name="table">
          <option value="AUTO" selected>auto-detect from filename</option>
          <option>RQSREQHDRT</option>
          <option>RQSREQDTLT</option>
          <option>RQSCODEFLT</option>
        </select>
      </label>
      &nbsp;&nbsp;
      <label style="color:#5f6b62;">
        <input type="checkbox" name="clear" value="1" checked> clear table first
      </label>
    </p>
    <p><button type="submit" style="background:#007bff;color:#fff;border:0;border-radius:50px;
        padding:.5rem 1.4rem;font-weight:700;cursor:pointer;">Load</button></p>
  </form>
  <p style="color:#5f6b62;font-size:.85rem;border-top:1px dashed #dfe6e1;padding-top:.75rem;">
    PHP upload limits on this instance: upload_max_filesize =
    <b><?php echo ini_get('upload_max_filesize'); ?></b>, post_max_size =
    <b><?php echo ini_get('post_max_size'); ?></b>. The detail CSV is ~5&nbsp;MB -
    if the limit shows smaller, IT needs to raise it (or use the CPYFRMIMPF
    route from the command line).
  </p>
</div>
</body></html>
