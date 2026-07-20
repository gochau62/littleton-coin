/*    ***************************************************
      * Program Name - ReqStn.js                        *
      *                                                 *
      * Narrative - Requisition Station front-end       *
      *   logic: grid load/auto-refresh (replaces the   *
      *   Access Form_Timer), add-request modal with    *
      *   dynamic lines (replaces the fixed 30 rows of  *
      *   legacy getEntry.php), authorize and return-   *
      *   item actions via ajax + swal.                 *
      *                                                 *
      * Author    - G CHAU                              *
      *             Littleton Coin Company              *
      *             Littleton NH                        *
      * Date Written 07/20/2026                         *
      ***************************************************   */

var gridRows = [];
var lookups = null;
var autoTimer = null;

$(document).ready(function () {
    loadLookups();
    loadGrid();
    startAutoRefresh();
    tickClock();
    setInterval(tickClock, 1000);

    $('#btnRefresh').on('click', loadGrid);
    $('#chkAutoRefresh').on('change', startAutoRefresh);
    $('#txtFilter').on('input', renderGrid);
    $('#btnAdd').on('click', openAddModal);
    $('#btnAddLine').on('click', addLineRow);
    $('#btnSubmit').on('click', submitRequisition);
    $('#btnAuthorize').on('click', authorizeCurrent);

    // close buttons for both modals
    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).prop('hidden', true);
    });

    // open the view modal from a grid row
    $('#gridBody').on('click', 'tr[data-req]', function () {
        openViewModal($(this).data('req'));
    });

    // returned checkbox inside the view modal
    $('#viewLineBody').on('change', '.rq-returned', function () {
        var cb = $(this);
        postAjax({
            action: 'returned',
            reqNum: cb.data('req'),
            lineNum: cb.data('line'),
            flag: cb.is(':checked') ? 'Y' : 'N'
        }, function () { loadGrid(); });
    });
});

function tickClock() {
    $('#rqClock').text(new Date().toLocaleString());
}

function startAutoRefresh() {
    if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    if ($('#chkAutoRefresh').is(':checked')) {
        autoTimer = setInterval(loadGrid, 60000);   // Access used a form timer
    }
}

function postAjax(data, onOk) {
    $.post('ReqStn_ajax.php', data, function (resp) {
        if (resp && resp.ok) { onOk(resp); }
        else {
            swal('Error', (resp && resp.msg) ? resp.msg : 'Request failed.', 'error');
        }
    }, 'json').fail(function () {
        swal('Error', 'Server error - see the log.', 'error');
    });
}

/* ------------------------- grid ------------------------- */

function loadGrid() {
    postAjax({ action: 'list' }, function (resp) {
        gridRows = resp.rows;
        renderGrid();
    });
}

function fmtDate(dec) {
    var s = String(dec);
    if (s.length !== 8 || s === '00000000') { return ''; }
    return s.substr(4, 2) + '/' + s.substr(6, 2) + '/' + s.substr(0, 4);
}

function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}

function renderGrid() {
    var filter = $('#txtFilter').val().toLowerCase();
    var html = '';
    var shown = 0;

    $.each(gridRows, function (i, r) {
        var hay = (r['RHREQ#'] + ' ' + r.RHNAME + ' ' + r.RDITEM + ' ' + r.RDDESC).toLowerCase();
        if (filter && hay.indexOf(filter) < 0) { return; }
        shown++;

        var auth = (r.RHAUTF === 'Y')
            ? '<span class="rq-pill rq-ok">' + esc(r.RHAUTB) + '</span>'
            : '<span class="rq-pill rq-warn">None</span>';
        var rush = (r.RHRUSH === 'Y')
            ? '<span class="rq-pill rq-rushpill">RUSH</span>' : '';

        html += '<tr data-req="' + esc(r['RHREQ#']) + '">' +
            '<td>' + esc(r['RHREQ#']) + '</td>' +
            '<td>' + fmtDate(r.RHRQDT) + '</td>' +
            '<td>' + esc(r.RHNAME) + '</td>' +
            '<td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td>' +
            '<td>' + esc(r.RDLOC) + '</td>' +
            '<td class="rq-num">' + esc(r.RDQTY) + '</td>' +
            '<td>' + rush + '</td>' +
            '<td>' + auth + '</td>' +
            '<td>' + (r.RDRTNF === 'Y' ? fmtDate(r.RDRTDT) : '') + '</td>' +
            '</tr>';
    });

    $('#gridBody').html(html ||
        '<tr><td colspan="10" class="rq-empty">No open requisitions.</td></tr>');
    $('#lblCount').text(shown + ' line' + (shown === 1 ? '' : 's'));
}

/* --------------------- add requisition ------------------- */

function loadLookups() {
    postAjax({ action: 'lookups' }, function (resp) {
        lookups = resp;
        fillSelect('#addName', resp.names, 'RQNAME', 'RQNAME');
        fillSelect('#addAreaCode', resp.areaCodes, 'ARCODE', 'ARDESC');
        fillSelect('#addAreaType', resp.areaTypes, 'ATTYPE', 'ATTYPE');
        fillSelect('#authBy', resp.authBy, 'AUNAME', 'AUNAME');
    });
}

function fillSelect(sel, rows, valCol, txtCol) {
    var html = '';
    $.each(rows, function (i, r) {
        html += '<option value="' + esc(r[valCol]) + '">' + esc(r[txtCol]) + '</option>';
    });
    $(sel).html(html);
}

