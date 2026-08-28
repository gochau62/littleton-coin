<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_diag.php         *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 08/28/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */
?>

<?php
    // every error prints, so a failure is visible instead of a blank page
    @ini_set('display_errors', '1');
    @ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<div id="errorMsg" style="display:none"></div>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// the trace prints as it goes, so a stop anywhere still shows its progress
echo '<pre style="font:12px Consolas,monospace; padding:1rem; background:#fff; ' .
     'border:1px solid #ddd; overflow:auto">';
echo "TRACE START\n";
@ob_flush(); @flush();


// helpers carry a ptd prefix so nothing in the framework collides
function ptdOut($label, $value = '') {
    echo htmlspecialchars($label) .
         ($value !== '' ? ': ' . htmlspecialchars($value) : '') . "\n";
    @ob_flush(); @flush();
}


function ptdRows($rows, $max = 8) {
    if ($rows === false || $rows === null) {
        ptdOut('  READ FAILED', strval($GLOBALS['prjErr'] ?? ''));
        return;
    }
    ptdOut('  rows returned', strval(count($rows)));
    $i = 0;
    foreach ($rows as $r) {
        if ($i++ >= $max) { ptdOut('  ...'); break; }
        $bits = array();
        foreach ($r as $k => $v) {
            $bits[] = trim(strval($k)) . '=[' . trim(strval($v)) . ']';
        }
        ptdOut('  ' . implode(' ', $bits));
    }
}


// run a statement, trying dot then slash qualification
function ptdSql($conn, $sql) {
    if (!$conn) { return false; }
    $stmt = @db2_exec($conn, $sql);
    if (!$stmt) {
        $alt = str_replace('QSYS2.', 'QSYS2/', $sql);
        $stmt = @db2_exec($conn, $alt);
    }
    if (!$stmt) { ptdOut('  SQL ERROR', @db2_stmt_errormsg()); return false; }
    $out = array();
    while ($row = db2_fetch_assoc($stmt)) { $out[] = $row; }
    return $out;
}

$proj = intval($_GET['proj'] ?? 260082);
ptdOut('project traced', strval($proj));
ptdOut('run as', strval($user));
ptdOut('script dir', __DIR__);
ptdOut('document root', strval($_SERVER['DOCUMENT_ROOT'] ?? ''));
ptdOut('');

// authority level 20, the developers group
$authorized = 'yes';
$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }
if (function_exists('chkAutUsr') && $conn) {
    $authorized = chkAutUsr($conn, $user, 'LCCONLINE', 20);
}
ptdOut('connection', $conn ? 'open' : 'NONE');
ptdOut('authorized', strval($authorized));
// dev and production see different libraries and folders
foreach (array('PRJ_PROC_LIB', 'PRJ_NOTES_FILE', 'PRJ_WEBNOTES_DIR',
               'PRJ_DATA_DIR') as $c) {
    if (defined($c)) { ptdOut($c, strval(constant($c))); }
}
ptdOut('');

