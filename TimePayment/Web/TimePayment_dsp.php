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
        --tp-blue-hv: #0056b3; --tp-accent: #eaf6ee; --tp-line: #dfe6e1;
        --tp-text: #222; --tp-muted: #5f6b62; --tp-amber: #9a6a14; --tp-red: #c0392b;
        --tp-mono: "Consolas", "Courier New", monospace; }

.tp-app { font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
          color: var(--tp-text); padding: 1rem 1.25rem 2rem; }

.tp-app h1 { font-size: 1.1rem; font-weight: 600; color: var(--tp-green-dk); margin: 0 0 .9rem; }
.tp-app h2 { font-size: .95rem; font-weight: 600; margin: 1.4rem 0 .5rem; }

/* the file picker row, with the column order spelled out beneath it */
.tp-filerow { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.tp-filerow input[type=file] { font-size: .88rem; }
.tp-note { color: var(--tp-muted); font-size: .82rem; margin: .5rem 0 0; }

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
.tp-summary { font-size: .88rem; margin: 0 0 .5rem; }
.tp-summary b { font-weight: 700; }
.tp-summary .tp-bad { color: var(--tp-red); }
.tp-summary .tp-mailed { color: var(--tp-amber); }

/* both tables scroll inside their own box with the heading row staying put */
.tp-tablewrap { overflow-x: auto; max-height: 55vh; overflow-y: auto;
                border: 1px solid var(--tp-line); border-radius: 6px; }
.tp-grid { width: 100%; border-collapse: collapse; font-size: .86rem; }
.tp-grid thead th { position: sticky; top: 0; z-index: 5; background: var(--tp-accent);
                    color: var(--tp-green-dk); text-align: left; padding: .45rem .7rem;
                    border-bottom: 1px solid var(--tp-line); white-space: nowrap; }
.tp-grid tbody td { padding: .35rem .7rem; border-bottom: 1px solid var(--tp-line);
                    white-space: nowrap; }
.tp-grid tbody tr:last-child td { border-bottom: none; }
.tp-grid .tp-mono { font-family: var(--tp-mono); }
.tp-grid .tp-msg { white-space: normal; }
.tp-empty { color: var(--tp-muted); padding: .6rem .7rem; }

/* one word per row outcome */
.tp-st { font-weight: 700; font-size: .8rem; }
.tp-st-added { color: var(--tp-green); }
.tp-st-updated { color: var(--tp-blue); }
.tp-st-error { color: var(--tp-red); }

/* an expired record stays in the grid but reads as done with */
tr.tp-expired td { color: #9aa39d; }
tr.tp-expired td.tp-exp { color: var(--tp-red); }

/* the search box over the records table, with the count on the right */
.tp-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin: 0 0 .5rem; }
.tp-filter { flex: 1 1 200px; max-width: 300px; padding: .4rem .65rem;
             border: 1px solid var(--tp-line); border-radius: 6px; }
.tp-count { color: var(--tp-muted); font-size: .82rem; margin-left: auto; }
</style>

<div class="tp-app">

    <h1>Item Time Payment</h1>

    <form id="tpForm" enctype="multipart/form-data" onsubmit="return false;">
        <input type="hidden" name="action" value="upload">
        <div class="tp-filerow">
            <input type="file" id="tpFile" name="myFile" accept=".xlsx,.xls,.csv">
            <button type="button" class="tp-btn tp-btn-primary" id="btnUpload">Upload</button>
            <button type="button" class="tp-btn" id="btnTemplate">Template</button>
        </div>
    </form>
    <p class="tp-note">Item #, Source Code, Plan (optional), Expiration Date &mdash; headings on row 1.</p>

    <div id="resultsBlock" hidden>
        <h2>Results</h2>
        <div class="tp-summary" id="resSummary"></div>
        <div class="tp-tablewrap">
            <table class="tp-grid" id="tblResults">
                <thead><tr>
                    <th>Row</th><th>Item #</th><th>Source</th><th>Plan</th>
                    <th>Expiration</th><th>Result</th><th>Detail</th>
                </tr></thead>
                <tbody id="resBody"></tbody>
            </table>
        </div>
    </div>

    <h2>Records on file</h2>
    <div class="tp-toolbar">
        <input type="text" class="tp-filter" id="txtSearch" placeholder="Search item # or source code">
        <span class="tp-count" id="lblCount"></span>
    </div>
    <div class="tp-tablewrap">
        <table class="tp-grid" id="tblGrid">
            <thead><tr>
                <th>Item #</th><th>Description</th><th>Source</th>
                <th>Plan</th><th>Plan Description</th><th>Expiration</th>
            </tr></thead>
            <tbody id="gridBody"></tbody>
        </table>
    </div>

</div>

<?php
}
?>
