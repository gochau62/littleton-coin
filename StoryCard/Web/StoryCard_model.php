<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCard_model.php               *  -->
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
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

$GLOBALS['stcErr'] = '';

// the printed card, taken from the Access save guards and the message text that goes with them, which agree: side 1 holds 11 lines and side 2 holds 9, numbered 1 to 11 and 13 to 21, at 50 characters a line. Line 12 and line 22 are read by the old screen but it could never write them
define('STC_S1_FIRST', 1);
define('STC_S1_LAST', 11);
define('STC_S2_FIRST', 13);
define('STC_S2_LAST', 21);
define('STC_LINE_LEN', 50);
define('STC_SRK_LEN', 15);
define('STC_SKU_LEN', 10);

// the footer key. The Access FootMaintenance combo was bound to the FooterSelect query and read with "scfsky=" & whatever it held, so the key is a real choice and travels with every footer call. BodyMaintenance's read-only strip always asked for key 1, and still does
define('STC_FOOT_KEY', 1);

// activity log path in the LCCOnline_logs
define('STC_ACT_LOG', __DIR__ . '/LCCOnline_logs/storycard_activity.log');

// append one activity line
function stcActLog($user, $action, $detail = '') {
    $line = date('Y-m-d H:i:s') . ' ' .
            ($user !== '' ? $user : 'unknown') . ' ' .
            ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' .
            $action . ($detail !== '' ? ' ' . $detail : '');
    if (@file_put_contents(STC_ACT_LOG, $line . PHP_EOL, FILE_APPEND) === false) {
        error_log('storycard_activity.log write failed (' .
                  (error_get_last()['message'] ?? 'unknown reason') .
                  ') - activity: ' . $line);
    }
}

// record the real Db2 error for the caller and the log, then return false so callers can bail
function stcFail($where) {
    $GLOBALS['stcErr'] = $where . ': ' . db2_stmt_error() . ' ' . db2_stmt_errormsg();
    error_log('StoryCard ' . $GLOBALS['stcErr']);
    return false;
}

// shared runner for every proc that returns a result set: prepare, bind each parameter in order, execute, and collect every row as an associative array
function stcFetchAll($conn, $sql, $params = array()) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return stcFail("prepare $sql"); }

    foreach ($params as $i => $p) {
        $GLOBALS['stcP' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'stcP' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return stcFail("execute $sql"); }

    $result = array();
    while ($row = db2_fetch_assoc($stmt)) {
        $result[] = $row;
    }
    return $result;
}

// shared runner for the procs that only write: bind in order and execute
function stcExec($conn, $sql, $params, $where) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return stcFail("prepare $where"); }

    foreach ($params as $i => $p) {
        $GLOBALS['stcW' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'stcW' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return stcFail("execute $where"); }
    return true;
}

// a SKU is compared against a CHAR(10) key, so it is upper cased and trimmed here and nowhere else
function stcCleanSku($sku) {
    return strtoupper(substr(trim((string)$sku), 0, STC_SKU_LEN));
}

// PROGRAM NAME STYCRD001S type CARDS: the SKU list, which is exactly the row source the Access Combo1 carried - only SKUs that already have a story card, joined to the item master. A SKU with no card is not on this list; the way to start one is to type the number, the same as the old combo
function stcSkuSearch($conn, $key = '') {
    return stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                       array('CARDS', stcCleanSku($key)));
}

// PROGRAM NAME STYCRD001S type ONE: the old CheckSku and the item description read in a single call. No row back means the SKU is not on the item master
function stcGetSku($conn, $sku) {
    $rows = stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                        array('ONE', stcCleanSku($sku)));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}

// PROGRAM NAME STYCRD001S type CARD: the body lines held for one SKU, in line order
function stcGetCard($conn, $sku) {
    return stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                       array('CARD', stcCleanSku($sku)));
}

