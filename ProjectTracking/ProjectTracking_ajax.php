<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_ajax.php         *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 08/11/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// AJAX endpoint, buffer from byte 0 so stray include output can't corrupt the JSON
ob_start();
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}

// the same vendored PhpSpreadsheet copy the other loaders read uploads with -
// only needed for the Excel download action
$prjVendor = '/www/seidenphp/htdocs/vendor/autoload.php';
if (file_exists($prjVendor)) { require_once $prjVendor; }

if (defined('SESSION_NAME')) { session_name(SESSION_NAME); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$user     = $_SESSION['username'] ?? '';
$password = $_SESSION['password'] ?? '';

require_once __DIR__ . '/ProjectTracking_model.php';

$conn = null;
if (function_exists('getDB2PConn')) { $conn = getDB2PConn($user, $password); }

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// every JSON reply - including a failure on the download action's path -
// purges the buffer and claims the content type itself, so stray include
// output can never ride along and no reply goes out as text/html
function prjOut($arr) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) { header('Content-Type: application/json'); }
    echo json_encode($arr);
    exit;
}


function prjOutFail($msg = '') {
    prjOut(array("ok" => false,
                 "msg" => $msg !== '' ? $msg : ($GLOBALS['prjErr'] ?: 'Request failed.')));
}


// a stored YYYYMMDD number as MM/DD/YYYY, blank when there is no date
function prjFmtDate($dec) {
    $s = strval(intval($dec));
    if (strlen($s) !== 8) { return ''; }
    return substr($s, 4, 2) . '/' . substr($s, 6, 2) . '/' . substr($s, 0, 4);
}


// trim a project row down to what the screens render, so the JSON stays small
function prjRowOut($row) {
    return array(
        'num'    => intval($row['PJNUM']),
        'desc'   => trim($row['PJDESC']),
        'pgmr'   => trim($row['PJPGMR']),
        'rqst'   => trim($row['PJRQST']),
        'dept'   => trim($row['PJDEPT']),
        'deptpr' => intval($row['PJDEPTPR']),
        'scpr'   => intval($row['PJSCPR']),
        'stage'  => $row['STAGE'],
        'status' => $row['STATUS'],
        'low'    => floatval($row['PJESTLOW']),
        'hi'     => floatval($row['PJESTHI']),
        'hours'  => floatval($row['PJHOURS']),
        // submitted (created) date; ?? 0 keeps the page alive until the
        // recompiled PRJTRK001S that returns PJSUBDATE is on the box
        'sub'    => prjFmtDate($row['PJSUBDATE'] ?? 0),
        'subraw' => intval($row['PJSUBDATE'] ?? 0),
        'sched'  => prjFmtDate($row['PJSCHDATE']),
        // the raw YYYYMMDD rides along so the table can sort the formatted
        // date chronologically instead of month-first
        'schedraw' => intval($row['PJSCHDATE']),
        'comp'   => prjFmtDate($row['PJCOMPDATE']),
        // 1 = on the PTS report extracts (the SC pipeline), 0 = stale
        'pipe'   => intval($row['PIPE'] ?? 1),
    );
}


// group project rows by programmer the way the Projects-by-developer
// spreadsheet does: busiest first, the Unassigned bucket last
function prjGroupByPgmr($projects) {
    $groups = array();
    foreach ($projects as $row) {
        $pgmr = trim($row['PJPGMR']);
        if ($pgmr === '') { $pgmr = 'Unassigned'; }
        if (!isset($groups[$pgmr])) { $groups[$pgmr] = array(); }
        $groups[$pgmr][] = $row;
    }
    $unassigned = $groups['Unassigned'] ?? null;
    unset($groups['Unassigned']);
    ksort($groups);
    if ($unassigned !== null) { $groups['Unassigned'] = $unassigned; }
    return $groups;
}


if (!$conn) {
    prjOutFail("No database connection - sign in to LCC Online first.");
}

// level 20 is the developers group (10 is only the minimum to use LCCOnline)
if (function_exists('chkAutUsr') && chkAutUsr($conn, $user, "LCCONLINE", 20) != "yes") {
    prjOutFail("Current user profile is not authorized to use this tool.");
}

