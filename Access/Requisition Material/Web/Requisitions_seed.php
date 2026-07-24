<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_seed.php            *  -->
<!--  *                                                 *  -->
<!--  * Narrative - One-time data seeder (DEV TOOL, not *  -->
<!--  *   part of the runtime app). Pick one of the     *  -->
<!--  *   Data/*.csv files from your PC and it loads    *  -->
<!--  *   the matching table: RQSREQHDRT, RQSREQDTLT    *  -->
<!--  *   or RQSCODEFLT. Batched-commit inserts, auto   *  -->
<!--  *   identity RESTART after a header load, and     *  -->
<!--  *   validation counts at the end. The CSVs are    *  -->
<!--  *   already in Db2 format - no transforms here.   *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/22/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
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

$authorized = "yes";
if ($conn && function_exists('chkAutUsr')) {
    $authorized = chkAutUsr($conn, $user, "LCCONLINE", 50);
}
if (!$conn || $authorized != "yes") {
    exit('Not authorized (sign in to LCC Online first).');
}

// table name -> column list; CSV column order matches exactly (no header row)
$TABLES = array(
    'RQSREQHDRT' => array('RHREQ#','RHNAME','RHRQDT','RHRQTM','RHARCD','RHARTY',
                          'RHRUSH','RHAUTF','RHAUTB','RHBDGE','RHCMNT'),
    'RQSREQDTLT' => array('RDREQ#','RDLIN#','RDITEM','RDLOC','RDCNDT','RDDESC',
                          'RDQTY','RDCOST','RDRETL','RDACST','RDBDGE','RDSKUT',
                          'RDRTNF','RDRTDT'),
    'RQSCODEFLT' => array('CDTYPE','CDCODE','CDDESC','CDACTV'),
);

/* ----------------------------- the load ------------------------------ */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['csv'])) {
    header('Content-Type: text/plain; charset=utf-8');

    $up = $_FILES['csv'];
    if ($up['error'] !== UPLOAD_ERR_OK) {
        exit("Upload failed (code {$up['error']}). If the file is the detail CSV (~5 MB),\n" .
             "check php.ini upload_max_filesize / post_max_size - currently " .
             ini_get('upload_max_filesize') . " / " . ini_get('post_max_size') . ".\n");
    }

    // which table: explicit choice wins, otherwise detect from the filename
    $table = strtoupper(trim($_POST['table'] ?? ''));
    if ($table === '' || $table === 'AUTO') {
        foreach ($TABLES as $t => $c) {
            if (stripos($up['name'], $t) !== false) { $table = $t; break; }
        }
    }
    if (!isset($TABLES[$table])) {
        exit("Could not tell which table '{$up['name']}' belongs to.\n" .
             "Name the file after its table or pick the table in the form.\n");
    }
    $cols = $TABLES[$table];
    $ncol = count($cols);

    echo "SEED LOAD  {$up['name']}  ->  {$table}  (" . number_format($up['size']) . " bytes)\n";
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

    @db2_autocommit($conn, DB2_AUTOCOMMIT_OFF);   // batch commits: much faster on journaled tables

    $fh = fopen($up['tmp_name'], 'r');
    $row = 0; $ok = 0; $bad = 0; $t0 = microtime(true);
    while (($f = fgetcsv($fh)) !== false) {
        if ($f === array(null)) { continue; }     // blank line
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
    echo "\nGo back and load the next file, or open Requisitions_ctl.php to see the grid.\n";
    exit;
}

/* ----------------------------- the form ------------------------------ */
?>
<!DOCTYPE html>
<html><head><title>Requisitions Seeder</title></head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background:#f8f8f8;margin:0;">
<div style="background:#1C4532;color:#fff;padding:.7rem 1.25rem;font-weight:600;">
  Requisitions Data Seeder <span style="opacity:.7;font-weight:400;">- dev load tool</span>
</div>
<div style="max-width:620px;margin:1.5rem auto;background:#fff;border:1px solid #dfe6e1;border-radius:8px;padding:1.25rem;">
  <p style="color:#5f6b62;margin-top:0;">
    Pick one of the <code>Data/*.csv</code> files from the repo. The target table is
    detected from the filename (or pick it yourself). Load order:
    <b>RQSREQHDRT &rarr; RQSREQDTLT &rarr; RQSCODEFLT</b>.
  </p>
  <form method="post" enctype="multipart/form-data">
    <p><input type="file" name="csv" accept=".csv" required></p>
    <p>
      <label style="color:#5f6b62;">Table:
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
    route in Data/README.md).
  </p>
</div>
</body></html>
