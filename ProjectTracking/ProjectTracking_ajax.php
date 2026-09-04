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

// buffer output so nothing corrupts the JSON
ob_start();
foreach (['Utils/common_functions.php', 'Utils/default_values.php'] as $f) {
    if (file_exists($f)) { require_once $f; }
}

// vendored PhpSpreadsheet, only for the download action
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

// purge the buffer, claim the content type, reply
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


// YYYYMMDD as MM/DD/YYYY, blank when no date
function prjFmtDate($dec) {
    $s = strval(intval($dec));
    if (strlen($s) !== 8) { return ''; }
    return substr($s, 4, 2) . '/' . substr($s, 6, 2) . '/' . substr($s, 0, 4);
}


// trim a row to what the screens render
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
        // checklist items still red, why a project is not yet reviewable
        'missing' => $row['MISSING'] ?? array(),
        // submitted this SC cycle, whatever the stage says
        'fresh'  => (intval($GLOBALS['prjWindowFrom'] ?? 0) > 0 &&
                     intval($row['PJSUBDATE'] ?? 0) >= intval($GLOBALS['prjWindowFrom'])) ? 1 : 0,
        'fire'   => (trim(strval($row['PJTYPE'] ?? '')) === 'FR') ? 1 : 0,
        'rescode' => strtoupper(trim(strval($row['PJRESCOD'] ?? ''))),
        'status' => $row['STATUS'],
        'low'    => floatval($row['PJESTLOW']),
        'hi'     => floatval($row['PJESTHI']),
        'hours'  => floatval($row['PJHOURS']),
        // ?? 0 tolerates an old PRJTRK001S compile
        'sub'    => prjFmtDate($row['PJSUBDATE'] ?? 0),
        'subraw' => intval($row['PJSUBDATE'] ?? 0),
        'sched'  => prjFmtDate($row['PJSCHDATE']),
        // raw date so the table sorts chronologically
        'schedraw' => intval($row['PJSCHDATE']),
        'comp'   => prjFmtDate($row['PJCOMPDATE']),
        // 1 = on the SC pipeline, 0 = stale
        'pipe'   => intval($row['PIPE'] ?? 1),
    );
}


// group rows by programmer, Unassigned last
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

// level 20 is the developers group
if (function_exists('chkAutUsr') && chkAutUsr($conn, $user, "LCCONLINE", 20) != "yes") {
    prjOutFail("Current user profile is not authorized to use this tool.");
}

switch ($action) {

    // everything the dashboard draws in one round trip
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

        // the digest stays in the cache file
        $weekly = prjWeeklyRead();
        if (is_array($weekly)) { unset($weekly['digest']); }

        prjOut(array("ok" => true,
                     "tiles" => $rollup['tiles'],
                     "pipeline" => $rollup['pipeline'],
                     "status" => $rollup['status'],
                     "load" => $rollup['load'],
                     "stages" => $GLOBALS['prjStages'],
                     "statuses" => $GLOBALS['prjStatuses'],
                     "developers" => $GLOBALS['prjDevelopers'],
                     "projects" => $out,
                     "weekly" => $weekly,
                     // true when the count fell back to everything
                     "pipenote" => ($pipe === null),
                     "pipeinfo" => $GLOBALS['prjPipeInfo'] ?? '',
                     "updated" => date('M j, Y')));

    // the assignments rows; complete=Y adds finished work
    case 'assignments':
        $includeComplete = (($_POST['complete'] ?? $_GET['complete'] ?? '') === 'Y') ? 'Y' : 'N';
        $projects = prjProjects($conn, $includeComplete);
        if ($projects === false) { prjOutFail(); }
        prjMarkPipeline($projects,
            prjPipelineCheck($projects, prjPipelineNums($conn, $projects)));

        $out = array();
        foreach ($projects as $row) { $out[] = prjRowOut($row); }

        prjOut(array("ok" => true,
                     "stages" => $GLOBALS['prjStages'],
                     "statuses" => $GLOBALS['prjStatuses'],
                     "developers" => $GLOBALS['prjDevelopers'],
                     "projects" => $out,
                     "updated" => date('M j, Y')));

    // digest, AI summary, cache; POST only
    case 'weeklygenerate':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            prjOutFail("The weekly summary is generated with a POST.");
        }
        // optional picker period; junk or oversized ranges refused
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
        // the digest stays out of the response
        unset($result['digest']);
        prjOut(array("ok" => true, "weekly" => $result));

    // the assignments view as a workbook, grouped per programmer
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

        // stale records stay out unless asked for
        if ($includeStale !== 'Y') {
            $projects = array_values(array_filter($projects, function ($row) {
                return intval($row['PIPE']) === 1 ||
                       $row['STAGE'] === 'complete' || $row['STAGE'] === 'rejected';
            }));
        }

        // tracked developers plus Unassigned only
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
            $r += 1; // blank row between programmers
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
