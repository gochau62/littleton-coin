<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_model.php        *  -->
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

$GLOBALS['prjErr'] = '';

// the model that writes the weekly summary. Priced per million tokens, and a
// week's digest is a few thousand tokens, so a weekly run costs pennies
define('PRJ_AI_MODEL', 'claude-opus-5');
define('PRJ_AI_URL', 'https://api.anthropic.com/v1/messages');

// the activity log lives in the LCCOnline_logs folder beside the PHP, like
// every other tool's log. The weekly summary cache and the API key do NOT -
// LCCOnline_logs is web-served and the Clario purge job empties it monthly,
// so anything secret or durable goes in a folder outside the htdocs tree
define('PRJ_ACT_LOG', __DIR__ . '/LCCOnline_logs/projecttracking_activity.log');
define('PRJ_DATA_DIR', '/www/seidenphp/ProjectTracking_data');
define('PRJ_KEY_FILE', '/www/seidenphp/anthropic_api.key');

// steering committee pipeline stages, in the order the dashboard shows them.
// 'complete' is not part of the pipeline but comes back from prjStage()
$GLOBALS['prjStages'] = array(
    'new'       => 'New request',
    'awaiting'  => 'Awaiting review',
    'needsinfo' => 'Needs more info',
    'parked'    => 'Parked',
    'approved'  => 'Approved',
    'rejected'  => 'Rejected',
);

// status buckets for the "Projects by status" donut (open projects only)
$GLOBALS['prjStatuses'] = array(
    'active'      => 'Active',
    'waituser'    => 'Waiting on user',
    'onhold'      => 'On hold',
    'estnotneed'  => 'Est. not needed',
);


// append one line to the activity log, with the write suppressed so a bad one
// never takes the app down, and if it still fails the reason and the line fall
// to php.log so nothing is lost
function prjActLog($user, $action, $detail = '') {
    $line = date('Y-m-d H:i:s') . ' ' .
            ($user !== '' ? $user : 'unknown') . ' ' .
            ($_SERVER['REMOTE_ADDR'] ?? '-') . ' ' .
            $action . ($detail !== '' ? ' ' . $detail : '');
    if (@file_put_contents(PRJ_ACT_LOG, $line . PHP_EOL, FILE_APPEND) === false) {
        error_log('projecttracking_activity.log write failed (' .
                  (error_get_last()['message'] ?? 'unknown reason') .
                  ') - activity: ' . $line);
    }
}


// record the real Db2 error for the caller and the log, then return false
function prjFail($where) {
    $GLOBALS['prjErr'] = $where . ': ' . db2_stmt_error() . ' ' . db2_stmt_errormsg();
    error_log('ProjectTracking ' . $GLOBALS['prjErr']);
    return false;
}


// shared runner for PRJTRK001S: prepare, bind each parameter in order,
// execute, and collect every row as an associative array
function prjFetchAll($conn, $sql, $params = array()) {
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return prjFail("prepare $sql"); }

    foreach ($params as $i => $p) {
        $GLOBALS['prjP' . $i] = $p;
        db2_bind_param($stmt, $i + 1, 'prjP' . $i, DB2_PARAM_IN);
    }
    if (!db2_execute($stmt)) { return prjFail("execute $sql"); }

    $result = array();
    while ($row = db2_fetch_assoc($stmt)) {
        $result[] = $row;
    }
    return $result;
}


// PROGRAM NAME PRJTRK001S type LIST: every non-internal project with its
// newest estimate and summed hours. $includeComplete 'Y' adds completed and
// rejected projects; the default returns open work only
function prjProjects($conn, $includeComplete = 'N') {
    $rows = prjFetchAll($conn, "CALL PRJTRK001S(?, ?, ?)",
                        array('LIST', $includeComplete === 'Y' ? 'Y' : 'N', ''));
    if ($rows === false) { return false; }

    foreach ($rows as $i => $row) {
        $rows[$i]['STAGE']  = prjStage($row);
        $rows[$i]['STATUS'] = prjStatus($row);
    }
    return $rows;
}


// PROGRAM NAME PRJTRK001S type TIME: time entries in a YYYYMMDD date range
function prjTime($conn, $from, $to) {
    return prjFetchAll($conn, "CALL PRJTRK001S(?, ?, ?)",
                       array('TIME', strval(intval($from)), strval(intval($to))));
}


// PROGRAM NAME PRJTRK001S type NOTES: project comment index rows in a range
function prjNotes($conn, $from, $to) {
    return prjFetchAll($conn, "CALL PRJTRK001S(?, ?, ?)",
                       array('NOTES', strval(intval($from)), strval(intval($to))));
}


