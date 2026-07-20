<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_dsp.php                   *  -->
<!--  *                                                 *  -->
<!--  * Narrative - Requisition Station display.        *  -->
<!--  *   Main grid (replaces Access frmMain), add-     *  -->
<!--  *   request modal (replaces legacy getEntry.php)  *  -->
<!--  *   and view/authorize modal (replaces            *  -->
<!--  *   getIdInfo.php / getUpdate.php).               *  -->
<!--  *   Styling lives in the <style> block up top and *  -->
<!--  *   the page logic in the <script> block at the   *  -->
<!--  *   bottom (Sellbrite/WavePickSearch pattern) -   *  -->
<!--  *   the display owns everything the browser gets. *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

function dspRequisitions($user) {
?>

<style>
/* Requisition Station styling - inline per shop preference:
   the display file owns everything visual. */
:root {
  --rq-navy:   #1c3557;
  --rq-blue:   #2f6fb2;
  --rq-bg:     #f4f6f9;
  --rq-line:   #d9dee6;
  --rq-text:   #22293a;
  --rq-muted:  #6b7486;
  --rq-green:  #1e7e34;
  --rq-amber:  #b26a00;
  --rq-red:    #b02a37;
}

.rq-app {
  font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
  color: var(--rq-text);
  background: var(--rq-bg);
  padding: 0 0 2rem 0;
}

/* ---------- top bar ---------- */
.rq-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--rq-navy);
  color: #fff;
  padding: .6rem 1.25rem;
}
.rq-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.rq-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

/* ---------- toolbar ---------- */
.rq-toolbar {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .75rem 1.25rem;
  flex-wrap: wrap;
}
.rq-filter {
  flex: 1 1 220px;
  max-width: 340px;
  padding: .45rem .7rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
}
.rq-count { color: var(--rq-muted); font-size: .85rem; margin-left: auto; }
.rq-auto  { color: var(--rq-muted); font-size: .85rem; user-select: none; }

