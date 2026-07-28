<?php
/*    ***************************************************  -->
<!--  * Program Name - zzprobe.php                       *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/27/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

// dev only probe, not an RFP object, delete it once the procedures are confirmed built
// drop it next to the StoryCard PHP and open it in a browser
// it answers three questions the app itself only answers by failing:
//   1. are all eight STYCRD procedures actually in the library
//   2. does any of them have a second signature left behind by CREATE OR REPLACE
//   3. can the web profile really call them, or is it short an authority
//
// ?lib=LSCDEVLIBP picks the library, dev is the default
// ?call=0 skips the live calls and only reads the catalog

header('Content-Type: text/plain; charset=utf-8');

foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}
if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

$lib      = strtoupper(preg_replace('/[^A-Za-z0-9_#$@]/', '', $_GET['lib'] ?? 'LSCDEVLIBP'));
$doCalls  = (($_GET['call'] ?? '1') !== '0');

// what should be there, and how many parameters each one takes. The parameter
// count is the whole point: CREATE OR REPLACE does not replace across different
// counts, so changing a signature leaves the old one behind and the web profile
// may keep resolving to it. That is the failure that looks like "my change did
// nothing"
$EXPECTED = array(
    'STYCRD001S' => array('parms' => 2, 'what' => 'SKU lookup - CARDS / ITEMS / ONE'),
    'STYCRD002S' => array('parms' => 1, 'what' => 'read one card, every body line'),
    'STYCRD003S' => array('parms' => 1, 'what' => 'read the shared footer'),
    'STYCRD004S' => array('parms' => 4, 'what' => 'write one body line'),
    'STYCRD005S' => array('parms' => 3, 'what' => 'blank the tail of both sides'),
    'STYCRD006S' => array('parms' => 3, 'what' => 'write one footer line'),
    'STYCRD007S' => array('parms' => 2, 'what' => 'drop the tail of the footer'),
    'STYCRD008S' => array('parms' => 1, 'what' => 'delete a whole card'),
);

// only the read-only ones get called for real. Nothing here writes
$SAFE_CALLS = array(
    'STYCRD001S' => array('CALL ' . '%LIB%.STYCRD001S(?, ?)', array('ONE', 'ZZPROBE999')),
    'STYCRD002S' => array('CALL ' . '%LIB%.STYCRD002S(?)',    array('ZZPROBE999')),
    'STYCRD003S' => array('CALL ' . '%LIB%.STYCRD003S(?)',    array(1)),
);

function line($c = '-') { echo str_repeat($c, 74) . "\n"; }

echo "STORY CARD PROCEDURE PROBE\n";
line('=');
echo "library    : $lib\n";
echo "user       : " . ($user !== '' ? $user : '(no session - sign in to LCC Online first)') . "\n";
echo "server time: " . date('Y-m-d H:i:s') . "\n\n";

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }
if (!$conn) {
    echo "NO DATABASE CONNECTION\n";
    echo "getDB2PConn returned nothing. Sign in to LCC Online in this browser first,\n";
    echo "then reload this page.\n";
    if (function_exists('db2_conn_errormsg')) { echo "\n" . db2_conn_errormsg() . "\n"; }
    exit;
}
echo "connection : ok\n\n";


// ---------------------------------------------------------------- catalog

$sql = "SELECT ROUTINE_NAME, SPECIFIC_NAME, ROUTINE_TYPE,
               IN_PARMS, OUT_PARMS, INOUT_PARMS,
               ROUTINE_CREATED, LAST_ALTERED
          FROM QSYS2.SYSROUTINES
         WHERE ROUTINE_SCHEMA = ?
           AND ROUTINE_NAME LIKE 'STYCRD%'
         ORDER BY ROUTINE_NAME, SPECIFIC_NAME";

$found = array();      // name => list of signatures
$stmt  = db2_prepare($conn, $sql);
if (!$stmt) {
    echo "could not read QSYS2.SYSROUTINES: " . db2_stmt_errormsg() . "\n";
} else {
    $p1 = $lib;
    db2_bind_param($stmt, 1, 'p1', DB2_PARAM_IN);
    if (!db2_execute($stmt)) {
        echo "could not read QSYS2.SYSROUTINES: " . db2_stmt_errormsg() . "\n";
    } else {
        while ($r = db2_fetch_assoc($stmt)) {
            $n = trim($r['ROUTINE_NAME']);
            $found[$n][] = array(
                'specific' => trim($r['SPECIFIC_NAME']),
                'parms'    => intval($r['IN_PARMS']) + intval($r['OUT_PARMS']) + intval($r['INOUT_PARMS']),
                'created'  => trim((string)$r['ROUTINE_CREATED']),
                'altered'  => trim((string)$r['LAST_ALTERED']),
                'type'     => trim($r['ROUTINE_TYPE']),
            );
        }
    }
}

$sigCount = 0;
foreach ($found as $sigs) { $sigCount += count($sigs); }

if (!$found) {
    echo "NOTHING NAMED STYCRD% IS IN $lib.\n";
    line();
    echo "Either the RUNSQLSTM has not been run yet, or the members went into a\n";
    echo "different library. Try ?lib=OTHERLIB.\n\n";
} else {
    echo "catalog    : $sigCount STYCRD routine(s) in $lib\n\n";
}


// ---------------------------------------------------------------- checklist

echo "CHECKLIST\n";
line();
printf("%-12s %-6s %-6s %-22s %s\n", 'PROCEDURE', 'THERE', 'PARMS', 'LAST ALTERED', 'WHAT IT IS');
line();

$missing = 0; $dupes = 0; $wrongParms = 0;

foreach ($EXPECTED as $name => $want) {
    $sigs = $found[$name] ?? array();

    if (!$sigs) {
        $missing++;
        printf("%-12s %-6s %-6s %-22s %s\n", $name, 'NO', '-', '-', $want['what']);
        continue;
    }

    // the first signature that matches the parameter count we expect
    $match = null;
    foreach ($sigs as $s) { if ($s['parms'] === $want['parms']) { $match = $s; break; } }

    if ($match === null) {
        $wrongParms++;
        printf("%-12s %-6s %-6s %-22s %s\n", $name, 'yes',
               $sigs[0]['parms'] . '!' . $want['parms'], substr($sigs[0]['altered'], 0, 19), $want['what']);
    } else {
        printf("%-12s %-6s %-6s %-22s %s\n", $name, 'yes', $match['parms'],
               substr($match['altered'] !== '' ? $match['altered'] : $match['created'], 0, 19),
               $want['what']);
    }

    if (count($sigs) > 1) {
        $dupes++;
        foreach ($sigs as $s) {
            printf("%-12s   %s %d parameters, specific name %s\n", '', '>', $s['parms'], $s['specific']);
        }
    }
}

// anything in the library that is not on the list
foreach ($found as $name => $sigs) {
    if (!isset($EXPECTED[$name])) {
        printf("%-12s %-6s %-6s %-22s %s\n", $name, 'yes', $sigs[0]['parms'], '-',
               '** not on the expected list **');
    }
}
echo "\n";


// ---------------------------------------------------------------- live calls

$callFail = 0;
$noAuth   = false;

if ($doCalls) {
    echo "CAN THE WEB PROFILE ACTUALLY CALL THEM\n";
    line();
    echo "read only calls with arguments that match nothing, so nothing is written\n\n";

    foreach ($SAFE_CALLS as $name => $spec) {
        if (!isset($found[$name])) { printf("  %-12s skipped, not in %s\n", $name, $lib); continue; }

        list($tmpl, $args) = $spec;
        $call = str_replace('%LIB%', $lib, $tmpl);

        $st = db2_prepare($conn, $call);
        if (!$st) {
            $callFail++;
            printf("  %-12s PREPARE FAILED  %s\n", $name, trim(db2_stmt_errormsg()));
            continue;
        }
        foreach ($args as $i => $a) {
            $GLOBALS['probeArg' . $i] = $a;
            db2_bind_param($st, $i + 1, 'probeArg' . $i, DB2_PARAM_IN);
        }
        if (!db2_execute($st)) {
            $callFail++;
            $msg = trim(db2_stmt_errormsg());
            printf("  %-12s CALL FAILED     %s\n", $name, $msg);
            if (strpos($msg, '-551') !== false || stripos($msg, 'not authorized') !== false) {
                $noAuth = true;
            }
            continue;
        }
        $rows = 0;
        while (db2_fetch_assoc($st)) { $rows++; }
        printf("  %-12s ok, returned %d row%s\n", $name, $rows, $rows === 1 ? '' : 's');
    }
    echo "\n";
} else {
    echo "live calls skipped (call=0)\n\n";
}


// ---------------------------------------------------------------- the files

echo "THE FILES THE PROCEDURES READ AND WRITE\n";
line();
foreach (array('IVSCBODYP' => 'story card body text',
               'IVSCFOOTP' => 'shared footer text',
               'ITMMSTP'   => 'item master') as $file => $what) {
    $st = db2_prepare($conn, "SELECT COUNT(*) AS N FROM LSCPRDLIB.$file");
    if ($st && db2_execute($st) && ($r = db2_fetch_assoc($st))) {
        printf("  LSCPRDLIB/%-10s %12s rows   %s\n", $file, number_format($r['N']), $what);
    } else {
        printf("  LSCPRDLIB/%-10s unreadable   %s\n", $file,
               $st ? trim(db2_stmt_errormsg()) : 'prepare failed');
    }
}
echo "\n";


// ---------------------------------------------------------------- verdict

line('=');
if ($missing === 0 && $dupes === 0 && $wrongParms === 0 && $callFail === 0) {
    echo "ALL EIGHT ARE BUILT IN $lib, NOTHING IS DUPLICATED, AND THE WEB PROFILE\n";
    echo "CAN CALL THEM. The screen has everything it needs.\n";
} else {
    if ($callFail > 0) {
        if ($noAuth) {
            echo "$callFail call(s) came back not authorized. The procedures are there but the\n";
            echo "  web profile cannot run them, which is a GRTOBJAUT and not a code problem:\n";
            echo "  GRTOBJAUT OBJ($lib) OBJTYPE(*LIB) USER(webprofile) AUT(*USE)\n";
            echo "  GRTOBJAUT OBJ($lib/STYCRD*) OBJTYPE(*PGM) USER(webprofile) AUT(*USE)\n";
        } else {
            echo "$callFail call(s) failed for a reason other than authority. Read the message\n";
            echo "  above; the procedure is built but it is not running clean.\n";
        }
    }
    if ($missing > 0) {
        echo "$missing procedure(s) are not built. RUNSQLSTM the missing .PROC members:\n";
        echo "  RUNSQLSTM SRCFILE($lib/QSQLSRC) SRCMBR(STYCRDnnnS) COMMIT(*NONE)\n";
    }
    if ($wrongParms > 0) {
        echo "$wrongParms procedure(s) are built with a parameter count the app does not\n";
        echo "  expect. Rebuild them from this repo's .PROC members.\n";
    }
    if ($dupes > 0) {
        echo "$dupes procedure(s) have more than one signature. CREATE OR REPLACE does not\n";
        echo "  replace across different parameter counts, so the old one is still there and\n";
        echo "  the app may keep resolving to it. Drop the wrong one by its parameter types:\n";
        echo "  DROP PROCEDURE $lib.STYCRDnnnS(type, type)\n";
    }
}
line('=');
?>
