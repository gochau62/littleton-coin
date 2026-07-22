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

function dspRequisitions($user, $rqLookups = null, $mode = '') {
?>

<style>
/* Requisition Station styling - inline per shop preference:
   the display file owns everything visual. */
:root {
  /* LCC house palette - same greens as SellbriteBulkLoader */
  --rq-green-dk: #1C4532;   /* headings, header bar */
  --rq-green:    #2e8b57;   /* .btn-green actions (Authorize) */
  --rq-green-hv: #1e6e43;   /* green hover, accents */
  --rq-blue:     #007bff;   /* primary buttons (Sellbrite default .btn) */
  --rq-blue-hv:  #0056b3;   /* primary hover */
  --rq-accent:   #eaf6ee;   /* light green fills */
  --rq-bg:       #f8f8f8;
  --rq-line:     #dfe6e1;
  --rq-text:     #222;
  --rq-muted:    #5f6b62;   /* the standard LCC label/text color */
  --rq-amber:    #9a6a14;
  --rq-red:      #c0392b;
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
  background: var(--rq-green-dk);
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
.rq-updated { color: var(--rq-muted); font-size: .8rem; }
.rq-updated.rq-stale { color: var(--rq-red); font-weight: 700; }
.rq-lines input.rq-bad { border-color: var(--rq-red); background: #fff5f5; }

/* ---------- buttons ---------- */
.rq-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: .45rem 1.1rem;
  border: 1px solid #b4b4b4;
  border-radius: 50px;               /* house pill buttons */
  background: #fff;
  color: var(--rq-text);
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
}
.rq-btn:hover { border-color: var(--rq-blue); color: var(--rq-blue); }   /* Sellbrite .btn-ghost hover */
.rq-btn-primary {
  background: var(--rq-blue);
  border-color: var(--rq-blue);
  color: #fff;
}
.rq-btn-primary:hover { background: var(--rq-blue-hv); border-color: var(--rq-blue-hv); color: #fff; }
.rq-btn-green {
  background: var(--rq-green);
  border-color: var(--rq-green);
  color: #fff;
}
.rq-btn-green:hover { background: var(--rq-green-hv); border-color: var(--rq-green-hv); color: #fff; }
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
  background: var(--rq-accent);
  color: var(--rq-green-dk);
  text-align: left;
  padding: .55rem .7rem;
  border-bottom: 2px solid var(--rq-line);
  white-space: nowrap;
}
.rq-grid tbody td {
  padding: .45rem .7rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-grid tbody tr:nth-child(even) { background: #f7faf8; }
#tblGrid tbody tr { cursor: pointer; }
#tblGrid tbody tr:hover { background: var(--rq-accent); }
.rq-grid tbody tr.rq-selected { background: #dff0e5; }
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
.rq-ok       { background: var(--rq-accent); color: var(--rq-green-hv); }
.rq-warn     { background: #fdf0dd; color: var(--rq-amber); }
.rq-rushpill { background: #ffd1d1; color: var(--rq-red); }

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
.rq-modal-head h2 { margin: 0; font-size: 1.05rem; color: var(--rq-green-dk); }
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
.rq-rushgrp { display: flex; gap: .9rem; align-items: center;
              font-size: .82rem; color: var(--rq-muted); }
.rq-rushgrp label { display: inline-flex; flex-direction: row; gap: .3rem;
                    align-items: center; font-size: .9rem; color: var(--rq-text); }
#addDate { background: #f0f2f1; color: var(--rq-muted); }
.rq-formrow input[type=text], .rq-formrow select { min-width: 190px; }
.rq-lines input {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: .25rem .35rem;
  font-size: .85rem;
}
.rq-lines input:focus {
  border-color: var(--rq-green);
  outline: none;
  background: var(--rq-accent);
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

/* ---------- entry-only mode (workfloor shortcut ?mode=entry) ---------- */
/* No grid, no reports - the entry form IS the page, and it can't be
   dismissed, matching the old request.php the floor had favorited. */
.rq-entry .rq-toolbar, .rq-entry .rq-card { display: none; }
.rq-entry #mdlAdd .rq-x,
.rq-entry #mdlAdd .rq-modal-foot [data-close] { display: none; }
.rq-entry .rq-overlay { background: var(--rq-bg); padding-top: 1.5rem; }
.rq-entry .rq-modal-wide { max-width: 1280px; }   /* room for the full sheet */

/* ---------- monthly report ---------- */
.rpt-title { margin: 0 0 .75rem 0; color: var(--rq-green-dk); }
.rpt-table .rpt-group td { font-weight: 700; background: var(--rq-accent); }
.rpt-table .rpt-subtotal td, .rpt-table .rpt-grand td { font-weight: 700; background: #f7faf8; }
#rptMonth { padding: .35rem .5rem; border: 1px solid var(--rq-line); border-radius: 6px; }
.rq-modal-head .rq-btn { margin-right: .4rem; }
</style>

<div class="rq-app<?php echo $mode === 'entry' ? ' rq-entry' : ''; ?>">

  <header class="rq-topbar">
    <h1><?php echo $mode === 'entry' ? 'Requisition Entry' : 'Requisition Station'; ?></h1>
    <div class="rq-topbar-right">
      <span id="rqUser"><?php echo htmlspecialchars($user); ?></span>
      <span id="rqClock"></span>
    </div>
  </header>

  <div class="rq-toolbar">
    <button type="button" class="rq-btn rq-btn-primary" id="btnAdd">+ Add Requisition</button>
    <button type="button" class="rq-btn" id="btnRefresh">&#8635; Refresh</button>
    <button type="button" class="rq-btn" id="btnMonthly">Monthly Report</button>
    <button type="button" class="rq-btn" id="btnPreview">Preview Report</button>
    <button type="button" class="rq-btn" id="btnOutstanding">Total Outstanding</button>
    <label class="rq-auto">
      <input type="checkbox" id="chkAutoRefresh" checked> Auto-refresh
    </label>
    <input type="search" id="txtFilter" class="rq-filter"
           placeholder="Filter by req #, name, item...">
    <span class="rq-count" id="lblCount"></span>
    <span class="rq-updated" id="lblUpdated" title="Last successful refresh"></span>
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
        <!-- header fields laid out like the legacy getEntry.php form -->
        <div class="rq-formrow">
          <label>Requestor:
            <select id="addName"></select>
          </label>
          <label>Date:
            <input type="text" id="addDate" readonly tabindex="-1">
          </label>
        </div>
        <div class="rq-formrow">
          <span class="rq-rushgrp">Rush:
            <label><input type="radio" name="addRush" value="Y"> Yes</label>
            <label><input type="radio" name="addRush" value="N" checked> No</label>
          </span>
        </div>
        <div class="rq-formrow">
          <label>Area Code:
            <select id="addAreaCode"></select>
          </label>
          <label>Area Type:
            <select id="addAreaType"></select>
          </label>
          <label>Authorized By:
            <select id="addAuthBy"></select>
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
        <button type="button" class="rq-btn rq-btn-primary" id="btnSubmit"><?php
            echo $mode === 'entry' ? 'Insert' : 'Submit Requisition'; ?></button>
      </div>
    </div>
  </div>

  <!-- ==== View / Authorize modal (replaces getIdInfo.php + getUpdate.php) ==== -->
  <div class="rq-overlay" id="mdlView" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>Requisition <span id="viewReqNum"></span></h2>
        <div>
          <button type="button" class="rq-btn" id="btnPrintReq">&#128424; Print</button>
          <button type="button" class="rq-x" data-close="mdlView">&times;</button>
        </div>
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
          <button type="button" class="rq-btn rq-btn-green" id="btnAuthorize">Authorize</button>
        </div>
      </div>
    </div>
  </div>

  <!-- === Monthly Report modal (replaces "Requested Material Summary") === -->
  <div class="rq-overlay" id="mdlReport" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>Monthly Update: Requisitioned Product</h2>
        <div>
          <input type="month" id="rptMonth">
          <button type="button" class="rq-btn" id="btnRunReport">Run</button>
          <button type="button" class="rq-btn rq-btn-primary" id="btnPrintReport">&#128424; Print</button>
          <button type="button" class="rq-x" data-close="mdlReport">&times;</button>
        </div>
      </div>
      <div class="rq-modal-body" id="rptBody">
        <div class="rq-empty">Pick a month and press Run.</div>
      </div>
    </div>
  </div>

</div>

<script>
/* Requisition Station front-end logic - inline at the bottom of the
   display per the Sellbrite/WavePickSearch pattern: grid load and
   auto-refresh (replaces the Access Form_Timer), add-request modal
   with dynamic lines, authorize and return-item actions via ajax. */
var RQ_PRELOAD = <?php echo $rqLookups ? json_encode($rqLookups) : 'null'; ?>;
var RQ_MODE = '<?php echo $mode; ?>';    // 'entry' = workfloor entry-only shortcut
var gridRows = [];
var lastGridJson = '';
var lookups = null;
var autoTimer = null;
var selectedReq = null;    // the requisition clicked in the grid (Access "current record")
var lastReqRows = null;    // data behind the open view modal

$(document).ready(function () {
    loadLookups();
    tickClock();
    setInterval(tickClock, 1000);

    if (RQ_MODE === 'entry') {
        openAddModal();          // the entry form IS the page
    } else {
        loadGrid();
        startAutoRefresh();
    }

    $('#btnRefresh').on('click', loadGrid);
    $('#chkAutoRefresh').on('change', startAutoRefresh);
    $('#txtFilter').on('input', renderGrid);
    $('#btnAdd').on('click', openAddModal);
    $('#btnAddLine').on('click', addLineRow);
    $('#btnSubmit').on('click', submitRequisition);
    $('#btnAuthorize').on('click', authorizeCurrent);

    // reports - Monthly Report button (Requested Material Summary) and the
    // per-requisition Print (rptRequest "Preview Report")
    $('#btnMonthly').on('click', openMonthlyReport);
    $('#btnPreview').on('click', previewReport);
    $('#btnOutstanding').on('click', printOutstanding);
    $('#btnRunReport').on('click', runMonthlyReport);
    $('#btnPrintReport').on('click', function () {
        printHtml($('#rptBody').html(), 'Monthly Update: Requisitioned Product');
    });
    $('#btnPrintReq').on('click', printRequisition);

    // close buttons for both modals
    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).prop('hidden', true);
    });

    // clicking a row selects that requisition (like moving the Access record
    // pointer) and opens its view
    $('#gridBody').on('click', 'tr[data-req]', function () {
        selectedReq = $(this).data('req');
        $('#gridBody tr').removeClass('rq-selected');
        $('#gridBody tr[data-req="' + selectedReq + '"]').addClass('rq-selected');
        openViewModal(selectedReq);
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

    // ESC closes the topmost open window (except the entry-mode form,
    // which is the whole page)
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && RQ_MODE !== 'entry') {
            $('.rq-overlay').not('[hidden]').last().prop('hidden', true);
        }
    });

    // refresh immediately when the tab becomes visible again
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { loadGrid(true); }
    });

    // item autofill - the legacy dRec() behavior: entering an item number
    // fills description/coin date/cost/retail from its most recent use
    $('#lineBody').on('change', '.ln-item', function () {
        var row = $(this).closest('tr');
        var item = $(this).val().trim();
        if (item === '') { return; }
        postAjax({ action: 'itemlookup', item: item }, function (resp) {
            if (!resp.row) { return; }
            if (row.find('.ln-desc').val().trim() === '') { row.find('.ln-desc').val(resp.row.RDDESC); }
            if (row.find('.ln-cndt').val().trim() === '') { row.find('.ln-cndt').val(resp.row.RDCNDT); }
            if (!parseFloat(row.find('.ln-cost').val())) { row.find('.ln-cost').val(resp.row.RDCOST); }
            if (!parseFloat(row.find('.ln-retail').val())) { row.find('.ln-retail').val(resp.row.RDRETL); }
        }, true);
        row.find('.ln-loc').trigger('focus');
    });

    // Enter hops to the next field like the legacy form's onEnterKey chain;
    // Enter on the last field of the last row starts a new line
    $('#lineBody').on('keydown', 'input', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        var inputs = $('#lineBody input:visible');
        var i = inputs.index(this);
        if (i === inputs.length - 1) {
            addLineRow();
            inputs = $('#lineBody input:visible');
        }
        inputs.eq(i + 1).trigger('focus');
    });

    // deep links, for shortcuts and shared links:
    //   ?id=N        -> open that requisition's view
    //   ?action=add  -> open the entry form directly
    if (RQ_MODE !== 'entry') {
        var qs = new URLSearchParams(window.location.search);
        if (qs.get('id')) {
            openViewModal(parseInt(qs.get('id'), 10));
        } else if (qs.get('action') === 'add') {
            openAddModal();
        }
    }
});

function tickClock() {
    var now = new Date().toLocaleString();
    $('#rqClock').text(now);
    $('#addDate').val(now);    // the entry form's Date runs live, like the
                               // legacy form's Clock.js
}

function startAutoRefresh() {
    if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    if ($('#chkAutoRefresh').is(':checked')) {
        autoTimer = setInterval(function () {      // Access used a form timer
            if (!document.hidden) { loadGrid(true); }
        }, 60000);
    }
}

// silent=true: background work (auto-refresh, autofill) marks the freshness
// indicator stale instead of popping error dialogs at a kiosk screen
function postAjax(data, onOk, silent) {
    $.post('Requisitions_ajax.php', data, function (resp) {
        if (resp && resp.ok) { onOk(resp); }
        else if (silent) { markStale(); }
        else {
            swal('Error', (resp && resp.msg) ? resp.msg : 'Request failed.', 'error');
        }
    }, 'json').fail(function () {
        if (silent) { markStale(); }
        else { swal('Error', 'Server error - see the log.', 'error'); }
    });
}

function markStale() {
    $('#lblUpdated').addClass('rq-stale');
}

/* ------------------------- grid ------------------------- */

var rqFirstLoad = 1;   // the first list call logs the station OPEN

function loadGrid(background) {
    var first = rqFirstLoad;
    rqFirstLoad = 0;
    postAjax({ action: 'list', first: first }, function (resp) {
        $('#lblUpdated').removeClass('rq-stale')
            .text('Updated ' + new Date().toLocaleTimeString());
        var j = JSON.stringify(resp.rows);
        if (j === lastGridJson) { return; }     // nothing changed - skip the re-render
        lastGridJson = j;
        gridRows = resp.rows;
        renderGrid();
    }, background === true);
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

function applyLookups(resp) {
    lookups = resp;
    // all four lists come from the RQSCODEFLT code file via REQSTN007S
    fillSelect('#addName', resp.names, 'CDCODE', 'CDCODE');
    fillSelect('#addAreaCode', resp.areaCodes, 'CDCODE', 'CDDESC');
    fillSelect('#addAreaType', resp.areaTypes, 'CDCODE', 'CDCODE');
    fillSelect('#authBy', resp.authBy, 'CDCODE', 'CDCODE');
    // entry form's pre-authorizer, defaulting like the legacy form
    fillSelect('#addAuthBy', resp.authBy, 'CDCODE', 'CDCODE');
    $('#addAuthBy').prepend('<option value="">Authorization = None</option>').val('');
}

function loadLookups() {
    if (RQ_PRELOAD) { applyLookups(RQ_PRELOAD); return; }   // came with the page
    postAjax({ action: 'lookups' }, applyLookups);
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
    // entry mode presents the tall sheet like the legacy form; the
    // station modal starts small - Enter on the last field grows both
    var rows = (RQ_MODE === 'entry') ? 15 : 3;
    for (var i = 0; i < rows; i++) { addLineRow(); }
    $('#addComments').val('');
    $('input[name="addRush"][value="N"]').prop('checked', true);
    $('#addAuthBy').val('');
    $('#addDate').val(new Date().toLocaleString());
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
    var bad = 0;
    $('#lineBody input').removeClass('rq-bad');
    $('#lineBody tr').each(function () {
        var t = $(this);
        if (t.find('.ln-item').val().trim() === '') { return; }

        // catch typos here, not as a Db2 error after submit
        var qtyIn = t.find('.ln-qty');
        if (!(parseFloat(qtyIn.val()) > 0)) { qtyIn.addClass('rq-bad'); bad++; }
        $.each(['.ln-cost', '.ln-retail', '.ln-acost'], function (j, cls) {
            var f = t.find(cls);
            var v = f.val().replace(/,/g, '').trim();
            if (v !== '' && isNaN(parseFloat(v))) { f.addClass('rq-bad'); bad++; }
        });

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

    if (bad > 0) {
        swal('Check the highlighted fields',
             'Quantity must be a number greater than zero, and the dollar fields must be numeric.',
             'warning');
        return;
    }
    if (lines.length === 0) {
        swal('Nothing to submit', 'Enter at least one line with an item number.', 'warning');
        return;
    }

    var payload = {
        reqName: $('#addName').val(),
        areaCode: $('#addAreaCode').val(),
        areaType: $('#addAreaType').val(),
        rush: $('input[name="addRush"]:checked').val() === 'Y' ? 'Y' : 'N',
        authBy: $('#addAuthBy').val() || '',
        comments: $('#addComments').val(),
        lines: lines
    };

    postAjax({ action: 'insert', payload: JSON.stringify(payload) }, function (resp) {
        swal('Requisition ' + resp.reqNum + ' created',
             resp.lines + ' line(s) inserted.', 'success');
        if (RQ_MODE === 'entry') {
            openAddModal();          // fresh blank form for the next request
        } else {
            $('#mdlAdd').prop('hidden', true);
            loadGrid();
        }
    });
}

/* -------------------- view / authorize ------------------- */

function openViewModal(reqNum) {
    postAjax({ action: 'get', reqNum: reqNum }, function (resp) {
        if (!resp.rows.length) {
            swal('Not found', 'Requisition ' + reqNum + ' was not found.', 'warning');
            return;
        }
        lastReqRows = resp.rows;
        selectedReq = reqNum;
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

/* ---------------------- reports ---------------------- */

function money(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function openMonthlyReport() {
    var d = new Date();
    var m = d.getMonth() + 1;
    $('#rptMonth').val(d.getFullYear() + '-' + (m < 10 ? '0' + m : m));
    $('#rptBody').html('<div class="rq-empty">Pick a month and press Run.</div>');
    $('#mdlReport').prop('hidden', false);
}

function runMonthlyReport() {
    var ym = $('#rptMonth').val();                       // "2026-07"
    if (!ym) { swal('Pick a month', '', 'warning'); return; }
    postAjax({ action: 'monthly', yyyymm: parseInt(ym.replace('-', ''), 10) },
             function (resp) { renderMonthlyReport(resp.rows, ym); });
}

function renderMonthlyReport(rows, ym) {
    if (!rows.length) {
        $('#rptBody').html('<div class="rq-empty">No requisitioned product in ' + esc(ym) + '.</div>');
        return;
    }
    var name = null, sub = null;
    var grand = { qty: 0, extc: 0, extr: 0, lines: 0 };

    function subtotalRow() {
        if (name === null) { return ''; }
        return '<tr class="rpt-subtotal"><td colspan="4">Summary for ' + esc(name) +
               ' (' + sub.lines + ' detail record' + (sub.lines === 1 ? '' : 's') + ')</td>' +
               '<td class="rq-num">' + sub.qty + '</td><td></td><td></td>' +
               '<td class="rq-num">' + money(sub.extc) + '</td>' +
               '<td class="rq-num">' + money(sub.extr) + '</td><td></td></tr>';
    }

    var body = '';
    $.each(rows, function (i, r) {
        if (r.RHNAME !== name) {
            body += subtotalRow();
            name = r.RHNAME;
            sub = { qty: 0, extc: 0, extr: 0, lines: 0 };
            body += '<tr class="rpt-group"><td colspan="10">' + esc(name) + '</td></tr>';
        }
        var qty = parseFloat(r.RDQTY) || 0;
        var extc = parseFloat(r.RDEXTC) || 0;
        var extr = parseFloat(r.RDEXTR) || 0;
        sub.qty += qty; sub.extc += extc; sub.extr += extr; sub.lines++;
        grand.qty += qty; grand.extc += extc; grand.extr += extr; grand.lines++;
        body += '<tr>' +
            '<td>' + fmtDate(r.RHRQDT) + '</td>' +
            '<td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + qty + '</td>' +
            '<td class="rq-num">' + money(r.RDCOST) + '</td>' +
            '<td class="rq-num">' + money(r.RDRETL) + '</td>' +
            '<td class="rq-num">' + money(extc) + '</td>' +
            '<td class="rq-num">' + money(extr) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td>' +
            '</tr>';
    });
    body += subtotalRow();
    body += '<tr class="rpt-grand"><td colspan="4">Grand total (' + grand.lines + ' detail records)</td>' +
            '<td class="rq-num">' + grand.qty + '</td><td></td><td></td>' +
            '<td class="rq-num">' + money(grand.extc) + '</td>' +
            '<td class="rq-num">' + money(grand.extr) + '</td><td></td></tr>';

    $('#rptBody').html(
        '<h3 class="rpt-title">Monthly Update: Requisitioned Product &mdash; ' + esc(ym) + '</h3>' +
        '<div class="rq-tablewrap"><table class="rq-grid rpt-table"><thead><tr>' +
        '<th>Req. Date</th><th>Item #</th><th>Coin Date</th><th>Description</th>' +
        '<th class="rq-num">Qty</th><th class="rq-num">Cost</th><th class="rq-num">Retail</th>' +
        '<th class="rq-num">Ext. Cost</th><th class="rq-num">Ext. Retail</th><th>SKU To</th>' +
        '</tr></thead><tbody>' + body + '</tbody></table></div>');
}

// "Total Outstanding Requisitions" - the Access report of the same name:
// every open line grouped by requisitioner, Sum(Quantity)/Sum(Total Retail)
// group footers, long-date print stamp
function printOutstanding() {
    if (!gridRows.length) {
        swal('Nothing outstanding', 'There are no open requisition lines to print.', 'info');
        return;
    }
    var rows = gridRows.slice().sort(function (a, b) {
        return (a.RHNAME + a.RHRQDT).localeCompare(b.RHNAME + b.RHRQDT);
    });
    var name = null, sub = null;
    var grand = { qty: 0, cost: 0, retl: 0 };

    function subtotalRow() {
        if (name === null) { return ''; }
        return '<tr class="rpt-subtotal"><td colspan="4">Summary for ' + esc(name) + '</td>' +
               '<td class="rq-num">' + sub.qty + '</td><td></td>' +
               '<td class="rq-num">' + money(sub.cost) + '</td>' +
               '<td class="rq-num">' + money(sub.retl) + '</td></tr>';
    }

    var body = '';
    $.each(rows, function (i, r) {
        if (r.RHNAME !== name) {
            body += subtotalRow();
            name = r.RHNAME;
            sub = { qty: 0, cost: 0, retl: 0 };
            body += '<tr class="rpt-group"><td colspan="8">' + esc(name) + '</td></tr>';
        }
        var qty = parseFloat(r.RDQTY) || 0;
        var extc = qty * (parseFloat(r.RDCOST) || 0);
        var extr = qty * (parseFloat(r.RDRETL) || 0);
        sub.qty += qty; sub.cost += extc; sub.retl += extr;
        grand.qty += qty; grand.cost += extc; grand.retl += extr;
        body += '<tr>' +
            '<td>' + fmtDate(r.RHRQDT) + '</td>' +
            '<td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + qty + '</td>' +
            '<td>' + esc(r.RHARTY) + '</td>' +
            '<td class="rq-num">' + money(extc) + '</td>' +
            '<td class="rq-num">' + money(extr) + '</td>' +
            '</tr>';
    });
    body += subtotalRow();
    body += '<tr class="rpt-grand"><td colspan="4">Grand total</td>' +
            '<td class="rq-num">' + grand.qty + '</td><td></td>' +
            '<td class="rq-num">' + money(grand.cost) + '</td>' +
            '<td class="rq-num">' + money(grand.retl) + '</td></tr>';

    printHtml('<h3>Total Outstanding Requisitions</h3>' +
        '<div>Printed ' + new Date().toLocaleDateString('en-US',
            { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + '</div><br>' +
        '<table><thead><tr>' +
        '<th>Date of Request</th><th>Item # Requested</th><th>Date of Coin</th>' +
        '<th>Description of Material</th><th class="rq-num">Amount Requested</th>' +
        '<th>Area Type</th><th class="rq-num">Total Cost</th><th class="rq-num">Total Retail</th>' +
        '</tr></thead><tbody>' + body + '</tbody></table>',
        'Total Outstanding Requisitions');
}

// "Preview Report" - the Access rptRequest/rptRequestDetail preview for the
// requisition selected in the grid: header info + lines with extended
// cost/retail and a quantity total
function reqPrintHtml(rows) {
    var h = rows[0];
    var auth = (h.RHAUTF === 'Y') ? esc(h.RHAUTB) : '!!!NOT AUTHORIZED!!!';
    var head = '<h3>Requisition ' + esc(h['RHREQ#']) + '</h3>' +
        '<div><b>Requestor:</b> ' + esc(h.RHNAME) + ' &nbsp; <b>Date:</b> ' + fmtDate(h.RHRQDT) +
        ' &nbsp; <b>Area:</b> ' + esc(h.RHARCD) + ' - ' + esc(h.RHARTY) +
        ' &nbsp; <b>Rush:</b> ' + (h.RHRUSH === 'Y' ? 'Yes' : 'No') +
        ' &nbsp; <b>Authorized by:</b> ' + auth + '</div>' +
        (h.RHCMNT ? '<div><b>Comments:</b> ' + esc(h.RHCMNT) + '</div>' : '') + '<br>';

    var qty = 0, extc = 0, extr = 0;
    var body = '';
    $.each(rows, function (i, r) {
        if (r['RDLIN#'] == null) { return; }
        var q = parseFloat(r.RDQTY) || 0;
        var ec = q * (parseFloat(r.RDCOST) || 0);
        var er = q * (parseFloat(r.RDRETL) || 0);
        qty += q; extc += ec; extr += er;
        body += '<tr><td>' + esc(r['RDLIN#']) + '</td><td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDLOC) + '</td><td>' + esc(r.RDCNDT) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td><td class="rq-num">' + q + '</td>' +
            '<td class="rq-num">' + money(r.RDCOST) + '</td>' +
            '<td class="rq-num">' + money(r.RDRETL) + '</td>' +
            '<td class="rq-num">' + money(ec) + '</td>' +
            '<td class="rq-num">' + money(er) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td>' +
            '<td>' + (r.RDRTNF === 'Y' ? fmtDate(r.RDRTDT) : '') + '</td></tr>';
    });
    body += '<tr class="rpt-grand"><td colspan="5">Total</td><td class="rq-num">' + qty + '</td>' +
        '<td></td><td></td><td class="rq-num">' + money(extc) + '</td>' +
        '<td class="rq-num">' + money(extr) + '</td><td></td><td></td></tr>';

    return head + '<table><thead><tr><th>Line</th><th>Item #</th><th>Location</th>' +
        '<th>Coin Date</th><th>Description</th><th class="rq-num">Qty</th>' +
        '<th class="rq-num">Cost</th><th class="rq-num">Retail</th>' +
        '<th class="rq-num">Ext. Cost</th><th class="rq-num">Ext. Retail</th>' +
        '<th>SKU To</th><th>Returned</th></tr></thead><tbody>' + body + '</tbody></table>';
}

function previewReport() {
    if (!selectedReq) {
        swal('Pick a requisition', 'Click a row in the grid first, then Preview Report.', 'info');
        return;
    }
    postAjax({ action: 'get', reqNum: selectedReq }, function (resp) {
        if (!resp.rows.length) {
            swal('Not found', 'Requisition ' + selectedReq + ' was not found.', 'warning');
            return;
        }
        printHtml(reqPrintHtml(resp.rows), 'Requisition ' + selectedReq);
    });
}

// open a clean window with just the report and print it - the web version
// of Access's separate report preview window
function printHtml(innerHtml, title) {
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>' + title + '</title><style>' +
        'body{font-family:Arial,sans-serif;font-size:11px;margin:24px;}' +
        'h3{margin:0 0 12px 0;}table{width:100%;border-collapse:collapse;}' +
        'th,td{border-bottom:1px solid #999;padding:3px 6px;text-align:left;}' +
        '.rq-num{text-align:right;}' +
        '.rpt-group td{font-weight:bold;border-bottom:2px solid #333;padding-top:10px;}' +
        '.rpt-subtotal td,.rpt-grand td{font-weight:bold;}' +
        '.rpt-grand td{border-top:2px solid #333;}' +
        '.rq-pill{font-weight:bold;}' +
        '</style></head><body>' + innerHtml + '</body></html>');
    w.document.close();
    w.focus();
    w.print();
}

function printRequisition() {
    if (!lastReqRows || !lastReqRows.length) { return; }
    printHtml(reqPrintHtml(lastReqRows), 'Requisition ' + $('#viewReqNum').text());
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
