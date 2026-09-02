<?php
/*    ***************************************************  -->
<!--  * Program Name - PROJ_pgmrs_dsp.php                *  -->
<!--  *                                                 *  -->
<!--  * Narrative - The programmers on a project, each  *  -->
<!--  *             with a work status, a scheduled     *  -->
<!--  *             start and comments under their name *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/01/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// YYYYMMDD to the date input's YYYY-MM-DD
function pgmrIsoDate($dec) {
	$s = strval(intval($dec));
	if (strlen($s) !== 8) { return ''; }
	return substr($s, 0, 4) . '-' . substr($s, 4, 2) . '-' . substr($s, 6, 2);
}


// YYYYMMDD as MM/DD/YYYY, blank when none
function pgmrSlashDate($dec) {
	$s = strval(intval($dec));
	if (strlen($s) !== 8) { return ''; }
	return substr($s, 4, 2) . '/' . substr($s, 6, 2) . '/' . substr($s, 0, 4);
}


// YYYY-MM-DD or MM/DD/YYYY to YYYYMMDD, 0 when neither
function pgmrDecDate($txt) {
	$txt = trim(strval($txt));
	if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $txt, $m)) {
		return intval($m[1] . $m[2] . $m[3]);
	}
	if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $txt, $m)) {
		return intval($m[3] . str_pad($m[1], 2, '0', STR_PAD_LEFT)
		              . str_pad($m[2], 2, '0', STR_PAD_LEFT));
	}
	return 0;
}


// project managers, the programmer class and sysopr may change programmers
function pgmrCanEdit($conn, $user) {
	$auth = getRecPRAUTHP($conn, $user);
	$cls  = trim(strval($_SESSION['usrclass'] ?? ''));
	return (trim(strval($auth['PAPRJMNGR'] ?? '')) == 'Y'
	        || $cls == '*PGMR' || $cls == '*SYSOPR');
}


// a change-log row so notices and the weekly report see it
function pgmrLogChange($conn, $projNum, $user, $text) {
	$values = array($projNum, date('Ymd'), date('His'), $user, 'Pgmrs', ' ',
	                substr($text, 0, 30));
	insertPRCHGLOGP($conn, $values);
}


function pgmrEsc($s) {
	return htmlspecialchars(strval($s), ENT_QUOTES, 'UTF-8');
}


// the panel; the ajax hands it back again after every change
function renderProjPgmrPanel($conn, $projRecord, $canEdit, $user, $isPM = false) {
	$proj = intval($projRecord['PR#'] ?? 0);
	if ($proj <= 0) { return ''; }
	$primary = strtoupper(trim(strval($projRecord['PRPGMR'] ?? '')));
	$user    = strtoupper(trim(strval($user)));

	// status wording straight from the dropdown file
	$stsDesc = array();
	foreach (getRecsPRSTATUSP($conn) as $s) {
		$stsDesc[trim($s['PRSCODE'])] = trim($s['PRSDESC']);
	}

	$rows = getProjPgmrs($conn, $proj);
	$cmts = getProjPgmrComments($conn, $proj);
	if ($rows === false || $cmts === false) {
		return "<div id='pgmrPanel' class='pgmrPanel'><i>Programmer assignments are "
		     . "not available - PRJTRK002S is not on this system yet.</i></div>";
	}

	// hours per programmer, the same read as Programmer time to date
	$hours = array();
	foreach (getProjUserTime($conn, $proj) as $t) {
		$p = strtoupper(trim($t['PTPGMR']));
		$hours[$p] = ($hours[$p] ?? 0) + floatval($t['PTTIME']);
	}

	// primary first, then the additional programmers in the order added
	$people = array();
	if ($primary !== '') {
		$people[$primary] = array(
			'primary' => true,
			'sts'     => trim(strval($projRecord['PRWRKSTS'] ?? '')),
			'start'   => intval($projRecord['PRESTR'] ?? 0));
	}
	foreach ($rows as $r) {
		$p = strtoupper(trim($r['PGPGMR']));
		if ($p === '' || isset($people[$p])) { continue; }
		$people[$p] = array('primary' => false,
		                    'sts'     => trim(strval($r['PGWRKSTS'])),
		                    'start'   => intval($r['PGSTRDATE']));
	}

	$html  = "<style>"
	       . ".pgmrPanel{margin:.6rem 0 .4rem;padding:.5rem .7rem;border:1px solid #cfd6de;"
	       . "border-radius:6px;background:#fbfcfd;max-width:100%;font-size:.85rem}"
	       . ".pgmrPanel .pgmrHead{font-weight:bold;margin-bottom:.35rem}"
	       . ".pgmrPanel table.pgmrTable{border-collapse:collapse}"
	       . ".pgmrPanel table.pgmrTable th,.pgmrPanel table.pgmrTable td{padding:.15rem .5rem;"
	       . "text-align:left;vertical-align:middle;border-bottom:1px solid #e6eaef}"
	       . ".pgmrPanel table.pgmrTable th{font-size:.78rem;color:#555}"
	       . ".pgmrPanel .pgmrTag{font-size:.72rem;color:#666;margin-left:.25rem}"
	       . ".pgmrPanel .pgmrCmtGroup{margin-top:.55rem;padding-top:.35rem;border-top:1px dashed #d8dde3}"
	       . ".pgmrPanel .pgmrCmtName{font-weight:bold}"
	       . ".pgmrPanel .pgmrCmt{margin:.25rem 0 .25rem .6rem}"
	       . ".pgmrPanel .pgmrCmtWho{font-size:.75rem;color:#555;margin-right:.35rem}"
	       . ".pgmrPanel textarea{width:95%;max-width:100%;vertical-align:top}"
	       . ".pgmrPanel a{cursor:pointer}"
	       . "</style>";
	$html .= "<div id='pgmrPanel' class='pgmrPanel'>";
	$html .= "<div class='pgmrHead'>Programmers on this project</div>";
	$html .= "<table class='pgmrTable'><tr><th>Programmer</th><th>Status</th>"
	       . "<th>Scheduled start</th><th>Hours</th><th></th></tr>";

	if (count($people) === 0) {
		$html .= "<tr><td colspan='5'><i>No programmer assigned yet.</i></td></tr>";
	}
	foreach ($people as $p => $info) {
		$sts  = $info['sts'];
		$desc = $stsDesc[$sts] ?? $sts;
		$html .= "<tr data-pgmr='" . pgmrEsc($p) . "'><td>" . pgmrEsc($p);
		if ($info['primary']) {
			// the primary's status and start are the project's own fields above
			$html .= "<span class='pgmrTag'>(primary)</span></td>"
			       . "<td>" . pgmrEsc($desc !== '' ? $desc : 'Not set') . "</td>"
			       . "<td>" . pgmrEsc(pgmrSlashDate($info['start'])) . "</td>";
		} elseif ($canEdit) {
			$html .= "</td><td><select class='pgmrSts' onchange=\"pgmrSave('" . pgmrEsc($p) . "')\">"
			       . "<option value=''" . ($sts === '' ? " selected" : "") . ">Not set</option>";
			foreach ($stsDesc as $code => $d) {
				$html .= "<option value='" . pgmrEsc($code) . "'"
				       . ($code === $sts ? " selected" : "") . ">" . pgmrEsc($d) . "</option>";
			}
			$html .= "</select></td><td><input type='date' class='pgmrDate' value='"
			       . pgmrIsoDate($info['start']) . "' onchange=\"pgmrSave('" . pgmrEsc($p) . "')\" /></td>";
		} else {
			$html .= "</td><td>" . pgmrEsc($desc !== '' ? $desc : 'Not set') . "</td>"
			       . "<td>" . pgmrEsc(pgmrSlashDate($info['start'])) . "</td>";
		}
		$html .= "<td style='text-align:right'>" . pgmrEsc($hours[$p] ?? 0) . "</td><td>";
		if ($canEdit && !$info['primary']) {
			$html .= "<a onclick=\"pgmrRemove('" . pgmrEsc($p) . "')\">Remove</a>";
		}
		$html .= "</td></tr>";
	}

	// anyone from the programmer list who is not on the project yet
	if ($canEdit) {
		$html .= "<tr><td colspan='5'><select id='pgmrAdd'><option value=''>Add a programmer...</option>";
		foreach (getPgmrListPRIDTRANSP($conn) as $g) {
			$p = strtoupper(trim($g['PGDEVPRF']));
			if ($p === '' || isset($people[$p])) { continue; }
			$html .= "<option value='" . pgmrEsc($p) . "'>" . pgmrEsc($p) . "</option>";
		}
		$html .= "</select> <a onclick='pgmrAdd()'>Add</a></td></tr>";
	}
	$html .= "</table>";

	// comments filed under each name, newest first
	$byPgmr = array();
	foreach ($cmts as $c) {
		$byPgmr[strtoupper(trim($c['CMPGMR']))][] = $c;
	}
	foreach ($people as $p => $info) {
		$html .= "<div class='pgmrCmtGroup'><div class='pgmrCmtName'>" . pgmrEsc($p) . "</div>";
		foreach ($byPgmr[$p] ?? array() as $c) {
			$who = strtoupper(trim($c['CMUSER']));
			$html .= "<div class='pgmrCmt'><span class='pgmrCmtWho'>"
			       . pgmrEsc(pgmrSlashDate($c['CMDATE']) . ' ' . $who) . "</span>"
			       . nl2br(pgmrEsc($c['CMTEXT']));
			if ($canEdit && ($isPM || $who === $user)) {
				$html .= " <a class='pgmrTag' onclick=\"pgmrCommentRemove(" . intval($c['CMSEQ']) . ")\">remove</a>";
			}
			$html .= "</div>";
		}
		if ($canEdit) {
			$html .= "<div class='pgmrCmt'><textarea id='pgmrCmtTxt_" . pgmrEsc($p) . "' rows='2' "
			       . "maxlength='4000' placeholder='Comment for " . pgmrEsc($p) . "'></textarea><br/>"
			       . "<a onclick=\"pgmrCommentAdd('" . pgmrEsc($p) . "')\">Add comment</a></div>";
		}
		$html .= "</div>";
	}

	return $html . "</div>";
}
?>
