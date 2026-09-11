<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectDetail_pgmrs.php          *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/11/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// several programmers on a project, and their comments
// nothing here touches a legacy file, it only calls PRJTRK002S

// one call into the procedure, one type per call
function prjPgmrCall($conn, $type, $proj, $pgmr = '', $sts = '', $date = 0,
                     $user = '', $text = '', $seq = 0) {
    if (!$conn) { return false; }
    $stmt = @db2_prepare($conn, "Call PRJTRK002S(?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) { return false; }
    $inType = $type;  $inProj = intval($proj); $inPgmr = strtoupper(trim($pgmr));
    $inSts  = trim($sts); $inDate = intval($date); $inUser = strtoupper(trim($user));
    $inText = strval($text); $inSeq = intval($seq); $inDate2 = 0;
    db2_bind_param($stmt, 1, "inType", DB2_PARAM_IN);
    db2_bind_param($stmt, 2, "inProj", DB2_PARAM_IN);
    db2_bind_param($stmt, 3, "inPgmr", DB2_PARAM_IN);
    db2_bind_param($stmt, 4, "inSts",  DB2_PARAM_IN);
    db2_bind_param($stmt, 5, "inDate", DB2_PARAM_IN);
    db2_bind_param($stmt, 6, "inDate2", DB2_PARAM_IN);
    db2_bind_param($stmt, 7, "inUser", DB2_PARAM_IN);
    db2_bind_param($stmt, 8, "inText", DB2_PARAM_IN);
    db2_bind_param($stmt, 9, "inSeq",  DB2_PARAM_IN);
    if (!@db2_execute($stmt)) { return false; }
    $rows = array();
    while ($r = db2_fetch_assoc($stmt)) { $rows[] = $r; }
    return $rows;
}

// the reads and writes the screen needs
function prjPgmrRows($conn, $proj)        { return prjPgmrCall($conn, 'PGLIST', $proj); }
function prjPgmrCmtRows($conn, $proj)     { return prjPgmrCall($conn, 'CMLIST', $proj); }
function prjPgmrRemove($conn, $proj, $p)  { return prjPgmrCall($conn, 'PGDEL', $proj, $p) !== false; }
function prjPgmrSave($conn, $proj, $p, $sts, $date, $user) {
    return prjPgmrCall($conn, 'PGSAVE', $proj, $p, $sts, $date, $user) !== false;
}

// the writer's own profile is stamped on, never taken from the form
function prjPgmrCmtAdd($conn, $proj, $user, $text) {
    $user = strtoupper(trim($user));
    return prjPgmrCall($conn, 'CMADD', $proj, $user, '', 0, $user, $text) !== false;
}
function prjPgmrCmtRemove($conn, $proj, $seq) {
    return prjPgmrCall($conn, 'CMDEL', $proj, '', '', 0, '', '', $seq) !== false;
}

// yyyymmdd out of the file into what a date box wants
function prjPgmrIso($dec) {
    $d = intval($dec);
    if ($d < 10000000) { return ''; }
    return sprintf('%04d-%02d-%02d', intdiv($d, 10000), intdiv($d, 100) % 100, $d % 100);
}

// and the way it reads on the page
function prjPgmrSlash($dec) {
    $d = intval($dec);
    if ($d < 10000000) { return ''; }
    return sprintf('%02d/%02d/%04d', intdiv($d, 100) % 100, $d % 100, intdiv($d, 10000));
}

// a date box value back into the decimal the file holds
function prjPgmrDec($txt) {
    $t = trim(strval($txt));
    if ($t === '') { return 0; }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m)) {
        return intval($m[1] . $m[2] . $m[3]);
    }
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $t, $m)) {
        return intval(sprintf('%04d%02d%02d', $m[3], $m[1], $m[2]));
    }
    return 0;
}

// hhmmss into a readable clock time
function prjPgmrClock($dec) {
    $t = intval($dec);
    $h = intdiv($t, 10000); $m = intdiv($t, 100) % 100;
    $ap = ($h >= 12) ? 'pm' : 'am';
    $h12 = $h % 12; if ($h12 === 0) { $h12 = 12; }
    return sprintf('%d:%02d %s', $h12, $m, $ap);
}

function prjPgmrEsc($s) {
    return htmlspecialchars(strval($s), ENT_QUOTES);
}

// may this person change assignments - the tab's own rule
function prjPgmrMayEdit($screenData) {
    return ($screenData['PAPRJMNGR'] == 'Y'
            || trim(strval($_SESSION['usrclass'] ?? '')) == '*PGMR'
            || trim(strval($_SESSION['usrclass'] ?? '')) == '*SYSOPR');
}