if ($authorized != 'yes') {
    ptdOut('stopping - not authorized');
} else {

/* 0 - what is actually deployed beside this page */
ptdOut('0. FILES IN THIS DIRECTORY');
$found = array();
foreach (array('ProjectTracking_model.php', 'ProjectTracking_ctl.php',
               'ProjectTracking_ajax.php', 'ProjectTracking_dsp.php',
               'ProjectDevelopers_ctl.php', 'ProjectDevelopers_dsp.php') as $f) {
    $p = __DIR__ . '/' . $f;
    ptdOut('  ' . $f, file_exists($p)
           ? date('Y-m-d H:i', filemtime($p)) . '  ' . filesize($p) . ' bytes'
           : 'MISSING');
    if (file_exists($p)) { $found[] = $f; }
}
// anything ProjectTracking-ish anywhere near, in case it lives elsewhere
foreach (array(__DIR__, __DIR__ . '/ProjectTracking',
               rtrim(strval($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/LCCOnline',
               rtrim(strval($_SERVER['DOCUMENT_ROOT'] ?? ''), '/')) as $dir) {
    $hit = @glob($dir . '/ProjectTracking_model.php');
    if (is_array($hit) && count($hit) > 0) { ptdOut('  model also seen at', $hit[0]); }
}
ptdOut('');

// the model is optional here - every read below stands on its own
$modelPath = '';
foreach (array(__DIR__ . '/ProjectTracking_model.php',
               __DIR__ . '/ProjectTracking/ProjectTracking_model.php') as $cand) {
    if (file_exists($cand)) { $modelPath = $cand; break; }
}
if ($modelPath !== '') {
    require_once $modelPath;
    ptdOut('model loaded from', $modelPath);
} else {
    ptdOut('model not found', 'running the reads without it');
}
ptdOut('');


// the same call the model makes, so this page works without it
function ptdNotes($conn, $proj) {
    $stmt = @db2_prepare($conn, "CALL PHP0003S(?, ?)");
    if (!$stmt) { ptdOut('  prepare failed', @db2_stmt_errormsg()); return false; }
    $a = strval(intval($proj)); $b = 'PROJ_';
    @db2_bind_param($stmt, 1, 'a', DB2_PARAM_IN);
    @db2_bind_param($stmt, 2, 'b', DB2_PARAM_IN);
    if (!@db2_execute($stmt)) {
        ptdOut('  execute failed', @db2_stmt_errormsg()); return false;
    }
    $rows = array();
    while ($r = db2_fetch_assoc($stmt)) { $rows[] = $r; }
    return $rows;
}

/* 1 - the project screen's own procedure, straight up */
ptdOut('1. PHP0003S - THE PROJECT SCREEN\'S OWN READ');
$raw = ptdNotes($conn, $proj);
ptdRows($raw, 12);
ptdOut('');

/* 1b - the same rows straight from the file */
ptdOut('1b. WBNOTEIDXP DIRECT');
ptdRows(ptdSql($conn,
    "SELECT RTRIM(WNPREFIX) AS WNPREFIX, RTRIM(WNIDVAL) AS WNIDVAL, " .
    "RTRIM(WNUSER) AS WNUSER, WNDATE, WNTIME, RTRIM(WNTYPE) AS WNTYPE, " .
    "RTRIM(WNPATH) AS WNPATH FROM WBNOTEIDXP WHERE RTRIM(WNIDVAL) = '" .
    $proj . "' ORDER BY WNDATE, WNTIME"), 12);
ptdOut('');

/* 2 - which file holds the WebNotes columns */
ptdOut('2. WEBNOTES FILES IN THE CATALOG');
$cat = ptdSql($conn,
    "SELECT C.SYSTEM_TABLE_SCHEMA AS LIB, C.SYSTEM_TABLE_NAME AS FIL, " .
    "T.TABLE_TYPE AS TYP, COUNT(DISTINCT C.SYSTEM_COLUMN_NAME) AS COLS " .
    "FROM QSYS2.SYSCOLUMNS C JOIN QSYS2.SYSTABLES T " .
    "ON T.SYSTEM_TABLE_SCHEMA = C.SYSTEM_TABLE_SCHEMA " .
    "AND T.SYSTEM_TABLE_NAME = C.SYSTEM_TABLE_NAME " .
    "WHERE C.SYSTEM_COLUMN_NAME IN ('WNPREFIX','WNIDVAL','WNUSER','WNDATE'," .
    "'WNTIME','WNTYPE','WNPATH') " .
    "GROUP BY C.SYSTEM_TABLE_SCHEMA, C.SYSTEM_TABLE_NAME, T.TABLE_TYPE " .
    "HAVING COUNT(DISTINCT C.SYSTEM_COLUMN_NAME) >= 5");
ptdRows($cat, 20);
ptdOut('');

/* 3 - what those files hold for this project, and overall */
ptdOut('3. WHAT THOSE FILES HOLD');
if (is_array($cat) && count($cat) > 0) {
    foreach ($cat as $c) {
        $lib = trim(strval($c['LIB'])); $fil = trim(strval($c['FIL']));
        ptdOut('  -- ' . $lib . '/' . $fil . ' rows for project ' . $proj);
        ptdRows(ptdSql($conn,
            "SELECT RTRIM(WNPREFIX) AS WNPREFIX, RTRIM(WNIDVAL) AS WNIDVAL, " .
            "RTRIM(WNUSER) AS WNUSER, WNDATE, WNTIME, RTRIM(WNTYPE) AS WNTYPE, " .
            "RTRIM(WNPATH) AS WNPATH FROM " . $lib . "." . $fil .
            " WHERE RTRIM(WNIDVAL) = '" . $proj . "'"), 12);
        ptdOut('  -- ' . $lib . '/' . $fil . ' prefixes and date range');
        ptdRows(ptdSql($conn,
            "SELECT RTRIM(WNPREFIX) AS PREFIX, COUNT(*) AS CNT, " .
            "MIN(WNDATE) AS OLDEST, MAX(WNDATE) AS NEWEST FROM " .
            $lib . "." . $fil . " GROUP BY RTRIM(WNPREFIX)"), 12);
    }
} else {
    ptdOut('  no candidate file found');
}
ptdOut('');

/* 4 - the report's own read */
ptdOut('4. THE REPORT READ - prjNotes()');
$notes = function_exists('prjNotes')
         ? prjNotes($conn, 20000101, 29991231, array($proj)) : false;
if ($notes === false) {
    ptdOut('  model not loaded, mapping step 1 instead');
    $notes = array();
    foreach (is_array($raw) ? $raw : array() as $r) {
        $notes[] = array(
            'NTPROJ' => trim(strval($r['WNIDVAL'] ?? $proj)),
            'NTUSER' => trim(strval($r['WNUSER'] ?? '')),
            'NTDATE' => intval($r['WNDATE'] ?? 0),
            'NTTIME' => intval($r['WNTIME'] ?? 0),
            'NTTYPE' => trim(strval($r['WNTYPE'] ?? '')),
            'NTPATH' => trim(strval($r['WNPATH'] ?? '')));
    }
}
ptdRows($notes, 12);
ptdOut('');

/* 5 - can the comment text be read off the IFS */
ptdOut('5. READING THE COMMENT FILES');
if (!is_array($notes) || count($notes) === 0) {
    ptdOut('  no rows from step 4 to try');
} else {
    foreach (array_slice($notes, 0, 5) as $n) {
        $path = trim(strval($n['NTPATH'] ?? ''));
        $time = strval(intval($n['NTTIME'] ?? 0));
        while (strlen($time) < 6) { $time = '0' . $time; }
        $stem = 'PROJ_' . trim(strval($n['NTPROJ'] ?? '')) .
                strval(intval($n['NTDATE'] ?? 0));
        ptdOut('  type ' . trim(strval($n['NTTYPE'] ?? '')) . ' name ' . $stem . $time);
        $dirs = array($path, __DIR__ . '/' . trim($path, '/'),
                      rtrim(strval($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') .
                      '/' . trim($path, '/'));
        foreach ($dirs as $dir) {
            if ($dir === '' || $dir === '/') { continue; }
            $f = rtrim($dir, '/') . '/' . $stem . $time;
            ptdOut('    try ' . $f, is_readable($f) ? 'READABLE' : 'no');
            $hit = @glob(rtrim($dir, '/') . '/' . $stem . '*');
            if (is_array($hit) && count($hit) > 0) {
                ptdOut('    prefix match', $hit[0]);
            }
        }
        if (function_exists('prjNoteText')) {
            $txt = prjNoteText($n);
            ptdOut('    prjNoteText length', strval(strlen($txt)));
            if ($txt !== '') { ptdOut('    first 200', substr($txt, 0, 200)); }
        }
    }
}
ptdOut('');

/* 6 - the real digest for a period: ?from=20260801&to=20260831 */
$dFrom = intval($_GET['from'] ?? 0);
$dTo   = intval($_GET['to'] ?? 0);
if (($dFrom <= 0 || $dTo <= 0) && function_exists('prjWeekRange')) {
    list($dFrom, $dTo) = prjWeekRange();
}
ptdOut('6. THE DIGEST ITSELF - ' . $dFrom . ' to ' . $dTo);
ptdOut('   (add ?from=20260801&to=20260831 to trace another period)');

$dTime = (function_exists('prjTime') && $dFrom > 0)
         ? prjTime($conn, $dFrom, $dTo) : false;
if ($dTime === false) {
    ptdOut('  time read FAILED', strval($GLOBALS['prjErr'] ?? ''));
} else {
    $wk = array();
    foreach ($dTime as $t) {
        $x = intval($t['TMPROJ']);
        if ($x > 0) { $wk[$x] = true; }
    }
    ptdOut('  projects with time logged', implode(',', array_keys($wk)));
    ptdOut('  traced project in that list',
           isset($wk[$proj]) ? 'YES' : 'NO - no comment of its can be read');

    $all = prjNotes($conn, $dFrom, $dTo, array_keys($wk));
    ptdOut('  comment rows for those projects', strval(count($all)));

    $inPeriod = array();
    foreach ($all as $n) {
        $d = intval($n['NTDATE'] ?? 0);
        if ($d >= $dFrom && $d <= $dTo) { $inPeriod[] = $n; }
    }
    ptdOut('  of those, dated inside the period', strval(count($inPeriod)));

    $types = array();
    foreach ($inPeriod as $n) {
        $ty = trim(strval($n['NTTYPE'] ?? '')); if ($ty === '') { $ty = '(blank)'; }
        $types[$ty] = ($types[$ty] ?? 0) + 1;
    }
    foreach ($types as $ty => $cnt) { ptdOut('    type ' . $ty, strval($cnt)); }
    ptdOut('  NOTE: only ComntIT reaches the developer sections');

    $withText = 0;
    foreach ($inPeriod as $n) {
        if (prjNoteText($n) !== '') { $withText += 1; }
    }
    ptdOut('  of those, text readable', strval($withText));
}
ptdOut('');

/* 7 - the project screen's own model, last because it may redeclare */
ptdOut('7. THE PROJECT SCREEN READ - add ?legacy=1 to run this');
if (($_GET['legacy'] ?? '') !== '1') {
    ptdOut('  skipped');
} elseif (!file_exists('WebNotes/webNotesModel.php')) {
    ptdOut('  WebNotes/webNotesModel.php not found beside this script');
} else {
    require_once 'WebNotes/webNotesModel.php';
    if (!function_exists('getRecordsWebNotes')) {
        ptdOut('  getRecordsWebNotes() not defined there');
    } else {
        $legacy = getRecordsWebNotes(strval($proj), 'PROJ_', $conn);
        ptdRows(is_array($legacy) ? $legacy : array(), 12);
    }
}

}

ptdOut('');
ptdOut('END OF TRACE');
echo '</pre>';

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
