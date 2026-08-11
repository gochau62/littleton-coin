<?php
/*    ***************************************************  -->
<!--  * Program Name - ProjectTracking_ctl.php          *  -->
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
<!--  * Project   -                                     *  -->
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

    document.title = "Project Tracking - Overview";

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

    prjActLog($user, 'OPEN', 'dashboard');

    include "ProjectTracking_dsp.php";
    dspProjectTracking();
?>

<script>
// Project Tracking dashboard frontend logic: one fetch, then render the
// tiles, pipeline, charts, weekly summary and project table from it
var dashData = null;
var sortKey = 'num';
var sortDir = 1;
var searchTimer = null;

$(document).ready(function () {
    loadDashboard();

    $('#txtSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(renderTable, 250);
    });
    $('#selPgmr, #selStage').on('change', renderTable);
    $('#tblProjects thead th').on('click', function () {
        var k = $(this).data('k');
        if (sortKey === k) { sortDir = -sortDir; }
        else { sortKey = k; sortDir = 1; }
        renderTable();
    });
    $('#btnWeekly').on('click', generateWeekly);
});


// ajax and shared helpers

function postAjax(data, onOk) {
    $.post('ProjectTracking_ajax.php', data, function (resp) {
        if (resp && resp.ok) { onOk(resp); }
        else {
            showErrorMessage((resp && resp.msg) ? resp.msg : 'Request failed.');
        }
    }, 'json').fail(function () {
        showErrorMessage('Server error - see the log.');
    });
}


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// chart hover tooltip helpers - one shared floating div
function tipShow(evt, html) {
    $('#ptTip').html(html).css({
        display: 'block',
        left: (evt.clientX + 14) + 'px',
        top: (evt.clientY + 14) + 'px'
    });
}


function tipHide() { $('#ptTip').hide(); }


function loadDashboard() {
    postAjax({ action: 'dashboard' }, function (resp) {
        dashData = resp;
        $('#ptUpdated').text('updated ' + resp.updated);
        renderTiles(resp.tiles);
        renderPipeline(resp.pipeline, resp.stages);
        renderLoad(resp.load);
        renderDonut(resp.status, resp.statuses);
        renderWeekly(resp.weekly);
        fillFilters(resp);
        renderTable();
    });
}


function renderTiles(t) {
    $('#tileOpen').text(t.open);
    $('#tileNew').text(t.new);
    $('#tileReview').text(t.screview);
    $('#tileUnassigned').text(t.unassigned);
}


function renderPipeline(pipe, stages) {
    var html = '';
    $.each(stages, function (key, label) {
        html += '<div class="pt-seg pt-seg-' + key + '">' +
                '<div class="pt-val">' + (pipe[key] || 0) + '</div>' +
                '<div class="pt-lbl">' + esc(label) + '</div></div>';
    });
    $('#pipeRow').html(html);
}


// horizontal pill bars, one per programmer, count labels at the bar ends.
// Blue for assigned programmers, red for the Unassigned bucket
function renderLoad(load) {
    var names = Object.keys(load);
    if (names.length === 0) {
        $('#loadChart').html('<div class="pt-empty">No open projects.</div>');
        return;
    }
    var max = 0;
    $.each(load, function (n, c) { if (c > max) { max = c; } });

    var labelW = 110, valueW = 34, rowH = 26, barH = 12, w = 560;
    var plotW = w - labelW - valueW;
    var h = names.length * rowH + 6;
    var svg = '<svg width="100%" viewBox="0 0 ' + w + ' ' + h +
              '" style="min-width:420px" role="img" aria-label="Open projects per programmer">';

    $.each(names, function (i, name) {
        var count = load[name];
        var y = i * rowH + 6;
        var len = Math.max(barH, Math.round(plotW * count / max));
        var color = (name === 'Unassigned') ? '#d03b3b' : '#2a78d6';
        var label = (name === 'Unassigned') ? 'Unassigned' : name;

        svg += '<g class="pt-bar" data-name="' + esc(label) + '" data-count="' + count + '">' +
               '<text x="' + (labelW - 8) + '" y="' + (y + barH - 2) +
               '" text-anchor="end" font-size="11" fill="#5f6570">' + esc(label) + '</text>' +
               '<rect x="' + labelW + '" y="' + y + '" width="' + len +
               '" height="' + barH + '" rx="' + (barH / 2) + '" fill="' + color + '"></rect>' +
               '<text x="' + (labelW + len + 6) + '" y="' + (y + barH - 2) +
               '" font-size="11" fill="#1c1e21">' + count + '</text>' +
               '<rect x="0" y="' + (y - 4) + '" width="' + w + '" height="' + rowH +
               '" fill="transparent"></rect>' +
               '</g>';
    });
    svg += '</svg>';
    $('#loadChart').html(svg);

    $('#loadChart .pt-bar').on('mousemove', function (evt) {
        var n = $(this).data('name'), c = $(this).data('count');
        tipShow(evt, esc(n) + ' &mdash; ' + c + ' open project' + (c === 1 ? '' : 's'));
    }).on('mouseleave', tipHide);
}


// status donut: stroke-drawn segments with small gaps, total in the center,
// legend alongside. Gray is deliberate for On hold - it reads as inactive
function renderDonut(status, labels) {
    var colors = { active: '#2a78d6', waituser: '#eb6834',
                   onhold: '#898781', estnotneed: '#008300' };
    var total = 0;
    $.each(status, function (k, c) { total += c; });

    var size = 150, stroke = 26, r = (size - stroke) / 2;
    var c = size / 2, circ = 2 * Math.PI * r, gap = 2;
    var svg = '<svg width="' + size + '" height="' + size +
              '" role="img" aria-label="Open projects by status">';

    if (total === 0) {
        svg += '<circle cx="' + c + '" cy="' + c + '" r="' + r +
               '" fill="none" stroke="#eef0f2" stroke-width="' + stroke + '"></circle>';
    } else {
        var offset = 0;
        $.each(labels, function (key, label) {
            var cnt = status[key] || 0;
            if (cnt === 0) { return; }
            var len = circ * cnt / total;
            var dash = Math.max(len - gap, 0.5);
            var pct = Math.round(100 * cnt / total);
            svg += '<circle class="pt-arc" data-label="' + esc(label) +
                   '" data-count="' + cnt + '" data-pct="' + pct + '"' +
                   ' cx="' + c + '" cy="' + c + '" r="' + r + '" fill="none"' +
                   ' stroke="' + colors[key] + '" stroke-width="' + stroke + '"' +
                   ' stroke-dasharray="' + dash + ' ' + (circ - dash) + '"' +
                   ' stroke-dashoffset="' + (-offset) + '"' +
                   ' transform="rotate(-90 ' + c + ' ' + c + ')"></circle>';
            offset += len;
        });
    }
    svg += '<text x="' + c + '" y="' + (c - 2) + '" text-anchor="middle"' +
           ' font-size="22" font-weight="650" fill="#1c1e21">' + total + '</text>' +
           '<text x="' + c + '" y="' + (c + 16) + '" text-anchor="middle"' +
           ' font-size="10" fill="#5f6570">open</text></svg>';
    $('#statusDonut').html(svg);

    var leg = '';
    $.each(labels, function (key, label) {
        leg += '<div><span class="pt-dot" style="background:' + colors[key] + '"></span>' +
               esc(label) + '<span class="pt-cnt">' + (status[key] || 0) + '</span></div>';
    });
    $('#statusLegend').html(leg);

    $('#statusDonut .pt-arc').on('mousemove', function (evt) {
        tipShow(evt, esc($(this).data('label')) + ' &mdash; ' +
                $(this).data('count') + ' (' + $(this).data('pct') + '%)');
    }).on('mouseleave', tipHide);
}


function renderWeekly(w) {
    if (!w || !w.text) {
        $('#weeklyMeta').text('');
        $('#weeklyNote').text('');
        return;
    }
    function slashes(d) {
        var s = String(d);
        return s.length === 8
            ? s.substr(4, 2) + '/' + s.substr(6, 2) + '/' + s.substr(0, 4) : s;
    }
    var how = (w.source === 'ai')
        ? 'written by ' + (w.model || 'Claude')
        : 'plain rollup (no AI summary available)';
    $('#weeklyMeta').text('Week ' + slashes(w.from) + ' - ' + slashes(w.to) +
        ' · generated ' + w.generated_at + ' by ' + w.generated_by +
        ' · ' + how);
    $('#weeklyText').text(w.text);
    $('#weeklyNote').text(w.source === 'fallback' && w.note ? w.note : '');
}


function generateWeekly() {
    var btn = $('#btnWeekly');
    btn.prop('disabled', true).text('Generating...');
    $.post('ProjectTracking_ajax.php', { action: 'weeklygenerate' }, function (resp) {
        btn.prop('disabled', false).text('Generate for last week');
        if (resp && resp.ok) { renderWeekly(resp.weekly); }
        else {
            showErrorMessage((resp && resp.msg) ? resp.msg : 'The weekly summary failed.');
        }
    }, 'json').fail(function () {
        btn.prop('disabled', false).text('Generate for last week');
        showErrorMessage('Server error - see the log.');
    });
}


function fillFilters(resp) {
    var pgmrs = {};
    $.each(resp.projects, function (i, p) {
        pgmrs[p.pgmr === '' ? 'Unassigned' : p.pgmr] = true;
    });
    var opts = '<option value="">All assignees</option>';
    $.each(Object.keys(pgmrs).sort(), function (i, n) {
        opts += '<option value="' + esc(n) + '">' + esc(n) + '</option>';
    });
    $('#selPgmr').html(opts);

    opts = '<option value="">All stages</option>';
    $.each(resp.stages, function (key, label) {
        opts += '<option value="' + key + '">' + esc(label) + '</option>';
    });
    $('#selStage').html(opts);
}


function stageChip(stage) {
    var label = (dashData.stages && dashData.stages[stage]) ||
                stage.charAt(0).toUpperCase() + stage.slice(1);
    return '<span class="pt-chip pt-chip-' + esc(stage) + '">' + esc(label) + '</span>';
}


function renderTable() {
    if (!dashData) { return; }
    var q = $('#txtSearch').val().trim().toLowerCase();
    var fPgmr = $('#selPgmr').val();
    var fStage = $('#selStage').val();
    var stageOrder = Object.keys(dashData.stages);

    var rows = $.grep(dashData.projects, function (p) {
        if (q !== '' && String(p.num).indexOf(q) === -1 &&
            p.desc.toLowerCase().indexOf(q) === -1) { return false; }
        if (fPgmr !== '' && (p.pgmr === '' ? 'Unassigned' : p.pgmr) !== fPgmr) { return false; }
        if (fStage !== '' && p.stage !== fStage) { return false; }
        return true;
    });

    rows.sort(function (a, b) {
        var x = a[sortKey], y = b[sortKey];
        if (sortKey === 'stage') {
            x = stageOrder.indexOf(x); y = stageOrder.indexOf(y);
        }
        if (typeof x === 'string') { x = x.toLowerCase(); y = String(y).toLowerCase(); }
        if (x < y) { return -sortDir; }
        if (x > y) { return sortDir; }
        return a.num - b.num;
    });

    var html = '';
    $.each(rows, function (i, p) {
        var pgmr = (p.pgmr === '')
            ? '<span class="pt-unassigned">Unassigned</span>' : esc(p.pgmr);
        html += '<tr>' +
            '<td class="pt-num"><a href="PROJ_ctl.php?projnum=' + p.num + '">' + p.num + '</a></td>' +
            '<td title="' + esc(p.desc) + '">' + esc(p.desc) + '</td>' +
            '<td>' + pgmr + '</td>' +
            '<td>' + stageChip(p.stage) + '</td>' +
            '<td class="pt-num">' + p.deptpr + '</td>' +
            '<td class="pt-num">' + p.scpr + '</td>' +
            '<td class="pt-num">' + p.hours + '</td>' +
            '<td>' + esc(p.sched) + '</td>' +
            '</tr>';
    });
    $('#gridBody').html(html ||
        '<tr><td colspan="8" class="pt-empty">No projects match.</td></tr>');
    $('#lblCount').text(rows.length + ' project' + (rows.length === 1 ? '' : 's'));
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