switch ($action) {

    // everything the dashboard draws in one round trip: the stat tiles, the
    // pipeline, the two charts, the project table, and the cached weekly
    // summary. Counts are scoped to the SC pipeline - the union of the four
    // PTS report extracts the monthly spreadsheet is built from - so the
    // open number matches the spreadsheet; open records on none of those
    // reports only feed the stale counter and stay off the dashboard table
    case 'dashboard':
        $projects = prjProjects($conn, 'N');
        if ($projects === false) { prjOutFail(); }
        $pipe = prjPipelineCheck($projects, prjPipelineNums($conn, $projects));
        prjMarkPipeline($projects, $pipe);
        $rollup = prjDashboardRollup($projects);

        $out = array();
        foreach ($projects as $row) {
            if (intval($row['PIPE']) === 0) { continue; }
            $out[] = prjRowOut($row);
        }

        // the summary text is the deliverable; the digest behind it stays in
        // the cache file where it can be checked, not in every page load
        $weekly = prjWeeklyRead();
        if (is_array($weekly)) { unset($weekly['digest']); }

        prjActLog($user, 'DASHBOARD', $GLOBALS['prjPipeInfo'] ?? '');
        prjOut(array("ok" => true,
                     "tiles" => $rollup['tiles'],
                     "pipeline" => $rollup['pipeline'],
                     "status" => $rollup['status'],
                     "load" => $rollup['load'],
                     "stages" => $GLOBALS['prjStages'],
                     // the donut's four fixed buckets - the by-developer
                     // page gets the stored PRWRKSTS labels instead
                     "statuses" => $GLOBALS['prjStatusBuckets'],
                     "developers" => $GLOBALS['prjDevelopers'],
                     "projects" => $out,
                     "weekly" => $weekly,
                     // true when the PTS report reads produced nothing usable
                     // and the dashboard fell back to counting all open work;
                     // pipeinfo says what each report proc returned
                     "pipenote" => ($pipe === null),
                     "pipeinfo" => $GLOBALS['prjPipeInfo'] ?? '',
                     "updated" => date('M j, Y')));

    // the assignments page rows; complete=Y adds finished and rejected work.
    // Every row carries its pipe flag so the page can hide stale records
    // client-side and still offer the include-stale checkbox
    case 'assignments':
        $includeComplete = (($_POST['complete'] ?? $_GET['complete'] ?? '') === 'Y') ? 'Y' : 'N';
        $projects = prjProjects($conn, $includeComplete);
        if ($projects === false) { prjOutFail(); }
        prjMarkPipeline($projects,
            prjPipelineCheck($projects, prjPipelineNums($conn, $projects)));

        $out = array();
        foreach ($projects as $row) { $out[] = prjRowOut($row); }

        prjActLog($user, 'ASSIGNMENTS', 'complete=' . $includeComplete);
        prjOut(array("ok" => true,
                     "stages" => $GLOBALS['prjStages'],
                     "statuses" => $GLOBALS['prjStatuses'],
                     "developers" => $GLOBALS['prjDevelopers'],
                     "projects" => $out,
                     "updated" => date('M j, Y')));

    // build this week's digest, write the AI summary, cache it. POST only so
    // a crawler or prefetch can't burn an API call
    case 'weeklygenerate':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            prjOutFail("The weekly summary is generated with a POST.");
        }
        // optional explicit period from the card's picker - the client
        // sends a resolved Mon-Sun week or calendar month as YYYYMMDD.
        // Junk or oversized ranges are refused; nothing sent means the
        // default prior week
        $from = intval($_POST['from'] ?? 0);
        $to   = intval($_POST['to'] ?? 0);
        if ($from > 0 || $to > 0) {
            $f = DateTime::createFromFormat('!Ymd', strval($from));
            $t = DateTime::createFromFormat('!Ymd', strval($to));
            if (!$f || !$t || $f > $t || $f->diff($t)->days > 45) {
                prjOutFail("Pick a single week or month to report on.");
            }
        }
        list($ok, $result) = prjGenerateWeekly($conn, $user, $from, $to);
        if (!$ok) { prjOutFail($result); }
        // the digest rides in the cache file for reference, not in the response
        unset($result['digest']);
        prjOut(array("ok" => true, "weekly" => $result));

    // the assignments view as a workbook, grouped per programmer the way the
    // Projects-by-developer spreadsheet lays it out
    case 'download':
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            prjOutFail("The spreadsheet library is not available on this server.");
        }
        $includeComplete = (($_GET['complete'] ?? '') === 'Y') ? 'Y' : 'N';
        $includeStale    = (($_GET['stale'] ?? '') === 'Y') ? 'Y' : 'N';
        $projects = prjProjects($conn, $includeComplete);
        if ($projects === false) { prjOutFail(); }
        prjMarkPipeline($projects,
            prjPipelineCheck($projects, prjPipelineNums($conn, $projects)));

        // stale open records stay out of the workbook unless asked for, the
        // same visibility rule the page applies; finished work already on
        // the sheet (complete=Y) is governed by that checkbox alone
        if ($includeStale !== 'Y') {
            $projects = array_values(array_filter($projects, function ($row) {
                return intval($row['PIPE']) === 1 ||
                       $row['STAGE'] === 'complete' || $row['STAGE'] === 'rejected';
            }));
        }

        // only the tracked developers' groups (and Unassigned) go in the
        // workbook - the same groups the monthly spreadsheet carries
        $projects = array_values(array_filter($projects, function ($row) {
            $pgmr = trim($row['PJPGMR']);
            return $pgmr === '' || prjTrackedDev($pgmr);
        }));

        $book  = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Projects by developer');

        $heads = array('Pjt#', 'SC Stage', 'Status', 'Dept', 'Dept Prty',
                       'SC Prty', 'Description', 'Low Est', 'Hi Est', 'Hours',
                       'Sched Comp', 'Comp Date');
        $widths = array('A' => 10, 'B' => 16, 'C' => 15, 'D' => 8, 'E' => 10,
                        'F' => 9, 'G' => 52, 'H' => 9, 'I' => 9, 'J' => 9,
                        'K' => 12, 'L' => 12);
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $r = 1;
        foreach (prjGroupByPgmr($projects) as $pgmr => $rows) {
            $count = count($rows);
            $sheet->setCellValue('A' . $r,
                $pgmr . ' - ' . $count . ' project' . ($count === 1 ? '' : 's'));
            $sheet->mergeCells('A' . $r . ':L' . $r);
            $sheet->getStyle('A' . $r)->getFont()->setBold(true)->setSize(12);
            $r += 1;

            $sheet->fromArray($heads, NULL, 'A' . $r);
            $sheet->getStyle('A' . $r . ':L' . $r)->getFont()->setBold(true);
            $r += 1;

            foreach ($rows as $row) {
                $sheet->fromArray(array(
                    intval($row['PJNUM']),
                    $GLOBALS['prjStages'][$row['STAGE']] ?? ucfirst($row['STAGE']),
                    $GLOBALS['prjStatuses'][$row['STATUS']] ?? '',
                    trim($row['PJDEPT']),
                    intval($row['PJDEPTPR']),
                    intval($row['PJSCPR']),
                    trim($row['PJDESC']),
                    floatval($row['PJESTLOW']),
                    floatval($row['PJESTHI']),
                    floatval($row['PJHOURS']),
                    prjFmtDate($row['PJSCHDATE']),
                    prjFmtDate($row['PJCOMPDATE']),
                ), NULL, 'A' . $r);
                $r += 1;
            }
            $r += 1; // blank row between programmers, like the source sheet
        }

        prjActLog($user, 'DOWNLOAD', 'complete=' . $includeComplete .
                  ' stale=' . $includeStale);

        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ProjectDevelopers_' .
               date('Ymd') . '.xlsx"');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx');
        $writer->save('php://output');
        exit;

    default:
        prjOutFail("Unknown action.");
}
?>
