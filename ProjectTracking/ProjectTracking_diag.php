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
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<div id="errorMsg" style="display:none"></div>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// authority level 20, the developers group
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);
}

if ($authorized != "yes") {
    echo 'Not authorized.';
} else {

require_once __DIR__ . '/ProjectTracking_model.php';

// the project to trace; ?proj=260082 by default
$proj = intval($_GET['proj'] ?? 260082);
$conn = function_exists('getDB2PConn') ? getDB2PConn($user, $password) : null;

// print one line of the report
function d($label, $value = '') {
    echo htmlspecialchars($label) .
         ($value !== '' ? ': ' . htmlspecialchars($value) : '') . "\n";
}


// print the first rows of a result set with every column
function dRows($rows, $max = 8) {
    if ($rows === false) { d('  READ FAILED', $GLOBALS['prjErr'] ?? ''); return; }
    d('  rows returned', strval(count($rows)));
    $i = 0;
    foreach ($rows as $r) {
        if ($i++ >= $max) { d('  ...'); break; }
        $bits = array();
        foreach ($r as $k => $v) {
            $bits[] = trim(strval($k)) . '=[' . trim(strval($v)) . ']';
        }
        d('  ' . implode(' ', $bits));
    }
}


// run one SQL statement and hand back its rows
function dSql($conn, $sql) {
    $stmt = @db2_exec($conn, $sql);
    if (!$stmt) { return false; }
    $out = array();
    while ($row = db2_fetch_assoc($stmt)) { $out[] = $row; }
    return $out;
}

echo '<pre style="font:12px Consolas,monospace; padding:1rem; background:#fff; ' .
     'border:1px solid #ddd; overflow:auto">';

d('PROJECT TRACKING - COMMENT FEED TRACE');
d('project traced', strval($proj));
d('run as', $user);
d('');

/* 1 - the legacy WebNotes model, the exact call the project screen makes */
d('1. THE PROJECT SCREEN\'S OWN READ (WebNotes/webNotesModel.php)');
if (!file_exists('WebNotes/webNotesModel.php')) {
    d('  webNotesModel.php not found beside this script');
} else {
    require_once 'WebNotes/webNotesModel.php';
    if (!function_exists('getRecordsWebNotes')) {
        d('  getRecordsWebNotes() not defined by that file');
    } else {
        $legacy = getRecordsWebNotes(strval($proj), 'PROJ_', $conn);
        if (!is_array($legacy)) { $legacy = array(); }
        dRows($legacy);
    }
}
d('');

/* 2 - which file the catalog search finds */
d('2. THE WEBNOTES FILE THE PROCEDURE LOCATES');
$cat = dSql($conn,
    "SELECT C.SYSTEM_TABLE_SCHEMA AS LIB, C.SYSTEM_TABLE_NAME AS FILE, " .
    "T.TABLE_TYPE AS TYP, COUNT(DISTINCT C.SYSTEM_COLUMN_NAME) AS COLS " .
    "FROM QSYS2.SYSCOLUMNS C JOIN QSYS2.SYSTABLES T " .
    "ON T.SYSTEM_TABLE_SCHEMA = C.SYSTEM_TABLE_SCHEMA " .
    "AND T.SYSTEM_TABLE_NAME = C.SYSTEM_TABLE_NAME " .
    "WHERE C.SYSTEM_COLUMN_NAME IN ('WNPREFIX','WNIDVAL','WNUSER','WNDATE'," .
    "'WNTIME','WNTYPE','WNPATH') " .
    "GROUP BY C.SYSTEM_TABLE_SCHEMA, C.SYSTEM_TABLE_NAME, T.TABLE_TYPE " .
    "HAVING COUNT(DISTINCT C.SYSTEM_COLUMN_NAME) >= 5");
dRows($cat, 20);
d('');

/* 3 - what that file holds for this project, straight from SQL */
d('3. THAT FILE\'S ROWS FOR THIS PROJECT');
if (is_array($cat) && count($cat) > 0) {
    foreach ($cat as $c) {
        $lib = trim($c['LIB']); $fil = trim($c['FILE']);
        d('  -- ' . $lib . '/' . $fil);
        dRows(dSql($conn,
            "SELECT RTRIM(WNPREFIX) AS WNPREFIX, RTRIM(WNIDVAL) AS WNIDVAL, " .
            "RTRIM(WNUSER) AS WNUSER, WNDATE, WNTIME, RTRIM(WNTYPE) AS WNTYPE, " .
            "RTRIM(WNPATH) AS WNPATH FROM " . $lib . "." . $fil .
            " WHERE RTRIM(WNIDVAL) = '" . $proj . "'"), 12);
        // and what prefixes/dates that file uses at all
        dRows(dSql($conn,
            "SELECT RTRIM(WNPREFIX) AS PREFIX, COUNT(*) AS ROWS, " .
            "MIN(WNDATE) AS OLDEST, MAX(WNDATE) AS NEWEST FROM " .
            $lib . "." . $fil . " GROUP BY RTRIM(WNPREFIX)"), 12);
    }
} else {
    d('  no candidate file, so nothing to read');
}
d('');

/* 4 - the procedure's own NOTES read, the one the report uses */
d('4. THE REPORT\'S READ - PRJTRK001S NOTES');
$notes = prjNotes($conn, array($proj));
dRows($notes, 12);
d('');

/* 5 - can the comment text actually be read off the IFS */
d('5. READING THE COMMENT FILES');
d('  script dir', __DIR__);
d('  document root', strval($_SERVER['DOCUMENT_ROOT'] ?? ''));
$src = (is_array($notes) && count($notes) > 0) ? $notes : array();
if (empty($src) && isset($legacy) && is_array($legacy) && count($legacy) > 0) {
    // fall back to the legacy row shape so the path test still runs
    foreach ($legacy as $l) {
        $src[] = array('NTPROJ' => $l['WNIDVAL'] ?? '', 'NTDATE' => $l['WNDATE'] ?? 0,
                       'NTTIME' => $l['WNTIME'] ?? 0, 'NTPATH' => $l['WNPATH'] ?? '',
                       'NTTYPE' => $l['WNTYPE'] ?? '');
    }
    d('  (using the rows from step 1)');
}
if (empty($src)) {
    d('  no comment rows to try');
} else {
    foreach (array_slice($src, 0, 5) as $n) {
        $path = trim(strval($n['NTPATH'] ?? ''));
        $time = strval(intval($n['NTTIME'] ?? 0));
        while (strlen($time) < 6) { $time = '0' . $time; }
        $stem = 'PROJ_' . trim(strval($n['NTPROJ'] ?? '')) .
                strval(intval($n['NTDATE'] ?? 0));
        d('  type ' . trim(strval($n['NTTYPE'] ?? '')) . ' file ' . $stem . $time);
        foreach (array($path, __DIR__ . '/' . trim($path, '/'),
                       rtrim(strval($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/' .
                       trim($path, '/')) as $dir) {
            if ($dir === '' || $dir === '/') { continue; }
            $f = rtrim($dir, '/') . '/' . $stem . $time;
            d('    try ' . $f, is_readable($f) ? 'READABLE' : 'no');
            $hit = @glob(rtrim($dir, '/') . '/' . $stem . '*');
            if (is_array($hit) && count($hit) > 0) {
                d('    prefix match found', $hit[0]);
            }
        }
        $txt = prjNoteText($n);
        d('    prjNoteText length', strval(strlen($txt)));
        if ($txt !== '') { d('    first 200', substr($txt, 0, 200)); }
    }
}
d('');
d('END OF TRACE');
echo '</pre>';

}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
