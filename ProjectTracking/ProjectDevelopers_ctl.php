<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectDevelopers_ctl.php        *  -->
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
?>

<?php
    // retrieves and sets password and username; StartBlockScriptB stays up
    // top BEFORE any page output, the settled controller convention - the
    // framework stashes this address and lands the person back here after
    // sign-on, so a bookmark reopens this page instead of the home screen
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
    if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type="text/javascript">

    document.title = "Project Tracking - Projects by Developer";

    // small message helpers following the LCC convention: show the red error box with a message, or the standard not authorized message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
// check users authority - level 20, the developers group (10 is only the
// minimum to use LCCOnline)
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/ProjectTracking_model.php';

    prjActLog($user, 'OPEN', 'assignments');

    include "ProjectTracking_dsp.php";
    include "ProjectDevelopers_dsp.php";
    dspProjectDevelopers();
?>

<script>
// Projects-by-developer frontend logic: fetch the rows, group them per
// programmer like the monthly spreadsheet, filter and count client-side
var asgData = null;
var searchTimer = null;

$(document).ready(function () {
    loadProjectDevelopers();

    $('#lnkRefresh').on('click', function (e) {
        e.preventDefault();
        loadProjectDevelopers();
    });
    $('#txtSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderGroups, 250);
    });
    $('#selPgmr').on('change', renderGroups);
    $('#chkComplete').on('change', loadProjectDevelopers);
    // the rows already carry their pipe flag, so toggling stale re-renders
    // without another fetch; the developer filter refills because a hidden
    // developer should not linger in the dropdown
    $('#chkStale').on('change', function () {
        if (asgData) { fillDevFilter(); renderGroups(); }
    });
    $('#btnDownload').on('click', function () {
        // a plain navigation so the browser handles the workbook download
        window.location = 'ProjectTracking_ajax.php?action=download&complete=' +
            ($('#chkComplete').is(':checked') ? 'Y' : 'N') +
            '&stale=' + ($('#chkStale').is(':checked') ? 'Y' : 'N');
    });
});


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// esc() for attribute values (quotes escaped too), for hover titles and
// option values built into HTML strings
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
}


// each fetch takes a sequence number so a slow earlier response can never
// overwrite a newer one when the checkbox is toggled quickly
var loadSeq = 0;

function loadProjectDevelopers() {
    var seq = ++loadSeq;
    var complete = $('#chkComplete').is(':checked') ? 'Y' : 'N';
    $.post('ProjectTracking_ajax.php', { action: 'assignments', complete: complete },
        function (resp) {
            if (seq !== loadSeq) { return; }
            if (!resp || !resp.ok) {
                showErrorMessage((resp && resp.msg) ? resp.msg : 'Request failed.');
                return;
            }
            asgData = resp;
            $('#ptUpdated').text('updated ' + resp.updated);
            fillDevFilter();
            renderGroups();
        }, 'json').fail(function () {
            if (seq !== loadSeq) { return; }
            showErrorMessage('Server error - see the log.');
        });
}


// the rows currently visible under the stale rule: pipeline rows always,
// stale open rows only when the checkbox asks for them. Completed/rejected
// rows are governed by their own checkbox (the fetch), not this one
function visibleRows() {
    var showStale = $('#chkStale').is(':checked');
    return $.grep(asgData.projects, function (p) {
        if (!showStale && p.pipe === 0 &&
            p.stage !== 'complete' && p.stage !== 'rejected') { return false; }
        return true;
    });
}


function fillDevFilter() {
    var current = $('#selPgmr').val() || '';
    var pgmrs = {};
    $.each(visibleRows(), function (i, p) {
        pgmrs[p.pgmr === '' ? 'Unassigned' : p.pgmr] = true;
    });
    var opts = '<option value="">All developers</option>';
    $.each(Object.keys(pgmrs).sort(), function (i, n) {
        opts += '<option value="' + attr(n) + '"' +
                (n === current ? ' selected' : '') + '>' + esc(n) + '</option>';
    });
    $('#selPgmr').html(opts);
}


// stage chip with a compact label so it reads whole inside its column even
// beside the sidebar; the full wording rides on the hover title
var sgShort = { needsinfo: 'Needs info', awaiting: 'Awaiting', 'new': 'New' };

function stageChip(stage) {
    var full = (asgData.stages && asgData.stages[stage]) ||
               stage.charAt(0).toUpperCase() + stage.slice(1);
    return '<span class="pt-chip pt-chip-' + esc(stage) + '" title="' + attr(full) + '">' +
           esc(sgShort[stage] || full) + '</span>';
}


// per-row status dot, blank for completed/rejected rows which have none.
// The cell shows a short form so the column never clips; the full wording
// from the shared status map rides on the hover title
var stShort = { active: 'Active', waituser: 'Waiting',
                onhold: 'On hold', estnotneed: 'No estimate' };

