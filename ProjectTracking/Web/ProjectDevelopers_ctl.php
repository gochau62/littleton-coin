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
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
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
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// check users authority (10 is the minimum to use LCCOnline; 20 here because
// that is the PMS level the legacy PROJ_* pages require)
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

    $('#txtSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderGroups, 250);
    });
    $('#selPgmr').on('change', renderGroups);
    $('#chkComplete').on('change', loadProjectDevelopers);
    $('#btnDownload').on('click', function () {
        // a plain navigation so the browser handles the workbook download
        window.location = 'ProjectTracking_ajax.php?action=download&complete=' +
            ($('#chkComplete').is(':checked') ? 'Y' : 'N');
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
            fillDevFilter(resp);
            renderGroups();
        }, 'json').fail(function () {
            if (seq !== loadSeq) { return; }
            showErrorMessage('Server error - see the log.');
        });
}


function fillDevFilter(resp) {
    var current = $('#selPgmr').val() || '';
    var pgmrs = {};
    $.each(resp.projects, function (i, p) {
        pgmrs[p.pgmr === '' ? 'Unassigned' : p.pgmr] = true;
    });
    var opts = '<option value="">All developers</option>';
    $.each(Object.keys(pgmrs).sort(), function (i, n) {
        opts += '<option value="' + attr(n) + '"' +
                (n === current ? ' selected' : '') + '>' + esc(n) + '</option>';
    });
    $('#selPgmr').html(opts);
}


function stageChip(stage) {
    var label = (asgData.stages && asgData.stages[stage]) ||
                stage.charAt(0).toUpperCase() + stage.slice(1);
    return '<span class="pt-chip pt-chip-' + esc(stage) + '">' + esc(label) + '</span>';
}


function groupTable(rows) {
    var html = '<div class="pt-card" style="margin-top:.3rem"><div class="pt-tablewrap">' +
        '<table class="pt-grid"><thead><tr>' +
        '<th class="pt-num">Pjt#</th><th>SC stage</th><th>Dept</th>' +
        '<th class="pt-num">Dept prty</th><th class="pt-num">SC prty</th>' +
        '<th>Description</th><th class="pt-num">Low</th><th class="pt-num">Hi</th>' +
        '<th class="pt-num">Hours</th><th>Sched comp</th><th>Comp date</th>' +
        '</tr></thead><tbody>';
    $.each(rows, function (i, p) {
        html += '<tr>' +
            '<td class="pt-num"><a href="PROJ_ctl.php?projnum=' + p.num + '">' + p.num + '</a></td>' +
            '<td>' + stageChip(p.stage) + '</td>' +
            '<td>' + esc(p.dept) + '</td>' +
            '<td class="pt-num">' + p.deptpr + '</td>' +
            '<td class="pt-num">' + p.scpr + '</td>' +
            '<td title="' + attr(p.desc) + '">' + esc(p.desc) + '</td>' +
            '<td class="pt-num">' + p.low + '</td>' +
            '<td class="pt-num">' + p.hi + '</td>' +
            '<td class="pt-num">' + p.hours + '</td>' +
            '<td>' + esc(p.sched) + '</td>' +
            '<td>' + esc(p.comp) + '</td>' +
            '</tr>';
    });
    return html + '</tbody></table></div></div>';
}


function renderGroups() {
    if (!asgData) { return; }
    var q = $('#txtSearch').val().trim().toLowerCase();
    var fPgmr = $('#selPgmr').val();

    var rows = $.grep(asgData.projects, function (p) {
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