// PROGRAM NAME PRJTRK001S type COMP: projects completed in a range
function prjCompleted($conn, $from, $to) {
    return prjFetchAll($conn, "CALL PRJTRK001S(?, ?, ?)",
                       array('COMP', strval(intval($from)), strval(intval($to))));
}


// PROGRAM NAME PRJTRK001S type PGMR: the programmer profile list
function prjProgrammers($conn) {
    return prjFetchAll($conn, "CALL PRJTRK001S(?, ?, ?)",
                       array('PGMR', '', ''));
}


/* ---------------------------------------------------------------------------
   Stage and status derivations.

   The green screen never carried a single "SC stage" column - the stage is
   implied by which fields are filled in. These two functions are the ONE
   place that mapping lives, shared by the dashboard, the assignments page,
   the Excel download and the weekly summary. If the steering committee wants
   a bucket drawn differently, change it here and every screen follows.
--------------------------------------------------------------------------- */

// steering committee stage for one project row:
//   rejected   - resolution code REJ
//   complete   - actual completion date on file
//   approved   - the steering committee assigned it a priority
//   new        - no estimate on file yet (just submitted)
//   parked     - estimated, but the department priority was zeroed out
//   needsinfo  - estimated, but no scheduled completion date yet
//   awaiting   - estimated and scheduled, waiting on the committee
function prjStage($row) {
    if (trim($row['PJRESCOD']) === 'REJ') { return 'rejected'; }
    if (intval($row['PJCOMPDATE']) > 0)   { return 'complete'; }
    if (intval($row['PJSCPR']) > 0)       { return 'approved'; }
    if (trim($row['PJHASEST']) !== 'Y')   { return 'new'; }
    if (intval($row['PJDEPTPR']) <= 0)    { return 'parked'; }
    if (intval($row['PJSCHDATE']) <= 0)   { return 'needsinfo'; }
    return 'awaiting';
}


// status bucket for the donut. Only open projects get a bucket:
//   estnotneed - fire projects (type FR) go straight to work, no estimate
//   onhold     - department priority zeroed out
//   active     - scheduled completion date on file ("in-play")
//   waituser   - everything else is waiting on the requestor or committee
function prjStatus($row) {
    if (trim($row['PJRESCOD']) === 'REJ') { return ''; }
    if (intval($row['PJCOMPDATE']) > 0)   { return ''; }
    if (trim($row['PJTYPE']) === 'FR')    { return 'estnotneed'; }
    if (intval($row['PJDEPTPR']) <= 0 && trim($row['PJHASEST']) === 'Y') { return 'onhold'; }
    if (intval($row['PJSCHDATE']) > 0)    { return 'active'; }
    return 'waituser';
}


// roll one project list up into everything the dashboard draws: the stat
// tiles, the pipeline counts, the per-programmer load and the status donut
function prjDashboardRollup($projects) {
    $tiles = array('open' => 0, 'new' => 0, 'screview' => 0, 'unassigned' => 0);
    $pipeline = array_fill_keys(array_keys($GLOBALS['prjStages']), 0);
    $status = array_fill_keys(array_keys($GLOBALS['prjStatuses']), 0);
    $load = array();

    foreach ($projects as $row) {
        $stage = $row['STAGE'];
        $open = ($stage !== 'complete' && $stage !== 'rejected');

        if (isset($pipeline[$stage])) { $pipeline[$stage] += 1; }
        if ($row['STATUS'] !== '' && isset($status[$row['STATUS']])) {
            $status[$row['STATUS']] += 1;
        }

        if ($open) {
            $tiles['open'] += 1;
            if ($stage === 'new') { $tiles['new'] += 1; }
            if ($stage === 'awaiting' || $stage === 'needsinfo') { $tiles['screview'] += 1; }

            $pgmr = trim($row['PJPGMR']);
            if ($pgmr === '') {
                $tiles['unassigned'] += 1;
                $pgmr = 'Unassigned';
            }
            if (!isset($load[$pgmr])) { $load[$pgmr] = 0; }
            $load[$pgmr] += 1;
        }
    }

    // busiest programmer first, with the Unassigned bucket always last so it
    // reads as the red call-to-action bar the layout template shows
    $unassigned = $load['Unassigned'] ?? 0;
    unset($load['Unassigned']);
    arsort($load);
    if ($unassigned > 0) { $load['Unassigned'] = $unassigned; }

    return array('tiles' => $tiles, 'pipeline' => $pipeline,
                 'status' => $status, 'load' => $load);
}


/* ---------------------------------------------------------------------------
   Weekly activity digest + AI summary.
--------------------------------------------------------------------------- */