function statusChip(status) {
    if (!status) { return ''; }
    var full = (asgData.statuses && asgData.statuses[status]) || status;
    return '<span class="pt-st pt-st-' + esc(status) + '" title="' + attr(full) + '">' +
           esc(stShort[status] || full) + '</span>';
}


function groupTable(rows) {
    // fixed column widths so every developer's table lines up with the next,
    // and every column reads whole in the space beside the sidebar - nothing
    // scrolls and nothing grows. The two priorities share one "Prty D/S"
    // cell, the estimate range shares one "Est" cell, and the completion
    // date column only appears when completed work is being shown (it is
    // blank on every open project). The description takes whatever is left
    var withComp = $('#chkComplete').is(':checked');
    var html = '<div class="pt-card" style="margin-top:.3rem"><div class="pt-tablewrap">' +
        '<table class="pt-grid">' +
        '<colgroup><col style="width:64px"><col style="width:13%">' +
        '<col style="width:12.5%"><col style="width:48px"><col style="width:58px">' +
        '<col>' +
        '<col style="width:64px"><col style="width:52px"><col style="width:84px">' +
        (withComp ? '<col style="width:84px">' : '') +
        '</colgroup><thead><tr>' +
        '<th class="pt-num">Pjt#</th><th>SC stage</th><th>Status</th><th>Dept</th>' +
        '<th class="pt-num pt-wrap" title="Department priority / SC priority">Prty D/S</th>' +
        '<th>Description</th>' +
        '<th class="pt-num" title="Estimate, low to high hours">Est</th>' +
        '<th class="pt-num">Hours</th><th class="pt-wrap">Sched comp</th>' +
        (withComp ? '<th class="pt-wrap">Comp date</th>' : '') +
        '</tr></thead><tbody>';
    $.each(rows, function (i, p) {
        var est = (p.low || p.hi) ? (p.low + '–' + p.hi) : '';
        html += '<tr>' +
            '<td class="pt-num"><a href="PROJ_ctl.php?projnum=' + p.num + '">' + p.num + '</a></td>' +
            '<td>' + stageChip(p.stage) + '</td>' +
            '<td>' + statusChip(p.status) + '</td>' +
            '<td>' + esc(p.dept) + '</td>' +
            '<td class="pt-num" title="Dept priority ' + p.deptpr +
                ', SC priority ' + p.scpr + '">' + p.deptpr + ' / ' + p.scpr + '</td>' +
            '<td title="' + attr(p.desc) + '">' + esc(p.desc) + '</td>' +
            '<td class="pt-num" title="Estimate low ' + p.low + ', high ' + p.hi +
                ' hours">' + est + '</td>' +
            '<td class="pt-num">' + p.hours + '</td>' +
            '<td>' + esc(p.sched) + '</td>' +
            (withComp ? '<td>' + esc(p.comp) + '</td>' : '') +
            '</tr>';
    });
    return html + '</tbody></table></div></div>';
}


function renderGroups() {
    if (!asgData) { return; }
    var q = $('#txtSearch').val().trim().toLowerCase();
    var fPgmr = $('#selPgmr').val();

    var rows = $.grep(visibleRows(), function (p) {
        if (q !== '' && String(p.num).indexOf(q) === -1 &&
            p.desc.toLowerCase().indexOf(q) === -1) { return false; }
        if (fPgmr !== '' && (p.pgmr === '' ? 'Unassigned' : p.pgmr) !== fPgmr) { return false; }
        return true;
    });

    // group by developer, alphabetical, with Unassigned always last - the
    // same order the Projects-by-developer spreadsheet uses
    var groups = {};
    $.each(rows, function (i, p) {
        var key = (p.pgmr === '') ? 'Unassigned' : p.pgmr;
        if (!groups[key]) { groups[key] = []; }
        groups[key].push(p);
    });
    var names = Object.keys(groups).sort();
    var un = names.indexOf('Unassigned');
    if (un !== -1) { names.splice(un, 1); names.push('Unassigned'); }

    var html = '';
    $.each(names, function (i, name) {
        var list = groups[name];
        var head = (name === 'Unassigned') ? 'Unassigned Pgmr' : name;
        html += '<div class="pt-group"><h3' +
                (name === 'Unassigned' ? ' class="pt-unassigned"' : '') + '>' +
                esc(head) + ' <span class="pt-cnt">&mdash; ' + list.length +
                ' project' + (list.length === 1 ? '' : 's') + '</span></h3>' +
                groupTable(list) + '</div>';
    });

    $('#groupList').html(html ||
        '<div class="pt-card pt-empty">No projects match.</div>');
    $('#lblCount').text(rows.length + ' project' + (rows.length === 1 ? '' : 's') +
                        ' / ' + names.length + ' group' + (names.length === 1 ? '' : 's'));
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
