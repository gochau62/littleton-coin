<?php
/*    ***************************************************  -->
<!--  * Program Name - Time_dsp.php                     *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 09/09/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260082                              *  -->
<!--  ***************************************************   */

// shared styles and header, then the week's grid
function dspTime() {
    prjStyles();
?>
<style>
/* the week reads as one card: which week, then a row per project */
.pt-wk-bar { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem;
        border-bottom: 1px solid var(--pt-line); flex-wrap: wrap; }
.pt-wk-when { font-size: .95rem; font-weight: 700; }
.pt-wk-step { font: inherit; font-size: .82rem; font-weight: 600; cursor: pointer;
        padding: .3rem .6rem; border: 1px solid var(--pt-line); border-radius: 8px;
        background: var(--pt-card); color: var(--pt-text); }
.pt-wk-step:hover { border-color: var(--pt-blue); color: var(--pt-blue); }
.pt-wk-tot { margin-left: auto; font-size: .82rem; color: var(--pt-muted); }
.pt-wk-tot b { color: var(--pt-text); font-size: .95rem; }

.pt-time { width: 100%; border-collapse: collapse; }
.pt-time th { font-size: .68rem; font-weight: 600; letter-spacing: .04em;
        text-transform: uppercase; color: var(--pt-muted); text-align: center;
        padding: .5rem .3rem; border-bottom: 1px solid var(--pt-line); }
.pt-time th:first-child, .pt-time td:first-child { text-align: left; }
.pt-time td { padding: .35rem .3rem; border-bottom: 1px solid var(--pt-line-soft);
        text-align: center; font-size: .84rem; }
.pt-time tbody tr:hover { background: var(--pt-line-soft); }
/* the weekend reads quieter than the working days */
.pt-time .pt-wknd { background: rgba(152, 162, 179, .06); }
.pt-time th.pt-today { color: var(--pt-blue); }

.pt-time .pt-proj { white-space: nowrap; }
.pt-time .pt-proj a { font-weight: 600; }
.pt-time .pt-pdesc { display: block; font-size: .78rem; color: var(--pt-muted);
        white-space: normal; }

/* one hours box per day, wide enough for 8.25 */
.pt-hrs { width: 56px; font: inherit; font-size: .84rem; text-align: center;
        color: var(--pt-text); background: var(--pt-card);
        border: 1px solid var(--pt-field); border-radius: 7px; padding: .3rem .2rem; }
.pt-hrs:focus { outline: 0; border-color: var(--pt-blue);
        box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
.pt-hrs.pt-ok { border-color: var(--pt-green); background: #f4faf4; }
.pt-hrs.pt-bad { border-color: var(--pt-red); background: var(--pt-chip-red); }
.pt-hrs:disabled { background: var(--pt-bg); color: var(--pt-faint); }
.pt-rowtot { font-weight: 700; }
.pt-time tfoot td { padding: .5rem .3rem; font-weight: 700;
        border-top: 1px solid var(--pt-line); }

.pt-addrow { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap;
        padding: .75rem 1rem; border-top: 1px solid var(--pt-line); }
.pt-addrow input { font: inherit; font-size: .84rem; padding: .4rem .55rem;
        border: 1px solid var(--pt-field); border-radius: 8px; width: 150px; }
.pt-addrow .pt-hint { font-size: .78rem; color: var(--pt-muted); }
</style>

<!-- stdPage seats the page beside the nav menu -->
<div id="stdPage">
<div class="pt-app">

    <?php prjHeader('Time entry',
                    '<span class="pt-when" id="ptUpdated"></span>' .
                    '<a href="#" id="lnkRefresh" class="pt-refresh">&#8635; Refresh</a>',
                    'time'); ?>

    <div class="pt-card">
        <div class="pt-wk-bar">
            <button type="button" class="pt-wk-step" id="wkPrev">&lsaquo; Previous</button>
            <span class="pt-wk-when" id="wkWhen"></span>
            <button type="button" class="pt-wk-step" id="wkNext">Next &rsaquo;</button>
            <button type="button" class="pt-wk-step" id="wkThis">This week</button>
            <span class="pt-wk-tot">Week total <b id="wkTotal">0</b></span>
        </div>

        <div class="pt-scr-err" id="wkErr" hidden
             style="margin:.75rem 1rem; padding:.55rem .7rem; border-radius:8px;
                    background:var(--pt-chip-red); color:var(--pt-red);
                    font-size:.82rem; font-weight:600;"></div>

        <div class="pt-tablewrap" style="max-height:none; border:0">
            <table class="pt-time" id="tblTime">
                <thead><tr id="wkHead"></tr></thead>
                <tbody id="wkBody"></tbody>
                <tfoot id="wkFoot"></tfoot>
            </table>
        </div>

        <div class="pt-addrow">
            <span class="pt-hint">Add a project to this week:</span>
            <input type="text" id="wkAdd" placeholder="Project #" inputmode="numeric">
            <button type="button" class="pt-wk-step" id="wkAddGo">Add</button>
            <span class="pt-hint" id="wkAddMsg"></span>
        </div>
    </div>

</div>
</div>
<?php
}
?>