// PROGRAM NAME STYCRD001S type KEYS: the footer keys to choose from, which is the Access FooterSelect query the FootMaintenance combo was bound to
function stcFooterKeys($conn) {
    return stcFetchAll($conn, "CALL STYCRD001S(?, ?)", array('KEYS', ''));
}

// PROGRAM NAME STYCRD001S type FOOT: the footer lines for one key
function stcGetFooter($conn, $sky = STC_FOOT_KEY) {
    return stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                       array('FOOT', (string)intval($sky)));
}

// PROGRAM NAME STYCRD002S type CARD: write one body line, updating it when it is there and inserting it when it is not, which is what CheckLineMode decided per line in the Access save
function stcSaveBodyLine($conn, $sku, $lineNo, $text, $searchKey) {
    return stcExec($conn, "CALL STYCRD002S(?, ?, ?, ?, ?)",
                   array('CARD',
                         stcCleanSku($sku),
                         intval($lineNo),
                         substr((string)$text, 0, STC_LINE_LEN),
                         substr((string)$searchKey, 0, STC_SRK_LEN)),
                   'STYCRD002S CARD');
}

// PROGRAM NAME STYCRD002S type FOOTI or FOOTU: write one footer line. The Access footer screen picked insert or update once, up front, from whether the footer was empty, and stayed in that mode for every line - update mode never inserted
function stcSaveFootLine($conn, $sky, $lineNo, $text, $mode) {
    $type = ($mode === 'i') ? 'FOOTI' : 'FOOTU';
    return stcExec($conn, "CALL STYCRD002S(?, ?, ?, ?, ?)",
                   array($type, (string)intval($sky), intval($lineNo),
                         substr((string)$text, 0, STC_LINE_LEN), ''),
                   'STYCRD002S ' . $type);
}

// PROGRAM NAME STYCRD003S: blank the body lines from $from up to line 21. The Access UpdateBlank loop ran only in update mode, only from wherever side 2 finished, and only as far as 21, so side 1 is never tidied
function stcTrimCard($conn, $sku, $from) {
    return stcExec($conn, "CALL STYCRD003S(?, ?)",
                   array(stcCleanSku($sku), intval($from)),
                   'STYCRD003S');
}

// turn the rows STYCRD001S type CARD gives back into the shape the screen works in: two arrays of line text indexed from 0, plus the card level search key. Lines the file does not carry come back as empty strings so the screen always has a full set of boxes
function stcCardToSides($rows) {
    $side1 = array_fill(0, STC_S1_LAST - STC_S1_FIRST + 1, '');
    $side2 = array_fill(0, STC_S2_LAST - STC_S2_FIRST + 1, '');
    $srk   = '';

    foreach ($rows as $r) {
        $lno = intval($r['SCBLNO']);
        $txt = rtrim((string)$r['SCBTXT']);
        if ($srk === '') { $srk = rtrim((string)$r['SCBSRK']); }

        if ($lno >= STC_S1_FIRST && $lno <= STC_S1_LAST) {
            $side1[$lno - STC_S1_FIRST] = $txt;
        } elseif ($lno >= STC_S2_FIRST && $lno <= STC_S2_LAST) {
            $side2[$lno - STC_S2_FIRST] = $txt;
        }
    }

    return array('side1' => $side1, 'side2' => $side2, 'searchKey' => $srk);
}

// the Access save walked the text box a line at a time and kept a running line number that went up for every line it read, whether or not it wrote it. This returns that same counter: where the side finished, one past the last line the user filled in
function stcNextLine($lines, $firstLineNo) {
    $used = 0;
    foreach ($lines as $i => $txt) {
        if (trim((string)$txt) !== '') { $used = $i + 1; }
    }
    return $firstLineNo + $used;
}

