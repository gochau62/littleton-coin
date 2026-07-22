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
.rq-lines input.rq-bad { background: #fff5f5; }
.rq-lines td:has(input.rq-bad) { outline: 2px solid var(--rq-red); outline-offset: -2px; }

/* ---------- item type-ahead dropdown ---------- */
.rq-suggest {
  position: fixed;
  z-index: 200;
  background: #fff;
  border: 1px solid #999;
  border-radius: 4px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
  max-height: 230px;
  overflow-y: auto;
  font-size: .85rem;
}
.rq-suggest div { padding: .3rem .6rem; cursor: pointer; white-space: nowrap; }
.rq-suggest div b { color: var(--rq-blue); }
.rq-suggest div.active, .rq-suggest div:hover { background: var(--rq-accent); }

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
.rq-sel { width: 1.4rem; min-width: 1.4rem; color: var(--rq-green-dk); }
tr.rq-selected .rq-sel::before { content: '\25B6'; font-size: .7rem; }
.rq-reqlink { color: var(--rq-blue); font-weight: 600; cursor: pointer; }
.rq-reqlink:hover { text-decoration: underline; color: var(--rq-blue-hv); }
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
.rq-formrow input[type=text],
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
#addDate { background: #f0f2f1; min-width: 240px; }
.rq-formrow input[type=text], .rq-formrow select { min-width: 190px; }
/* spreadsheet-style sheet: the CELL is the box (one grid, no boxes in
   boxes), the input inside is invisible, the focused cell lights up */
.rq-lines { table-layout: fixed; border-collapse: collapse; }
.rq-lines th { padding: .3rem .45rem; }
.rq-lines tbody td {
  border: 1px solid #c9d2cc;
  padding: 0;
  overflow: hidden;
  background: #fff;
}
.rq-lines tbody tr:nth-child(even) td:not(:last-child) { background: #f5f6f6; }
/* active cell: no fill, just a slim blue box hugging the inside edge */
.rq-lines tbody td:focus-within {
  outline: 2px solid var(--rq-blue);
  outline-offset: -2px;
}
.rq-lines tbody td:last-child {                       /* the ✕ column */
  border: none;
  background: none;
  text-align: center;
  vertical-align: middle;
}
.rq-lines .rq-linedel { padding: 0; font-size: 1.05rem; line-height: 1; color: #b4b4b4; }
.rq-lines .rq-linedel:hover { color: var(--rq-red); }
.rq-lines input {
  width: 100%;
  box-sizing: border-box;
  border: none;
  background: transparent;
  padding: .32rem .45rem;
  font-size: .85rem;
}
.rq-lines input:focus { outline: none; }
.rq-comments { margin-top: .9rem; }
.rq-comments input { width: 100%; }

/* ---------- legacy-style requisition view ---------- */
.rq-lgcy { font-size: .9rem; }
.rq-lgcyrow { margin: .3rem 0; display: flex; align-items: center; }
.rq-lgcyrow label { min-width: 118px; color: var(--rq-text); }
.rq-lgcyrow .rq-lgcyital, .rq-lgcyital { font-style: italic; font-weight: 700; }
.rq-lgcyval, .rq-lgcyrow select, .rq-lgcyrow input {
  display: inline-block;
  min-width: 200px;
  border: 1px solid #999;
  border-radius: 3px;
  background: #fff;
  padding: .15rem .45rem;
  font-size: .9rem;
  color: var(--rq-text);
}
.rq-lgcyval { background: #fafafa; }
.rq-lgcytable { border-collapse: separate; border-spacing: 4px 5px; }
.rq-lgcytable thead th { position: static; background: none; border: none;
                         color: var(--rq-text); font-weight: 700; padding: .2rem .45rem; }
.rq-lgcytable tbody td { border: 1px solid #999; border-radius: 3px;
                         background: #fff; padding: .2rem .45rem; }
.rq-lgcytable tbody td.rq-nobox { border: none; background: none; }

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
.rpt-table .rpt-req td { font-weight: 600; color: var(--rq-muted); background: #fbfcfb; }
.rpt-table .rpt-cmnt { font-style: italic; color: var(--rq-muted); }
.rpt-table .rpt-subtotal td, .rpt-table .rpt-grand td { font-weight: 700; background: #f7faf8; }
.rpt-stamp { margin-top: .75rem; color: var(--rq-muted); font-size: .85rem; }
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
            <th class="rq-sel"></th>
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
          <tr><td colspan="11" class="rq-empty">Loading...</td></tr>
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
            <colgroup>
              <col style="width:12%"><col style="width:8%"><col style="width:9%">
              <col style="width:27%"><col style="width:6%"><col style="width:7%">
              <col style="width:7%"><col style="width:9%"><col style="width:12%">
              <col style="width:3%">
            </colgroup>
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
        <!-- header fields stacked like the legacy request.php?id= view -->
        <div class="rq-lgcy">
          <div class="rq-lgcyrow"><label>ID:</label><span class="rq-lgcyval" id="v_id"></span></div>
          <div class="rq-lgcyrow"><label>Name:</label><span class="rq-lgcyval" id="v_name"></span></div>
          <div class="rq-lgcyrow"><label>Area Code:</label><span class="rq-lgcyval" id="v_acode"></span></div>
          <div class="rq-lgcyrow"><label>Area Type:</label><span class="rq-lgcyval" id="v_atype"></span></div>
          <div class="rq-lgcyrow"><label>Date:</label><span class="rq-lgcyval" id="v_date"></span></div>
          <div class="rq-lgcyrow"><label>Inv DE Number:</label><span class="rq-lgcyval" id="v_denum"></span></div>
          <div class="rq-lgcyrow"><label class="rq-lgcyital">Returned</label><span id="v_returned" style="border:none;"></span></div>
          <div class="rq-lgcyrow"><label>Authorized By:</label><select id="authBy"></select></div>
          <div class="rq-lgcyrow"><label>Comments:</label><input type="text" id="authComments" maxlength="500"></div>
        </div>

        <hr style="border:none;border-top:2px solid #333;margin:.9rem 0;">

        <p style="margin:.25rem 0 .6rem 0;">
          <button type="button" class="rq-btn" id="btnUpdate">Update</button>
        </p>

        <div class="rq-tablewrap">
          <table class="rq-grid rq-lgcytable" id="tblViewLines">
            <thead>
              <tr>
                <th>Item#:</th><th>Location:</th><th>Date:</th><th>Description:</th>
                <th class="rq-num">Qty:</th><th class="rq-num">Cost:</th>
                <th class="rq-num">Retail:</th><th class="rq-num">Add. Cost</th>
                <th>SKU To:</th><th>Returned</th>
              </tr>
            </thead>
            <tbody id="viewLineBody"></tbody>
          </table>
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
    // the entry sheet in a new tab - the same move Access's "Add Requests"
    // button made when it opened request.php in a browser window; the grid
    // stays open behind it and picks up the new req on its next refresh
    $('#btnAdd').on('click', function () {
        window.open('Requisitions_ctl.php?mode=entry', '_blank');
    });
    $('#btnAddLine').on('click', addLineRow);
    $('#btnSubmit').on('click', submitRequisition);
    $('#btnUpdate').on('click', updateCurrent);

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

    // clicking a row SELECTS the requisition (the ▶ gutter shows it, like
    // Access's record pointer) - it does not open it
    $('#gridBody').on('click', 'tr[data-req]', function () {
        selectedReq = $(this).data('req');
        $('#gridBody tr').removeClass('rq-selected');
        $('#gridBody tr[data-req="' + selectedReq + '"]').addClass('rq-selected');
    });

    // clicking the blue req # opens that requisition
    $('#gridBody').on('click', '.rq-reqlink', function () {
        openViewModal($(this).closest('tr').data('req'));
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

    // live item search: type 2+ characters in Item# and pick from the list
    var srchTimer = null;
    $('#lineBody').on('input', '.ln-item', function () {
        var inp = $(this);
        clearTimeout(srchTimer);
        var v = inp.val().trim();
        if (v.length < 2) { hideSuggest(); return; }
        srchTimer = setTimeout(function () {
            postAjax({ action: 'itemsearch', q: v }, function (resp) {
                showSuggest(inp, resp.rows);
            }, true);
        }, 250);
    });
    $('#lineBody').on('blur', '.ln-item', function () {
        setTimeout(hideSuggest, 150);      // let a click on the list land first
    });
    $('#lineBody').on('keydown', '.ln-item', function (e) {
        var box = $('#rqSuggest');
        if (!box.length) { return; }
        var items = box.children();
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            var i = items.index(items.filter('.active'));
            i = (e.key === 'ArrowDown') ? Math.min(i + 1, items.length - 1) : Math.max(i - 1, 0);
            items.removeClass('active').eq(i).addClass('active');
        } else if (e.key === 'Escape') {
            hideSuggest();
            e.stopPropagation();
        }
    });

    // Enter hops to the next field like the legacy form's onEnterKey chain;
    // Enter on the last field of the last row starts a new line. With the
    // item dropdown open, Enter picks the highlighted item instead.
    $('#lineBody').on('keydown', 'input', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        var box = $('#rqSuggest');
        if (box.length && $(this).hasClass('ln-item')) {
            var act = box.children('.active');
            (act.length ? act : box.children().first()).trigger('mousedown');
            return;
        }
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

        // always show the stored text; green only for a real authorizer.
        // (Legacy data has flag=Y rows whose name is still the None
        // placeholder - the old Update set the flag unconditionally.)
        var authName = r.RHAUTB || 'Authorization = None';
        var isReal = r.RHAUTF === 'Y' &&
                     authName !== 'Authorization = None' &&
                     authName !== 'Authorization In Process';
        var auth = '<span class="rq-pill ' + (isReal ? 'rq-ok' : 'rq-warn') + '">' +
                   esc(authName) + '</span>';
        var rush = (r.RHRUSH === 'Y')
            ? '<span class="rq-pill rq-rushpill">RUSH</span>' : '';

        html += '<tr data-req="' + esc(r['RHREQ#']) + '">' +
            '<td class="rq-sel"></td>' +
            '<td><span class="rq-reqlink" title="Open requisition ' + esc(r['RHREQ#']) + '">' +
                esc(r['RHREQ#']) + '</span></td>' +
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
        '<tr><td colspan="11" class="rq-empty">No open requisitions.</td></tr>');
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
    // entry form's pre-authorizer. "Authorization = None" is a REAL row in
    // the AUTHBY list (13k+ historical reqs store that literal string), and
    // it sorts first, so it is the natural default - nothing synthetic added.
    fillSelect('#addAuthBy', resp.authBy, 'CDCODE', 'CDCODE');
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
    $('#addAuthBy').prop('selectedIndex', 0);   // "Authorization = None" sorts first
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

        // legacy-style header fields
        $('#v_id').text(h['RHREQ#']);
        $('#v_name').text(h.RHNAME);
        $('#v_acode').text(h.RHARCD);
        $('#v_atype').text(h.RHARTY);
        $('#v_date').text(fmtDateTimeIso(h.RHRQDT, h.RHRQTM));
        $('#v_denum').text(h.RHBDGE);

        var allReturned = true, anyLine = false;
        $.each(resp.rows, function (i, r) {
            if (r['RDLIN#'] == null) { return; }
            anyLine = true;
            if (r.RDRTNF !== 'Y') { allReturned = false; }
        });
        $('#v_returned').text(anyLine && allReturned ? 'Yes' : 'No');

        // authorized-by: preselect the stored value, adding it if it is an
        // old name no longer on the AUTHBY list
        var sel = $('#authBy');
        var val = h.RHAUTB || 'Authorization = None';
        if (!sel.find('option').filter(function () { return this.value === val; }).length) {
            sel.append('<option>' + esc(val) + '</option>');
        }
        sel.val(val);
        $('#authComments').val(h.RHCMNT);

        var html = '';
        $.each(resp.rows, function (i, r) {
            if (r['RDLIN#'] == null) { return; }
            html += '<tr>' +
                '<td>' + esc(r.RDITEM) + '</td>' +
                '<td>' + esc(r.RDLOC) + '</td>' +
                '<td>' + esc(r.RDCNDT) + '</td>' +
                '<td>' + esc(r.RDDESC) + '</td>' +
                '<td class="rq-num">' + esc(r.RDQTY) + '</td>' +
                '<td class="rq-num">' + money(r.RDCOST) + '</td>' +
                '<td class="rq-num">' + money(r.RDRETL) + '</td>' +
                '<td class="rq-num">' + money(r.RDACST) + '</td>' +
                '<td>' + esc(r.RDSKUT) + '</td>' +
                '<td class="rq-nobox"><input type="checkbox" class="rq-returned"' +
                ' data-req="' + esc(h['RHREQ#']) + '" data-line="' + esc(r['RDLIN#']) + '"' +
                (r.RDRTNF === 'Y' ? ' checked' : '') + '></td>' +
                '</tr>';
        });
        $('#viewLineBody').html(html);

        $('#mdlView').data('req', h['RHREQ#']).prop('hidden', false);
    });
}

// "2011-01-20 11:09:03" like the legacy view's Date field
function fmtDateTimeIso(d8, t6) {
    var s = String(d8);
    if (s.length !== 8 || s === '00000000') { return ''; }
    var t = String(1000000 + (parseInt(t6, 10) || 0)).slice(1);
    return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8) + ' ' +
           t.slice(0, 2) + ':' + t.slice(2, 4) + ':' + t.slice(4, 6);
}

/* ------------------ item search dropdown ------------------ */

function hideSuggest() { $('#rqSuggest').remove(); }

function showSuggest(inp, rows) {
    hideSuggest();
    if (!rows || !rows.length || !inp.is(':focus')) { return; }
    var box = $('<div id="rqSuggest" class="rq-suggest"></div>');
    $.each(rows, function (i, r) {
        $('<div></div>')
            .html('<b>' + esc(r.RDITEM) + '</b> &nbsp; ' + esc(r.RDDESC))
            .data('row', r)
            .appendTo(box);
    });
    var rc = inp[0].getBoundingClientRect();
    box.css({ left: rc.left + 'px', top: (rc.bottom + 2) + 'px', minWidth: rc.width + 'px' });
    $('body').append(box);
    // mousedown (not click) so the pick lands before the input's blur
    box.children().on('mousedown', function (e) {
        e.preventDefault();
        var r = $(this).data('row');
        var row = inp.closest('tr');
        inp.val(r.RDITEM);
        row.find('.ln-desc').val(r.RDDESC);
        row.find('.ln-cndt').val(r.RDCNDT);
        row.find('.ln-cost').val(r.RDCOST);
        row.find('.ln-retail').val(r.RDRETL);
        hideSuggest();
        row.find('.ln-loc').trigger('focus');
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

// Faithful to the printed Access report: grouped by Name, then by
// requisition (date + req#) with the req's comments and "Req. Totals",
// then "Totals by Name", long-date stamp at the end.
function renderMonthlyReport(rows, ym) {
    if (!rows.length) {
        $('#rptBody').html('<div class="rq-empty">No requisitioned product in ' + esc(ym) + '.</div>');
        return;
    }
    var body = '';
    var name = null, req = null;
    var nT = null, rT = null, reqComments = '';
    var grand = { qty: 0, cost: 0, retl: 0, reqs: 0 };

    function totalsLine(cls, label, t) {
        return '<tr class="' + cls + '"><td colspan="9" style="text-align:right;">' + label +
               ' &nbsp; Total Qty: ' + t.qty +
               ' &nbsp;&nbsp; Total Retail: $' + money(t.retl) +
               ' &nbsp;&nbsp; Total Cost: $' + money(t.cost) + '</td></tr>';
    }
    function closeReq() {
        if (req === null) { return; }
        if (reqComments) {
            body += '<tr><td></td><td colspan="8" class="rpt-cmnt">Req. Comments: ' +
                    esc(reqComments) + '</td></tr>';
        }
        body += totalsLine('rpt-subtotal', 'Req. Totals:', rT);
        req = null;
    }
    function closeName() {
        if (name === null) { return; }
        closeReq();
        body += totalsLine('rpt-subtotal', 'Totals by Name:', nT);
    }

    $.each(rows, function (i, r) {
        if (r.RHNAME !== name) {
            closeName();
            name = r.RHNAME;
            nT = { qty: 0, cost: 0, retl: 0 };
            body += '<tr class="rpt-group"><td colspan="9">' + esc(name) + '</td></tr>';
        }
        if (r['RHREQ#'] !== req) {
            closeReq();
            req = r['RHREQ#'];
            rT = { qty: 0, cost: 0, retl: 0 };
            reqComments = r.RHCMNT || '';
            grand.reqs++;
            body += '<tr class="rpt-req"><td colspan="9">' + fmtDate(r.RHRQDT) +
                    ' &nbsp;&mdash;&nbsp; Req # ' + esc(req) + '</td></tr>';
        }
        var q = parseFloat(r.RDQTY) || 0;
        var c = parseFloat(r.RDCOST) || 0, rt = parseFloat(r.RDRETL) || 0;
        var ec = parseFloat(r.RDEXTC) || 0, er = parseFloat(r.RDEXTR) || 0;
        rT.qty += q; rT.cost += ec; rT.retl += er;
        nT.qty += q; nT.cost += ec; nT.retl += er;
        grand.qty += q; grand.cost += ec; grand.retl += er;
        body += '<tr>' +
            '<td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + q + '</td>' +
            '<td class="rq-num">$' + money(c) + '</td>' +
            '<td class="rq-num">$' + money(ec) + '</td>' +
            '<td class="rq-num">$' + money(rt) + '</td>' +
            '<td class="rq-num">$' + money(er) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td>' +
            '</tr>';
    });
    closeName();
    body += totalsLine('rpt-grand', 'Grand Total (' + grand.reqs + ' requisitions):', grand);

    $('#rptBody').html(
        '<h3 class="rpt-title">Monthly Update: Requisitioned Product &mdash; ' + esc(ym) + '</h3>' +
        '<div class="rq-tablewrap"><table class="rq-grid rpt-table"><thead><tr>' +
        '<th>Item #</th><th>Coin Date</th><th>Description</th>' +
        '<th class="rq-num">Qty</th><th class="rq-num">Cost</th><th class="rq-num">Ext. Cost</th>' +
        '<th class="rq-num">Retail</th><th class="rq-num">Ext. Retail</th><th>Sku To</th>' +
        '</tr></thead><tbody>' + body + '</tbody></table></div>' +
        '<div class="rpt-stamp">' + new Date().toLocaleDateString('en-US',
            { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + '</div>');
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

// "1/20/2011 11:09:03 AM" from the decimal date + time pair, matching
// the printed report's Date field
function fmtDateTime(d8, t6) {
    var s = String(d8);
    if (s.length !== 8 || s === '00000000') { return ''; }
    var t = String(1000000 + (parseInt(t6, 10) || 0)).slice(1);   // pad hhmmss
    var hh = parseInt(t.slice(0, 2), 10);
    var ap = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12; if (hh === 0) { hh = 12; }
    return parseInt(s.slice(4, 6), 10) + '/' + parseInt(s.slice(6, 8), 10) + '/' + s.slice(0, 4) +
           ' ' + hh + ':' + t.slice(2, 4) + ':' + t.slice(4, 6) + ' ' + ap;
}

// "Preview Report" - faithful to the printed rptRequest: the four-row
// header block, the Sku#..Sku To column set, stacked totals at bottom
// right, Comments last
function reqPrintHtml(rows) {
    var h = rows[0];
    var head =
        '<table class="rpt-hdr"><tr>' +
        '<td><b>Requisition #:</b> ' + esc(h['RHREQ#']) + '</td>' +
        '<td><b>Requisitioner:</b> ' + esc(h.RHNAME) + '</td></tr>' +
        '<tr><td><b>Rush:</b> ' + (h.RHRUSH === 'Y' ? 'Yes' : 'No') + '</td>' +
        '<td><b>Date:</b> ' + fmtDateTime(h.RHRQDT, h.RHRQTM) + '</td></tr>' +
        '<tr><td><b>Authorized By:</b> ' + esc(h.RHAUTB || 'Authorization = None') + '</td>' +
        '<td><b>DataEntry:</b> ' + esc(h.RHBDGE) + '</td></tr>' +
        '<tr><td><b>Area Code:</b> ' + esc(h.RHARCD) + '</td>' +
        '<td><b>Area Type:</b> ' + esc(h.RHARTY) + '</td></tr></table><br>';

    var qty = 0, extc = 0, extr = 0;
    var body = '';
    $.each(rows, function (i, r) {
        if (r['RDLIN#'] == null) { return; }
        var q = parseFloat(r.RDQTY) || 0;
        var ec = q * (parseFloat(r.RDCOST) || 0);
        var er = q * (parseFloat(r.RDRETL) || 0);
        qty += q; extc += ec; extr += er;
        body += '<tr><td>' + esc(r.RDITEM) + '</td><td>' + esc(r.RDLOC) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td><td>' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + q + '</td>' +
            '<td class="rq-num">' + money(r.RDCOST) + '</td>' +
            '<td class="rq-num">' + money(ec) + '</td>' +
            '<td class="rq-num">' + money(r.RDRETL) + '</td>' +
            '<td class="rq-num">' + money(er) + '</td>' +
            '<td class="rq-num">' + money(r.RDACST) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td></tr>';
    });

    return head +
        '<table><thead><tr><th>Sku #</th><th>Loc</th><th>Coin Date</th><th>Description</th>' +
        '<th class="rq-num">Qty</th><th class="rq-num">Cost</th><th class="rq-num">Ext Cost</th>' +
        '<th class="rq-num">Retail</th><th class="rq-num">Ext Retail</th>' +
        '<th class="rq-num">Add Cost$</th><th>Sku To</th></tr></thead><tbody>' +
        body + '</tbody></table>' +
        '<div class="rpt-totals">Total Qty: ' + qty + '<br>' +
        'Total Retail: $' + money(extr) + '<br>' +
        'Total Cost: $' + money(extc) + '</div>' +
        (h.RHCMNT ? '<div><b>Comments:</b> ' + esc(h.RHCMNT) + '</div>' : '');
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
        '.rpt-req td{font-weight:bold;color:#444;}' +
        '.rpt-cmnt{font-style:italic;color:#444;}' +
        '.rpt-subtotal td,.rpt-grand td{font-weight:bold;}' +
        '.rpt-grand td{border-top:2px solid #333;}' +
        '.rpt-hdr td{border:none;padding:2px 30px 2px 0;}' +
        '.rpt-totals{text-align:right;font-weight:bold;margin:12px 0;line-height:1.6;}' +
        '.rpt-stamp{margin-top:12px;color:#555;}' +
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

function updateCurrent() {
    var reqNum = $('#mdlView').data('req');
    postAjax({
        action: 'update',
        reqNum: reqNum,
        authBy: $('#authBy').val(),
        comments: $('#authComments').val()
    }, function () {
        $('#mdlView').prop('hidden', true);
        swal('Updated', 'Record req_num=' + reqNum + ' has been updated.', 'success');
        loadGrid();
    });
}
</script>

<?php } ?>