/* ---------- buttons ---------- */
.rq-btn {
  padding: .45rem .9rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
  background: #fff;
  color: var(--rq-text);
  font-size: .9rem;
  cursor: pointer;
}
.rq-btn:hover { border-color: var(--rq-blue); color: var(--rq-blue); }
.rq-btn-primary {
  background: var(--rq-blue);
  border-color: var(--rq-blue);
  color: #fff;
}
.rq-btn-primary:hover { background: var(--rq-navy); color: #fff; }
.rq-btn-ghost { border-style: dashed; color: var(--rq-muted); margin: .5rem 0; }

/* ---------- card + grid ---------- */
.rq-card {
  background: #fff;
  border: 1px solid var(--rq-line);
  border-radius: 8px;
  margin: 0 1.25rem;
  overflow: hidden;
}
.rq-tablewrap { overflow-x: auto; max-height: 70vh; }
.rq-grid { width: 100%; border-collapse: collapse; font-size: .88rem; }
.rq-grid thead th {
  position: sticky;
  top: 0;
  background: #eef1f6;
  color: var(--rq-navy);
  text-align: left;
  padding: .55rem .7rem;
  border-bottom: 2px solid var(--rq-line);
  white-space: nowrap;
}
.rq-grid tbody td {
  padding: .45rem .7rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-grid tbody tr:nth-child(even) { background: #fafbfd; }
#tblGrid tbody tr { cursor: pointer; }
#tblGrid tbody tr:hover { background: #e9f2fb; }
.rq-num { text-align: right; }
.rq-empty { text-align: center; color: var(--rq-muted); padding: 1.5rem !important; }

/* ---------- status pills ---------- */
.rq-pill {
  display: inline-block;
  padding: .1rem .55rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
  white-space: nowrap;
}
.rq-ok       { background: #e2f2e6; color: var(--rq-green); }
.rq-warn     { background: #fdf0dd; color: var(--rq-amber); }
.rq-rushpill { background: #fbe3e6; color: var(--rq-red); }

/* ---------- modals ---------- */
.rq-overlay {
  position: fixed;
  inset: 0;
  background: rgba(20, 28, 45, .45);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 4vh 1rem;
  z-index: 50;
}
.rq-overlay[hidden] { display: none; }
.rq-modal {
  background: #fff;
  border-radius: 10px;
  width: 100%;
  max-width: 640px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 40px rgba(0, 0, 0, .25);
}
.rq-modal-wide { max-width: 1080px; }
.rq-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .8rem 1.1rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-modal-head h2 { margin: 0; font-size: 1.05rem; color: var(--rq-navy); }
.rq-modal-body { padding: 1rem 1.1rem; overflow-y: auto; }
.rq-modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  padding: .8rem 1.1rem;
  border-top: 1px solid var(--rq-line);
}
.rq-x {
  border: 0;
  background: none;
  font-size: 1.3rem;
  line-height: 1;
  color: var(--rq-muted);
  cursor: pointer;
}
.rq-x:hover { color: var(--rq-red); }
.rq-linedel { font-size: 1rem; }

/* ---------- add / view forms ---------- */
.rq-formrow {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: .9rem;
}
.rq-formrow label,
.rq-comments {
  display: flex;
  flex-direction: column;
  gap: .25rem;
  font-size: .82rem;
  color: var(--rq-muted);
}
.rq-formrow select,
.rq-comments input,
.rq-authrow select,
.rq-authrow input {
  padding: .4rem .6rem;
  border: 1px solid var(--rq-line);
  border-radius: 6px;
  font-size: .9rem;
  color: var(--rq-text);
}
.rq-rush { flex-direction: row !important; align-items: center; gap: .4rem; }
.rq-lines input {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: .25rem .35rem;
  font-size: .85rem;
}
.rq-lines input:focus {
  border-color: var(--rq-blue);
  outline: none;
  background: #f4f9ff;
}
.rq-comments { margin-top: .9rem; }
.rq-comments input { width: 100%; }

.rq-viewhead {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: .4rem .9rem;
  font-size: .88rem;
  margin-bottom: .9rem;
}
.rq-authrow {
  display: flex;
  align-items: flex-end;
  gap: .9rem;
  flex-wrap: wrap;
  margin-top: 1rem;
  padding-top: .9rem;
  border-top: 1px dashed var(--rq-line);
}
.rq-authrow label { display: flex; flex-direction: column; gap: .25rem;
                    font-size: .82rem; color: var(--rq-muted); }
.rq-authrow .rq-comments { flex: 1; margin-top: 0; }
</style>

<div class="rq-app">

  <header class="rq-topbar">
    <h1>Requisition Station</h1>
    <div class="rq-topbar-right">
      <span id="rqUser"><?php echo htmlspecialchars($user); ?></span>
      <span id="rqClock"></span>
    </div>
  </header>

  <div class="rq-toolbar">
    <button type="button" class="rq-btn rq-btn-primary" id="btnAdd">+ Add Requisition</button>
    <button type="button" class="rq-btn" id="btnRefresh">&#8635; Refresh</button>
    <label class="rq-auto">
      <input type="checkbox" id="chkAutoRefresh" checked> Auto-refresh
    </label>
    <input type="search" id="txtFilter" class="rq-filter"
           placeholder="Filter by req #, name, item...">
    <span class="rq-count" id="lblCount"></span>
  </div>

  <div class="rq-card">
    <div class="rq-tablewrap">
      <table class="rq-grid" id="tblGrid">
        <thead>
          <tr>
            <th>Req #</th>
            <th>Date</th>
            <th>Requestor</th>
            <th>Item #</th>
            <th>Description</th>
            <th>Loc</th>
            <th class="rq-num">Qty</th>
            <th>Rush</th>
            <th>Authorized</th>
            <th>Returned</th>
          </tr>
        </thead>
        <tbody id="gridBody">
          <tr><td colspan="10" class="rq-empty">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============ Add Requisition modal (replaces getEntry.php) ============ -->
  <div class="rq-overlay" id="mdlAdd" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>New Requisition</h2>
        <button type="button" class="rq-x" data-close="mdlAdd">&times;</button>
      </div>

      <div class="rq-modal-body">
        <div class="rq-formrow">
          <label>Requestor
            <select id="addName"></select>
          </label>
          <label>Area Code
            <select id="addAreaCode"></select>
          </label>
          <label>Area Type
            <select id="addAreaType"></select>
          </label>
          <label class="rq-rush">Rush
            <input type="checkbox" id="addRush">
          </label>
        </div>

        <div class="rq-tablewrap">
          <table class="rq-grid rq-lines" id="tblLines">
            <thead>
              <tr>
                <th>Item #</th><th>Location</th><th>Item Date</th>
                <th>Description</th><th class="rq-num">Qty</th>
                <th class="rq-num">Cost $</th><th class="rq-num">Retail $</th>
                <th class="rq-num">Add Cost $</th><th>SKU To</th><th></th>
              </tr>
            </thead>
            <tbody id="lineBody"></tbody>
          </table>
        </div>
        <button type="button" class="rq-btn rq-btn-ghost" id="btnAddLine">+ Add line</button>

        <label class="rq-comments">Comments
          <input type="text" id="addComments" maxlength="500">
        </label>
      </div>

      <div class="rq-modal-foot">
        <button type="button" class="rq-btn" data-close="mdlAdd">Cancel</button>
        <button type="button" class="rq-btn rq-btn-primary" id="btnSubmit">Submit Requisition</button>
      </div>
    </div>
  </div>

  <!-- ==== View / Authorize modal (replaces getIdInfo.php + getUpdate.php) ==== -->
  <div class="rq-overlay" id="mdlView" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>Requisition <span id="viewReqNum"></span></h2>
        <button type="button" class="rq-x" data-close="mdlView">&times;</button>
      </div>

      <div class="rq-modal-body">
        <div class="rq-viewhead" id="viewHead"></div>

        <div class="rq-tablewrap">
          <table class="rq-grid" id="tblViewLines">
            <thead>
              <tr>
                <th>Line</th><th>Item #</th><th>Location</th><th>Item Date</th>
                <th>Description</th><th class="rq-num">Qty</th>
                <th class="rq-num">Cost $</th><th class="rq-num">Retail $</th>
                <th>SKU To</th><th>Returned</th>
              </tr>
            </thead>
            <tbody id="viewLineBody"></tbody>
          </table>
        </div>

        <div class="rq-authrow" id="authRow">
          <label>Authorized by
            <select id="authBy"></select>
          </label>
          <label class="rq-comments">Comments
            <input type="text" id="authComments" maxlength="500">
          </label>
          <button type="button" class="rq-btn rq-btn-primary" id="btnAuthorize">Authorize</button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
/* Requisition Station front-end logic - inline at the bottom of the
   display per the Sellbrite/WavePickSearch pattern: grid load and
   auto-refresh (replaces the Access Form_Timer), add-request modal
   with dynamic lines, authorize and return-item actions via ajax. */
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

    // deep links - the legacy request.php shims redirect here, so the
    // production/inventory controllers' saved bookmarks keep working:
    //   ?id=N        (was request.php?id=N)  -> open that requisition
    //   ?action=add  (was request.php)       -> open the entry form
    var qs = new URLSearchParams(window.location.search);
    if (qs.get('id')) {
        openViewModal(parseInt(qs.get('id'), 10));
    } else if (qs.get('action') === 'add') {
        openAddModal();
    }
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
    $.post('Requisitions_ajax.php', data, function (resp) {
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
        '<td><input class="ln-item" size="12" maxlength="16"></td>' +
        '<td><input class="ln-loc" size="6" maxlength="3"></td>' +
        '<td><input class="ln-cndt" size="8" maxlength="10"></td>' +
        '<td><input class="ln-desc" size="40" maxlength="50"></td>' +
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
</script>

<?php } ?>