// the programmers on a project, drawn under the assigned field
function prjPgmrList($conn, $screenData, $canEdit) {
    $proj = intval($screenData['PR#'] ?? 0);
    if ($proj <= 0) { return ''; }
    $rows = prjPgmrRows($conn, $proj);
    if ($rows === false) {
        return "<div class='pt-pgmr-none'>Additional programmers need PRJTRK002S "
             . "on this server.</div>";
    }

    // the wording the status dropdown itself uses
    $stsDesc = array();
    if (function_exists('getRecsPRSTATUSP')) {
        foreach (getRecsPRSTATUSP($conn) as $s) {
            $stsDesc[trim($s['PRSCODE'])] = trim($s['PRSDESC']);
        }
    }
    // hours each person has booked, the same read as the time box
    $hours = array();
    if (function_exists('getProjUserTime')) {
        foreach (getProjUserTime($conn, $proj) as $t) {
            $p = strtoupper(trim($t['PTPGMR']));
            $hours[$p] = ($hours[$p] ?? 0) + floatval($t['PTTIME']);
        }
    }
    $primary = strtoupper(trim(strval($screenData['PRPGMR'] ?? '')));

    $h = "<div id='ptPgmrList' class='pt-pgmr'>";
    $on = array();
    foreach ($rows as $r) {
        $p = strtoupper(trim($r['PGPGMR']));
        if ($p === '' || $p === $primary) { continue; }
        $on[] = $p;
        $sts = trim(strval($r['PGWRKSTS']));
        $h .= "<div class='pt-pgmr-row' data-pgmr='" . prjPgmrEsc($p) . "'>";
        $h .= "<span class='pt-pgmr-who'>" . prjPgmrEsc($p) . "</span>";
        if ($canEdit) {
            $h .= "<select class='pt-pgmr-sts' onchange=\"ptPgmrSave('" . prjPgmrEsc($p) . "')\">"
                . "<option value=''" . ($sts === '' ? " selected" : "") . ">Not set</option>";
            foreach ($stsDesc as $code => $d) {
                $h .= "<option value='" . prjPgmrEsc($code) . "'"
                    . ($code === $sts ? " selected" : "") . ">" . prjPgmrEsc($d) . "</option>";
            }
            $h .= "</select>";
            $h .= "<input type='date' class='pt-pgmr-date' value='"
                . prjPgmrIso($r['PGSTRDATE']) . "' title='Scheduled start date' "
                . "onchange=\"ptPgmrSave('" . prjPgmrEsc($p) . "')\" />";
        } else {
            $d = $stsDesc[$sts] ?? $sts;
            $h .= "<span class='pt-pgmr-val'>" . prjPgmrEsc($d !== '' ? $d : 'Not set') . "</span>";
            $h .= "<span class='pt-pgmr-val'>" . prjPgmrEsc(prjPgmrSlash($r['PGSTRDATE'])) . "</span>";
        }
        $h .= "<span class='pt-pgmr-hrs'>" . prjPgmrEsc($hours[$p] ?? 0) . " hrs</span>";
        if ($canEdit) {
            $h .= "<a class='pt-pgmr-x' onclick=\"ptPgmrRemove('" . prjPgmrEsc($p)
                . "')\" title='Remove'>&times;</a>";
        }
        $h .= "</div>";
    }

    // anyone on the programmer list who is not on the project yet
    if ($canEdit && function_exists('getPgmrListPRIDTRANSP')) {
        $opts = '';
        foreach (getPgmrListPRIDTRANSP($conn) as $g) {
            $p = strtoupper(trim($g['PGDEVPRF']));
            if ($p === '' || $p === $primary || in_array($p, $on)) { continue; }
            $opts .= "<option value='" . prjPgmrEsc($p) . "'>" . prjPgmrEsc($p) . "</option>";
        }
        if ($opts !== '') {
            $h .= "<div class='pt-pgmr-add'><select id='ptPgmrAdd'>"
                . "<option value=''>Add another programmer...</option>" . $opts
                . "</select> <a onclick='ptPgmrAdd()'>Add</a></div>";
        }
    }
    return $h . "</div>";
}

// the comments, each stamped with who wrote it and when
function prjPgmrComments($conn, $screenData, $canEdit) {
    $proj = intval($screenData['PR#'] ?? 0);
    if ($proj <= 0) { return ''; }
    $rows = prjPgmrCmtRows($conn, $proj);
    if ($rows === false) { return ''; }
    $me   = strtoupper(trim(strval($_SESSION['username'] ?? '')));
    $isPM = ($screenData['PAPRJMNGR'] == 'Y');

    $h = "<div id='ptPgmrCmts' class='pt-pgmrcmt'>";
    foreach ($rows as $c) {
        $who = strtoupper(trim($c['CMUSER']));
        $h .= "<div class='pt-pgmrcmt-one'><div class='pt-pgmrcmt-by'>"
            . prjPgmrEsc($who) . " &middot; " . prjPgmrEsc(prjPgmrSlash($c['CMDATE']))
            . " " . prjPgmrEsc(prjPgmrClock($c['CMTIME'] ?? 0));
        if ($canEdit && ($isPM || $who === $me)) {
            $h .= " <a class='pt-pgmrcmt-x' onclick=\"ptPgmrCmtRemove("
                . intval($c['CMSEQ']) . ")\">remove</a>";
        }
        $h .= "</div><div class='pt-pgmrcmt-txt'>"
            . nl2br(prjPgmrEsc($c['CMTEXT'])) . "</div></div>";
    }
    if ($canEdit) {
        $h .= "<div class='pt-pgmrcmt-new'><textarea id='ptPgmrCmtTxt' rows='2' maxlength='4000' "
            . "placeholder='Add a comment as " . prjPgmrEsc($me) . "'></textarea>"
            . "<div><a onclick='ptPgmrCmtAdd()'>Add comment</a></div></div>";
    }
    return $h . "</div>";
}
