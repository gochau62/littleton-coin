<?php
/*    ***************************************************  -->
<!--  * Program Name - TimePayment_dsp.php              *  -->
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

function dspTimePayment() {
?>

<style>
/* all the styling for this screen lives here, not in a shared stylesheet */
:root { /* one place for the house colors, so every green and blue on the screen matches */
        --tp-green-dk: #1C4532; --tp-green: #2e8b57; --tp-blue: #007bff;
        --tp-blue-hv: #0056b3; --tp-accent: #eaf6ee; --tp-bg: #f8f8f8; --tp-line: #dfe6e1;
        --tp-text: #222; --tp-muted: #5f6b62; --tp-amber: #9a6a14; --tp-red: #c0392b;
        --tp-mono: "Consolas", "Courier New", monospace; }

.tp-app { font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
          color: var(--tp-text); background: var(--tp-bg); padding: 0 0 2rem 0; }

/* dark green title bar */
.tp-topbar { background: var(--tp-green-dk); color: #fff; padding: .6rem 1.25rem; }
.tp-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }

/* each section sits in a rounded white card on the grey page */
.tp-card { background: #fff; border: 1px solid var(--tp-line); border-radius: 8px;
           margin: 1rem 1.25rem 0; padding: .9rem 1.1rem; }
.tp-card h2 { font-size: .95rem; font-weight: 600; margin: 0 0 .6rem; color: var(--tp-green-dk); }

