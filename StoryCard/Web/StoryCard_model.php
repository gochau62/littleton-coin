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
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

$GLOBALS['stcErr'] = '';

// the printed card: side 1 is lines 1 to 12, side 2 is 13 to 22, and every
// line is 50 characters wide. The Access screen believed all three numbers
// and then guarded on 11 and 21, which is why lines 12 and 22 were unusable
define('STC_S1_FIRST', 1);
define('STC_S1_LAST', 12);
define('STC_S2_FIRST', 13);
define('STC_S2_LAST', 22);
define('STC_LINE_LEN', 50);
define('STC_SRK_LEN', 15);
define('STC_SKU_LEN', 10);

// the footer is one shared block; the Access combo could only ever pick key 1
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


// PROGRAM NAME STYCRD001S: the SKU picker, where CARDS lists only SKUs that already have a story card (what the Access combo box showed) and ITEMS searches the whole item master so a card can be started for something that has none yet
function stcSkuSearch($conn, $type, $key = '') {
    $type = strtoupper(trim($type));
    if (!in_array($type, array('CARDS', 'ITEMS'))) { $type = 'CARDS'; }
    return stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                       array($type, stcCleanSku($key)));
}


// PROGRAM NAME STYCRD001S type ONE: the old CheckSku and the item description read in a single call. No row back means the SKU is not on the item master
function stcGetSku($conn, $sku) {
    $rows = stcFetchAll($conn, "CALL STYCRD001S(?, ?)",
                        array('ONE', stcCleanSku($sku)));
    if ($rows === false) { return false; }
    return $rows ? $rows[0] : null;
}


// PROGRAM NAME STYCRD002S: every body line held for one SKU, in line order
function stcGetCard($conn, $sku) {
    return stcFetchAll($conn, "CALL STYCRD002S(?)", array(stcCleanSku($sku)));
}


// PROGRAM NAME STYCRD003S: the shared footer lines
function stcGetFooter($conn, $sky = STC_FOOT_KEY) {
    return stcFetchAll($conn, "CALL STYCRD003S(?)", array(intval($sky)));
}


// PROGRAM NAME STYCRD004S: write one body line, inserting or updating as needed
function stcSaveBodyLine($conn, $sku, $lineNo, $text, $searchKey) {
    return stcExec($conn, "CALL STYCRD004S(?, ?, ?, ?)",
                   array(stcCleanSku($sku),
                         intval($lineNo),
                         substr((string)$text, 0, STC_LINE_LEN),
                         substr((string)$searchKey, 0, STC_SRK_LEN)),
                   'STYCRD004S');
}


// PROGRAM NAME STYCRD005S: blank whatever sits past the end of each side, so a card that got shorter really is shorter
function stcTrimCard($conn, $sku, $side1Last, $side2Last) {
    return stcExec($conn, "CALL STYCRD005S(?, ?, ?)",
                   array(stcCleanSku($sku), intval($side1Last), intval($side2Last)),
                   'STYCRD005S');
}


// PROGRAM NAME STYCRD006S: write one footer line, inserting or updating as needed
function stcSaveFootLine($conn, $lineNo, $text, $sky = STC_FOOT_KEY) {
    return stcExec($conn, "CALL STYCRD006S(?, ?, ?)",
                   array(intval($sky), intval($lineNo),
                         substr((string)$text, 0, STC_LINE_LEN)),
                   'STYCRD006S');
}


// PROGRAM NAME STYCRD007S: drop footer lines past the last one written
function stcTrimFooter($conn, $lastLine, $sky = STC_FOOT_KEY) {
    return stcExec($conn, "CALL STYCRD007S(?, ?)",
                   array(intval($sky), intval($lastLine)),
                   'STYCRD007S');
}


// PROGRAM NAME STYCRD008S: delete a whole card, used to back out a new card whose save failed part way and by the Delete button
function stcDeleteCard($conn, $sku) {
    return stcExec($conn, "CALL STYCRD008S(?)", array(stcCleanSku($sku)),
                   'STYCRD008S');
}


// turn the rows STYCRD002S gives back into the shape the screen works in: two arrays of line text indexed from 0, plus the card level search key. Lines the file does not carry come back as empty strings so the screen always has a full set of boxes
function stcCardToSides($rows) {
    $side1 = array_fill(0, STC_S1_LAST - STC_S1_FIRST + 1, '');
    $side2 = array_fill(0, STC_S2_LAST - STC_S2_FIRST + 1, '');
    $srk   = '';
    $stray = array();

    foreach ($rows as $r) {
        $lno = intval($r['SCBLNO']);
        $txt = rtrim((string)$r['SCBTXT']);
        if ($srk === '') { $srk = rtrim((string)$r['SCBSRK']); }

        if ($lno >= STC_S1_FIRST && $lno <= STC_S1_LAST) {
            $side1[$lno - STC_S1_FIRST] = $txt;
        } elseif ($lno >= STC_S2_FIRST && $lno <= STC_S2_LAST) {
            $side2[$lno - STC_S2_FIRST] = $txt;
        } else {
            // a line number no printed side owns. Surfaced rather than hidden
            $stray[] = array('line' => $lno, 'text' => $txt);
        }
    }

    return array('side1' => $side1, 'side2' => $side2,
                 'searchKey' => $srk, 'stray' => $stray);
}


