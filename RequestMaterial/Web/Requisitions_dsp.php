<?php
/*    ***************************************************  -->
<!--  * Program Name - Requisitions_dsp.php             *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/20/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

function dspRequisitions($user, $rqLookups = null, $mode = '') {
?>

<style>
/* all the styling for this screen lives here, not in a shared stylesheet */
:root {
  /* one place for the house colors, so every green and blue on the screen matches */
  --rq-green-dk: #1C4532;
  --rq-green:    #2e8b57;
  --rq-green-hv: #1e6e43;
  --rq-blue:     #007bff;
  --rq-blue-hv:  #0056b3;
  --rq-accent:   #eaf6ee;
  --rq-bg:       #f8f8f8;
  --rq-line:     #dfe6e1;
  --rq-text:     #222;
  --rq-muted:    #5f6b62;
  --rq-amber:    #9a6a14;
  --rq-red:      #c0392b;
}

.rq-app {
  font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
  color: var(--rq-text);
  background: var(--rq-bg);
  padding: 0 0 2rem 0;
}

/* dark green title bar, with the signed in user and the clock on the right */
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

/* strip above the grid: action buttons, the Show list, and the filter box */
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
.rq-show  { color: var(--rq-muted); font-size: .85rem; user-select: none; }
.rq-showsel { margin-left: .3rem; padding: .35rem .5rem; font-size: .85rem;
              border: 1px solid var(--rq-line); border-radius: 6px; background: #fff;
              color: var(--rq-text); }
.rq-updated { color: var(--rq-muted); font-size: .8rem; }
.rq-updated.rq-stale { color: var(--rq-red); font-weight: 700; }
.rq-lines input.rq-bad { background: #fff5f5; }
.rq-lines td:has(input.rq-bad) { outline: 2px solid var(--rq-red); outline-offset: -2px; }

/* typeahead list that drops under the item number and badge boxes */
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

/* white pill buttons, used on the toolbar and inside the popup windows */
.rq-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: .45rem 1.1rem;
  border: 1px solid #b4b4b4;
  /* house pill buttons */
  border-radius: 50px;
  background: #fff;
  color: var(--rq-text);
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
}
/* hover only recolors the outline and text, so the plain button stays white */
.rq-btn:hover { border-color: var(--rq-blue); color: var(--rq-blue); }
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

/* the grid sits in a rounded white card and scrolls inside it, header staying put */
.rq-card {
  background: #fff;
  border: 1px solid var(--rq-line);
  border-radius: 8px;
  margin: 0 1.25rem;
  overflow: hidden;
}
.rq-tablewrap { overflow-x: auto; max-height: 70vh; }
.rq-grid { width: 100%; border-collapse: collapse; font-size: .88rem; }
/* fixed column widths; borders stay uncollapsed so the frozen header keeps its lines */
#tblGrid { table-layout: fixed; min-width: 780px; font-size: .86rem;
           border-collapse: separate; border-spacing: 0; }
#tblGrid tbody td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                    padding: .4rem .5rem; }
#tblGrid .rq-ret { font-size: .78rem; color: var(--rq-muted); text-align: right; }
#tblGrid .rq-ret input { vertical-align: middle; }
#tblGrid .rq-ret label { margin: 0 .35rem 0 .3rem; }
#tblGrid .rq-retdate { width: 5.4rem; padding: .12rem .35rem; font-size: .78rem;
                       border: 1px solid var(--rq-line); border-radius: 4px; }
