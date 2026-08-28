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
ptdOut('');

if ($authorized != 'yes') {
    ptdOut('stopping - not authorized');
} else {

require_once __DIR__ . '/ProjectTracking_model.php';
ptdOut('model loaded', 'yes');
ptdOut('');

/* 1 - the project screen's own procedure, straight up */
ptdOut('1. PHP0003S - THE PROJECT SCREEN\'S OWN READ');
ptdRows(prjFetchAll($conn, "CALL PHP0003S(?, ?)",
                    array(strval($proj), 'PROJ_')), 12);
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
$notes = prjNotes($conn, array($proj));
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
        $txt = prjNoteText($n);
        ptdOut('    text length', strval(strlen($txt)));
        if ($txt !== '') { ptdOut('    first 200', substr($txt, 0, 200)); }
    }
}
ptdOut('');

/* 5 - the project screen's own model, last because it may redeclare */
ptdOut('5. THE PROJECT SCREEN READ - add ?legacy=1 to run this');
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
