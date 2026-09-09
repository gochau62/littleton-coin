<?php
/*    ***************************************************  -->
<!--  * Program Name - Project_dsp.php                   *  -->
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

// the project screen's own styles, on top of the shared ones
function dspProject($projNum) {
    prjStyles();
?>
<style>
/* the screen is one card: title row, tabs, then the fields */
.pt-scr { max-width: 860px; }
.pt-scr-head { display: flex; align-items: flex-start; justify-content: space-between;
        gap: 1rem; padding: .95rem 1.15rem .8rem; border-bottom: 1px solid var(--pt-line); }
.pt-scr-what { font-size: .68rem; font-weight: 600; letter-spacing: .07em;
        text-transform: uppercase; color: var(--pt-muted); }
.pt-scr-num { font-size: 1.32rem; font-weight: 700; margin-top: .1rem;
        display: flex; align-items: center; gap: .5rem; }
.pt-scr-num .pt-chip { font-size: .68rem; vertical-align: middle; }
.pt-scr-btns { display: flex; gap: .5rem; flex-shrink: 0; }

/* Save carries the accent, Discard stays quiet until it matters */
.pt-sbtn { font: inherit; font-size: .82rem; font-weight: 600; cursor: pointer;
        padding: .45rem .85rem; border-radius: 8px; border: 1px solid transparent;
        background: var(--pt-card); color: var(--pt-text); }
.pt-sbtn-save { background: var(--pt-blue); border-color: var(--pt-blue); color: #fff; }
.pt-sbtn-save:hover:not(:disabled) { background: var(--pt-blue-dk); border-color: var(--pt-blue-dk); }
.pt-sbtn-off { background: var(--pt-chip-gray); border-color: var(--pt-line);
        color: var(--pt-faint); cursor: default; }
.pt-sbtn-discard { border-color: var(--pt-line); color: var(--pt-red); }
.pt-sbtn-discard:hover:not(:disabled) { background: var(--pt-chip-red); }
.pt-sbtn:disabled { opacity: .55; cursor: default; }

/* tab strip: the live tab reads as a raised card edge */
.pt-tabs { display: flex; gap: .25rem; padding: .55rem 1.15rem 0;
        border-bottom: 1px solid var(--pt-line); }
.pt-tab { font-size: .84rem; font-weight: 600; color: var(--pt-muted);
        padding: .45rem .75rem; border: 1px solid transparent; border-bottom: 0;
        border-radius: 8px 8px 0 0; cursor: pointer; margin-bottom: -1px;
        background: none; }
.pt-tab:hover { color: var(--pt-text); background: var(--pt-line-soft); }
.pt-tab.pt-on { color: var(--pt-text); background: var(--pt-card);
        border-color: var(--pt-line); }

.pt-pane { padding: 1.05rem 1.15rem 1.25rem; }
.pt-pane[hidden] { display: none; }

/* two fields to a row, one when the field wants the width */
.pt-row { display: flex; flex-wrap: wrap; gap: .9rem 1.1rem; }
.pt-fld { flex: 1 1 calc(50% - .55rem); min-width: 200px;
        margin-bottom: .15rem; }
.pt-fld-wide { flex-basis: 100%; }
.pt-fld label { display: block; font-size: .76rem; font-weight: 600;
        color: var(--pt-muted); margin-bottom: .28rem; }
.pt-fld input, .pt-fld select, .pt-fld textarea {
        width: 100%; font: inherit; font-size: .86rem; color: var(--pt-text);
        background: var(--pt-card); border: 1px solid var(--pt-field);
        border-radius: 8px; padding: .5rem .6rem; }
.pt-fld textarea { resize: vertical; min-height: 4.5rem; }
.pt-fld input:focus, .pt-fld select:focus, .pt-fld textarea:focus {
        outline: 0; border-color: var(--pt-blue);
        box-shadow: 0 0 0 3px rgba(42, 120, 214, .12); }
.pt-fld input::placeholder, .pt-fld textarea::placeholder { color: var(--pt-faint); }
.pt-fld input:read-only, .pt-fld textarea:read-only { background: var(--pt-bg); }

/* a value nobody can change here reads as plain text on the page */
.pt-fld .pt-ro { font-size: .86rem; padding: .5rem .1rem; min-height: 1.2rem;
        border-bottom: 1px solid var(--pt-line-soft); }
.pt-fld .pt-ro:empty::after { content: '\2014'; color: var(--pt-faint); }

.pt-note { font-size: .78rem; color: var(--pt-muted); margin: 0 0 .85rem; }
.pt-saved { font-size: .8rem; font-weight: 600; color: var(--pt-green);
        align-self: center; }
.pt-scr-err { margin: 0 1.15rem 1rem; padding: .55rem .7rem; border-radius: 8px;
        background: var(--pt-chip-red); color: var(--pt-red);
        font-size: .82rem; font-weight: 600; }
</style>

<!-- stdPage seats the page beside the nav menu -->
<div id="stdPage">
<div class="pt-app">

    <?php prjHeader('Project', '<span class="pt-when" id="ptUpdated"></span>', 'project'); ?>

    <div class="pt-card pt-scr">
        <div class="pt-scr-head">
            <div>
                <div class="pt-scr-what">Project</div>
                <div class="pt-scr-num" id="scrNum"><?php echo intval($projNum); ?></div>
            </div>
            <div class="pt-scr-btns">
                <span class="pt-saved" id="scrSaved" hidden>Saved</span>
                <button type="button" class="pt-sbtn pt-sbtn-save" id="btnSave" disabled>Save project</button>
                <button type="button" class="pt-sbtn pt-sbtn-discard" id="btnDiscard" disabled>Discard</button>
            </div>
        </div>

        <div class="pt-tabs" id="scrTabs">
            <button type="button" class="pt-tab pt-on" data-pane="general">General</button>
            <button type="button" class="pt-tab" data-pane="it">IT stuff</button>
            <button type="button" class="pt-tab" data-pane="payback">Payback</button>
            <button type="button" class="pt-tab" data-pane="sc">Steering committee</button>
        </div>

        <div class="pt-scr-err" id="scrErr" hidden></div>

        <div class="pt-pane" id="pane-general"></div>
        <div class="pt-pane" id="pane-it" hidden></div>
        <div class="pt-pane" id="pane-payback" hidden></div>
        <div class="pt-pane" id="pane-sc" hidden></div>
    </div>

</div>
</div>
<?php
}
?>