#tblGrid .rq-retdone { color: var(--rq-green); font-weight: 700; font-size: .78rem; }
#tblGrid .rq-sel { vertical-align: middle; }
#tblGrid tbody tr.rq-r1 td { border-bottom: none; padding-bottom: .12rem; }
#tblGrid tbody tr.rq-r1 td.rq-sel { border-bottom: 1px solid var(--rq-line); }
#tblGrid tbody tr.rq-r2 td { padding-top: 0; }
#tblGrid .rq-desc { color: var(--rq-muted); font-size: .82rem; }
/* striping, hover and selection cover the whole record, both of its rows */
#tblGrid tbody tr { background: #fff; }
#tblGrid tbody tr.rq-alt { background: #f7faf8; }
#tblGrid tbody tr.rq-hov { background: var(--rq-accent); }
#tblGrid tbody tr.rq-selected { background: #dff0e5; }
.rq-grid thead th {
  position: sticky;
  top: 0;
  /* header always paints over cell content */
  z-index: 5;
  background: var(--rq-accent);
  color: var(--rq-green-dk);
  text-align: left;
  padding: .55rem .7rem;
  border-bottom: 2px solid var(--rq-line);
  white-space: nowrap;
}
/* shadows, not borders, so the frozen header keeps its lines and paints over the rows */
#tblGrid thead th {
  border: none;
  box-shadow: inset -1px 0 0 0 #333, inset 0 1px 0 0 #333, 0 2px 0 0 #333;
}
#tblGrid thead th:first-child {
  box-shadow: inset 1px 0 0 0 #333, inset -1px 0 0 0 #333, inset 0 1px 0 0 #333, 0 2px 0 0 #333;
}
/* clickable sort headers */
#tblGrid thead th[data-sortkey] { cursor: pointer; user-select: none; }
#tblGrid thead th[data-sortkey]:hover { background: #dbeee2; }
#tblGrid thead th .rq-sortind { color: var(--rq-green); font-size: .7rem; margin-left: .2rem; }
#tblGrid thead th.rq-sorted { background: #d3ecdd; }
.rq-grid tbody td {
  padding: .45rem .7rem;
  border-bottom: 1px solid var(--rq-line);
}
.rq-grid:not(#tblGrid) tbody tr:nth-child(even) { background: #f7faf8; }
#tblGrid tbody tr { cursor: pointer; }
.rq-grid tbody tr.rq-selected { background: #dff0e5; }
.rq-sel { width: 1.4rem; min-width: 1.4rem; color: var(--rq-green-dk); }
tr.rq-selected .rq-sel::before { content: '\25B6'; font-size: .7rem; }
.rq-reqlink { color: var(--rq-blue); font-weight: 600; cursor: pointer; }
.rq-reqlink:hover { text-decoration: underline; color: var(--rq-blue-hv); }
/* badge box: editable in the grid; all lines of a req share it */
.rq-badgewrap { position: relative; display: inline-block; width: 100%; }
.rq-grid .rq-badge { width: 100%; box-sizing: border-box;
                     padding: .2rem 1rem .2rem .35rem; font-size: .85rem;
                     border: 1px solid var(--rq-line); border-radius: 4px; }
.rq-grid .rq-badge:focus { outline: 2px solid var(--rq-blue); outline-offset: -1px;
                           border-color: var(--rq-blue); }
/* the small arrow that opens the employee list, like the old Access badge box */
.rq-badgedd { position: absolute; right: 2px; top: 50%; transform: translateY(-50%);
              border: 0; background: none; padding: 0 .15rem; line-height: 1;
              font-size: .7rem; color: var(--rq-muted); cursor: pointer; }
.rq-badgedd:hover { color: var(--rq-blue); }
.rq-suggest-empty { padding: .3rem .6rem; color: var(--rq-muted);
                    font-style: italic; cursor: default; }
.rq-num { text-align: right; }
.rq-empty { text-align: center; color: var(--rq-muted); padding: 1.5rem !important; }

/* small colored labels in the Authorized and Rush columns */
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

/* modals: the shaded overlay and the white window that add, view and report all share */
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

/* labels, boxes and the Rush choice on the new requisition form */
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
/* line grid looks like a spreadsheet: the cell draws the box, the input shows none */
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
/* last column is the remove line button, so it gets no box and no shading */
.rq-lines tbody td:last-child {
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

/* the view window keeps the boxed, stacked look of the old requisition screen */
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

/* entry only mode: the work floor form fills the page and cannot be closed */
.rq-entry .rq-toolbar, .rq-entry .rq-card { display: none; }
.rq-entry #mdlAdd .rq-modal-head .rq-x,
.rq-entry #mdlAdd .rq-modal-foot [data-close] { display: none; }
.rq-entry .rq-overlay { background: var(--rq-bg); padding-top: 1.5rem; }
/* wider window in entry mode so every column of the line sheet fits */
.rq-entry .rq-modal-wide { max-width: 1280px; }

/* monthly report: cleaner layout, but the text sizes match the old printed report */
#rptMonthSel, #rptYearSel { padding: .35rem .5rem; border: 1px solid var(--rq-line);
                            border-radius: 6px; background: #fff; font-size: .9rem; }
.rpt-stamp { margin-top: .75rem; color: var(--rq-muted); font-size: .8rem; }
.rpt-mutitle { font-family: Georgia, "Times New Roman", serif; font-style: italic;
               color: #17306e; margin: 0 0 1px 0; font-size: 1.35rem; }
.rpt-musub { color: #5b6371; font-size: .8rem; margin: 0 0 .7rem 0; }
.rpt-ital { font-family: Georgia, "Times New Roman", serif; font-style: italic;
            font-weight: 700; color: #17306e; }
.rpt-mu table { width: 100%; border-collapse: collapse; font-size: .82rem; line-height: 1.3; }
.rpt-mu th, .rpt-mu td { border: none; padding: 2px 8px; text-align: left; vertical-align: top; }
.rpt-mu thead th { color: #17306e; font-weight: 700; font-size: .72rem;
                   text-transform: uppercase; letter-spacing: .04em;
                   padding-bottom: 4px; border-bottom: 1.5px solid #4a5c93; }
.rpt-mu .rq-num { text-align: right; font-variant-numeric: tabular-nums; }
.rpt-mu tr.rpt-line td { border-bottom: 1px solid #dce1ea; }
.rpt-mu tr.rpt-alt td { background: #f6f8fc; }
.rpt-mu tr.rpt-name td { font-weight: 700; color: #17306e; font-size: .98rem;
                         background: #e4eafb; padding: 7px 8px;
                         border-top: 2px solid #17306e; border-bottom: 1px solid #c3ccdf; }
.rpt-mu tr.rpt-reqhd td { color: #5b6371; padding-top: 5px; font-weight: 600; }
.rpt-mu tr.rpt-reqhd .rpt-rq { color: #17306e; font-weight: 700; }
.rpt-mu tr.rpt-cmt td { color: #5b6371; font-size: .78rem; padding-top: 1px; }
.rpt-ret .rpt-y { color: #1c7a4c; font-weight: 700; font-variant-numeric: tabular-nums; }
.rpt-ret .rpt-n { color: #8a91a0; }
.rpt-totblk { display: flex; flex-wrap: wrap; gap: .1rem 2rem; align-items: baseline;
              margin: 1px 0 3px; padding: 2px 8px; border-top: 1px solid #b7bdca; }
.rpt-totlbl { min-width: 165px; }
.rpt-totv { font-variant-numeric: tabular-nums; }
.rpt-totv .rpt-ital { margin-right: .3rem; }
.rpt-nametot { background: #f2f5fc; border-top-color: #4a5c93; }
.rpt-grand { margin-top: 5px; border-top: 2px solid #17306e; background: #eaeff9; }
/* the surrounding page puts a line on every table cell, so the report block sets its own */
#rptBody .rpt-mu th, #rptBody .rpt-mu td { border: 0; background: none; }
#rptBody .rpt-mu thead th { border-bottom: 1.5px solid #4a5c93; }
#rptBody .rpt-mu tr.rpt-line td { border-bottom: 1px solid #dce1ea; }
#rptBody .rpt-mu tr.rpt-alt td { background: #f6f8fc; }
#rptBody .rpt-mu tr.rpt-name td { background: #e4eafb; border-top: 2px solid #17306e; border-bottom: 1px solid #c3ccdf; }
.rq-modal-head .rq-btn { margin-right: .4rem; }
</style>

<div class="rq-app<?php echo $mode === 'entry' ? ' rq-entry' : ''; ?>">

  <header class="rq-topbar">
    <h1><?php echo $mode === 'entry' ? 'Requisition Entry' : 'Requisition Material'; ?></h1>
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
    <label class="rq-auto">
      <input type="checkbox" id="chkAutoRefresh" checked> Auto-refresh
    </label>
    <label class="rq-show">Show
      <select id="selShow" class="rq-showsel">
        <option value="O" selected>Open</option>
        <option value="R">Returned</option>
        <option value="A">All</option>
      </select>
    </label>
    <input type="search" id="txtFilter" class="rq-filter"
           placeholder="Filter by req #, name, item, badge...">
    <span class="rq-count" id="lblCount"></span>
    <span class="rq-updated" id="lblUpdated" title="Last successful refresh"></span>
  </div>

  <div class="rq-card">
    <div class="rq-tablewrap">
      <!-- compact fixed pixel columns with the leftover width going to Requestor, so nothing gets crushed -->
      <table class="rq-grid" id="tblGrid">
        <colgroup>
          <col style="width:22px"><col style="width:58px"><col style="width:88px">
          <col>
          <col style="width:96px"><col style="width:40px"><col style="width:52px">
          <col style="width:88px"><col style="width:160px"><col style="width:68px">
        </colgroup>
        <thead>
          <tr>
            <th class="rq-sel"></th>
            <th data-sortkey="RHREQ#">Req #<span class="rq-sortind"></span></th>
            <th data-sortkey="RHRQDT">Date<span class="rq-sortind"></span></th>
            <th data-sortkey="RHNAME">Requestor<span class="rq-sortind"></span></th>
            <th data-sortkey="RDITEM">Item #<span class="rq-sortind"></span></th>
            <th data-sortkey="RDLOC">Loc<span class="rq-sortind"></span></th>
            <th class="rq-num" data-sortkey="RDQTY">Qty<span class="rq-sortind"></span></th>
            <th data-sortkey="RHBDGE">Badge #<span class="rq-sortind"></span></th>
            <th data-sortkey="RHAUTB">Authorized<span class="rq-sortind"></span></th>
            <th data-sortkey="RHRUSH">Rush<span class="rq-sortind"></span></th>
          </tr>
        </thead>
        <tbody id="gridBody">
          <tr><td colspan="10" class="rq-empty">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Requisition window, standing in for the old entry page -->
  <div class="rq-overlay" id="mdlAdd" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>New Requisition</h2>
        <button type="button" class="rq-x" data-close="mdlAdd">&times;</button>
      </div>

      <div class="rq-modal-body">
        <!-- requestor, date, rush and the area choices that apply to the whole requisition -->
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

  <!-- View and Update window, doing the job of the old detail and update pages -->
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
        <!-- one requisition stacked label over value; Authorized By and Comments are editable here -->
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
                <th>SKU To:</th><th>Returned</th><th>Date Ret.</th>
              </tr>
            </thead>
            <tbody id="viewLineBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Report window, the old Requested Material Summary report rebuilt for the browser -->
  <div class="rq-overlay" id="mdlReport" hidden>
    <div class="rq-modal rq-modal-wide">
      <div class="rq-modal-head">
        <h2>Monthly Update: Requisitioned Product</h2>
        <div>
          <!-- plain month and year dropdowns because Firefox draws a month picker as a bare text box -->
          <select id="rptMonthSel" title="Month"></select>
          <select id="rptYearSel" title="Year"></select>
          <button type="button" class="rq-btn rq-btn-primary" id="btnPrintReport">&#128424; Print</button>
          <button type="button" class="rq-x" data-close="mdlReport">&times;</button>
        </div>
      </div>
      <div class="rq-modal-body" id="rptBody">
        <div class="rq-empty">Loading this month...</div>
      </div>
    </div>
  </div>

</div>

<script>
// Requisition Material frontend logic (grid, add/entry, view, reports)
var RQ_PRELOAD = <?php echo $rqLookups ? json_encode($rqLookups) : 'null'; ?>;
// entry mode comes from the workfloor shortcut link and is checked all through this script
var RQ_MODE = '<?php echo $mode; ?>';
var gridRows = [];
var lastGridJson = '';
// which lines the grid shows: O open (default), R returned, A all
var gridShow = 'O';
// the grid opens sorted by date with the newest requisitions first
var gridSort = { key: 'RHRQDT', dir: -1 };
// brief pause before the Returned or All search runs, so it does not fire on every keystroke
var gridSearchTimer = null;
var lookups = null;
var autoTimer = null;
// grid row last clicked, the current record the arrow points at
var selectedReq = null;
// the rows of the requisition showing in the view window, reused by its Print button
var lastReqRows = null;
// checked Return Items wait here with their date until the next refresh saves them
var pendingReturns = {};
// true while arrow keys travel the sheet, so landing on an Item # cell does not pop its menu
var sheetNavMove = false;

$(document).ready(function () {
    loadLookups();
    tickClock();
    setInterval(tickClock, 1000);

    if (RQ_MODE === 'entry') {
        // entry mode: the entry form IS the page
        openAddModal();
    } else {
        loadGrid();
        startAutoRefresh();
    }

    $('#btnRefresh').on('click', loadGrid);
    $('#chkAutoRefresh').on('change', startAutoRefresh);
    // changing Show reloads from the server so returned lines can come back into view
    $('#selShow').on('change', function () {
        gridShow = $(this).val();
        lastGridJson = '';
        loadGrid();
    });
    // with Show on Open the filter works on the loaded rows; Returned and All search the server
    $('#txtFilter').on('input', function () {
        if (gridShow === 'O') {
            renderGrid();
        } else {
            clearTimeout(gridSearchTimer);
            gridSearchTimer = setTimeout(function () {
                lastGridJson = '';
                loadGrid();
            }, 300);
        }
    });
    // click a column header to sort by it; click again to flip direction
    $('#tblGrid thead').on('click', 'th[data-sortkey]', function () {
        var key = $(this).data('sortkey');
        if (gridSort.key === key) { gridSort.dir = -gridSort.dir; }
        else { gridSort.key = key; gridSort.dir = 1; }
        renderGrid();
    });
    // entry sheet in a new tab; the grid picks up the new req on refresh
    $('#btnAdd').on('click', function () {
        window.open('Requisitions_ctl.php?mode=entry', '_blank');
    });
    $('#btnAddLine').on('click', addLineRow);
    $('#btnSubmit').on('click', submitRequisition);
    $('#btnUpdate').on('click', updateCurrent);

    // report buttons: the Monthly Update and the per requisition preview
    $('#btnMonthly').on('click', openMonthlyReport);
    $('#btnPreview').on('click', previewReport);
    // changing month or year reruns the report on the spot
    $('#rptMonthSel, #rptYearSel').on('change', runMonthlyReport);
    $('#btnPrintReport').on('click', function () {
        printHtml($('#rptBody').html(), 'Monthly Update: Requisitioned Product');
    });
    $('#btnPrintReq').on('click', printRequisition);

    // X and Cancel buttons close whichever window they sit in
    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).prop('hidden', true);
    });

    // clicking a row selects it (the ▶ gutter), it does not open it
    $('#gridBody').on('click', 'tr[data-req]', function () {
        selectedReq = $(this).data('req');
        $('#gridBody tr').removeClass('rq-selected');
        $('#gridBody tr[data-req="' + selectedReq + '"]').addClass('rq-selected');
    });

    // clicking the blue req # opens that requisition
    $('#gridBody').on('click', '.rq-reqlink', function () {
        openViewModal($(this).closest('tr').data('req'));
    });

    // hover highlights the whole record, both of its rows
    $('#gridBody').on('mouseenter', 'tr[data-rec]', function () {
        $('#gridBody tr[data-rec="' + $(this).data('rec') + '"]').addClass('rq-hov');
    }).on('mouseleave', 'tr[data-rec]', function () {
        $('#gridBody tr[data-rec="' + $(this).data('rec') + '"]').removeClass('rq-hov');
    });

    // checking Return Item fills in today's date, but nothing saves until the next refresh
    $('#gridBody').on('change', '.rq-gridret', function () {
        var cb = $(this);
        var key = cb.attr('data-req') + '|' + cb.attr('data-line');
        var dateInp = cb.closest('.rq-ret').find('.rq-retdate');
        if (cb.is(':checked')) {
            if (dateInp.val().trim() === '') { dateInp.val(fmtToday()); }
            pendingReturns[key] = dateInp.val().trim();
        } else {
            delete pendingReturns[key];
            dateInp.val('');
        }
    });

    // keep the pending date in step while the user edits it
    $('#gridBody').on('input', '.rq-retdate', function () {
        var td = $(this).closest('.rq-ret');
        var cb = td.find('.rq-gridret');
        if (cb.is(':checked')) {
            pendingReturns[cb.attr('data-req') + '|' + cb.attr('data-line')] =
                $(this).val().trim();
        }
    });

    // the badge box saves as soon as you leave it; every line of the requisition shares it
    $('#gridBody').on('change', '.rq-badge', function () {
        var inp = $(this);
        var reqNum = String(inp.data('req'));
        var val = inp.val().trim();
        // only a real badge number saves; typed name text reverts to what was stored
        var codes = badgeCodeSet();
        if (val !== '' && !codes[val] && !/^\d+$/.test(val)) {
            inp.val(reqBadge(reqNum));
            return;
        }
        postAjax({
            action: 'update',
            reqNum: reqNum,
            badge: val
        }, function () {
            // update the requisition's other lines in place so the grid keeps its scroll spot
            $.each(gridRows, function (i, r) {
                if (String(r['RHREQ#']) === reqNum) { r.RHBDGE = val; }
            });
            lastGridJson = JSON.stringify(gridRows);
            $('#gridBody .rq-badge').each(function () {
                if (this !== inp[0] && String($(this).data('req')) === reqNum) {
                    $(this).val(val);
                }
            });
        });
    });

    // the badge box opens the employee list on focus and narrows it as you type
    $('#gridBody').on('focusin', '.rq-badge', function () {
        showBadgeSuggest($(this), false);
    });
    $('#gridBody').on('input', '.rq-badge', function () {
        showBadgeSuggest($(this), true);
    });
    $('#gridBody').on('blur', '.rq-badge', function () {
        // short wait so a click on the list registers before the menu closes
        setTimeout(hideBadgeSuggest, 150);
    });
    // small arrow opens or closes the list without taking focus off the box
    $('#gridBody').on('mousedown', '.rq-badgedd', function (e) {
        e.preventDefault();
        var inp = $(this).siblings('.rq-badge');
        if ($('#rqBadgeSuggest').length) {
            hideBadgeSuggest();
        } else {
            inp.trigger('focus');
            showBadgeSuggest(inp, false);
        }
    });
    $('#gridBody').on('keydown', '.rq-badge', function (e) {
        var box = $('#rqBadgeSuggest');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!box.length) { return; }
            e.preventDefault();
            var items = box.children();
            var i = items.index(items.filter('.active'));
            i = (e.key === 'ArrowDown') ? Math.min(i + 1, items.length - 1)
                                        : Math.max(i - 1, 0);
            items.removeClass('active').eq(i).addClass('active');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var act = box.children('.active');
            if (act.length) { act.trigger('mousedown'); }
            // no highlighted row: Enter commits what was typed
            else { $(this).trigger('blur'); }
        } else if (e.key === 'Escape') {
            hideBadgeSuggest();
        }
    });

    // checking Returned in the view window saves that line right away
    $('#viewLineBody').on('change', '.rq-returned', function () {
        var cb = $(this);
        postAjax({
            action: 'returned',
            reqNum: cb.data('req'),
            lineNum: cb.data('line'),
            flag: cb.is(':checked') ? 'Y' : 'N'
        }, function () { loadGrid(); });
    });

    // ESC closes the topmost window (never the entry mode form)
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && RQ_MODE !== 'entry') {
            $('.rq-overlay').not('[hidden]').last().prop('hidden', true);
        }
    });

    // switching back to this tab refreshes the grid right away
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { loadGrid(true); }
    });

    // leaving a full item number fills the blank description, item date and price boxes
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

    // item list drops down when you land in the box, and narrows as you type
    var srchTimer = null;
    $('#lineBody').on('input', '.ln-item', function () {
        var inp = $(this);
        clearTimeout(srchTimer);
        var v = inp.val().trim();
        srchTimer = setTimeout(function () {
            postAjax({ action: 'itemsearch', q: v }, function (resp) {
                showSuggest(inp, resp.rows);
            }, true);
        }, 250);
    });
    $('#lineBody').on('focusin', '.ln-item', function () {
        // arrow key travel through the column should not pop the menu
        if (sheetNavMove) { sheetNavMove = false; return; }
        var inp = $(this);
        postAjax({ action: 'itemsearch', q: inp.val().trim() }, function (resp) {
            showSuggest(inp, resp.rows);
        }, true);
    });
    $('#lineBody').on('blur', '.ln-item', function () {
        // small pause so a click on the list lands before the menu closes
        setTimeout(hideSuggest, 150);
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
        } else if (e.key === 'Tab' && !e.shiftKey) {
            // Tab picks the highlighted row or the top match; an empty box just tabs on
            var act = items.filter('.active');
            if (act.length) {
                e.preventDefault();
                act.trigger('mousedown');
            } else if ($(this).val().trim() !== '') {
                e.preventDefault();
                items.first().trigger('mousedown');
            } else {
                hideSuggest();
            }
        } else if (e.key === 'Escape') {
            hideSuggest();
            e.stopPropagation();
        }
    });

    // Enter moves to the next box; past the last box it adds another requisition line
    $('#lineBody').on('keydown', 'input', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        var box = $('#rqSuggest');
        if (box.length && $(this).hasClass('ln-item')) {
            // pick only a deliberate choice; an untouched box hops on
            var act = box.children('.active');
            if (act.length) { act.trigger('mousedown'); return; }
            if ($(this).val().trim() !== '') {
                box.children().first().trigger('mousedown');
                return;
            }
            hideSuggest();
        }
        var inputs = $('#lineBody input:visible');
        var i = inputs.index(this);
        if (i === inputs.length - 1) {
            addLineRow();
            inputs = $('#lineBody input:visible');
        }
        inputs.eq(i + 1).trigger('focus');
    });

    // up and down arrows change rows; left and right jump cells once the cursor reaches the end of the text
    $('#lineBody').on('keydown', 'input', function (e) {
        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) < 0) { return; }
        // an open item menu owns the up/down keys
        if ($('#rqSuggest').length && $(this).hasClass('ln-item')) { return; }
        var cell = $(this).closest('td');
        var row = cell.closest('tr');
        var target = null;
        if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
            var col = row.children('td').index(cell);
            var tr = (e.key === 'ArrowDown') ? row.next('tr') : row.prev('tr');
            target = tr.children('td').eq(col).find('input');
        } else {
            var atStart = this.selectionStart === 0 && this.selectionEnd === 0;
            var atEnd = this.selectionStart === this.value.length &&
                        this.selectionEnd === this.value.length;
            if (e.key === 'ArrowLeft' && atStart) { target = cell.prev('td').find('input'); }
            else if (e.key === 'ArrowRight' && atEnd) { target = cell.next('td').find('input'); }
        }
        if (target && target.length) {
            e.preventDefault();
            sheetNavMove = target.hasClass('ln-item');
            target.trigger('focus');
            // the cell you land on has its text selected, so typing replaces it
            target[0].select();
        }
    });

    // deep links: id opens that requisition, action add opens the entry form
    if (RQ_MODE !== 'entry') {
        var qs = new URLSearchParams(window.location.search);
        if (qs.get('id')) {
            openViewModal(parseInt(qs.get('id'), 10));
        } else if (qs.get('action') === 'add') {
            openAddModal();
        }
    }
});

// clock and auto refresh

function tickClock() {
    var now = new Date().toLocaleString();
    $('#rqClock').text(now);
    // Date box on the entry form keeps ticking along with the header clock
    $('#addDate').val(now);
}


function startAutoRefresh() {
    if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
    if ($('#chkAutoRefresh').is(':checked')) {
        // with the box checked it quietly reloads once a minute, like the old Access timer
        autoTimer = setInterval(function () {
            if (!document.hidden) { loadGrid(true); }
        }, 60000);
    }
}


// ajax and shared helpers

// a quiet background request that fails just turns the Updated time red, no error box
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


// today as mm/dd/yyyy, for the Return Item date autofill
function fmtToday() {
    var d = new Date();
    return String(101 + d.getMonth()).slice(1) + '/' +
           String(100 + d.getDate()).slice(1) + '/' + d.getFullYear();
}


// stored dates are an 8 digit yyyymmdd number, so show them as mm/dd/yyyy
function fmtDate(dec) {
    var s = String(dec);
    if (s.length !== 8 || s === '00000000') { return ''; }
    return s.substr(4, 2) + '/' + s.substr(6, 2) + '/' + s.substr(0, 4);
}


// turn a typed mm/dd/yyyy date into the 8 digit stored number; 0 when it is not a real date
function parseDateMDY(s) {
    var m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/.exec(s);
    if (!m) { return 0; }
    var mo = parseInt(m[1], 10), dy = parseInt(m[2], 10), yr = parseInt(m[3], 10);
    var d = new Date(yr, mo - 1, dy);
    if (d.getFullYear() !== yr || d.getMonth() !== mo - 1 || d.getDate() !== dy) { return 0; }
    return yr * 10000 + mo * 100 + dy;
}


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// esc() for attribute values (quotes escaped too)
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
}


// grid

// a refresh submits pending Return Items first, then reloads the grid
function loadGrid(background) {
    // Open pulls all its rows and filters them in the browser; Returned and All search on the server
    var q = (gridShow === 'O') ? '' : $('#txtFilter').val().trim();
    submitPendingReturns(function () {
        postAjax({ action: 'list', show: gridShow, q: q }, function (resp) {
            $('#lblUpdated').removeClass('rq-stale')
                .text('Updated ' + new Date().toLocaleTimeString());
            var j = JSON.stringify(resp.rows);
            // nothing changed, skip the redraw
            if (j === lastGridJson) { return; }
            lastGridJson = j;
            gridRows = resp.rows;
            renderGrid();
        }, background === true);
    }, background === true);
}


// checked Return Items are saved before the grid reloads; a bad date halts it so nothing is lost
function submitPendingReturns(next, silent) {
    var keys = Object.keys(pendingReturns);
    if (!keys.length) { next(); return; }

    var bad = 0;
    $.each(keys, function (i, k) {
        if (!parseDateMDY(String(pendingReturns[k]))) { bad++; }
    });
    if (bad > 0) {
        if (!silent) {
            swal('Check the return date',
                 'A checked Return Item has an invalid date (mm/dd/yyyy). Fix the date or uncheck the box, then refresh again.',
                 'warning');
        }
        return;
    }

    var done = 0;
    $.each(keys, function (i, k) {
        var p = k.split('|');
        postAjax({
            action: 'returned',
            reqNum: p[0],
            lineNum: p[1],
            flag: 'Y',
            dateRet: parseDateMDY(String(pendingReturns[k]))
        }, function () {
            delete pendingReturns[k];
            done++;
            if (done === keys.length) { next(); }
        }, silent);
    });
}


function renderGrid() {
    var filter = $('#txtFilter').val().toLowerCase();
    var html = '';
    var shown = 0;

    // Returned and All come back already filtered and capped at 500, so only Open filters here
    var rows = gridRows;
    if (gridShow === 'O' && filter) {
        rows = $.grep(gridRows, function (r) {
            var hay = (r['RHREQ#'] + ' ' + r.RHNAME + ' ' + r.RDITEM + ' ' +
                       r.RDDESC + ' ' + (r.RHBDGE || '')).toLowerCase();
            return hay.indexOf(filter) >= 0;
        });
    }
    // header sort: sort a copy so the server order is kept when unsorted
    if (gridSort.key) { rows = rows.slice().sort(gridCompare); }

    $.each(rows, function (i, r) {
        shown++;

        // the name text decides the color; only Authorization None and In Process stay yellow
        var authName = r.RHAUTB || 'Authorization = None';
        var isReal = authName !== 'Authorization = None' &&
                     authName !== 'Authorization In Process';
        var auth = '<span class="rq-pill ' + (isReal ? 'rq-ok' : 'rq-warn') + '">' +
                   esc(authName) + '</span>';
        var rush = (r.RHRUSH === 'Y')
            ? '<span class="rq-pill rq-rushpill">RUSH</span>' : '';

        // each record draws as two rows: the fields on top, description and Return Item below
        var recAttr = ' data-req="' + esc(r['RHREQ#']) + '" data-rec="' + shown + '"' +
                      ' class="' + (shown % 2 === 0 ? 'rq-alt ' : '');
        html += '<tr' + recAttr + 'rq-r1">' +
            '<td class="rq-sel" rowspan="2"></td>' +
            '<td><span class="rq-reqlink" title="Open requisition ' + esc(r['RHREQ#']) + '">' +
                esc(r['RHREQ#']) + '</span></td>' +
            '<td>' + fmtDate(r.RHRQDT) + '</td>' +
            '<td title="' + attr(r.RHNAME) + '">' + esc(r.RHNAME) + '</td>' +
            '<td title="' + attr(r.RDITEM) + '">' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDLOC) + '</td>' +
            '<td class="rq-num">' + esc(r.RDQTY) + '</td>' +
            '<td><span class="rq-badgewrap"><input class="rq-badge" maxlength="10"' +
            ' data-req="' + esc(r['RHREQ#']) + '"' +
            ' value="' + attr(r.RHBDGE) + '">' +
            '<button type="button" class="rq-badgedd" tabindex="-1"' +
            ' title="Pick employee">&#9662;</button></span></td>' +
            '<td title="' + attr(authName) + '">' + auth + '</td>' +
            '<td>' + rush + '</td>' +
            '</tr>';
        // a returned line just shows Returned with its date; an open line gets the Return Item box
        var retCell;
        if (r.RDRTNF === 'Y') {
            var rdate = fmtDate(r.RDRTDT);
            retCell = '<span class="rq-retdone">Returned' +
                      (rdate ? ' ' + rdate : '') + '</span>';
        } else {
            // a box checked but not yet saved keeps its check and its date when the grid redraws
            var pendKey = String(r['RHREQ#']) + '|' + String(r['RDLIN#']);
            var pend = pendingReturns.hasOwnProperty(pendKey) ? pendingReturns[pendKey] : null;
            retCell = '<input type="checkbox" class="rq-gridret"' +
                ' data-req="' + esc(r['RHREQ#']) + '"' +
                ' data-line="' + esc(r['RDLIN#']) + '"' +
                (pend !== null ? ' checked' : '') + '>' +
                '<label>Return Item:</label>' +
                '<input type="text" class="rq-retdate" maxlength="10"' +
                (pend !== null ? ' value="' + attr(pend) + '"' : '') + '>';
        }
        html += '<tr' + recAttr + 'rq-r2">' +
            '<td colspan="2"></td>' +
            '<td colspan="5" class="rq-desc" title="' + attr(r.RDDESC) + '">' +
                esc(r.RDDESC) + '</td>' +
            '<td colspan="2" class="rq-ret">' + retCell +
            '</td>' +
            '</tr>';
    });

    var searching = gridShow !== 'O' && $('#txtFilter').val().trim() !== '';
    var emptyMsg = searching ? 'No matches - try a different req #, name, item or badge.'
                 : gridShow === 'R' ? 'No returned requisitions.'
                 : gridShow === 'A' ? 'No requisitions.'
                 : 'No open requisitions.';
    $('#gridBody').html(html ||
        '<tr><td colspan="10" class="rq-empty">' + emptyMsg + '</td></tr>');

    // just the line count, the same plain readout every Show mode uses
    $('#lblCount').text(shown + ' line' + (shown === 1 ? '' : 's'));

    updateSortIndicators();
}


// header sort: number columns compare as numbers, everything else ignores case
function gridCompare(a, b) {
    var k = gridSort.key, av, bv;
    if (k === 'RHREQ#') { av = +a['RHREQ#']; bv = +b['RHREQ#']; }
    else if (k === 'RHRQDT') {
        av = (+a.RHRQDT || 0) * 1000000 + (+a.RHRQTM || 0);
        bv = (+b.RHRQDT || 0) * 1000000 + (+b.RHRQTM || 0);
    }
    else if (k === 'RDQTY') { av = +a.RDQTY || 0; bv = +b.RDQTY || 0; }
    else if (k === 'RHBDGE') {
        av = parseInt(a.RHBDGE, 10); bv = parseInt(b.RHBDGE, 10);
        if (isNaN(av)) { av = -1; } if (isNaN(bv)) { bv = -1; }
    }
    else {
        if (k === 'RHAUTB') { av = a.RHAUTB || 'Authorization = None'; bv = b.RHAUTB || 'Authorization = None'; }
        else if (k === 'RHNAME') { av = a.RHNAME || ''; bv = b.RHNAME || ''; }
        else if (k === 'RDITEM') { av = a.RDITEM || ''; bv = b.RDITEM || ''; }
        else if (k === 'RDLOC') { av = a.RDLOC || ''; bv = b.RDLOC || ''; }
        else if (k === 'RHRUSH') { av = a.RHRUSH || ''; bv = b.RHRUSH || ''; }
        else { return 0; }
        av = String(av).toLowerCase(); bv = String(bv).toLowerCase();
    }
    if (av < bv) { return -gridSort.dir; }
    if (av > bv) { return gridSort.dir; }
    return 0;
}


// paint the up/down arrow on the active sort header
function updateSortIndicators() {
    $('#tblGrid thead th[data-sortkey]').each(function () {
        var th = $(this);
        var active = th.data('sortkey') === gridSort.key;
        th.toggleClass('rq-sorted', active);
        th.find('.rq-sortind').text(active ? (gridSort.dir === 1 ? ' ▲' : ' ▼') : '');
    });
}

// add requisition

function applyLookups(resp) {
    lookups = resp;
    // all four lists come from the RQSCODEFLT code file via REQSTN007S
    fillSelect('#addName', resp.names, 'CDCODE', 'CDCODE');
    fillSelect('#addAreaCode', resp.areaCodes, 'CDCODE', 'CDDESC');
    fillSelect('#addAreaType', resp.areaTypes, 'CDCODE', 'CDCODE');
    fillSelect('#authBy', resp.authBy, 'CDCODE', 'CDCODE');
    // Authorization None is a stored choice in the code list, not a placeholder added here
    fillSelect('#addAuthBy', resp.authBy, 'CDCODE', 'CDCODE');
}


function loadLookups() {
    // the lists usually ride in with the page
    if (RQ_PRELOAD) { applyLookups(RQ_PRELOAD); return; }
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
    // entry mode opens fifteen blank lines; the popup on the main screen starts with three
    var rows = (RQ_MODE === 'entry') ? 15 : 3;
    for (var i = 0; i < rows; i++) { addLineRow(); }
    $('#addComments').val('');
    $('input[name="addRush"][value="N"]').prop('checked', true);
    // a new requisition starts on the first choice, Authorization None
    $('#addAuthBy').prop('selectedIndex', 0);
    $('#addDate').val(new Date().toLocaleString());
    $('#mdlAdd').prop('hidden', false);
    // focus the first cell after layout settles so its item menu opens in the right place
    setTimeout(function () {
        $('#lineBody input:first').trigger('focus');
    }, 150);
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
            // fresh blank form for the next request
            openAddModal();
        } else {
            $('#mdlAdd').prop('hidden', true);
            loadGrid();
        }
    });
}