// the reporting week is the most recently finished Monday..Sunday
function prjWeekRange() {
    $lastSunday = strtotime('last sunday');
    $monday = strtotime('-6 days', $lastSunday);
    return array(intval(date('Ymd', $monday)), intval(date('Ymd', $lastSunday)));
}


// gather what each developer did in the range: hours by project, comments by
// type, and completions. Everything the summary says comes from this digest,
// so the model has nothing to invent
function prjWeeklyDigest($conn, $from, $to) {
    $time = prjTime($conn, $from, $to);
    if ($time === false) { return false; }
    $notes = prjNotes($conn, $from, $to);
    if ($notes === false) { return false; }
    $completed = prjCompleted($conn, $from, $to);
    if ($completed === false) { return false; }

    // project descriptions for the numbers that show up in the digest
    $projects = prjProjects($conn, 'Y');
    if ($projects === false) { return false; }
    $desc = array();
    foreach ($projects as $p) {
        $desc[intval($p['PJNUM'])] = trim($p['PJDESC']);
    }

    $dev = array();
    $blank = array('hours_total' => 0, 'projects' => array(),
                   'comments' => array(), 'completed' => array());

    foreach ($time as $t) {
        $user = trim($t['TMUSER']);
        if ($user === '') { continue; }
        $num = intval($t['TMPROJ']);
        if (!isset($dev[$user])) { $dev[$user] = $blank; }
        if (!isset($dev[$user]['projects'][$num])) {
            $dev[$user]['projects'][$num] = array(
                'desc' => $desc[$num] ?? '', 'hours' => 0);
        }
        $dev[$user]['projects'][$num]['hours'] += floatval($t['TMHOURS']);
        $dev[$user]['hours_total'] += floatval($t['TMHOURS']);
    }

    foreach ($notes as $n) {
        $user = trim($n['NTUSER']);
        if ($user === '') { continue; }
        if (!isset($dev[$user])) { $dev[$user] = $blank; }
        $type = trim($n['NTTYPE']);
        if (!isset($dev[$user]['comments'][$type])) {
            $dev[$user]['comments'][$type] = 0;
        }
        $dev[$user]['comments'][$type] += 1;
    }

    foreach ($completed as $c) {
        $user = trim($c['PJPGMR']);
        if ($user === '') { $user = 'Unassigned'; }
        if (!isset($dev[$user])) { $dev[$user] = $blank; }
        $dev[$user]['completed'][] = array(
            'num' => intval($c['PJNUM']), 'desc' => trim($c['PJDESC']));
    }

    ksort($dev);
    return array('from' => $from, 'to' => $to, 'developers' => $dev);
}


// where the cached weekly summary lives; one file per week ending date, plus
// a "latest" copy the dashboard reads without knowing the date. The folder
// sits outside the served docroot - the digest is per-developer activity
// data and must only reach the browser through the authorized endpoint
function prjWeeklyPath($weekEnd) {
    return PRJ_DATA_DIR . '/projecttracking_weekly_' . intval($weekEnd) . '.json';
}


function prjWeeklyLatestPath() {
    return PRJ_DATA_DIR . '/projecttracking_weekly_latest.json';
}


function prjWeeklyRead() {
    $raw = @file_get_contents(prjWeeklyLatestPath());
    if ($raw === false) { return null; }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}


function prjWeeklyWrite($summary) {
    if (!is_dir(PRJ_DATA_DIR)) { @mkdir(PRJ_DATA_DIR, 0770, true); }
    $json = json_encode($summary);
    @file_put_contents(prjWeeklyPath($summary['to']), $json);
    if (@file_put_contents(prjWeeklyLatestPath(), $json) === false) {
        error_log('ProjectTracking: weekly summary cache write failed (' .
                  (error_get_last()['message'] ?? 'unknown reason') . ')');
    }
}


// the deterministic summary used when no API key is configured or the API
// call fails - plain per-developer lines straight from the digest
function prjFallbackSummary($digest) {
    $lines = array();
    foreach ($digest['developers'] as $user => $d) {
        $lines[] = $user . ':';
        if ($d['hours_total'] > 0) {
            $projLines = array();
            foreach ($d['projects'] as $num => $p) {
                $projLines[] = $num . ' ' . $p['desc'] . ' (' .
                               rtrim(rtrim(number_format($p['hours'], 2), '0'), '.') . ' hrs)';
            }
            $lines[] = '  Time: ' .
                       rtrim(rtrim(number_format($d['hours_total'], 2), '0'), '.') .
                       ' hours - ' . implode('; ', $projLines);
        }
        if (!empty($d['comments'])) {
            $cmt = array();
            foreach ($d['comments'] as $type => $cnt) { $cmt[] = $cnt . ' ' . $type; }
            $lines[] = '  Comments: ' . implode(', ', $cmt);
        }
        foreach ($d['completed'] as $c) {
            $lines[] = '  Completed: ' . $c['num'] . ' ' . $c['desc'];
        }
        $lines[] = '';
    }
    if (empty($digest['developers'])) {
        $lines[] = 'No time, comments, or completions were recorded this week.';
    }
    return trim(implode("\n", $lines));
}