function openAddModal() {
    $('#lineBody').empty();
    addLineRow(); addLineRow(); addLineRow();   // start with 3 blank lines
    $('#addComments').val('');
    $('#addRush').prop('checked', false);
    $('#mdlAdd').prop('hidden', false);
    $('#lineBody input:first').trigger('focus');
}

function addLineRow() {
    var row = '<tr>' +
        '<td><input class="ln-item" size="12"></td>' +
        '<td><input class="ln-loc" size="6"></td>' +
        '<td><input class="ln-cndt" size="8"></td>' +
        '<td><input class="ln-desc" size="40"></td>' +
        '<td><input class="ln-qty rq-num" size="5"></td>' +
        '<td><input class="ln-cost rq-num" size="7"></td>' +
        '<td><input class="ln-retail rq-num" size="7"></td>' +
        '<td><input class="ln-acost rq-num" size="7"></td>' +
        '<td><input class="ln-skuto" size="12"></td>' +
        '<td><button type="button" class="rq-x rq-linedel" title="Remove line"' +
        ' onclick="$(this).closest(\'tr\').remove()">&times;</button></td>' +
        '</tr>';
    $('#lineBody').append(row);
}

function submitRequisition() {
    var lines = [];
    $('#lineBody tr').each(function () {
        var t = $(this);
        if (t.find('.ln-item').val().trim() === '') { return; }
        lines.push({
            item: t.find('.ln-item').val().trim(),
            loc: t.find('.ln-loc').val().trim(),
            coinDate: t.find('.ln-cndt').val().trim(),
            desc: t.find('.ln-desc').val().trim(),
            qty: t.find('.ln-qty').val() || 0,
            cost: t.find('.ln-cost').val() || 0,
            retail: t.find('.ln-retail').val() || 0,
            addCost: t.find('.ln-acost').val() || 0,
            skuTo: t.find('.ln-skuto').val().trim()
        });
    });

    if (lines.length === 0) {
        swal('Nothing to submit', 'Enter at least one line with an item number.', 'warning');
        return;
    }

    var payload = {
        reqName: $('#addName').val(),
        areaCode: $('#addAreaCode').val(),
        areaType: $('#addAreaType').val(),
        rush: $('#addRush').is(':checked') ? 'Y' : 'N',
        comments: $('#addComments').val(),
        lines: lines
    };

    postAjax({ action: 'insert', payload: JSON.stringify(payload) }, function (resp) {
        $('#mdlAdd').prop('hidden', true);
        swal('Requisition ' + resp.reqNum + ' created',
             resp.lines + ' line(s) inserted.', 'success');
        loadGrid();
    });
}

/* -------------------- view / authorize ------------------- */

function openViewModal(reqNum) {
    postAjax({ action: 'get', reqNum: reqNum }, function (resp) {
        if (!resp.rows.length) {
            swal('Not found', 'Requisition ' + reqNum + ' was not found.', 'warning');
            return;
        }
        var h = resp.rows[0];
        $('#viewReqNum').text(h['RHREQ#']);

        var auth = (h.RHAUTF === 'Y')
            ? '<span class="rq-pill rq-ok">' + esc(h.RHAUTB) + '</span>'
            : '<span class="rq-pill rq-warn">NOT AUTHORIZED</span>';

        $('#viewHead').html(
            '<div><b>Requestor:</b> ' + esc(h.RHNAME) + '</div>' +
            '<div><b>Date:</b> ' + fmtDate(h.RHRQDT) + '</div>' +
            '<div><b>Area:</b> ' + esc(h.RHARCD) + ' - ' + esc(h.RHARTY) + '</div>' +
            '<div><b>Rush:</b> ' + (h.RHRUSH === 'Y' ? 'Yes' : 'No') + '</div>' +
            '<div><b>Authorized:</b> ' + auth + '</div>' +
            '<div><b>Comments:</b> ' + esc(h.RHCMNT) + '</div>');

        var html = '';
        $.each(resp.rows, function (i, r) {
            if (r['RDLIN#'] == null) { return; }
            html += '<tr>' +
                '<td>' + esc(r['RDLIN#']) + '</td>' +
                '<td>' + esc(r.RDITEM) + '</td>' +
                '<td>' + esc(r.RDLOC) + '</td>' +
                '<td>' + esc(r.RDCNDT) + '</td>' +
                '<td>' + esc(r.RDDESC) + '</td>' +
                '<td class="rq-num">' + esc(r.RDQTY) + '</td>' +
                '<td class="rq-num">' + esc(r.RDCOST) + '</td>' +
                '<td class="rq-num">' + esc(r.RDRETL) + '</td>' +
                '<td>' + esc(r.RDSKUT) + '</td>' +
                '<td><input type="checkbox" class="rq-returned"' +
                ' data-req="' + esc(h['RHREQ#']) + '" data-line="' + esc(r['RDLIN#']) + '"' +
                (r.RDRTNF === 'Y' ? ' checked' : '') + '></td>' +
                '</tr>';
        });
        $('#viewLineBody').html(html);

        $('#authComments').val(h.RHCMNT);
        $('#authRow').toggle(h.RHAUTF !== 'Y');
        $('#mdlView').data('req', h['RHREQ#']).prop('hidden', false);
    });
}

function authorizeCurrent() {
    var reqNum = $('#mdlView').data('req');
    postAjax({
        action: 'authorize',
        reqNum: reqNum,
        authBy: $('#authBy').val(),
        comments: $('#authComments').val()
    }, function () {
        $('#mdlView').prop('hidden', true);
        swal('Authorized', 'Requisition ' + reqNum + ' authorized.', 'success');
        loadGrid();
    });
}
