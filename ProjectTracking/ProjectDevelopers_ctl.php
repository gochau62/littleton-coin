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
    // StartBlock before output so bookmarks return here after sign-on
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
    if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type="text/javascript">

    document.title = "Project Tracking - Projects by Developer";

    // show the red error box with a message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
// authority level 20, the developers group
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/ProjectTracking_model.php';

    include "ProjectTracking_dsp.php";
    include "ProjectDevelopers_dsp.php";
    dspProjectDevelopers();
?>

<script>
// fetch rows, group per programmer, filter client-side
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
    $('#selPgmr, #selStatus').on('change', renderGroups);
});


// the dashboard's donut links here with ?status=<key>
function urlStatus() {
    var m = /[?&]status=([^&]*)/.exec(window.location.search);
    return m ? decodeURIComponent(m[1]) : '';
}


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// esc for attribute values, quotes escaped too
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
}


// sequence number so stale responses never win
var loadSeq = 0;

function loadProjectDevelopers() {
    var seq = ++loadSeq;
    $.post('ProjectTracking_ajax.php', { action: 'assignments' },
        function (resp) {
            if (seq !== loadSeq) { return; }
            if (!resp || !resp.ok) {
                showErrorMessage((resp && resp.msg) ? resp.msg : 'Request failed.');
                return;
            }
            asgData = resp;
            $('#ptUpdated').text('updated ' + resp.updated);
            fillDevFilter();
            fillStatusFilter();
            renderGroups();
        }, 'json').fail(function () {
            if (seq !== loadSeq) { return; }
            showErrorMessage('Server error - see the log.');
        });
}


// pipeline rows for tracked developers plus Unassigned only
function visibleRows() {
    var devs = asgData.developers || [];
    return $.grep(asgData.projects, function (p) {
        if (p.pipe === 0) { return false; }
        return p.pgmr === '' || $.inArray(p.pgmr, devs) !== -1;
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


// status choices, preselected from the URL on first load
function fillStatusFilter() {
    var current = $('#selStatus').val() || urlStatus();
    var opts = '<option value="">All statuses</option>';
    $.each(asgData.statuses || {}, function (key, label) {
        opts += '<option value="' + attr(key) + '"' +
                (key === current ? ' selected' : '') + '>' + esc(label) + '</option>';
    });
    $('#selStatus').html(opts);
}


// short chip labels; full wording on hover
var sgShort = { needsinfo: 'Needs info', awaiting: 'Awaiting', 'new': 'New' };

function stageChip(stage) {
    var full = (asgData.stages && asgData.stages[stage]) ||
               stage.charAt(0).toUpperCase() + stage.slice(1);
    return '<span class="pt-chip pt-chip-' + esc(stage) + '" title="' + attr(full) + '">' +
           esc(sgShort[stage] || full) + '</span>';
}


// status dot colored by the stored Work Status wording
function stClass(status, label) {
    if (status === 'notset') { return 'pt-st-notset'; }
    var l = label.toLowerCase();
    if (l.indexOf('hold') >= 0) { return 'pt-st-onhold'; }
    if (l.indexOf('wait') >= 0) { return 'pt-st-waituser'; }
    if (l.indexOf('activ') >= 0 || l.indexOf('work') >= 0 ||
        l.indexOf('prog') >= 0) { return 'pt-st-active'; }
    if (l.indexOf('test') >= 0 || l.indexOf('comp') >= 0 ||
        l.indexOf('done') >= 0 || l.indexOf('impl') >= 0) { return 'pt-st-estnotneed'; }
    return 'pt-st-other';
}

function statusChip(status) {
    if (!status) { return ''; }
    var full = (asgData.statuses && asgData.statuses[status]) || status;
    return '<span class="pt-st ' + stClass(status, full) + '" title="' + attr(full) + '">' +
           esc(full) + '</span>';
}


function groupTable(rows) {
    // fixed column widths so every group's table lines up
    var html = '<div class="pt-card" style="margin-top:.3rem"><div class="pt-tablewrap">' +
        '<table class="pt-grid">' +
        '<colgroup><col style="width:64px"><col style="width:13%">' +
        '<col style="width:12.5%"><col style="width:48px"><col style="width:58px">' +
        '<col>' +
        '<col style="width:64px"><col style="width:56px"><col style="width:92px">' +
        '</colgroup><thead><tr>' +
        '<th class="pt-num">Pjt#</th><th>SC stage</th><th>Status</th><th>Dept</th>' +
        '<th class="pt-num pt-wrap" title="Department priority / SC priority">Prty D/S</th>' +
        '<th>Description</th>' +
        '<th class="pt-num" title="Estimate, low to high hours">Est</th>' +
        '<th class="pt-num">Hours</th><th class="pt-wrap">Sched comp</th>' +
        '</tr></thead><tbody>';
    $.each(rows, function (i, p) {
        var est = (p.low || p.hi) ? (p.low + '–' + p.hi) : '';
        // the whole row opens the project screen
        html += '<tr class="pt-rowlink" data-num="' + p.num + '">' +
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
            '</tr>';
    });
    return html + '</tbody></table></div></div>';
}


function renderGroups() {
    if (!asgData) { return; }
    var q = $('#txtSearch').val().trim().toLowerCase();
    var fPgmr = $('#selPgmr').val();

    var fStatus = $('#selStatus').val() || '';
    var rows = $.grep(visibleRows(), function (p) {
        if (q !== '' && String(p.num).indexOf(q) === -1 &&
            p.desc.toLowerCase().indexOf(q) === -1) { return false; }
        if (fPgmr !== '' && (p.pgmr === '' ? 'Unassigned' : p.pgmr) !== fPgmr) { return false; }
        if (fStatus !== '' && p.status !== fStatus) { return false; }
        return true;
    });

    // group by developer, alphabetical, Unassigned last
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
        html += '<div class="pt-group"><h3' +
                (name === 'Unassigned' ? ' class="pt-unassigned"' : '') + '>' +
                esc(name) + ' <span class="pt-cnt">&mdash; ' + list.length +
                ' project' + (list.length === 1 ? '' : 's') + '</span></h3>' +
                groupTable(list) + '</div>';
    });

    $('#groupList').html(html ||
        '<div class="pt-card pt-empty">No projects match.</div>');

    $('#groupList .pt-rowlink').on('click', function (e) {
        if ($(e.target).is('a')) { return; }
        window.location = 'PROJ_ctl.php?projnum=' + $(this).data('num');
    });
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
