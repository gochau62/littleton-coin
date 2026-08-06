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

function dspTimePayment($user) {
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

/* dark green title bar, with the signed in user on the right */
.tp-topbar { display: flex; align-items: center; justify-content: space-between;
             background: var(--tp-green-dk); color: #fff; padding: .6rem 1.25rem; }
.tp-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.tp-topbar-right { font-size: .85rem; opacity: .9; }

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

/* both tables scroll inside their card with the heading row staying put. The card
   footprint never follows the data: the records box holds one fixed height whether
   the search matches 0 rows or 500, and the fixed table layout below keeps long
   values from widening the block until it slips under the LCCOnline sidebar */
.tp-tablewrap { overflow: auto; max-height: 22rem; }
.tp-fixedbox { height: 22rem; }
.tp-grid { width: 100%; min-width: 680px; table-layout: fixed;
           border-collapse: collapse; font-size: .86rem; }
.tp-grid thead th { position: sticky; top: 0; z-index: 5; background: var(--tp-accent);
                    color: var(--tp-green-dk); text-align: left; padding: .45rem .7rem;
                    border-bottom: 1px solid var(--tp-line); white-space: nowrap;
                    overflow: hidden; text-overflow: ellipsis; }
.tp-grid tbody td { padding: .35rem .7rem; border-bottom: 1px solid var(--tp-line);
                    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tp-grid tbody tr:nth-child(even) { background: #f7faf8; }
.tp-grid .tp-mono { font-family: var(--tp-mono); }
.tp-grid .tp-msg { white-space: normal; }
.tp-empty { color: var(--tp-muted); padding: .6rem .7rem; }

/* one word per row outcome */
.tp-st { font-weight: 700; font-size: .8rem; }
.tp-st-added { color: var(--tp-green); }
.tp-st-updated { color: var(--tp-blue); }
.tp-st-error { color: var(--tp-red); }

/* an expired record stays in the grid but reads as done with, its date in red */
tr.tp-expired td { color: #9aa39d; }
tr.tp-expired td.tp-exp { color: var(--tp-red); }

/* the search box over the records table, with the count on the right */
.tp-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin: 0 0 .6rem; }
.tp-filter { flex: 1 1 200px; max-width: 300px; padding: .4rem .65rem;
             border: 1px solid var(--tp-line); border-radius: 6px; }
.tp-count { color: var(--tp-muted); font-size: .82rem; margin-left: auto; }
</style>

<div class="tp-app">

    <div class="tp-topbar">
        <h1>Time Payment Items Maintenance</h1>
        <div class="tp-topbar-right"><?php echo htmlspecialchars($user); ?></div>
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
            <span class="tp-count" id="lblCount"></span>
        </div>
        <!-- the same columns, in the same order, as the green screen subfile; the item
             master description rides on the Item cell as a hover title -->
        <div class="tp-tablewrap tp-fixedbox">
            <table class="tp-grid" id="tblGrid">
                <colgroup><col style="width:15%"><col style="width:12%"><col style="width:8%">
                <col style="width:45%"><col style="width:20%"></colgroup>
                <thead><tr>
                    <th>Item</th><th>Source</th><th>Plan</th>
                    <th>Description</th><th>Expire Date</th>
                </tr></thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>
    </div>

</div>

<?php
}
?>
