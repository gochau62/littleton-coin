<?php
/*    ***************************************************  -->
<!--  * Program Name - TimePayment_ctl.php              *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/30/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 230077                              *  -->
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
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Time Payment Items Maintenance";
</script>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// keep the address for the sign on to land the person back here after signing in, which is what makes a bookmark work
if ($user === '') { $_SESSION['return_after_logon'] = $_SERVER['REQUEST_URI'] ?? ''; }

// check users authority - 10 is the LCCOnline minimum, 50 here because the upload writes a production file
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    // the framework's standard refusal page, the same call the older LCC tools make
    showNotAuthorized();
} else {

    require_once __DIR__ . '/TimePayment_model.php';

    tpyActLog($user, 'OPEN');

    include "TimePayment_dsp.php";
    dspTimePayment();
?>

<script>
// Item Time Payment upload frontend logic (upload, results report, review grid)
var gridToday = 0;
var gridRows = [];
// which records the grid shows: all (default), active only, or expired only
var gridShow = 'all';
// the grid opens on expiration date, newest first, so the offers still running are at the top
var gridSort = { key: 'TPEXDATE', dir: -1 };
// brief pause before the search runs, so it does not fire on every keystroke
var gridSearchTimer = null;

$(document).ready(function () {
    loadGrid();

    $('#btnUpload').on('click', uploadFile);
    // the template download is a plain navigation so the browser handles the file
    $('#btnTemplate').on('click', function () {
        window.location = 'TimePayment_ajax.php?action=template';
    });
    $('#txtSearch').on('input', function () {
        clearTimeout(gridSearchTimer);
        gridSearchTimer = setTimeout(loadGrid, 300);
    });
    // the Show list and the sort work on the loaded rows, no server round trip
    $('#selShow').on('change', function () {
        gridShow = $(this).val();
        renderGrid();
    });
    // click a column header to sort by it; click again to flip direction
    $('#tblGrid thead').on('click', 'th[data-sortkey]', function () {
        var key = $(this).data('sortkey');
        if (gridSort.key === key) { gridSort.dir = -gridSort.dir; }
        else { gridSort.key = key; gridSort.dir = 1; }
        renderGrid();
    });
});


// ajax and shared helpers

function postAjax(data, onOk) {
    $.post('TimePayment_ajax.php', data, function (resp) {
        if (resp && resp.ok) { onOk(resp); }
        else {
            swal('Error', (resp && resp.msg) ? resp.msg : 'Request failed.', 'error');
        }
    }, 'json').fail(function () {
        swal('Error', 'Server error - see the log.', 'error');
    });
}


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// esc() for attribute values (quotes escaped too), for the hover titles on clipped cells
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
}


// stored dates are an 8 digit yyyymmdd number, shown the way the green screen shows them: 12/28/26, 2/01/27
function fmtDate(dec) {
    var s = String(dec);
    if (s.length !== 8 || s === '00000000') { return ''; }
    return parseInt(s.substr(4, 2), 10) + '/' + s.substr(6, 2) + '/' + s.substr(2, 2);
}


// upload

function uploadFile() {
    var fileInput = $('#tpFile').get(0);
    if (fileInput.files.length === 0) {
        swal('No file attached',
             'Attach a spreadsheet with, in order: Item #, Source Code, Plan (optional), Expiration Date - then try again.',
             'error');
        return;
    }
    if (!/(\.xlsx|\.xls|\.csv)$/i.test(fileInput.value)) {
        swal('Incorrect file type',
             'Only .xlsx, .xls and .csv files are supported - attach one of those and try again.',
             'error');
        return;
    }

    var formData = new FormData($('#tpForm')[0]);
    $('#btnUpload').prop('disabled', true);

    $.ajax({
        url: 'TimePayment_ajax.php',
        data: formData,
        contentType: false,
        processData: false,
        type: 'POST',
        dataType: 'json',
        success: function (resp) {
            $('#btnUpload').prop('disabled', false);
            if (!resp || !resp.ok) {
                swal('Upload failed', (resp && resp.msg) ? resp.msg : 'Request failed.', 'error');
                return;
            }
            renderResults(resp);
            loadGrid();
            if (resp.errors === 0) {
                swal('All rows loaded',
                     resp.added + ' added, ' + resp.updated + ' updated.',
                     'success');
            } else {
                swal('Loaded with exceptions',
                     resp.added + ' added, ' + resp.updated + ' updated, ' +
                     resp.errors + ' skipped.' +
                     (resp.emailed ? '\nThe exception report was e-mailed to you.' : ''),
                     'warning');
            }
        },
        error: function () {
            $('#btnUpload').prop('disabled', false);
            swal('Upload failed', 'Server error - see the log.', 'error');
        }
    });
}


function renderResults(resp) {
    var summary = '<b>' + resp.added + '</b> added, <b>' + resp.updated + '</b> updated' +
                  (resp.errors > 0
                      ? ', <span class="tp-bad"><b>' + resp.errors + '</b> skipped</span>' +
                        (resp.emailed ? ' <span class="tp-mailed">(e-mailed to you)</span>' : '')
                      : '');
    $('#resSummary').html(summary);

    var html = '';
    $.each(resp.report || [], function (i, r) {
        html += '<tr>' +
            '<td>' + esc(r.row) + '</td>' +
            '<td class="tp-mono">' + esc(r.item) + '</td>' +
            '<td class="tp-mono">' + esc(r.src) + '</td>' +
            '<td class="tp-mono">' + esc(r.plan) + '</td>' +
            '<td>' + esc(r.exp) + '</td>' +
            '<td><span class="tp-st tp-st-' + esc(r.status) + '">' + esc(r.status) + '</span></td>' +
            '<td class="tp-msg" title="' + attr(r.msg) + '">' + esc(r.msg) + '</td>' +
            '</tr>';
    });
    $('#resBody').html(html ||
        '<tr><td colspan="7" class="tp-empty">The spreadsheet had no data rows.</td></tr>');
    $('#resultsBlock').prop('hidden', false);
}


// review grid

function loadGrid() {
    postAjax({ action: 'list', q: $('#txtSearch').val().trim() }, function (resp) {
        gridToday = resp.today || 0;
        gridRows = resp.rows || [];
        renderGrid();
    });
}


function isExpired(r) {
    return gridToday > 0 && parseInt(r.TPEXDATE, 10) < gridToday;
}


// header sort: the date column compares as a number, everything else ignores case
function gridCompare(a, b) {
    var k = gridSort.key, av, bv;
    if (k === 'TPEXDATE') { av = +a.TPEXDATE || 0; bv = +b.TPEXDATE || 0; }
    else {
        av = String(a[k] == null ? '' : a[k]).toLowerCase();
        bv = String(b[k] == null ? '' : b[k]).toLowerCase();
    }
    if (av < bv) { return -gridSort.dir; }
    if (av > bv) { return gridSort.dir; }
    return 0;
}


function renderGrid() {
    // Show narrows to active or expired; the sort works on a copy so the server order is kept underneath
    var rows = gridRows;
    if (gridShow !== 'all') {
        rows = $.grep(gridRows, function (r) {
            return gridShow === 'expired' ? isExpired(r) : !isExpired(r);
        });
    }
    rows = rows.slice().sort(gridCompare);

    var html = '';
    $.each(rows, function (i, r) {
        // an expired record stays visible but reads as done with; hovering the item shows its item master description
        html += '<tr' + (isExpired(r) ? ' class="tp-expired"' : '') + '>' +
            '<td class="tp-mono" title="' + attr(r.TPDESC) + '">' + esc(r.TPITEM) + '</td>' +
            '<td class="tp-mono">' + esc(r.TPSRCD) + '</td>' +
            '<td class="tp-mono">' + esc(r.TPPLAN) + '</td>' +
            '<td title="' + attr(r.TPPLDS) + '">' + esc(r.TPPLDS) + '</td>' +
            '<td class="tp-exp">' + fmtDate(r.TPEXDATE) + '</td>' +
            '</tr>';
    });
    var emptyMsg = gridShow === 'expired' ? 'No expired records match.'
                 : gridShow === 'active' ? 'No active records match.'
                 : 'No time payment records match.';
    $('#gridBody').html(html ||
        '<tr><td colspan="5" class="tp-empty">' + emptyMsg + '</td></tr>');
    $('#lblCount').text(rows.length === gridRows.length
        ? rows.length + ' record' + (rows.length === 1 ? '' : 's')
        : rows.length + ' of ' + gridRows.length + ' records');
    updateSortIndicators();
}


// paint the up/down arrow on the active sort header
function updateSortIndicators() {
    $('#tblGrid thead th[data-sortkey]').each(function () {
        var th = $(this);
        var active = th.data('sortkey') === gridSort.key;
        th.toggleClass('tp-sorted', active);
        th.find('.tp-sortind').text(active ? (gridSort.dir === 1 ? ' ▲' : ' ▼') : '');
    });
}
</script>
<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