/* the file picker row, with the column order spelled out beneath it */
.tp-filerow { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.tp-filerow input[type=file] { font-size: .88rem; }
.tp-note { color: var(--tp-muted); font-size: .82rem; margin: .6rem 0 0; }

/* white pill buttons, the house style */
.tp-btn { display: inline-flex; align-items: center; gap: 6px; padding: .45rem 1.1rem;
          border: 1px solid #b4b4b4; border-radius: 50px; background: #fff;
          color: var(--tp-text); font-size: .9rem; font-weight: 700; cursor: pointer; }

/* hover only recolors the outline and text, so the plain button stays white */
.tp-btn:hover { border-color: var(--tp-blue); color: var(--tp-blue); }
.tp-btn:disabled { opacity: .45; cursor: default; border-color: #b4b4b4; color: var(--tp-text); }
.tp-btn-primary { background: var(--tp-blue); border-color: var(--tp-blue); color: #fff; }
.tp-btn-primary:hover { background: var(--tp-blue-hv); border-color: var(--tp-blue-hv); color: #fff; }

/* the counts line over the upload results */
.tp-summary { font-size: .88rem; margin: 0 0 .6rem; }
.tp-summary .tp-bad { color: var(--tp-red); }
.tp-summary .tp-mailed { color: var(--tp-amber); }

/* both tables scroll inside their card, heading row staying put; fixed layout stops long values widening the block */
.tp-tablewrap { overflow: auto; max-height: 22rem; }
/* min-height on purpose: a plain height let the framework's table rule stretch three records to fill the whole box */
.tp-fixedbox { min-height: 22rem; }
/* borders stay uncollapsed so the frozen header can carry its own lines */
.tp-grid { width: 100%; min-width: 680px; table-layout: fixed;
           border-collapse: separate; border-spacing: 0; font-size: .86rem; }
/* inset shadows, not borders: the lines travel with the sticky header and the bottom line stays attached to the cell */
.tp-grid thead th { position: sticky; top: 0; z-index: 5; background: var(--tp-accent);
                    color: var(--tp-green-dk); text-align: left; padding: .45rem .7rem;
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
                    border: none;
                    box-shadow: inset -1px 0 0 0 #333, inset 0 1px 0 0 #333,
                                inset 0 -2px 0 0 #333; }
.tp-grid thead th:first-child { box-shadow: inset 1px 0 0 0 #333, inset -1px 0 0 0 #333,
                    inset 0 1px 0 0 #333, inset 0 -2px 0 0 #333; }
.tp-grid tbody td { padding: .35rem .7rem; border-bottom: 1px solid var(--tp-line);
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* stripe every other record so a wide row is easy to follow across */
.tp-grid tbody tr:nth-child(even) td { background: #ebf3ee; }
.tp-grid .tp-mono { font-family: var(--tp-mono); }
.tp-grid .tp-msg { white-space: normal; }
.tp-empty { color: var(--tp-muted); padding: .6rem .7rem; }

/* clickable sort headers, the same pattern as the Requisitions grid */
.tp-grid thead th[data-sortkey] { cursor: pointer; user-select: none; }
.tp-grid thead th[data-sortkey]:hover { background: #dbeee2; }
.tp-grid thead th.tp-sorted { background: #d3ecdd; }
.tp-grid thead th .tp-sortind { color: var(--tp-green); font-size: .7rem; margin-left: .2rem; }

/* one word per row outcome */
.tp-st { font-weight: 700; font-size: .8rem; }
.tp-st-added { color: var(--tp-green); }
.tp-st-updated { color: var(--tp-blue); }
.tp-st-error { color: var(--tp-red); }

/* an expired record reads as done with: red tinted row beating the stripe, struck-through item number, date in red */
.tp-grid tbody tr.tp-expired td { background: #fdeeec; color: #8a6f6c; }
.tp-grid tbody tr.tp-expired:nth-child(even) td { background: #fae4e1; }
.tp-grid tbody tr.tp-expired td:first-child { text-decoration: line-through; }
.tp-grid tbody tr.tp-expired td.tp-exp { color: var(--tp-red); font-weight: 700; }

/* the search box and Show list over the records table, with the count on the right */
.tp-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin: 0 0 .6rem; }
.tp-filter { flex: 1 1 200px; max-width: 300px; padding: .4rem .65rem;
             border: 1px solid var(--tp-line); border-radius: 6px; }
.tp-show { color: var(--tp-muted); font-size: .85rem; user-select: none; }
.tp-showsel { margin-left: .3rem; padding: .35rem .5rem; font-size: .85rem;
              border: 1px solid var(--tp-line); border-radius: 6px; background: #fff;
              color: var(--tp-text); }
.tp-count { color: var(--tp-muted); font-size: .82rem; margin-left: auto; }
</style>

<div class="tp-app">

    <div class="tp-topbar">
        <h1>Time Payment Items Maintenance</h1>
    </div>

    <div class="tp-card">
        <form id="tpForm" enctype="multipart/form-data" onsubmit="return false;">
            <input type="hidden" name="action" value="upload">
            <div class="tp-filerow">
                <input type="file" id="tpFile" name="myFile" accept=".xlsx,.xls,.csv">
                <button type="button" class="tp-btn tp-btn-primary" id="btnUpload">Upload</button>
                <button type="button" class="tp-btn" id="btnTemplate">Template</button>
            </div>
        </form>
        <p class="tp-note">Item #, Source Code, Plan (optional), Expiration Date &mdash; headings on row 1.</p>
    </div>

    <div class="tp-card" id="resultsBlock" hidden>
        <h2>Results</h2>
        <div class="tp-summary" id="resSummary"></div>
        <div class="tp-tablewrap">
            <table class="tp-grid" id="tblResults">
                <colgroup><col style="width:7%"><col style="width:14%"><col style="width:10%">
                <col style="width:8%"><col style="width:13%"><col style="width:10%">
                <col style="width:38%"></colgroup>
                <thead><tr>
                    <th>Row</th><th>Item #</th><th>Source</th><th>Plan</th>
                    <th>Expiration</th><th>Result</th><th>Detail</th>
                </tr></thead>
                <tbody id="resBody"></tbody>
            </table>
        </div>
    </div>

    <div class="tp-card">
        <h2>Records on file</h2>
        <div class="tp-toolbar">
            <input type="text" class="tp-filter" id="txtSearch" placeholder="Search item # or source code">
            <label class="tp-show">Show
                <select id="selShow" class="tp-showsel">
                    <option value="all">All</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                </select>
            </label>
            <span class="tp-count" id="lblCount"></span>
        </div>
        <!-- the green screen subfile columns in its order; the item description is the Item cell's hover title -->
        <div class="tp-tablewrap tp-fixedbox">
            <table class="tp-grid" id="tblGrid">
                <colgroup><col style="width:15%"><col style="width:12%"><col style="width:8%">
                <col style="width:45%"><col style="width:20%"></colgroup>
                <thead><tr>
                    <th data-sortkey="TPITEM">Item<span class="tp-sortind"></span></th>
                    <th data-sortkey="TPSRCD">Source<span class="tp-sortind"></span></th>
                    <th data-sortkey="TPPLAN">Plan<span class="tp-sortind"></span></th>
                    <th data-sortkey="TPPLDS">Description<span class="tp-sortind"></span></th>
                    <th data-sortkey="TPEXDATE">Expire Date<span class="tp-sortind"></span></th>
                </tr></thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>
    </div>

</div>

<?php
}
?>
