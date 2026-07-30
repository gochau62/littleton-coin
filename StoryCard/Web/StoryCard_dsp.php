<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCard_dsp.php                 *  -->
<!--  *                                                 *  -->
<!--  * Author    -  G CHAU                             *  -->
<!--  *              Littleton Coin Company             *  -->
<!--  *              Littleton NH                       *  -->
<!--  * Date Written 07/27/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260074                              *  -->
<!--  ***************************************************   */

require_once __DIR__ . '/StoryCard_model.php';

function dspStoryCard($user, $stcPreload = null, $mode = '') {
?>

<style>
/* Story Card Maintenance styling - inline: the display owns everything visual */
:root {
  --sc-green-dk: #1C4532;
  --sc-green:    #2e8b57;
  --sc-blue:     #007bff;
  --sc-blue-hv:  #0056b3;
  --sc-accent:   #eaf6ee;
  --sc-line:     #dfe6e1;
  --sc-text:     #222;
  --sc-muted:    #5f6b62;
  --sc-amber:    #9a6a14;
  --sc-font:     "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
  --sc-mono:     "Consolas", "Courier New", monospace;
}

.sc-app { font-family: var(--sc-font); color: var(--sc-text); background: #f8f8f8; padding-bottom: 2rem; }

.sc-topbar { display: flex; align-items: center; justify-content: space-between;
             background: var(--sc-green-dk); color: #fff; padding: .6rem 1.25rem; }
.sc-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.sc-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

.sc-card { background: #fff; border: 1px solid var(--sc-line); border-radius: 8px; margin: 1rem 1.25rem 0; padding: 1rem 1.1rem; }
.sc-rule { border: 0; border-top: 1px solid var(--sc-line); margin: 1.1rem 0; }

/* ----- fields ----- */ .sc-itembar { display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: nowrap; }
.sc-itembar .sc-btn { flex: 0 0 auto; }
.sc-searchrow { margin-top: 1.1rem; }
.sc-field { display: flex; flex-direction: column; gap: .25rem; }
.sc-field > span { font-size: .72rem; color: var(--sc-muted); text-transform: uppercase; letter-spacing: .05em; }
.sc-field input { padding: .45rem .6rem; border: 1px solid var(--sc-line); border-radius: 6px; font-size: .92rem; background: #fff; }
.sc-field input[readonly] { border-color: transparent; background: none; padding-left: 0; font-weight: 600; }
/* the description gives way so the buttons keep their place on the row */ .sc-fgrow { flex: 1 1 160px; min-width: 0; }
.sc-desc { width: 100%; max-width: 330px; }
.sc-mono, .sc-sku, .sc-srk, .sc-ltxt, .sc-footkey, .sc-sg-sku { font-family: var(--sc-mono); }
.sc-sku { width: calc(10ch + 2.4rem); padding-right: 1.6rem; text-transform: uppercase; }
.sc-srk { width: calc(15ch + 1.3rem); text-transform: uppercase; }

.sc-skubox { position: relative; display: inline-flex; align-items: center; }
.sc-skudd { position: absolute; right: .5rem; color: var(--sc-muted); cursor: pointer; user-select: none; font-size: .8rem; }

/* one word in one colour, in place of a sentence */
.sc-chip { margin-left: auto; padding: .2rem .6rem; border-radius: 50px;
           font-size: .78rem; font-weight: 700; text-transform: uppercase;
           visibility: hidden; }
.sc-chip.sc-on { visibility: visible; }
.sc-chip.sc-new { background: var(--sc-accent); color: var(--sc-green); }
.sc-chip.sc-dirty { background: #fbf1dc; color: var(--sc-amber); }

/* ----- buttons: house pill, ghost hover, same as the Sellbrite ones ----- */
.sc-btn { display: inline-flex; align-items: center; justify-content: center;
          padding: .45rem 1.1rem; border: 1px solid #b4b4b4; border-radius: 50px;
          background: #fff; color: var(--sc-text); font-size: .9rem;
          font-weight: 700; cursor: pointer; }
.sc-btn:hover { border-color: var(--sc-blue); color: var(--sc-blue); }
.sc-btn:disabled { opacity: .45; cursor: default; border-color: #b4b4b4; color: var(--sc-text); }
.sc-btn-primary, .sc-btn-primary:hover { background: var(--sc-blue); border-color: var(--sc-blue); color: #fff; }
.sc-btn-primary:hover { background: var(--sc-blue-hv); border-color: var(--sc-blue-hv); }

/* ----- the two printed sides ----- */
/* the two sides sit beside each other at every window width, like the form. They split the room; they never wrap into a stack */
.sc-sides { display: flex; gap: 1rem; flex-wrap: nowrap; align-items: flex-start;
            flex: 1 1 auto; min-width: 0; }
.sc-side { flex: 1 1 50%; min-width: 0; }
.sc-h { margin: 0 0 .3rem; font-size: .72rem; color: var(--sc-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.sc-lines { display: flex; flex-direction: column; gap: 2px; }
.sc-lrow { display: flex; align-items: center; gap: .35rem; }

/* the line number gutter. This is the Access "ruler" - Text23 and Text21 were text boxes filled with one digit per line in Form_Load, not a column scale */
.sc-lno { width: 2rem; text-align: right; font-size: .74rem; color: #b3bdb8;
          font-variant-numeric: tabular-nums; user-select: none; }
.sc-lrow.sc-filled .sc-lno { color: var(--sc-text); font-weight: 700; }

/* capped at fifty characters, the file column width, but able to shrink with the window - a narrower box scrolls while typing, which is exactly how the
   Access text boxes behaved. maxlength still holds the limit either way */
.sc-ltxt { flex: 1 1 auto; min-width: 0; max-width: calc(50ch + .9rem + 2px);
           padding: .3rem .45rem; border: 1px solid var(--sc-line);
           border-radius: 4px; font-size: .84rem; }
.sc-ltxt:focus { outline: 2px solid var(--sc-blue); outline-offset: -1px; border-color: var(--sc-blue); }

/* ----- footer, under side 2 where the Access form put it ----- */ .sc-footh { margin-top: 1rem; display: flex; align-items: center; gap: .5rem; }
.sc-footkey { padding: .15rem .4rem; font-size: .75rem; background: #fff;
              border: 1px solid var(--sc-line); border-radius: 4px;
              color: var(--sc-text); text-transform: none; font-weight: 400; }
/* the strip rows are the same numbered boxes the sides use, just read only */
#outFooter .sc-ltxt[readonly] { background: #fdfdfd; }

/* ----- SKU type-ahead ----- */
.sc-suggest { position: fixed; z-index: 200; background: #fff; border: 1px solid #999;
              border-radius: 4px; box-shadow: 0 6px 18px rgba(0,0,0,.18);
              max-height: 260px; overflow-y: auto;
              font-family: var(--sc-font); font-size: .85rem; color: var(--sc-text); }
.sc-suggest div { padding: .3rem .6rem; cursor: pointer; white-space: nowrap; }
.sc-suggest div b { color: var(--sc-blue); }
.sc-suggest div.active, .sc-suggest div:hover { background: var(--sc-accent); }
.sc-sg-sku { display: inline-block; width: calc(10ch + 1rem); }
.sc-sg-err { color: #a33; cursor: default; white-space: normal; }

/* ----- modal ----- */
.sc-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 150;
              display: flex; align-items: flex-start; justify-content: center;
              overflow-y: auto; padding: 2rem 1rem; }
.sc-overlay[hidden] { display: none; }
.sc-modal { background: #fff; border-radius: 8px; width: 100%; max-width: 620px; box-shadow: 0 12px 40px rgba(0,0,0,.3); }
.sc-modal-head, .sc-modal-foot { display: flex; align-items: center; padding: .8rem 1.1rem; }
.sc-modal-head { justify-content: space-between; border-bottom: 1px solid var(--sc-line); }
.sc-modal-foot { justify-content: flex-end; gap: .6rem; border-top: 1px solid var(--sc-line); }
.sc-modal-head h2 { margin: 0; font-size: 1.02rem; }
.sc-modal-body { padding: 1rem 1.1rem; }
.sc-x { border: 0; background: none; font-size: 1.4rem; line-height: 1; cursor: pointer; color: var(--sc-muted); }
</style>

<div class="sc-app">

  <header class="sc-topbar">
    <h1>Story Card Maintenance</h1>
    <div class="sc-topbar-right">
      <span id="scUser"><?php echo htmlspecialchars($user); ?></span>
      <span id="scClock"></span>
    </div>
  </header>

  <div class="sc-card">

    <div class="sc-itembar">
      <!-- a div and not a label: a label forwards a click anywhere inside it
           to its control, so clicking the arrow would fire the input's own
           click handler as well and the toggle would reopen immediately -->
      <div class="sc-field"><span>SKU</span>
        <span class="sc-skubox">
          <input type="text" id="txtSku" class="sc-sku sc-mono"
                 maxlength="<?php echo STC_SKU_LEN; ?>"
                 autocomplete="off" spellcheck="false">
          <span class="sc-skudd" id="btnSkuDrop" title="Show the list">&#9662;</span>
        </span>
      </div>
      <label class="sc-field sc-fgrow"><span>Description</span>
        <input type="text" id="outDesc" class="sc-desc" readonly tabindex="-1">
      </label>
      <button type="button" class="sc-btn sc-btn-primary" id="btnSave" disabled>Save</button>
      <button type="button" class="sc-btn" id="btnFooter">Footer</button>
      <span class="sc-chip" id="lblState"></span>
    </div>

    <hr class="sc-rule">

    <div class="sc-sides">
        <div class="sc-side">
          <div class="sc-h">Side 1</div>
          <div class="sc-lines" id="side1Lines"></div>
        </div>
        <div class="sc-side">
          <div class="sc-h">Side 2</div>
          <div class="sc-lines" id="side2Lines"></div>

          <div class="sc-h sc-footh">Footer
            <select class="sc-footkey" id="selFootKey"></select>
          </div>
          <div class="sc-lines" id="outFooter"></div>
        </div>
      </div>

    <label class="sc-field sc-searchrow"><span>Search text</span>
      <input type="text" id="txtSrk" class="sc-srk sc-mono"
             maxlength="<?php echo STC_SRK_LEN; ?>"
             autocomplete="off" spellcheck="false" disabled>
    </label>

  </div>

  <!-- ============ footer editor (the FootMaintenance form) ============ -->
  <div class="sc-overlay" id="mdlFooter" hidden>
    <div class="sc-modal">
      <div class="sc-modal-head">
        <h2>Footer</h2>
        <button type="button" class="sc-x" data-close="mdlFooter">&times;</button>
      </div>
      <div class="sc-modal-body">
        <div class="sc-itembar" style="margin-bottom:.8rem">
          <label class="sc-field"><span>Footer key</span>
            <span class="sc-skubox">
              <input type="text" id="mdlFootKey" class="sc-footkey sc-mono"
                     maxlength="7" inputmode="numeric" autocomplete="off"
                     style="width:6.5rem; padding:.35rem 1.5rem .35rem .5rem">
              <span class="sc-skudd" id="btnFootKeyDrop" title="Show the list">&#9662;</span>
            </span>
          </label>
          <span class="sc-chip" id="mdlFootNew"></span>
        </div>
        <div class="sc-lines" id="footLines"></div>
        <button type="button" class="sc-btn" id="btnFootAdd"
                style="margin-top:.6rem">+ Line</button>
      </div>
      <div class="sc-modal-foot">
        <button type="button" class="sc-btn" data-close="mdlFooter">Cancel</button>
        <button type="button" class="sc-btn sc-btn-primary" id="btnFootSave">Save</button>
      </div>
    </div>
  </div>

</div>

<script>
var STC_PRELOAD = <?php echo $stcPreload ? json_encode($stcPreload) : 'null'; ?>;
// STC_MODE 'footer' opens straight on the footer editor, the old FootMaintenance form
var STC_MODE = '<?php echo $mode; ?>';

// the printed card, straight off the model so nothing here can drift from it
var S1_FIRST = <?php echo STC_S1_FIRST; ?>, S1_LAST = <?php echo STC_S1_LAST; ?>;
var S2_FIRST = <?php echo STC_S2_FIRST; ?>, S2_LAST = <?php echo STC_S2_LAST; ?>;
// SCBTXT and SCFTXT are both CHAR(50); no box on this page may take more than the column behind it holds, so the width comes from the model, not from here
var LINE_LEN = <?php echo STC_LINE_LEN; ?>;
</script>

<?php
} // end dspStoryCard
?>
