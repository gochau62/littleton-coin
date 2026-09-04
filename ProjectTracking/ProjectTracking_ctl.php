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
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type="text/javascript">

    document.title = "Project Tracking";

    // show the red error box with a message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// record where the person was headed so sign-on can send them back
if ($user === '') { $_SESSION['return_after_logon'] = $_SERVER['REQUEST_URI'] ?? ''; }

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
    dspProjectTracking();
?>

<script>
// one fetch, then render every dashboard piece
var dashData = null;
// newest submitted first until a heading is clicked
var sortKey = 'sub';
var sortDir = -1;

$(document).ready(function () {
    loadDashboard();

    $('#lnkRefresh').on('click', function (e) {
        e.preventDefault();
        loadDashboard();
    });
    // the lookup filters the table and lists matching projects
    ptLookup({ rows: function () { return dashData ? dashData.projects : []; },
               after: renderTable, scroll: '#tblProjects' });
    $('#selPgmr, #selStage').on('change', function () {
        reviewOnly = false;
        renderTable();
    });
    $('#tblProjects thead th').on('click', function () {
        var k = $(this).data('k');
        if (sortKey === k) { sortDir = -sortDir; }
        else { sortKey = k; sortDir = 1; }
        renderTable();
    });
    $('#btnWeekly').on('click', generateWeekly);
    $('#selQueueDept').on('change', renderQueue);
    $('#queueMore').on('click', function (e) {
        e.preventDefault();
        queueAll = !queueAll;
        renderQueue();
    });

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


function loadDashboard() {
    postAjax({ action: 'dashboard' }, function (resp) {
        dashData = resp;
        $('#ptUpdated').text('updated ' + resp.updated);
        renderTiles(resp.tiles, resp.pipenote);
        renderPipeline(resp.pipeline, resp.stages);
        fillQueueDept();
        renderQueue();
        renderLoad(resp.load);
        renderDonut(resp.status, resp.statuses);
        renderWeekly(resp.weekly);
        fillFilters(resp);
        renderTable();
    });
}


// the Awaiting SC review tile spans two stages, so it filters on its own
var reviewOnly = false;

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

    // each tile lists what it counts in the table below
    $('.pt-stat').off('click').on('click', function () {
        var tile = $(this).data('tile');
        if (tile === 'new')             { showInTable({ stage: 'new' }); }
        else if (tile === 'review')     { showInTable({ review: true }); }
        else if (tile === 'unassigned') { showInTable({ pgmr: 'Unassigned' }); }
        else                            { showInTable({}); }
    });
}


// clicking a cell filters the table below to that stage
// the review queue: this SC cycle's uncoded projects, closest to ready first
var queueAll = false;

function queueRows() {
    var dept = $('#selQueueDept').val() || '';
    return $.grep(dashData.projects, function (p) {
        if (p.pipe === 0 || !p.fresh) { return false; }
        if (p.stage !== 'new' && p.stage !== 'awaiting' && p.stage !== 'needsinfo') { return false; }
        if (dept !== '' && p.dept !== dept) { return false; }
        return true;
    });
}

// 0 ready, 1 one item short, 2 more; the committee's NMI is work too
function queueBand(p) {
    if (p.rescode === 'NMI') { return 2; }
    var n = (p.missing || []).length;
    return n === 0 ? 0 : (n === 1 ? 1 : 2);
}

// fire first, then department priority 1-8; 0 and 9 are unranked
function queueRank(p) {
    var pr = (p.deptpr >= 1 && p.deptpr <= 8) ? p.deptpr : 99;
    return (p.fire ? 0 : 1000) + pr;
}

function fillQueueDept() {
    var cur = $('#selQueueDept').val() || '';
    var depts = {};
    $.each(dashData.projects, function (i, p) { if (p.dept) { depts[p.dept] = true; } });
    var opts = '<option value="">All departments</option>';
    $.each(Object.keys(depts).sort(), function (i, d) {
        opts += '<option value="' + attr(d) + '"' + (d === cur ? ' selected' : '') + '>' +
                esc(d) + '</option>';
    });
    $('#selQueueDept').html(opts);
}

function renderQueue() {
    if (!dashData) { return; }
    var rows = queueRows();
    rows.sort(function (a, b) {
        return queueBand(a) - queueBand(b) || queueRank(a) - queueRank(b) ||
               (b.subraw - a.subraw) || (b.num - a.num);
    });
    var c = [0, 0, 0];
    $.each(rows, function (i, p) { c[queueBand(p)] += 1; });
    var since = (dashData.window && dashData.window.from) ? 'submitted since ' +
                dashData.window.from + ' · ' : '';
    $('#queueCounts').text(since + c[0] + ' ready · ' + c[1] + ' missing one item · ' +
                           c[2] + ' need work');

    var html = '';
    $.each(rows.slice(0, queueAll ? rows.length : 15), function (i, p) {
        var need = '';
        if (p.rescode === 'NMI') {
            need += '<span class="pt-need pt-need-sc" title="The committee asked for more information">committee: more info</span>';
        } else if (!(p.missing || []).length) {
            need = '<span class="pt-need pt-need-ready">Ready</span>';
        }
        $.each(p.missing || [], function (j, m) { need += '<span class="pt-need">' + esc(m) + '</span>'; });
        html += '<tr class="pt-rowlink" data-num="' + p.num + '">' +
            '<td class="pt-num"><a href="' + projUrl(p.num) + '" target="_blank" rel="noopener">' +
                p.num + '</a>' +
                (p.fresh ? '<span class="pt-fresh" title="Submitted this SC cycle">NEW</span>' : '') +
            '</td>' +
            '<td title="' + attr(p.desc) + '">' + esc(p.desc) +
                (p.fire ? '<span class="pt-fire" title="Fire project">&#9650; fire</span>' : '') + '</td>' +
            '<td>' + esc(p.rqst) + '</td>' +
            '<td>' + esc(p.dept) + '</td>' +
            '<td>' + esc(p.sub) + '</td>' +
            '<td class="pt-num">' + ((p.deptpr >= 1 && p.deptpr <= 8) ? p.deptpr : '') + '</td>' +
            '<td>' + need + '</td></tr>';
    });
    $('#queueBody').html(html ||
        '<tr><td colspan="7" class="pt-empty">Nothing new since the last meeting.</td></tr>');

    var more = $('#queueMore');
    if (rows.length > 15) {
        more.text(queueAll ? 'Show the first 15' : 'Show all ' + rows.length).show();
    } else {
        more.hide();
    }
    $('#queueBody .pt-rowlink').on('click', function (e) {
        if ($(e.target).is('a')) { return; }
        openProj($(this).data('num'));
    });
}


function renderPipeline(pipe, stages) {
    var html = '';
    $.each(stages, function (key, label) {
        html += '<div class="pt-seg pt-seg-' + key + '" data-stage="' + key +
                '" title="Show the ' + attr(label) + ' projects">' +
                '<div class="pt-val">' + (pipe[key] || 0) + '</div>' +
                '<div class="pt-lbl">' + esc(label) + '</div></div>';
    });
    $('#pipeRow').html(html);
    $('#pipeRow .pt-seg').on('click', function () {
        showInTable({ stage: $(this).data('stage') });
    });
}


// point the project table's filters at one slice and scroll to it
function showInTable(f) {
    reviewOnly = (f.review === true);
    $('#selStage').val(f.stage !== undefined ? f.stage : '');
    $('#selPgmr').val(f.pgmr !== undefined ? f.pgmr : '');
    if ($('#selStage').val() === null) { $('#selStage').val(''); }
    if ($('#selPgmr').val() === null) { $('#selPgmr').val(''); }
    renderTable();
    var card = $('#tblProjects').closest('.pt-card');
    if (card.length) {
        $('html, body').animate({ scrollTop: card.offset().top - 12 }, 200);
    }
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

        // the row band sits behind everything: it takes the hover highlight
        // and catches the click across the whole row
        svg += '<g class="pt-bar" data-name="' + attr(label) + '" data-count="' + count +
               '"><rect class="pt-barbg" x="0" y="' + (y - 5) + '" width="' + w +
               '" height="' + (rowH - 2) + '" rx="6"></rect>' +
               '<text class="pt-barname" x="' + (labelW - 8) + '" y="' + (y + barH - 2) +
               '" text-anchor="end" font-size="11">' + esc(label) + '</text>' +
               '<rect x="' + labelW + '" y="' + y + '" width="' + len +
               '" height="' + barH + '" rx="' + (barH / 2) + '" fill="' + color + '"></rect>' +
               '<text x="' + (labelW + len + 6) + '" y="' + (y + barH - 2) +
               '" font-size="11" font-weight="600" fill="#101828">' + count + '</text>' +
               '</g>';
    });
    svg += '</svg>';
    $('#loadChart').html(svg);

    // no tooltip here - the row highlights instead, like the cards do
    $('#loadChart .pt-bar').on('click', function () {
        showInTable({ pgmr: $(this).data('name') });
    });
}


