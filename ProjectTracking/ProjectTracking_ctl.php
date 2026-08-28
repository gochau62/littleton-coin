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

    document.title = "Project Tracking - Overview";

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

    prjActLog($user, 'OPEN', 'dashboard');

    include "ProjectTracking_dsp.php";
    dspProjectTracking();
?>

<script>
// one fetch, then render every dashboard piece
var dashData = null;
var sortKey = 'num';
var sortDir = 1;
var searchTimer = null;

$(document).ready(function () {
    loadDashboard();

    $('#lnkRefresh').on('click', function (e) {
        e.preventDefault();
        loadDashboard();
    });
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

    // preset the calendar to the prior week, at midnight
    var t = new Date();
    calAnchor = new Date(t.getFullYear(), t.getMonth(),
                         t.getDate() - ((t.getDay() + 6) % 7) - 7);
    calView = { y: calAnchor.getFullYear(), m: calAnchor.getMonth() };
    calRender();

    $('#btnCalField').on('click', function (e) {
        e.stopPropagation();
        $('#ptCal').toggle();
    });
    $('#ptCal').on('click', function (e) { e.stopPropagation(); });
    $(document).on('click', function () { $('#ptCal').hide(); });
    $('#calPrev').on('click', function () { calNav(-1); });
    $('#calNext').on('click', function () { calNav(1); });
    $('#selWkMode').on('change', calRender);
    $('#calGrid').on('click', '.pt-cal-day', function () {
        var v = String($(this).data('ymd'));
        calAnchor = new Date(+v.substr(0, 4), +v.substr(4, 2) - 1, +v.substr(6, 2));
        calView = { y: calAnchor.getFullYear(), m: calAnchor.getMonth() };
        calRender();
        $('#ptCal').hide();
    });
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


// esc for attribute values, quotes escaped too
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
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
        renderTiles(resp.tiles, resp.pipenote);
        renderPipeline(resp.pipeline, resp.stages);
        renderLoad(resp.load);
        renderDonut(resp.status, resp.statuses);
        renderWeekly(resp.weekly);
        fillFilters(resp);
        renderTable();
    });
}


