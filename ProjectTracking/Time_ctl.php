<?php
/*    ***************************************************  -->
<!--  * Program Name - Time_ctl.php                     *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/09/2026                         *  -->
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
    include "Time_dsp.php";
    dspTime();
?>

<script>
// the week on screen, and what came back for it
var wkAnchor = 0;
var wkData = null;
var wkNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

$(document).ready(function () {
    loadWeek();

    $('#lnkRefresh').on('click', function (e) { e.preventDefault(); loadWeek(); });
    $('#wkPrev').on('click', function () { stepWeek(-7); });
    $('#wkNext').on('click', function () { stepWeek(7); });
    $('#wkThis').on('click', function () { wkAnchor = 0; loadWeek(); });
    $('#wkAddGo').on('click', addProject);
    $('#wkAdd').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addProject(); }
    });

    // the header lookup opens a project from here too
    ptLookup({});
});


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


function wkError(msg) {
    $('#wkErr').text(msg).prop('hidden', msg === '');
}


// yyyymmdd to a Date and back, so stepping a week stays on real days
function ymdToDate(v) {
    v = String(v);
    return new Date(+v.substr(0, 4), +v.substr(4, 2) - 1, +v.substr(6, 2));
}

function dateToYmd(d) {
    var m = d.getMonth() + 1, day = d.getDate();
    return d.getFullYear() * 10000 + m * 100 + day;
}


function stepWeek(days) {
    var from = (wkData && wkData.days) ? ymdToDate(wkData.days[0]) : new Date();
    from.setDate(from.getDate() + days);
    wkAnchor = dateToYmd(from);
    loadWeek();
}


function loadWeek() {
    $.post('ProjectTracking_ajax.php', { action: 'timeweek', week: wkAnchor },
        function (resp) {
            if (!resp || !resp.ok) {
                wkError((resp && resp.msg) ? resp.msg : 'Request failed.');
                return;
            }
            wkError('');
            wkData = resp;
            $('#ptUpdated').text('updated ' + resp.updated);
            renderWeek();
        }, 'json').fail(function () {
            wkError('Server error - see the log.');
        });
}


// mm/dd from yyyymmdd
function shortDate(v) {
    v = String(v);
    return +v.substr(4, 2) + '/' + +v.substr(6, 2);
}


function renderWeek() {
    var days = wkData.days;
    var today = dateToYmd(new Date());

    $('#wkWhen').text(shortDate(days[0]) + ' - ' + shortDate(days[6]) +
                      ', ' + String(days[6]).substr(0, 4));

    var head = '<th>Project</th>';
    $.each(days, function (i, d) {
        head += '<th class="' + (i > 4 ? 'pt-wknd' : '') +
                (+d === today ? ' pt-today' : '') + '">' +
                wkNames[i] + '<br>' + shortDate(d) + '</th>';
    });
    head += '<th>Total</th>';
    $('#wkHead').html(head);

    var body = '', colTot = [0, 0, 0, 0, 0, 0, 0], grand = 0;
    $.each(wkData.rows, function (i, r) {
        body += '<tr data-num="' + r.num + '">' +
                '<td class="pt-proj"><a href="' + projUrl(r.num) +
                '" target="_blank" rel="noopener">' + r.num + '</a>' +
                '<span class="pt-pdesc">' + esc(r.desc) + '</span></td>';
        $.each(r.hours, function (d, h) {
            colTot[d] += h; grand += h;
            body += '<td class="' + (d > 4 ? 'pt-wknd' : '') + '">' +
                    '<input class="pt-hrs" type="text" inputmode="decimal" ' +
                    'data-num="' + r.num + '" data-date="' + days[d] + '" ' +
                    'data-was="' + trimNum(h) + '" value="' + trimNum(h) + '"></td>';
        });
        body += '<td class="pt-rowtot" id="rt_' + r.num + '">' + trimNum(r.total) + '</td></tr>';
    });
    $('#wkBody').html(body ||
        '<tr><td colspan="9" class="pt-empty">No projects on this week yet. ' +
        'Add one below.</td></tr>');

    var foot = '<tr><td>Day total</td>';
    $.each(colTot, function (i, t) {
        foot += '<td class="' + (i > 4 ? 'pt-wknd' : '') + '">' + trimNum(t) + '</td>';
    });
    $('#wkFoot').html(foot + '<td>' + trimNum(grand) + '</td></tr>');
    $('#wkTotal').text(trimNum(grand));

    // a cell saves when it loses focus, and only if it changed
    $('#wkBody').off('change blur', '.pt-hrs').on('blur', '.pt-hrs', saveCell);
    $('#wkBody').on('keydown', '.pt-hrs', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $(this).blur(); }
    });
}


// 8.00 reads as 8, 8.25 keeps its quarter, 0 shows blank
function trimNum(n) {
    n = Math.round(parseFloat(n || 0) * 100) / 100;
    if (!n) { return ''; }
    return String(n);
}


function saveCell() {
    var box = $(this);
    var was = String(box.data('was') || '');
    var now = box.val().trim();
    if (now === was) { return; }

    var hours = (now === '') ? 0 : parseFloat(now);
    if (isNaN(hours) || hours < 0 || hours > 24) {
        box.addClass('pt-bad');
        wkError('Hours have to be a number between 0 and 24.');
        return;
    }
    box.removeClass('pt-bad pt-ok').prop('disabled', true);

    $.post('ProjectTracking_ajax.php',
        { action: 'timesave', proj: box.data('num'), date: box.data('date'), hours: hours },
        function (resp) {
            box.prop('disabled', false);
            if (!resp || !resp.ok) {
                box.addClass('pt-bad').val(was);
                wkError((resp && resp.msg) ? resp.msg : 'The time did not save.');
                return;
            }
            wkError('');
            box.addClass('pt-ok').val(trimNum(hours)).data('was', trimNum(hours));
            setTimeout(function () { box.removeClass('pt-ok'); }, 1200);
            retotal();
        }, 'json').fail(function () {
            box.prop('disabled', false).addClass('pt-bad').val(was);
            wkError('Server error - the time may not have saved. Refresh to check.');
        });
}


// add the row, column and week totals back up from what is on screen
function retotal() {
    var colTot = [0, 0, 0, 0, 0, 0, 0], grand = 0;
    $('#wkBody tr[data-num]').each(function () {
        var rowTot = 0;
        $(this).find('.pt-hrs').each(function (i) {
            var h = parseFloat($(this).val() || 0) || 0;
            rowTot += h; colTot[i] += h; grand += h;
        });
        $('#rt_' + $(this).data('num')).text(trimNum(rowTot));
    });
    $('#wkFoot td').each(function (i) {
        if (i === 0) { return; }
        $(this).text(trimNum(i <= 7 ? colTot[i - 1] : grand));
    });
    $('#wkTotal').text(trimNum(grand));
}


function addProject() {
    var num = $('#wkAdd').val().trim();
    if (!/^\d{1,6}$/.test(num)) {
        $('#wkAddMsg').text('Enter a project number.');
        return;
    }
    $('#wkAddMsg').text('Adding...');
    $.post('ProjectTracking_ajax.php', { action: 'timeadd', num: num },
        function (resp) {
            if (!resp || !resp.ok) {
                $('#wkAddMsg').text((resp && resp.msg) ? resp.msg : 'Could not add it.');
                return;
            }
            $('#wkAddMsg').text('');
            $('#wkAdd').val('');
            loadWeek();
        }, 'json').fail(function () {
            $('#wkAddMsg').text('Server error - see the log.');
        });
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