// status donut with the total in the center
function renderDonut(status, labels) {
    // color by the status wording: active blue, waiting amber, hold
    // purple, done green, unstatused grey
    var palette = ['#0ba5ec', '#d03b3b', '#1c5cab', '#b07b0e'];
    var pi = 0;
    var colors = {};
    $.each(labels, function (key, label) {
        var l = String(label).toLowerCase();
        if (key === 'notset') { colors[key] = '#06b6d4'; }
        else if (key === 'estnotneed') { colors[key] = '#1baf7a'; }
        else if (l.indexOf('hold') >= 0) { colors[key] = '#7a5af8'; }
        else if (l.indexOf('queue') >= 0) { colors[key] = '#db2777'; }
        else if (l.indexOf('wait') >= 0) { colors[key] = '#eda100'; }
        else if (l.indexOf('activ') >= 0 || l.indexOf('work') >= 0 ||
                 l.indexOf('prog') >= 0) { colors[key] = '#185fa5'; }
        else if (l.indexOf('test') >= 0 || l.indexOf('comp') >= 0 ||
                 l.indexOf('done') >= 0 || l.indexOf('impl') >= 0 ||
                 l.indexOf('needed') >= 0) { colors[key] = '#1baf7a'; }
        else { colors[key] = palette[pi % palette.length]; pi += 1; }
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
                   '" data-key="' + attr(key) +
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

    // segments and legend rows open the by-developer page on that status
    var leg = '';
    $.each(labels, function (key, label) {
        leg += '<div class="pt-legrow" data-status="' + attr(key) +
               '" title="Show the ' + attr(label) + ' projects by developer">' +
               '<span class="pt-dot" style="background:' + colors[key] + '"></span>' +
               esc(label) + '<span class="pt-cnt">' + (status[key] || 0) + '</span></div>';
    });
    $('#statusLegend').html(leg);

    function openStatus(key) {
        window.location = 'ProjectDevelopers_ctl.php?status=' + encodeURIComponent(key);
    }
    $('#statusLegend .pt-legrow').on('click', function () {
        openStatus($(this).data('status'));
    });
    // the segment lightens on hover; the legend beside it carries the counts
    $('#statusDonut .pt-arc').on('click', function () {
        openStatus($(this).data('key'));
    });
}


// render the writer's text as per-developer sections
function weeklyHtml(text) {
    function body(s) {
        return esc(s).replace(/\n/g, '<br>')
            .replace(/\b(\d{5,6})\b/g,
                '<a class="pt-wk-num" href="' + projUrl('$1') + '" target="_blank" rel="noopener">$1</a>');
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
    // the note also carries feed problems on an otherwise good run
    $('#weeklyNote').text(w.note || '');
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

function stageChip(stage, p) {
    var full = (dashData.stages && dashData.stages[stage]) ||
               stage.charAt(0).toUpperCase() + stage.slice(1);
    // hovering says which checklist items are still red
    var tip = full;
    if (p && p.missing && p.missing.length) { tip += ' - still needs: ' + p.missing.join(', '); }
    return '<span class="pt-chip pt-chip-' + esc(stage) + '" title="' + attr(tip) + '">' +
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
        if (reviewOnly && p.stage !== 'awaiting' && p.stage !== 'needsinfo') { return false; }
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
        // ties follow the direction, so newest first stays newest first
        return sortDir * (a.num - b.num);
    });

    var html = '';
    $.each(rows, function (i, p) {
        var pgmr = (p.pgmr === '')
            ? '<span class="pt-unassigned">Unassigned</span>' : esc(p.pgmr);
        html += '<tr class="pt-rowlink" data-num="' + p.num + '">' +
            '<td class="pt-num"><a href="' + projUrl(p.num) + '" target="_blank" rel="noopener">' + p.num + '</a>' +
                (p.fresh ? '<span class="pt-fresh" title="Submitted this SC cycle">NEW</span>' : '') + '</td>' +
            '<td title="' + attr(p.desc) + '">' + esc(p.desc) + '</td>' +
            '<td>' + esc(p.sub) + '</td>' +
            '<td>' + pgmr + '</td>' +
            '<td>' + stageChip(p.stage, p) + '</td>' +
            '<td class="pt-num">' + p.deptpr + '</td>' +
            '<td class="pt-num">' + p.scpr + '</td>' +
            '<td class="pt-num">' + p.hours + '</td>' +
            '<td>' + esc(p.sched) + '</td>' +
            '</tr>';
    });
    $('#gridBody').html(html ||
        '<tr><td colspan="9" class="pt-empty">No projects match.</td></tr>');

    $('#gridBody .pt-rowlink').on('click', function (e) {
        if ($(e.target).is('a')) { return; }
        openProj($(this).data('num'));
    });
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