function renderTiles(t, pipenote) {
    $('#tileOpen').text(t.open);
    $('#tileNew').text(t.new);
    $('#tileReview').text(t.screview);
    $('#tileUnassigned').text(t.unassigned);

    // the pipeline story rides on the hover title only
    if (pipenote) {
        $('#statOpen').attr('title', 'Counting every open record - none of ' +
            'the PTS report extracts could be read.');
    } else if (t.stale > 0) {
        $('#statOpen').attr('title', 'Projects the PTS report extracts ' +
            'track. ' + t.stale + ' older open records sit on none of ' +
            'those reports and are not counted.');
    }
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


// pill bars per programmer, Unassigned in red
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
    // viewBox scaling keeps the chart inside the card
    var svg = '<svg width="100%" viewBox="0 0 ' + w + ' ' + h +
              '" role="img" aria-label="Open projects per programmer">';

    $.each(names, function (i, name) {
        var count = load[name];
        var y = i * rowH + 6;
        var len = Math.max(barH, Math.round(plotW * count / max));
        var color = (name === 'Unassigned') ? '#d03b3b' : '#2a78d6';
        var label = (name === 'Unassigned') ? 'Unassigned' : name;

        svg += '<g class="pt-bar" data-name="' + attr(label) + '" data-count="' + count + '">' +
               '<text x="' + (labelW - 8) + '" y="' + (y + barH - 2) +
               '" text-anchor="end" font-size="11" fill="#667085">' + esc(label) + '</text>' +
               '<rect x="' + labelW + '" y="' + y + '" width="' + len +
               '" height="' + barH + '" rx="' + (barH / 2) + '" fill="' + color + '"></rect>' +
               '<text x="' + (labelW + len + 6) + '" y="' + (y + barH - 2) +
               '" font-size="11" font-weight="600" fill="#101828">' + count + '</text>' +
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


// status donut with the total in the center
function renderDonut(status, labels) {
    // template colors: blue, amber, gray, green buckets
    var colors = { active: '#185fa5', waituser: '#eda100',
                   onhold: '#898781', estnotneed: '#1baf7a' };
    $.each(labels, function (key) {
        if (!colors[key]) { colors[key] = '#7a5af8'; }
    });
    var total = 0;
    $.each(status, function (k, c) { total += c; });

    var size = 176, stroke = 28, r = (size - stroke) / 2;
    var c = size / 2, circ = 2 * Math.PI * r;
    var segs = 0;
    $.each(status, function (k, cnt) { if (cnt > 0) { segs += 1; } });
    // gaps only between segments
    var gap = (segs > 1) ? 2 : 0;
    var svg = '<svg width="' + size + '" height="' + size +
              '" role="img" aria-label="Assigned projects by status">';

    if (total === 0) {
        svg += '<circle cx="' + c + '" cy="' + c + '" r="' + r +
               '" fill="none" stroke="#eef0f4" stroke-width="' + stroke + '"></circle>';
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
           ' font-size="22" font-weight="650" fill="#101828">' + total + '</text>' +
           '<text x="' + c + '" y="' + (c + 16) + '" text-anchor="middle"' +
           ' font-size="10" fill="#667085">assigned</text></svg>';
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


// render the writer's text as per-developer sections
function weeklyHtml(text) {
    function body(s) {
        return esc(s).replace(/\n/g, '<br>')
            .replace(/\b(\d{5,6})\b/g,
                '<a class="pt-wk-num" href="PROJ_ctl.php?projnum=$1">$1</a>');
    }
    var html = '';
    $.each(String(text).split(/\n\s*\n/), function (i, b) {
        b = String(b).trim();
        if (b === '') { return; }
        var lines = b.split('\n');
        var first = lines[0].trim().replace(/:$/, '');
        if (/^((week|month|period)\s+)?overview/i.test(first)) {
            html += '<div class="pt-wk-total">' + body(b) + '</div>';
        } else if (lines.length > 1 && /^[A-Z][A-Z0-9 ._-]{1,14}$/.test(first)) {
            html += '<div class="pt-wk-dev"><h3>' + esc(first) + '</h3><p>' +
                    body(lines.slice(1).join('\n').trim()) + '</p></div>';
        } else {
            html += '<div class="pt-wk-dev"><p>' + body(b) + '</p></div>';
        }
    });
    return html || esc(text);
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
    // just the period and the generated time
    $('#weeklyMeta').text(slashes(w.from) + ' - ' + slashes(w.to) +
        ' · generated ' + w.generated_at);
    $('#weeklyText').html(weeklyHtml(w.text));
    $('#weeklyNote').text(w.source === 'fallback' && w.note ? w.note : '');
}


// month-grid calendar; a day picks its week or month
var calView = null;    // the month the grid is showing {y, m}
var calAnchor = null;  // the day last picked
var CAL_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November',
                  'December'];
var CAL_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

function ymd(d) {
    return d.getFullYear() * 10000 + (d.getMonth() + 1) * 100 + d.getDate();
}


// resolve the anchor day per mode, all at midnight
function calPeriod() {
    var d = calAnchor, from, to;
    if ($('#selWkMode').val() === 'month') {
        from = new Date(d.getFullYear(), d.getMonth(), 1);
        to   = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    } else {
        from = new Date(d.getFullYear(), d.getMonth(),
                        d.getDate() - ((d.getDay() + 6) % 7));
        to = new Date(from.getFullYear(), from.getMonth(), from.getDate() + 6);
    }
    return { from: from, to: to };
}


function calFieldLabel(p) {
    var f = p.from, t = p.to;
    if ($('#selWkMode').val() === 'month') {
        return CAL_MONTHS[f.getMonth()] + ' ' + f.getFullYear();
    }
    return CAL_SHORT[f.getMonth()] + ' ' + f.getDate() + ' – ' +
           (f.getMonth() === t.getMonth() ? '' : CAL_SHORT[t.getMonth()] + ' ') +
           t.getDate() + ', ' + t.getFullYear();
}


function calNav(dir) {
    var m = calView.m + dir;
    calView = { y: calView.y + Math.floor(m / 12), m: ((m % 12) + 12) % 12 };
    calRender();
}


function calRender() {
    $('#calTitle').text(CAL_MONTHS[calView.m] + ' ' + calView.y);
    var p = calPeriod();
    var first = new Date(calView.y, calView.m, 1);
    var start = new Date(first);
    start.setDate(1 - first.getDay());   // back to the grid's Sunday
    var dows = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var html = '';
    for (var i = 0; i < 7; i++) {
        html += '<div class="pt-cal-dow' +
                (i === 0 || i === 6 ? ' pt-cal-we' : '') + '">' + dows[i] + '</div>';
    }
    var d = new Date(start);
    for (var c = 0; c < 42; c++) {
        html += '<div class="pt-cal-day' +
                (d.getMonth() === calView.m ? '' : ' pt-cal-out') +
                (d >= p.from && d <= p.to ? ' pt-cal-sel' : '') +
                '" data-ymd="' + ymd(d) + '">' + d.getDate() + '</div>';
        d.setDate(d.getDate() + 1);
    }
    $('#calGrid').html(html);
    $('#calLabel').text(calFieldLabel(p));
}


function generateWeekly() {
    var p = calPeriod();
    var post = { action: 'weeklygenerate', from: ymd(p.from), to: ymd(p.to) };
    var btn = $('#btnWeekly');
    btn.prop('disabled', true).text('Generating...');
    $.post('ProjectTracking_ajax.php', post, function (resp) {
        btn.prop('disabled', false).text('Generate');
        if (resp && resp.ok) { renderWeekly(resp.weekly); }
        else {
            showErrorMessage((resp && resp.msg) ? resp.msg : 'The weekly summary failed.');
        }
    }, 'json').fail(function () {
        btn.prop('disabled', false).text('Generate');
        showErrorMessage('Server error - see the log.');
    });
}


function fillFilters(resp) {
    // keep the current selections across a refresh
    var curPgmr = $('#selPgmr').val() || '';
    var curStage = $('#selStage').val() || '';

    // filter lists the tracked developers plus Unassigned
    var have = {};
    $.each(resp.projects, function (i, p) {
        have[p.pgmr === '' ? 'Unassigned' : p.pgmr] = true;
    });
    var names = $.grep((resp.developers || []).slice().sort(), function (n) {
        return have[n] === true;
    });
    if (have['Unassigned']) { names.push('Unassigned'); }
    var opts = '<option value="">All assignees</option>';
    $.each(names, function (i, n) {
        opts += '<option value="' + attr(n) + '">' + esc(n) + '</option>';
    });
    $('#selPgmr').html(opts).val(curPgmr);
    if ($('#selPgmr').val() === null) { $('#selPgmr').val(''); }

    opts = '<option value="">All stages</option>';
    $.each(resp.stages, function (key, label) {
        opts += '<option value="' + key + '">' + esc(label) + '</option>';
    });
    $('#selStage').html(opts).val(curStage);
    if ($('#selStage').val() === null) { $('#selStage').val(''); }
}


// short chip labels; full wording on hover
var sgShort = { needsinfo: 'Needs info', awaiting: 'Awaiting', 'new': 'New' };

function stageChip(stage) {
    var full = (dashData.stages && dashData.stages[stage]) ||
               stage.charAt(0).toUpperCase() + stage.slice(1);
    return '<span class="pt-chip pt-chip-' + esc(stage) + '" title="' + attr(full) + '">' +
           esc(sgShort[stage] || full) + '</span>';
}


function renderTable() {
    if (!dashData) { return; }

    // mark the active sort column with a direction arrow
    $('#tblProjects thead th').removeClass('pt-sort-asc pt-sort-desc');
    $('#tblProjects thead th[data-k="' + sortKey + '"]')
        .addClass(sortDir === 1 ? 'pt-sort-asc' : 'pt-sort-desc');

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
        if (sortKey === 'sched') {
            // raw YYYYMMDD sorts chronologically
            x = a.schedraw; y = b.schedraw;
        }
        if (sortKey === 'sub') { x = a.subraw; y = b.subraw; }
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
            '<td title="' + attr(p.desc) + '">' + esc(p.desc) + '</td>' +
            '<td>' + esc(p.sub) + '</td>' +
            '<td>' + pgmr + '</td>' +
            '<td>' + stageChip(p.stage) + '</td>' +
            '<td class="pt-num">' + p.deptpr + '</td>' +
            '<td class="pt-num">' + p.scpr + '</td>' +
            '<td class="pt-num">' + p.hours + '</td>' +
            '<td>' + esc(p.sched) + '</td>' +
            '</tr>';
    });
    $('#gridBody').html(html ||
        '<tr><td colspan="9" class="pt-empty">No projects match.</td></tr>');
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