// write a whole card the way the Access save did: every line of side 1 from 1, every line of side 2 from 13, then in update mode blank from wherever side 2 finished up to line 21. If any line fails the card is put back the way it was, which the Access screen could not do - a failure there left the card half rewritten
function stcSaveCard($conn, $sku, $side1, $side2, $searchKey) {
    $sku = stcCleanSku($sku);

    $before = stcGetCard($conn, $sku);
    if ($before === false) { return false; }
    $isNew = (count($before) === 0);

    $written = 0;
    foreach (array(array($side1, STC_S1_FIRST, STC_S1_LAST),
                   array($side2, STC_S2_FIRST, STC_S2_LAST)) as $side) {
        list($lines, $first, $last) = $side;
        // the Access save walked a text box, so it only ever wrote as many lines as the user had actually typed. Stop at the last one filled in
        $stop = min(stcNextLine($lines, $first) - 1, $last);
        foreach ($lines as $i => $txt) {
            $lineNo = $first + $i;
            if ($lineNo > $stop) { break; }
            if (!stcSaveBodyLine($conn, $sku, $lineNo, $txt, $searchKey)) {
                stcRestoreCard($conn, $sku, $before, $isNew);
                $GLOBALS['stcErr'] = 'Line ' . $lineNo . ' failed (' .
                                     $GLOBALS['stcErr'] . ') - the card was put back the way it was.';
                return false;
            }
            $written++;
        }
    }

    if (!$isNew) {
        if (!stcTrimCard($conn, $sku, stcNextLine($side2, STC_S2_FIRST))) {
            stcRestoreCard($conn, $sku, $before, $isNew);
            $GLOBALS['stcErr'] = 'Blanking the unused lines failed (' .
                                 $GLOBALS['stcErr'] . ') - the card was put back the way it was.';
            return false;
        }
    }

    return $written;
}

// put a card back to the rows read before the save started
function stcRestoreCard($conn, $sku, $before, $isNew) {
    if ($isNew) {
        // nothing was on file, so the rows the failed save wrote are blanked rather than removed - this screen never deletes a record, the same as the Access screens it replaces
        stcTrimCard($conn, $sku, STC_S1_FIRST);
        return;
    }

    foreach ($before as $r) {
        $lno = intval($r['SCBLNO']);
        if ($lno < STC_S1_FIRST || $lno > STC_S2_LAST || $lno === STC_S1_LAST + 1) { continue; }
        stcSaveBodyLine($conn, $sku, $lno, rtrim((string)$r['SCBTXT']),
                        rtrim((string)$r['SCBSRK']));
    }
}

// write the footer the way the Access footer screen did: the mode is picked once from whether anything is on file, and every line goes through that same mode. In update mode a line past the end of the footer updates nothing, so the footer cannot grow once it has rows - that is the old behaviour and it is kept here on purpose
function stcSaveFooter($conn, $lines, $sky = STC_FOOT_KEY) {
    $before = stcGetFooter($conn, $sky);
    if ($before === false) { return false; }
    $mode = (count($before) === 0) ? 'i' : 'u';

    $keep = array();
    foreach ($lines as $txt) { $keep[] = rtrim((string)$txt); }
    while (count($keep) > 0 && trim(end($keep)) === '') { array_pop($keep); }

    $lineNo = 0;
    foreach ($keep as $txt) {
        $lineNo++;
        if (!stcSaveFootLine($conn, $sky, $lineNo, $txt, $mode)) {
            if ($mode === 'i') {
                // nothing was on file for this key: blank what the failed save managed to insert, so no partial footer is left behind
                for ($b = 1; $b < $lineNo; $b++) {
                    stcSaveFootLine($conn, $sky, $b, '', 'u');
                }
            } else {
                stcRestoreFooter($conn, $before, $sky);
            }
            $GLOBALS['stcErr'] = 'Footer line ' . $lineNo . ' failed (' .
                                 $GLOBALS['stcErr'] . ') - the footer was put back the way it was.';
            return false;
        }
    }

    return $lineNo;
}

// put the footer back to the rows read before the save started
function stcRestoreFooter($conn, $before, $sky = STC_FOOT_KEY) {
    foreach ($before as $r) {
        stcSaveFootLine($conn, $sky, intval($r['SCFLNO']),
                        rtrim((string)$r['SCFTXT']), 'u');
    }
}
?>
