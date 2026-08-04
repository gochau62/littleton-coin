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

    document.title = "Item Time Payment Upload";

    // small message helpers following the LCC convention: show the red error box with a message, or the standard not authorized message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// check users authority (10 is the minimum to use LCCOnline; 50 here because the upload writes a production pricing file, same as the other loaders)
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/TimePayment_model.php';

    tpyActLog($user, 'OPEN');

    include "TimePayment_dsp.php";
    dspTimePayment($user);
?>

<script>
// Item Time Payment upload frontend logic (upload, results report, review grid)
var gridToday = 0;
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


// stored dates are an 8 digit yyyymmdd number, so show them as mm/dd/yyyy
function fmtDate(dec) {
    var s = String(dec);
    if (s.length !== 8 || s === '00000000') { return ''; }
    return s.substr(4, 2) + '/' + s.substr(6, 2) + '/' + s.substr(0, 4);
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
    var pills = '<span class="tp-pill tp-pill-ok">' + resp.added + ' added</span>' +
                '<span class="tp-pill tp-pill-ok">' + resp.updated + ' updated</span>' +
                '<span class="tp-pill ' + (resp.errors > 0 ? 'tp-pill-err' : 'tp-pill-ok') + '">' +
                resp.errors + ' skipped</span>';
    if (resp.errors > 0 && resp.emailed) {
        pills += '<span class="tp-pill tp-pill-note">exception report e-mailed to you</span>';
    }
    $('#resSummary').html(pills);

    var html = '';
    $.each(resp.report || [], function (i, r) {
        html += '<tr>' +
            '<td>' + esc(r.row) + '</td>' +
            '<td class="tp-mono">' + esc(r.item) + '</td>' +
            '<td class="tp-mono">' + esc(r.src) + '</td>' +
            '<td class="tp-mono">' + esc(r.plan) + '</td>' +
            '<td>' + esc(r.exp) + '</td>' +
            '<td><span class="tp-st tp-st-' + esc(r.status) + '">' + esc(r.status) + '</span></td>' +
            '<td class="tp-msg">' + esc(r.msg) + '</td>' +
            '</tr>';
    });
    $('#resBody').html(html ||
        '<tr><td colspan="7" class="tp-empty">The spreadsheet had no data rows.</td></tr>');
    $('#resultsCard').prop('hidden', false);
}


// review grid

function loadGrid() {
    postAjax({ action: 'list', q: $('#txtSearch').val().trim() }, function (resp) {
        gridToday = resp.today || 0;
        renderGrid(resp.rows || []);
    });
}


function renderGrid(rows) {
    var html = '';
    $.each(rows, function (i, r) {
        // an expired record stays in the grid but reads as done with
        var expired = gridToday > 0 && parseInt(r.TPEXDATE, 10) < gridToday;
        html += '<tr' + (expired ? ' class="tp-expired"' : '') + '>' +
            '<td class="tp-mono">' + esc(r.TPITEM) + '</td>' +
            '<td>' + esc(r.TPDESC) + '</td>' +
            '<td class="tp-mono">' + esc(r.TPSRCD) + '</td>' +
            '<td class="tp-mono">' + esc(r.TPPLAN) + '</td>' +
            '<td>' + esc(r.TPPLDS) + '</td>' +
            '<td>' + fmtDate(r.TPEXDATE) + '</td>' +
            '<td>' + (expired ? '<span class="tp-exppill">Expired</span>' : '') + '</td>' +
            '</tr>';
    });
    $('#gridBody').html(html ||
        '<tr><td colspan="7" class="tp-empty">No time payment records match.</td></tr>');
    $('#lblCount').text(rows.length + ' record' + (rows.length === 1 ? '' : 's'));
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
