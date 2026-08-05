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
:root { /* one place for the house colors, so every green and blue on the screen matches */
        --rq-green-dk: #1C4532; --rq-green: #2e8b57; --rq-green-hv: #1e6e43; --rq-blue: #007bff;
        --rq-blue-hv: #0056b3; --rq-accent: #eaf6ee; --rq-bg: #f8f8f8; --rq-line: #dfe6e1;
        --rq-text: #222; --rq-muted: #5f6b62; --rq-amber: #9a6a14; --rq-red: #c0392b; }

.rq-app { font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
          color: var(--rq-text); background: var(--rq-bg); padding: 0 0 2rem 0; }

/* dark green title bar, with the signed in user and the clock on the right */
.rq-topbar { display: flex; align-items: center; justify-content: space-between;
             background: var(--rq-green-dk); color: #fff; padding: .6rem 1.25rem; }

.rq-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.rq-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

/* strip above the grid: action buttons, the Show list, and the filter box */
.rq-toolbar { display: flex; align-items: center; gap: .75rem; padding: .75rem 1.25rem;
              flex-wrap: wrap; }

.rq-filter { flex: 1 1 220px; max-width: 340px; padding: .45rem .7rem;
             border: 1px solid var(--rq-line); border-radius: 6px; }

.rq-count { color: var(--rq-muted); font-size: .85rem; margin-left: auto; }
.rq-auto { color: var(--rq-muted); font-size: .85rem; user-select: none; }
.rq-show { color: var(--rq-muted); font-size: .85rem; user-select: none; }
.rq-showsel { margin-left: .3rem; padding: .35rem .5rem; font-size: .85rem;
              border: 1px solid var(--rq-line); border-radius: 6px; background: #fff;
              color: var(--rq-text); }

.rq-updated { color: var(--rq-muted); font-size: .8rem; }
.rq-updated.rq-stale { color: var(--rq-red); font-weight: 700; }
.rq-lines input.rq-bad { background: #fff5f5; }
.rq-lines td:has(input.rq-bad) { outline: 2px solid var(--rq-red); outline-offset: -2px; }

/* typeahead list that drops under the item number and badge boxes */
.rq-suggest { position: fixed; z-index: 200; background: #fff; border: 1px solid #999;
              border-radius: 4px; box-shadow: 0 6px 18px rgba(0, 0, 0, .18); max-height: 230px;
              overflow-y: auto; font-size: .85rem; }

.rq-suggest div { padding: .3rem .6rem; cursor: pointer; white-space: nowrap; }
.rq-suggest div b { color: var(--rq-blue); }
.rq-suggest div.active, .rq-suggest div:hover { background: var(--rq-accent); }

/* white pill buttons, used on the toolbar and inside the popup windows */
.rq-btn { display: inline-flex; align-items: center; gap: 6px; padding: .45rem 1.1rem;
          border: 1px solid #b4b4b4; /* house pill buttons */ border-radius: 50px;
          background: #fff; color: var(--rq-text); font-size: .9rem; font-weight: 700;
          cursor: pointer; }

/* hover only recolors the outline and text, so the plain button stays white */
.rq-btn:hover { border-color: var(--rq-blue); color: var(--rq-blue); }
.rq-btn-primary { background: var(--rq-blue); border-color: var(--rq-blue); color: #fff; }

.rq-btn-primary:hover { background: var(--rq-blue-hv); border-color: var(--rq-blue-hv);
                        color: #fff; }
.rq-btn-green { background: var(--rq-green); border-color: var(--rq-green); color: #fff; }

.rq-btn-green:hover { background: var(--rq-green-hv); border-color: var(--rq-green-hv);
                      color: #fff; }
.rq-btn-ghost { border-style: dashed; color: var(--rq-muted); margin: .5rem 0; }

/* the grid sits in a rounded white card and scrolls inside it, header staying put */
.rq-card { background: #fff; border: 1px solid var(--rq-line); border-radius: 8px;
           margin: 0 1.25rem; overflow: hidden; }

.rq-tablewrap { overflow-x: auto; max-height: 70vh; }
.rq-grid { width: 100%; border-collapse: collapse; font-size: .88rem; }
/* fixed column widths; borders stay uncollapsed so the frozen header keeps its lines */
#tblGrid { table-layout: fixed; min-width: 780px; font-size: .86rem; border-collapse: separate;
           border-spacing: 0; }

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
.rq-grid thead th { position: sticky; top: 0; /* header always paints over cell content */
                    z-index: 5; background: var(--rq-accent); color: var(--rq-green-dk);
                    text-align: left; padding: .55rem .7rem;
                    border-bottom: 2px solid var(--rq-line); white-space: nowrap; }

/* shadows, not borders, so the frozen header keeps its lines and paints over the rows */
#tblGrid thead th { border: none;
                    box-shadow: inset -1px 0 0 0 #333, inset 0 1px 0 0 #333, 0 2px 0 0 #333; }

#tblGrid thead th:first-child { box-shadow: inset 1px 0 0 0 #333, inset -1px 0 0 0 #333, inset 0 1px 0 0 #333, 0 2px 0 0 #333; }

/* clickable sort headers */
#tblGrid thead th[data-sortkey] { cursor: pointer; user-select: none; }
#tblGrid thead th[data-sortkey]:hover { background: #dbeee2; }
#tblGrid thead th .rq-sortind { color: var(--rq-green); font-size: .7rem; margin-left: .2rem; }
#tblGrid thead th.rq-sorted { background: #d3ecdd; }
.rq-grid tbody td { padding: .45rem .7rem; border-bottom: 1px solid var(--rq-line); }

.rq-grid:not(#tblGrid) tbody tr:nth-child(even) { background: #f7faf8; }
#tblGrid tbody tr { cursor: pointer; }
.rq-grid tbody tr.rq-selected { background: #dff0e5; }
.rq-sel { width: 1.4rem; min-width: 1.4rem; color: var(--rq-green-dk); }
tr.rq-selected .rq-sel::before { content: '\25B6'; font-size: .7rem; }
.rq-reqlink { color: var(--rq-blue); font-weight: 600; cursor: pointer; }
.rq-reqlink:hover { text-decoration: underline; color: var(--rq-blue-hv); }
/* badge box: editable in the grid; all lines of a req share it */
.rq-badgewrap { position: relative; display: inline-block; width: 100%; }
.rq-grid .rq-badge { width: 100%; box-sizing: border-box; padding: .2rem 1rem .2rem .35rem;
                     font-size: .85rem; border: 1px solid var(--rq-line); border-radius: 4px; }

.rq-grid .rq-badge:focus { outline: 2px solid var(--rq-blue); outline-offset: -1px;
                           border-color: var(--rq-blue); }

/* item, location, quantity and description edit in place; the box stays invisible until pointed at so the grid still reads as a list rather than a form */
.rq-grid .rq-cell { width: 100%; box-sizing: border-box; padding: .15rem .3rem; font: inherit;
                    color: inherit; background: transparent; border: 1px solid transparent;
                    border-radius: 4px; }

.rq-grid .rq-cell:hover { border-color: var(--rq-line); background: #fff; }

.rq-grid .rq-cell:focus { outline: 2px solid var(--rq-blue); outline-offset: -1px;
                          border-color: var(--rq-blue); background: #fff; }

.rq-grid .rq-cellnum { text-align: right; }

/* the description keeps its quieter look while it is only being read */
#tblGrid .rq-desc .rq-cell { color: var(--rq-muted); font-size: .82rem; }

/* the small arrow that opens the employee list, like the old Access badge box */
.rq-badgedd { position: absolute; right: 2px; top: 50%; transform: translateY(-50%); border: 0;
              background: none; padding: 0 .15rem; line-height: 1; font-size: .7rem;
              color: var(--rq-muted); cursor: pointer; }

.rq-badgedd:hover { color: var(--rq-blue); }
.rq-suggest-empty { padding: .3rem .6rem; color: var(--rq-muted); font-style: italic;
                    cursor: default; }

.rq-num { text-align: right; }
.rq-empty { text-align: center; color: var(--rq-muted); padding: 1.5rem !important; }

/* small colored labels in the Authorized and Rush columns */
.rq-pill { display: inline-block; padding: .1rem .55rem; border-radius: 999px; font-size: .75rem;
           font-weight: 600; white-space: nowrap; }

.rq-ok { background: var(--rq-accent); color: var(--rq-green-hv); }
.rq-warn { background: #fdf0dd; color: var(--rq-amber); }
.rq-rushpill { background: #ffd1d1; color: var(--rq-red); }

/* modals: the shaded overlay and the white window that add, view and report all share */
.rq-overlay { position: fixed; inset: 0; background: rgba(20, 28, 45, .45); display: flex;
              align-items: flex-start; justify-content: center; padding: 4vh 1rem; z-index: 50; }

.rq-overlay[hidden] { display: none; }
.rq-modal { background: #fff; border-radius: 10px; width: 100%; max-width: 640px; max-height: 90vh;
            display: flex; flex-direction: column; box-shadow: 0 12px 40px rgba(0, 0, 0, .25); }

.rq-modal-wide { max-width: 1080px; }
.rq-modal-head { display: flex; align-items: center; justify-content: space-between;
                 padding: .8rem 1.1rem; border-bottom: 1px solid var(--rq-line); }

.rq-modal-head h2 { margin: 0; font-size: 1.05rem; color: var(--rq-green-dk); }
.rq-modal-body { padding: 1rem 1.1rem; overflow-y: auto; }
.rq-modal-foot { display: flex; justify-content: flex-end; gap: .6rem; padding: .8rem 1.1rem;
                 border-top: 1px solid var(--rq-line); }

.rq-x { border: 0; background: none; font-size: 1.3rem; line-height: 1; color: var(--rq-muted);
        cursor: pointer; }

.rq-x:hover { color: var(--rq-red); }
.rq-linedel { font-size: 1rem; }

/* labels, boxes and the Rush choice on the new requisition form */
.rq-formrow { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: .9rem; }

.rq-formrow label, .rq-comments { display: flex; flex-direction: column; gap: .25rem;
                                  font-size: .82rem; color: var(--rq-muted); }

.rq-formrow select, .rq-formrow input[type=text], .rq-comments input, .rq-authrow select, .rq-authrow input { padding: .4rem .6rem;
  border: 1px solid var(--rq-line); border-radius: 6px; font-size: .9rem; color: var(--rq-text); }

.rq-rush { flex-direction: row !important; align-items: center; gap: .4rem; }
.rq-rushgrp { display: flex; gap: .9rem; align-items: center; font-size: .82rem;
              color: var(--rq-muted); }

.rq-rushgrp label { display: inline-flex; flex-direction: row; gap: .3rem; align-items: center;
                    font-size: .9rem; color: var(--rq-text); }

#addDate { background: #f0f2f1; min-width: 240px; }
.rq-formrow input[type=text], .rq-formrow select { min-width: 190px; }
/* line grid looks like a spreadsheet: the cell draws the box, the input shows none */
.rq-lines { table-layout: fixed; border-collapse: collapse; }
.rq-lines th { padding: .3rem .45rem; }
.rq-lines tbody td { border: 1px solid #c9d2cc; padding: 0; overflow: hidden; background: #fff; }

.rq-lines tbody tr:nth-child(even) td:not(:last-child) { background: #f5f6f6; }
/* active cell: no fill, just a slim blue box hugging the inside edge */
.rq-lines tbody td:focus-within { outline: 2px solid var(--rq-blue); outline-offset: -2px; }

/* last column is the remove line button, so it gets no box and no shading */
.rq-lines tbody td:last-child { border: none; background: none; text-align: center;
                                vertical-align: middle; }

.rq-lines .rq-linedel { padding: 0; font-size: 1.05rem; line-height: 1; color: #b4b4b4; }
.rq-lines .rq-linedel:hover { color: var(--rq-red); }
.rq-lines input { width: 100%; box-sizing: border-box; border: none; background: transparent;
                  padding: .32rem .45rem; font-size: .85rem; }

.rq-lines input:focus { outline: none; }
.rq-comments { margin-top: .9rem; }
.rq-comments input { width: 100%; }

/* the view window keeps the boxed, stacked look of the old requisition screen */
.rq-lgcy { font-size: .9rem; }
.rq-lgcyrow { margin: .3rem 0; display: flex; align-items: center; }
.rq-lgcyrow label { min-width: 118px; color: var(--rq-text); }
.rq-lgcyrow .rq-lgcyital, .rq-lgcyital { font-style: italic; font-weight: 700; }
.rq-lgcyval, .rq-lgcyrow select, .rq-lgcyrow input { display: inline-block; min-width: 200px;
  border: 1px solid #999; border-radius: 3px; background: #fff; padding: .15rem .45rem;
  font-size: .9rem; color: var(--rq-text); }

.rq-lgcyval { background: #fafafa; }
.rq-lgcytable { border-collapse: separate; border-spacing: 4px 5px; }
.rq-lgcytable thead th { position: static; background: none; border: none; color: var(--rq-text);
                         font-weight: 700; padding: .2rem .45rem; }

.rq-lgcytable tbody td { border: 1px solid #999; border-radius: 3px; background: #fff;
                         padding: .2rem .45rem; }

.rq-lgcytable tbody td.rq-nobox { border: none; background: none; }

/* entry only mode: the work floor form fills the page and cannot be closed */
.rq-entry .rq-toolbar, .rq-entry .rq-card { display: none; }
.rq-entry #mdlAdd .rq-modal-head .rq-x, .rq-entry #mdlAdd .rq-modal-foot [data-close] { display: none; }

.rq-entry .rq-overlay { background: var(--rq-bg); padding-top: 1.5rem; }
/* wider window in entry mode so every column of the line sheet fits */
.rq-entry .rq-modal-wide { max-width: 1280px; }

/* monthly report: cleaner layout, but the text sizes match the old printed report */
#rptMonthSel, #rptYearSel { padding: .35rem .5rem; border: 1px solid var(--rq-line);
                            border-radius: 6px; background: #fff; font-size: .9rem; }

.rpt-stamp { margin-top: .75rem; color: var(--rq-muted); font-size: .8rem; }
.rpt-mutitle { font-family: Georgia, "Times New Roman", serif; font-style: italic; color: #17306e;
               margin: 0 0 1px 0; font-size: 1.35rem; }

.rpt-musub { color: #5b6371; font-size: .8rem; margin: 0 0 .7rem 0; }
.rpt-ital { font-family: Georgia, "Times New Roman", serif; font-style: italic; font-weight: 700;
            color: #17306e; }

.rpt-mu table { width: 100%; border-collapse: collapse; font-size: .82rem; line-height: 1.3; }
.rpt-mu th, .rpt-mu td { border: none; padding: 2px 8px; text-align: left; vertical-align: top; }
.rpt-mu thead th { color: #17306e; font-weight: 700; font-size: .72rem; text-transform: uppercase;
                   letter-spacing: .04em; padding-bottom: 4px; border-bottom: 1.5px solid #4a5c93; }

.rpt-mu .rq-num { text-align: right; font-variant-numeric: tabular-nums; }
.rpt-mu tr.rpt-line td { border-bottom: 1px solid #dce1ea; }
.rpt-mu tr.rpt-alt td { background: #f6f8fc; }
.rpt-mu tr.rpt-name td { font-weight: 700; color: #17306e; font-size: .98rem; background: #e4eafb;
                         padding: 7px 8px; border-top: 2px solid #17306e;
                         border-bottom: 1px solid #c3ccdf; }

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
#rptBody .rpt-mu tr.rpt-name td { background: #e4eafb; border-top: 2px solid #17306e;
                                  border-bottom: 1px solid #c3ccdf; }
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
          <div class="rq-lgcyrow"><label>Name:</label><input type="text" id="v_name" list="rqNameList" maxlength="50"></div>
          <div class="rq-lgcyrow"><label>Area Code:</label><input type="text" id="v_acode" list="rqAreaCodeList" maxlength="2"></div>
          <div class="rq-lgcyrow"><label>Area Type:</label><input type="text" id="v_atype" list="rqAreaTypeList" maxlength="25"></div>
          <div class="rq-lgcyrow"><label>Date:</label><span class="rq-lgcyval" id="v_date"></span></div>
          <div class="rq-lgcyrow"><label>Inv DE Number:</label><input type="text" id="v_denum" maxlength="4" title="The first four letters of the first name of whoever entered the requisition"></div>
          <div class="rq-lgcyrow"><label class="rq-lgcyital">Returned</label><span id="v_returned" style="border:none;"></span></div>
          <div class="rq-lgcyrow"><label>Authorized By:</label><input type="text" id="authBy" list="rqAuthByList" maxlength="50"></div>
          <div class="rq-lgcyrow"><label>Comments:</label><input type="text" id="authComments" maxlength="500"></div>

          <!-- the choices behind the boxes above; a box with a list attached can be typed into freely or picked from, which is how the old screen behaved -->
          <datalist id="rqNameList"></datalist>
          <datalist id="rqAreaCodeList"></datalist>
          <datalist id="rqAreaTypeList"></datalist>
          <datalist id="rqAuthByList"></datalist>
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

<?php } ?>