// view and update

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

        // legacy style header fields
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

        // preselect the saved authorizer; an old name no longer on the list is added so it appears
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
                '<td>' + (r.RDRTNF === 'Y' ? fmtDate(r.RDRTDT) : '') + '</td>' +
                '</tr>';
        });
        $('#viewLineBody').html(html);

        $('#mdlView').data('req', h['RHREQ#']).prop('hidden', false);
    });
}


// the view window Date reads year then month then day with the time, like the old screen
function fmtDateTimeIso(d8, t6) {
    var s = String(d8);
    if (s.length !== 8 || s === '00000000') { return ''; }
    var t = String(1000000 + (parseInt(t6, 10) || 0)).slice(1);
    return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8) + ' ' +
           t.slice(0, 2) + ':' + t.slice(2, 4) + ':' + t.slice(4, 6);
}


// the view window Update button, sending the authorized by name and comments through REQSTN005S
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

// badge dropdown

// badge choices come from the live employee list that rode in with the page
function badgeChoices() {
    var seen = {}, out = [];
    if (lookups && lookups.badges) {
        $.each(lookups.badges, function (i, r) {
            var c = String(r.CDCODE == null ? '' : r.CDCODE).trim();
            if (c !== '' && !seen[c]) {
                seen[c] = 1;
                out.push({ c: c, n: r.CDDESC || '' });
            }
        });
    }
    return out;
}