// the last line of a side that actually carries text, as a file line number. 0 for side 1 and 12 for side 2 mean the side was left empty, which is what STYCRD005S expects
function stcLastUsed($lines, $firstLineNo) {
    $last = $firstLineNo - 1;
    foreach ($lines as $i => $txt) {
        if (trim((string)$txt) !== '') { $last = $firstLineNo + $i; }
    }
    return $last;
}


// write a whole card in one go, and put it back the way it was if any line fails.
// A new card backs out by deleting it, an existing one by rewriting the snapshot
// taken before the first line was touched. The Access screen had neither: a save
// that died on line 7 of 22 left the card half rewritten with nothing to undo it
function stcSaveCard($conn, $sku, $side1, $side2, $searchKey) {
    $sku = stcCleanSku($sku);

    $before = stcGetCard($conn, $sku);
    if ($before === false) { return false; }
    $isNew = (count($before) === 0);

    $s1Last = stcLastUsed($side1, STC_S1_FIRST);
    $s2Last = stcLastUsed($side2, STC_S2_FIRST);

    // only write as far as the last line that carries text on each side, so a
    // three line card stays three rows instead of becoming twenty two mostly
    // blank ones. Blank lines before that point are still written, since a gap
    // in the middle of a side is a real part of the layout
    $written = 0;
    foreach (array(array($side1, STC_S1_FIRST, $s1Last),
                   array($side2, STC_S2_FIRST, $s2Last)) as $side) {
        list($lines, $first, $last) = $side;
        foreach ($lines as $i => $txt) {
            $lineNo = $first + $i;
            if ($lineNo > $last) { break; }
            if (!stcSaveBodyLine($conn, $sku, $lineNo, $txt, $searchKey)) {
                stcRestoreCard($conn, $sku, $before, $isNew);
                $GLOBALS['stcErr'] = 'Line ' . $lineNo . ' failed (' .
                                     $GLOBALS['stcErr'] . ') - the card was put back the way it was.';
                return false;
            }
            $written++;
        }
    }

    if (!stcTrimCard($conn, $sku, $s1Last, $s2Last)) {
        stcRestoreCard($conn, $sku, $before, $isNew);
        $GLOBALS['stcErr'] = 'Tidying the unused lines failed (' .
                             $GLOBALS['stcErr'] . ') - the card was put back the way it was.';
        return false;
    }

    return $written;
}


// put a card back to the rows read before the save started
function stcRestoreCard($conn, $sku, $before, $isNew) {
    if ($isNew) {
        stcDeleteCard($conn, $sku);
        return;
    }

    // a failed save can only have overwritten rows that were already there or
    // added rows past the end, so rewriting each original row and then blanking
    // everything past the two originals' last lines restores the card exactly
    $s1Last = STC_S1_FIRST - 1;
    $s2Last = STC_S2_FIRST - 1;
    foreach ($before as $r) {
        $lno = intval($r['SCBLNO']);
        // a stray line number outside the printed card was never touched by the
        // save, so leave it alone rather than trying to write it back
        if ($lno < STC_S1_FIRST || $lno > STC_S2_LAST) { continue; }
        stcSaveBodyLine($conn, $sku, $lno, rtrim((string)$r['SCBTXT']),
                        rtrim((string)$r['SCBSRK']));
        if ($lno <= STC_S1_LAST) {
            if ($lno > $s1Last) { $s1Last = $lno; }
        } elseif ($lno <= STC_S2_LAST) {
            if ($lno > $s2Last) { $s2Last = $lno; }
        }
    }
    stcTrimCard($conn, $sku, $s1Last, $s2Last);
}


// write the whole footer: every line in order, then drop anything past the end.
// Same back out as the card - the footer prints on every story card, so a half
// saved footer is worse than no save at all
function stcSaveFooter($conn, $lines, $sky = STC_FOOT_KEY) {
    $before = stcGetFooter($conn, $sky);
    if ($before === false) { return false; }

    // drop the trailing blanks the user left behind, keeping any gap in the middle
    $keep = array();
    foreach ($lines as $txt) { $keep[] = rtrim((string)$txt); }
    while (count($keep) > 0 && trim(end($keep)) === '') { array_pop($keep); }

    $lineNo = 0;
    foreach ($keep as $txt) {
        $lineNo++;
        if (!stcSaveFootLine($conn, $lineNo, $txt, $sky)) {
            stcRestoreFooter($conn, $before, $sky);
            $GLOBALS['stcErr'] = 'Footer line ' . $lineNo . ' failed (' .
                                 $GLOBALS['stcErr'] . ') - the footer was put back the way it was.';
            return false;
        }
    }

    if (!stcTrimFooter($conn, $lineNo, $sky)) {
        stcRestoreFooter($conn, $before, $sky);
        $GLOBALS['stcErr'] = 'Tidying the footer failed (' . $GLOBALS['stcErr'] .
                             ') - the footer was put back the way it was.';
        return false;
    }

    return $lineNo;
}


// put the footer back to the rows read before the save started
function stcRestoreFooter($conn, $before, $sky) {
    $last = 0;
    foreach ($before as $r) {
        $lno = intval($r['SCFLNO']);
        stcSaveFootLine($conn, $lno, rtrim((string)$r['SCFTXT']), $sky);
        if ($lno > $last) { $last = $lno; }
    }
    stcTrimFooter($conn, $last, $sky);
}
?>
