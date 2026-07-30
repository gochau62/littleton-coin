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
        --tp-green-dk: #1C4532; --tp-green: #2e8b57; --tp-green-hv: #1e6e43; --tp-blue: #007bff;
        --tp-blue-hv: #0056b3; --tp-accent: #eaf6ee; --tp-bg: #f8f8f8; --tp-line: #dfe6e1;
        --tp-text: #222; --tp-muted: #5f6b62; --tp-amber: #9a6a14; --tp-red: #c0392b;
        --tp-mono: "Consolas", "Courier New", monospace; }

.tp-app { font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
          color: var(--tp-text); background: var(--tp-bg); padding: 0 0 2rem 0; }

/* dark green title bar, with the signed in user on the right */
.tp-topbar { display: flex; align-items: center; justify-content: space-between;
             background: var(--tp-green-dk); color: #fff; padding: .6rem 1.25rem; }

.tp-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.tp-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

/* each section sits in a rounded white card */
.tp-card { background: #fff; border: 1px solid var(--tp-line); border-radius: 8px;
           margin: 1rem 1.25rem 0; padding: 1rem 1.1rem; }

.tp-card h2 { font-size: 1rem; margin: 0 0 .6rem 0; color: var(--tp-green-dk); }
.tp-help { color: var(--tp-muted); font-size: .88rem; margin: .3rem 0 .8rem; max-width: 62rem; }

/* the four column layout, spelled out where Marketing picks the file */
.tp-cols { border-collapse: collapse; font-size: .85rem; margin: 0 0 .9rem; }
.tp-cols th { text-align: left; background: var(--tp-accent); color: var(--tp-green-dk);
              padding: .35rem .7rem; border: 1px solid var(--tp-line); }
.tp-cols td { padding: .35rem .7rem; border: 1px solid var(--tp-line); color: var(--tp-muted); }
.tp-cols td b { color: var(--tp-text); }

/* white pill buttons, the house style */
.tp-btn { display: inline-flex; align-items: center; gap: 6px; padding: .45rem 1.1rem;
          border: 1px solid #b4b4b4; border-radius: 50px; background: #fff;
          color: var(--tp-text); font-size: .9rem; font-weight: 700; cursor: pointer; }

/* hover only recolors the outline and text, so the plain button stays white */
.tp-btn:hover { border-color: var(--tp-blue); color: var(--tp-blue); }
.tp-btn:disabled { opacity: .45; cursor: default; border-color: #b4b4b4; color: var(--tp-text); }
.tp-btn-primary { background: var(--tp-blue); border-color: var(--tp-blue); color: #fff; }
.tp-btn-primary:hover { background: var(--tp-blue-hv); border-color: var(--tp-blue-hv); color: #fff; }

/* the file picker row: the native input stays, with the chosen name echoed beside the buttons */
.tp-filerow { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; }
.tp-filerow input[type=file] { font-size: .88rem; }

/* summary pills over the results table */
.tp-pills { display: flex; gap: .6rem; flex-wrap: wrap; margin: 0 0 .8rem; }
.tp-pill { padding: .25rem .8rem; border-radius: 50px; font-size: .82rem; font-weight: 700; }
.tp-pill-ok { background: var(--tp-accent); color: var(--tp-green); }
.tp-pill-err { background: #fdecea; color: var(--tp-red); }
.tp-pill-note { background: #fbf1dc; color: var(--tp-amber); }

/* results and review grids share a look; they scroll inside the card with the header staying put */
.tp-tablewrap { overflow-x: auto; max-height: 55vh; overflow-y: auto; }
.tp-grid { width: 100%; border-collapse: collapse; font-size: .86rem; }
.tp-grid thead th { position: sticky; top: 0; z-index: 5; background: var(--tp-accent);
                    color: var(--tp-green-dk); text-align: left; padding: .5rem .7rem;
                    border-bottom: 2px solid var(--tp-line); white-space: nowrap; }
.tp-grid tbody td { padding: .4rem .7rem; border-bottom: 1px solid var(--tp-line);
                    white-space: nowrap; }
.tp-grid tbody tr:nth-child(even) { background: #f7faf8; }
.tp-grid .tp-mono { font-family: var(--tp-mono); }
.tp-grid .tp-msg { white-space: normal; }
.tp-empty { color: var(--tp-muted); padding: .8rem .7rem; }

/* one word per row outcome */
.tp-st { font-weight: 700; font-size: .8rem; text-transform: uppercase; }
.tp-st-added { color: var(--tp-green); }
.tp-st-updated { color: var(--tp-blue); }
.tp-st-error { color: var(--tp-red); }

/* an expired record stays visible but reads as done with */
tr.tp-expired td { color: #9aa39d; }
tr.tp-expired .tp-exppill { background: #fdecea; color: var(--tp-red); padding: .1rem .5rem;
                            border-radius: 50px; font-size: .72rem; font-weight: 700; }

/* strip above the review grid: the search box and the row count */
.tp-toolbar { display: flex; align-items: center; gap: .75rem; flex-wrap: wrap; margin: 0 0 .7rem; }
.tp-filter { flex: 1 1 220px; max-width: 340px; padding: .45rem .7rem;
             border: 1px solid var(--tp-line); border-radius: 6px; }
.tp-count { color: var(--tp-muted); font-size: .85rem; margin-left: auto; }
</style>

<div class="tp-app">

    <div class="tp-topbar">
        <h1>Item Time Payment Maintenance &mdash; Spreadsheet Upload</h1>
        <div class="tp-topbar-right"><span><?php echo htmlspecialchars($user); ?></span></div>
    </div>

    <div class="tp-card">
        <h2>Upload a spreadsheet</h2>
        <p class="tp-help">
            Each row makes one entry on the AS400 Item Time Payment screen (menu 007, option 05),
            keyed by item number and source code &mdash; repeat the item on another row for each
            additional source code. Row 1 is treated as column headings and skipped. Rows that fail
            a check are skipped and e-mailed back to you as an exception report; the rest are written.
        </p>
        <table class="tp-cols">
            <tr><th>Column A</th><th>Column B</th><th>Column C</th><th>Column D</th></tr>
            <tr>
                <td><b>Item #</b> &mdash; must be on the Item Master</td>
                <td><b>Source Code</b> &mdash; must be on OEPSRCE</td>
                <td><b>Plan</b> &mdash; optional; left blank it comes from the item's price
                    (items under $99.00 are skipped)</td>
                <td><b>Expiration Date</b> &mdash; MM/DD/YYYY, today or later</td>
            </tr>
        </table>
        <form id="tpForm" enctype="multipart/form-data" onsubmit="return false;">
            <input type="hidden" name="action" value="upload">
            <div class="tp-filerow">
                <input type="file" id="tpFile" name="myFile" accept=".xlsx,.xls,.csv">
                <button type="button" class="tp-btn tp-btn-primary" id="btnUpload">Upload</button>
                <button type="button" class="tp-btn" id="btnTemplate">Download template</button>
            </div>
        </form>
    </div>

    <div class="tp-card" id="resultsCard" hidden>
        <h2>Upload results</h2>
        <div class="tp-pills" id="resSummary"></div>
        <div class="tp-tablewrap">
            <table class="tp-grid" id="tblResults">
                <thead><tr>
                    <th>Row</th><th>Item #</th><th>Source Code</th><th>Plan</th>
                    <th>Expiration</th><th>Result</th><th>Detail</th>
                </tr></thead>
                <tbody id="resBody"></tbody>
            </table>
        </div>
    </div>

    <div class="tp-card">
        <h2>Time payment records on file</h2>
        <div class="tp-toolbar">
            <input type="text" class="tp-filter" id="txtSearch"
                   placeholder="Search by item # or source code...">
            <span class="tp-count" id="lblCount"></span>
        </div>
        <div class="tp-tablewrap">
            <table class="tp-grid" id="tblGrid">
                <thead><tr>
                    <th>Item #</th><th>Description</th><th>Source Code</th>
                    <th>Plan</th><th>Plan Description</th><th>Expiration</th><th></th>
                </tr></thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>
        <p class="tp-help">The grid shows at most 500 records &mdash; use the search box to reach the rest.</p>
    </div>

</div>

<?php
}
?>