// valid badge numbers, used to reject typed name text
function badgeCodeSet() {
    var set = {};
    $.each(badgeChoices(), function (i, b) { set[b.c] = 1; });
    return set;
}


// badge currently stored for a requisition, so a bad entry can revert
function reqBadge(reqNum) {
    var out = '';
    $.each(gridRows, function (i, r) {
        if (String(r['RHREQ#']) === reqNum) { out = r.RHBDGE || ''; return false; }
    });
    return out;
}


function hideBadgeSuggest() { $('#rqBadgeSuggest').remove(); }

// the second argument decides whether the list filters on typed text or shows everyone
function showBadgeSuggest(inp, filterTyped) {
    hideBadgeSuggest();
    var v = filterTyped ? inp.val().trim().toLowerCase() : '';
    var rows = [];
    $.each(badgeChoices(), function (i, b) {
        // match on badge number prefix or anywhere in the name
        if (v === '' || b.c.toLowerCase().indexOf(v) === 0 ||
            b.n.toLowerCase().indexOf(v) >= 0) { rows.push(b); }
    });
    if (!inp.is(':focus')) { return; }
    // typing with no match: no menu
    if (!rows.length && filterTyped) { return; }
    var box = $('<div id="rqBadgeSuggest" class="rq-suggest"></div>');
    if (!rows.length) {
        // employee list came back empty, so say so rather than show nothing
        $('<div class="rq-suggest-empty"></div>')
            .text('Employee list unavailable').appendTo(box);
    }
    // no cap on the list, the menu box scrolls when there are many employees
    $.each(rows, function (i, b) {
        $('<div></div>')
            .html('<b>' + esc(b.c) + '</b>' + (b.n ? ' &nbsp; ' + esc(b.n) : ''))
            .data('code', b.c)
            .appendTo(box);
    });
    var rc = inp[0].getBoundingClientRect();
    box.css({ left: rc.left + 'px', top: (rc.bottom + 2) + 'px', minWidth: rc.width + 'px' });
    $('body').append(box);
    // mousedown lands before blur; the empty state row picks nothing
    box.children().on('mousedown', function (e) {
        e.preventDefault();
        var code = $(this).data('code');
        if (code == null) { return; }
        inp.val(code);
        hideBadgeSuggest();
        // picking a name saves the badge, same as typing one and leaving the box
        inp.trigger('change');
    });
}