// the API key comes from the ANTHROPIC_API_KEY environment variable, or from
// a one-line key file OUTSIDE the htdocs tree so it can never be fetched over
// HTTP - never from source, after what happened with PROJ_sendChgNotif
function prjApiKey() {
    $key = trim(strval(getenv('ANTHROPIC_API_KEY')));
    if ($key !== '') { return $key; }
    if (is_readable(PRJ_KEY_FILE)) {
        return trim(strval(file_get_contents(PRJ_KEY_FILE)));
    }
    return '';
}


// one Messages API call turning the digest into the short per-developer
// write-up. Returns array(ok, text-or-error, model-used)
function prjClaudeSummary($digest) {
    $key = prjApiKey();
    if ($key === '') {
        return array(false, 'No Anthropic API key is configured - see the ' .
                     'weekly summary section of the technical reference.', '');
    }

    $system =
        "You write the weekly IT project status summary for Littleton Coin " .
        "Company's project tracking system. You are given a JSON digest of one " .
        "week's activity: per developer, the hours they logged by project, the " .
        "comments they wrote by type (ComntIT = IT comment, ComntGen = general, " .
        "ComntSC = steering committee, ComntPB = payback, Descrip = description), " .
        "and the projects completed.\n\n" .
        "Write a brief summary a manager can skim in a minute:\n" .
        "- One short section per developer, the developer's profile name as the " .
        "heading line, then 1-3 plain sentences covering where their time went, " .
        "notable comment activity, and anything completed.\n" .
        "- Refer to projects as 'number - description'.\n" .
        "- Only state what is in the digest. Never invent projects, hours, or " .
        "activity. If a developer has very little activity, one sentence is fine.\n" .
        "- Close with a one-sentence week overview (total hours, completions).\n" .
        "- Plain text only - no markdown symbols, no tables.";

    $body = json_encode(array(
        'model' => PRJ_AI_MODEL,
        'max_tokens' => 2000,
        'system' => $system,
        'messages' => array(array(
            'role' => 'user',
            'content' => 'Week ' . $digest['from'] . ' through ' . $digest['to'] .
                         ". Digest:\n" . json_encode($digest),
        )),
    ));

    $ch = curl_init(PRJ_AI_URL);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
        ),
    ));
    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
    curl_close($ch);

    if ($raw === false) {
        return array(false, 'Anthropic API request failed: ' . $curlErr, '');
    }
    $resp = json_decode($raw, true);
    if ($httpCode !== 200) {
        $msg = $resp['error']['message'] ?? ('HTTP ' . $httpCode);
        return array(false, 'Anthropic API error: ' . $msg, '');
    }
    if (($resp['stop_reason'] ?? '') === 'refusal') {
        return array(false, 'The model declined this request.', '');
    }

    $text = '';
    foreach ($resp['content'] ?? array() as $block) {
        if (($block['type'] ?? '') === 'text') { $text .= $block['text']; }
    }
    if (trim($text) === '') {
        return array(false, 'The API returned an empty summary.', '');
    }
    return array(true, trim($text), strval($resp['model'] ?? PRJ_AI_MODEL));
}


// build the digest for the last finished week (or an explicit range), get the
// AI write-up - falling back to the deterministic one - and cache the result
function prjGenerateWeekly($conn, $user, $from = 0, $to = 0) {
    if ($from <= 0 || $to <= 0) {
        list($from, $to) = prjWeekRange();
    }

    $digest = prjWeeklyDigest($conn, $from, $to);
    if ($digest === false) {
        return array(false, $GLOBALS['prjErr'] ?: 'The weekly digest reads failed.');
    }

    list($ok, $text, $model) = prjClaudeSummary($digest);
    $source = 'ai';
    $note = '';
    if (!$ok) {
        $note = $text;
        $text = prjFallbackSummary($digest);
        $source = 'fallback';
        $model = '';
    }

    $summary = array(
        'from' => $from,
        'to' => $to,
        'generated_at' => date('Y-m-d H:i:s'),
        'generated_by' => $user,
        'source' => $source,
        'model' => $model,
        'note' => $note,
        'text' => $text,
        'digest' => $digest,
    );
    prjWeeklyWrite($summary);
    prjActLog($user, 'WEEKLY', $from . '-' . $to . ' via ' . $source .
              ($note !== '' ? ' (' . $note . ')' : ''));
    return array(true, $summary);
}
?>