// item search dropdown

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

// reports

function money(n) {
    n = parseFloat(n) || 0;
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

var RQ_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
                 'July', 'August', 'September', 'October', 'November', 'December'];

function openMonthlyReport() {
    // month and year lists are built the first time the report opens
    if (!$('#rptMonthSel option').length) {
        var mh = '', yh = '';
        $.each(RQ_MONTHS, function (i, m) {
            mh += '<option value="' + (i + 1) + '">' + m + '</option>';
        });
        for (var y = new Date().getFullYear(); y >= 2000; y--) {
            yh += '<option>' + y + '</option>';
        }
        $('#rptMonthSel').html(mh);
        $('#rptYearSel').html(yh);
    }
    var d = new Date();
    $('#rptMonthSel').val(String(d.getMonth() + 1));
    $('#rptYearSel').val(String(d.getFullYear()));
    $('#rptBody').html('<div class="rq-empty">Loading this month...</div>');
    $('#mdlReport').prop('hidden', false);
    // open straight onto the current month instead of an empty prompt
    runMonthlyReport();
}


function runMonthlyReport() {
    var m = parseInt($('#rptMonthSel').val(), 10);
    var y = parseInt($('#rptYearSel').val(), 10);
    var label = $('#rptMonthSel option:selected').text() + ' ' + y;
    postAjax({ action: 'monthly', yyyymm: y * 100 + m },
             function (resp) { renderMonthlyReport(resp.rows, label); });
}


// Returned column: the date in green once it came back, a muted dash while still out
function rptReturned(r) {
    return r.RDRTNF === 'Y'
        ? '<span class="rpt-y">' + fmtDate(r.RDRTDT) + '</span>'
        : '<span class="rpt-n">&mdash;</span>';
}


// one compact totals row; the class marks it as a name subtotal or the grand total
function muTotals(label, t, cls) {
    return '<tr><td colspan="11"><div class="rpt-totblk ' + (cls || '') + '">' +
        '<span class="rpt-totlbl rpt-ital">' + label + '</span>' +
        '<span class="rpt-totv"><span class="rpt-ital">Qty</span> ' + t.qty + '</span>' +
        '<span class="rpt-totv"><span class="rpt-ital">Retail</span> $' + money(t.retl) + '</span>' +
        '<span class="rpt-totv"><span class="rpt-ital">Cost</span> $' + money(t.cost) + '</span>' +
        '</div></td></tr>';
}


// the monthly report groups lines by name, then by requisition, with totals at each break
function renderMonthlyReport(rows, label) {
    if (!rows.length) {
        $('#rptBody').html('<div class="rq-empty">No requisitioned product in ' + esc(label) + '.</div>');
        return;
    }
    var body = '';
    var name = null, req = null;
    var nT = null, rT = null, reqComments = '';
    var grand = { qty: 0, cost: 0, retl: 0, reqs: 0 };
    var alt = 0;

    function closeReq() {
        if (req === null) { return; }
        body += '<tr class="rpt-cmt"><td></td><td class="rpt-ital">Req. Comments:</td>' +
                '<td colspan="9">' + esc(reqComments) + '</td></tr>';
        body += muTotals('Req. Totals:', rT, '');
        req = null;
    }
    function closeName() {
        if (name === null) { return; }
        closeReq();
        body += muTotals('Totals by Name:', nT, 'rpt-nametot');
        name = null;
    }

    $.each(rows, function (i, r) {
        if (r.RHNAME !== name) {
            closeName();
            name = r.RHNAME;
            nT = { qty: 0, cost: 0, retl: 0 };
            body += '<tr class="rpt-name"><td colspan="11">' + esc(name) + '</td></tr>';
        }
        if (r['RHREQ#'] !== req) {
            closeReq();
            req = r['RHREQ#'];
            rT = { qty: 0, cost: 0, retl: 0 };
            reqComments = r.RHCMNT || '';
            grand.reqs++;
            body += '<tr class="rpt-reqhd"><td></td><td colspan="10">' +
                    '<span class="rpt-rq">Req. ' + esc(req) + '</span> &nbsp; ' +
                    fmtDate(r.RHRQDT) + '</td></tr>';
        }
        var q = parseFloat(r.RDQTY) || 0;
        var c = parseFloat(r.RDCOST) || 0, rt = parseFloat(r.RDRETL) || 0;
        var ec = parseFloat(r.RDEXTC) || 0, er = parseFloat(r.RDEXTR) || 0;
        rT.qty += q; rT.cost += ec; rT.retl += er;
        nT.qty += q; nT.cost += ec; nT.retl += er;
        grand.qty += q; grand.cost += ec; grand.retl += er;
        alt++;
        body += '<tr class="rpt-line' + (alt % 2 === 0 ? ' rpt-alt' : '') + '">' +
            '<td></td>' +
            '<td>' + esc(r.RDITEM) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td>' +
            '<td>' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + q + '</td>' +
            '<td class="rq-num">$' + money(c) + '</td>' +
            '<td class="rq-num">$' + money(ec) + '</td>' +
            '<td class="rq-num">$' + money(rt) + '</td>' +
            '<td class="rq-num">$' + money(er) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td>' +
            '<td class="rpt-ret">' + rptReturned(r) + '</td>' +
            '</tr>';
    });
    closeName();
    body += muTotals('Report Totals (' + grand.reqs + ' requisitions):', grand, 'rpt-grand');

    $('#rptBody').html(
        '<div class="rpt-mu">' +
        '<h3 class="rpt-mutitle">Monthly Update: Requisitioned Product</h3>' +
        '<div class="rpt-musub">' + esc(label) + '</div>' +
        '<table>' +
        '<colgroup><col style="width:10%"><col style="width:9%"><col style="width:8%">' +
        '<col style="width:24%"><col style="width:5%"><col style="width:8%">' +
        '<col style="width:8%"><col style="width:8%"><col style="width:8%">' +
        '<col style="width:5%"><col style="width:7%"></colgroup>' +
        '<thead><tr>' +
        '<th>Name</th><th>Req. Date<br>Item #</th><th>Coin Date</th><th>Description</th>' +
        '<th class="rq-num">Qty</th><th class="rq-num">Cost</th><th class="rq-num">Ext.<br>Cost</th>' +
        '<th class="rq-num">Retail</th><th class="rq-num">Ext.<br>Retail</th><th>Sku To</th><th>Returned</th>' +
        '</tr></thead><tbody>' + body + '</tbody></table>' +
        '<div class="rpt-stamp">' + esc(label) + ' &mdash; printed ' +
        new Date().toLocaleDateString('en-US',
            { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + '</div>' +
        '</div>');
}


// "1/20/2011 11:09:03 AM" like the printed report's Date field
function fmtDateTime(d8, t6) {
    var s = String(d8);
    if (s.length !== 8 || s === '00000000') { return ''; }
    // pad the stored time back to six digits so 90503 reads as 9:05:03
    var t = String(1000000 + (parseInt(t6, 10) || 0)).slice(1);
    var hh = parseInt(t.slice(0, 2), 10);
    var ap = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12; if (hh === 0) { hh = 12; }
    return parseInt(s.slice(4, 6), 10) + '/' + parseInt(s.slice(6, 8), 10) + '/' + s.slice(0, 4) +
           ' ' + hh + ':' + t.slice(2, 4) + ':' + t.slice(4, 6) + ' ' + ap;
}


// the printed copy of one requisition, a header block over a boxed grid of its lines
function reqPrintHtml(rows) {
    var h = rows[0];
    var head =
        '<table class="rpt-hdr">' +
        '<colgroup><col style="width:40%"><col style="width:32%"><col style="width:28%"></colgroup>' +
        '<tr><td><span class="rpt-lbl">Requisition #</span> ' + esc(h['RHREQ#']) + '</td>' +
            '<td><span class="rpt-lbl">Requisitioner:</span> ' + esc(h.RHNAME) + '</td><td></td></tr>' +
        '<tr><td><span class="rpt-lbl">Rush</span> ' + (h.RHRUSH === 'Y' ? 'Yes' : 'No') + '</td>' +
            '<td></td><td><span class="rpt-lbl">Date:</span> ' + fmtDateTime(h.RHRQDT, h.RHRQTM) + '</td></tr>' +
        '<tr><td><span class="rpt-lbl">Authorized By</span> ' + esc(h.RHAUTB || 'Authorization = None') + '</td>' +
            '<td><span class="rpt-lbl">DataEntry:</span> ' + esc(h.RHBDGE) + '</td><td></td></tr>' +
        '<tr><td><span class="rpt-lbl">Area Code:</span> ' + esc(h.RHARCD) + '</td>' +
            '<td><span class="rpt-lbl">Area Type:</span> ' + esc(h.RHARTY) + '</td><td></td></tr>' +
        '</table>';

    var qty = 0, extc = 0, extr = 0;
    var body = '';
    $.each(rows, function (i, r) {
        // returned lines print too; only a row with no line number is skipped
        if (r['RDLIN#'] == null) { return; }
        var q = parseFloat(r.RDQTY) || 0;
        var ec = q * (parseFloat(r.RDCOST) || 0);
        var er = q * (parseFloat(r.RDRETL) || 0);
        qty += q; extc += ec; extr += er;
        body += '<tr><td>' + esc(r.RDITEM) + '</td><td>' + esc(r.RDLOC) + '</td>' +
            '<td>' + esc(r.RDCNDT) + '</td><td class="rpt-desc">' + esc(r.RDDESC) + '</td>' +
            '<td class="rq-num">' + q + '</td>' +
            '<td class="rq-num">' + money(r.RDCOST) + '</td>' +
            '<td class="rq-num">' + money(ec) + '</td>' +
            '<td class="rq-num">' + money(r.RDRETL) + '</td>' +
            '<td class="rq-num">' + money(er) + '</td>' +
            '<td class="rq-num">' + money(r.RDACST) + '</td>' +
            '<td>' + esc(r.RDSKUT) + '</td>' +
            '<td class="rpt-ret">' + rptReturned(r) + '</td></tr>';
    });
    if (body === '') {
        body = '<tr><td colspan="12">This requisition has no lines.</td></tr>';
    }

    return head +
        '<table class="rpt-boxed">' +
        '<colgroup><col style="width:9%"><col style="width:4%"><col style="width:7%">' +
        '<col style="width:23%"><col style="width:5%"><col style="width:7%">' +
        '<col style="width:7%"><col style="width:7%"><col style="width:7%">' +
        '<col style="width:6%"><col style="width:6%"><col style="width:12%"></colgroup>' +
        '<thead><tr>' +
        '<th>Sku #:</th><th>Loc:</th><th>Coin<br>Date:</th><th>Description:</th>' +
        '<th class="rq-num">Qty:</th><th class="rq-num">Cost:</th><th class="rq-num">Ext<br>Cost:</th>' +
        '<th class="rq-num">Retail:</th><th class="rq-num">Ext<br>Retail:</th><th class="rq-num">Add<br>Cost$:</th><th>Sku To:</th><th>Returned:</th>' +
        '</tr></thead><tbody>' + body + '</tbody></table>' +
        '<div class="rpt-totals">' +
        '<span><span class="rpt-ital">Total Qty</span> ' + qty + '</span>' +
        '<span><span class="rpt-ital">Total Retail</span> $' + money(extr) + '</span>' +
        '<span><span class="rpt-ital">Total Cost</span> $' + money(extc) + '</span>' +
        '</div>' +
        (h.RHCMNT ? '<div class="rpt-cmtline"><b>Comments:</b> ' + esc(h.RHCMNT) + '</div>' : '');
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

// print

// print from a hidden frame on this page, so no extra window opens for anyone to close
function printHtml(innerHtml, title) {
    var old = document.getElementById('rqPrintFrame');
    if (old) { old.parentNode.removeChild(old); }
    var frame = document.createElement('iframe');
    frame.id = 'rqPrintFrame';
    frame.setAttribute('aria-hidden', 'true');
    frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.appendChild(frame);
    var doc = frame.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>' + title + '</title><style>' +
        'body{font-family:Arial,sans-serif;font-size:11px;margin:24px;color:#1b1d21;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        'h3{margin:0;}table{width:100%;border-collapse:collapse;}td,th{text-align:left;}' +
        '.rq-num{text-align:right;font-variant-numeric:tabular-nums;}' +
        // shared serif navy italic labels and the green or dash Returned cell
        '.rpt-ital{font-family:Georgia,"Times New Roman",serif;font-style:italic;font-weight:bold;color:#17306e;}' +
        '.rpt-ret .rpt-y{color:#1c7a4c;font-weight:bold;font-variant-numeric:tabular-nums;}' +
        '.rpt-ret .rpt-n{color:#8a91a0;}' +
        // header block on the printed requisition, one line per field
        '.rpt-hdr{table-layout:fixed;margin:6px 0 16px;}' +
        '.rpt-hdr td{border:none;padding:5px 0;font-size:12.5px;white-space:nowrap;}' +
        '.rpt-lbl{color:#17306e;font-weight:bold;}' +
        // boxed line grid on the printed requisition, tinted heading row and quiet stripes
        '.rpt-boxed{table-layout:fixed;}' +
        '.rpt-boxed th,.rpt-boxed td{border:1px solid #23262c;padding:3px 5px;overflow:hidden;white-space:nowrap;font-size:10px;}' +
        '.rpt-boxed thead th{background:#eef2fb;color:#17306e;font-size:9.5px;text-transform:uppercase;letter-spacing:.03em;vertical-align:bottom;}' +
        '.rpt-boxed tbody tr:nth-child(even) td{background:#f6f8fc;}' +
        '.rpt-boxed td.rpt-desc{white-space:normal;}' +
        '.rpt-totals{display:flex;justify-content:flex-end;gap:2.2rem;margin:12px 0 2px;font-weight:bold;font-size:12px;font-variant-numeric:tabular-nums;}' +
        '.rpt-totals .rpt-ital{margin-right:.3rem;}' +
        '.rpt-cmtline{margin-top:8px;font-size:11px;color:#5b6371;}' +
        // Monthly Update open layout, serif navy headings, compact single line totals
        '.rpt-mu table{font-size:11px;line-height:1.3;}' +
        '.rpt-mu th,.rpt-mu td{border:none;vertical-align:top;padding:2px 8px;}' +
        '.rpt-mu thead th{color:#17306e;font-weight:bold;font-size:9px;text-transform:uppercase;letter-spacing:.04em;border-bottom:1.5px solid #4a5c93;padding-bottom:4px;}' +
        '.rpt-mu tr.rpt-line td{border-bottom:1px solid #dce1ea;}' +
        '.rpt-mu tr.rpt-alt td{background:#f6f8fc;}' +
        '.rpt-mu tr.rpt-name td{font-weight:bold;color:#17306e;font-size:13px;background:#e4eafb;padding:7px 8px;border-top:2px solid #17306e;border-bottom:1px solid #c3ccdf;}' +
        '.rpt-mu tr.rpt-reqhd td{color:#5b6371;padding-top:4px;font-weight:bold;}' +
        '.rpt-mu tr.rpt-reqhd .rpt-rq{color:#17306e;}' +
        '.rpt-mu tr.rpt-cmt td{color:#5b6371;font-size:10px;padding-top:1px;}' +
        '.rpt-mutitle{font-family:Georgia,"Times New Roman",serif;font-style:italic;color:#17306e;margin:0 0 1px 0;font-size:20px;}' +
        '.rpt-musub{color:#5b6371;font-size:11px;margin:0 0 10px 0;}' +
        '.rpt-totblk{display:flex;flex-wrap:wrap;gap:.1rem 2rem;align-items:baseline;margin:1px 0 3px;padding:2px 8px;border-top:1px solid #b7bdca;}' +
        '.rpt-totlbl{min-width:165px;}.rpt-totv .rpt-ital{margin-right:.3rem;}' +
        '.rpt-nametot{background:#f2f5fc;border-top-color:#4a5c93;}' +
        '.rpt-grand{margin-top:5px;border-top:2px solid #17306e;background:#eaeff9;}' +
        '.rpt-stamp{margin-top:12px;color:#5b6371;font-size:10px;}' +
        '</style></head><body>' + innerHtml +
        // print fires only after the frame content loads, so nothing prints half built
        '<scr' + 'ipt>window.onload=function(){window.focus();window.print();};</scr' + 'ipt>' +
        '</body></html>');
    doc.close();
}


function printRequisition() {
    if (!lastReqRows || !lastReqRows.length) { return; }
    printHtml(reqPrintHtml(lastReqRows), 'Requisition ' + $('#viewReqNum').text());
}
</script>

<?php } ?>
