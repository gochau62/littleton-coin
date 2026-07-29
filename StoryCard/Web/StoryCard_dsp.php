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

.sc-app { font-family: var(--sc-font); color: var(--sc-text);
          background: #f8f8f8; padding-bottom: 2rem; }

.sc-topbar { display: flex; align-items: center; justify-content: space-between;
             background: var(--sc-green-dk); color: #fff; padding: .6rem 1.25rem; }
.sc-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.sc-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

.sc-card { background: #fff; border: 1px solid var(--sc-line); border-radius: 8px;
           margin: 1rem 1.25rem 0; padding: 1rem 1.1rem; }
.sc-rule { border: 0; border-top: 1px solid var(--sc-line); margin: 1.1rem 0; }

/* ----- fields ----- */
.sc-itembar { display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: nowrap; }
.sc-itembar .sc-btn { flex: 0 0 auto; }
.sc-searchrow { margin-top: 1.1rem; }
.sc-field { display: flex; flex-direction: column; gap: .25rem; }
.sc-field > span { font-size: .72rem; color: var(--sc-muted);
                   text-transform: uppercase; letter-spacing: .05em; }
.sc-field input { padding: .45rem .6rem; border: 1px solid var(--sc-line);
                  border-radius: 6px; font-size: .92rem; background: #fff; }
.sc-field input[readonly] { border-color: transparent; background: none;
                            padding-left: 0; font-weight: 600; }
/* the description gives way so the buttons keep their place on the row */
.sc-fgrow { flex: 1 1 160px; min-width: 0; }
.sc-desc { width: 100%; max-width: 330px; }
.sc-mono, .sc-sku, .sc-srk, .sc-ltxt, .sc-footlines, .sc-footkey, .sc-sg-sku {
  font-family: var(--sc-mono);
}
.sc-sku { width: calc(10ch + 2.4rem); padding-right: 1.6rem; text-transform: uppercase; }
.sc-srk { width: calc(15ch + 1.3rem); text-transform: uppercase; }

.sc-skubox { position: relative; display: inline-flex; align-items: center; }
.sc-skudd { position: absolute; right: .5rem; color: var(--sc-muted);
            cursor: pointer; user-select: none; font-size: .8rem; }

/* one word in one colour, in place of a sentence */
.sc-chip { margin-left: auto; padding: .2rem .6rem; border-radius: 50px;
           font-size: .78rem; font-weight: 700; text-transform: uppercase;
           visibility: hidden; }
.sc-chip.sc-on    { visibility: visible; }
.sc-chip.sc-new   { background: var(--sc-accent); color: var(--sc-green); }
.sc-chip.sc-dirty { background: #fbf1dc; color: var(--sc-amber); }

/* ----- buttons: house pill, ghost hover, same as the Sellbrite ones ----- */
.sc-btn { display: inline-flex; align-items: center; justify-content: center;
          padding: .45rem 1.1rem; border: 1px solid #b4b4b4; border-radius: 50px;
          background: #fff; color: var(--sc-text); font-size: .9rem;
          font-weight: 700; cursor: pointer; }
.sc-btn:hover { border-color: var(--sc-blue); color: var(--sc-blue); }
.sc-btn:disabled { opacity: .45; cursor: default;
                   border-color: #b4b4b4; color: var(--sc-text); }
.sc-btn-primary, .sc-btn-primary:hover {
  background: var(--sc-blue); border-color: var(--sc-blue); color: #fff; }
.sc-btn-primary:hover { background: var(--sc-blue-hv); border-color: var(--sc-blue-hv); }

/* ----- the two printed sides ----- */
/* the two sides sit beside each other at every window width, like the form.
   They split the room; they never wrap into a stack */
.sc-sides { display: flex; gap: 1rem; flex-wrap: nowrap; align-items: flex-start;
            flex: 1 1 auto; min-width: 0; }
.sc-side  { flex: 1 1 50%; min-width: 0; }
.sc-h { margin: 0 0 .3rem; font-size: .72rem; color: var(--sc-muted); font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em; }
.sc-lines { display: flex; flex-direction: column; gap: 2px; }
.sc-lrow  { display: flex; align-items: center; gap: .35rem; }

/* the line number gutter. This is the Access "ruler" - Text23 and Text21 were
   text boxes filled with one digit per line in Form_Load, not a column scale */
.sc-lno { width: 2rem; text-align: right; font-size: .74rem; color: #b3bdb8;
          font-variant-numeric: tabular-nums; user-select: none; }
.sc-lrow.sc-filled .sc-lno { color: var(--sc-text); font-weight: 700; }

/* capped at fifty characters, the file column width, but able to shrink with
   the window - a narrower box scrolls while typing, which is exactly how the
   Access text boxes behaved. maxlength still holds the limit either way */
.sc-ltxt { flex: 1 1 auto; min-width: 0; max-width: calc(50ch + .9rem + 2px);
           padding: .3rem .45rem; border: 1px solid var(--sc-line);
           border-radius: 4px; font-size: .84rem; }
.sc-ltxt:focus { outline: 2px solid var(--sc-blue); outline-offset: -1px;
                 border-color: var(--sc-blue); }

/* ----- footer, under side 2 where the Access form put it ----- */
.sc-footh { margin-top: 1rem; display: flex; align-items: center; gap: .5rem; }
.sc-footkey { padding: .15rem .4rem; font-size: .75rem; background: #fff;
              border: 1px solid var(--sc-line); border-radius: 4px;
              color: var(--sc-text); text-transform: none; font-weight: 400; }
.sc-footlines { font-size: .84rem; white-space: pre; margin: 0;
                margin-left: calc(2rem + .35rem + 1px + .45rem);
                overflow-x: auto; }
.sc-footlines:empty::before { content: "\2014"; color: #c4ccc8; }

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
.sc-modal { background: #fff; border-radius: 8px; width: 100%; max-width: 620px;
            box-shadow: 0 12px 40px rgba(0,0,0,.3); }
.sc-modal-head, .sc-modal-foot { display: flex; align-items: center;
                                 padding: .8rem 1.1rem; }
.sc-modal-head { justify-content: space-between; border-bottom: 1px solid var(--sc-line); }
.sc-modal-foot { justify-content: flex-end; gap: .6rem;
                 border-top: 1px solid var(--sc-line); }
.sc-modal-head h2 { margin: 0; font-size: 1.02rem; }
.sc-modal-body { padding: 1rem 1.1rem; }
.sc-x { border: 0; background: none; font-size: 1.4rem; line-height: 1;
        cursor: pointer; color: var(--sc-muted); }
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
          <pre class="sc-footlines" id="outFooter"></pre>
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
            <input type="text" id="mdlFootKey" class="sc-footkey sc-mono"
                   list="footKeyList" maxlength="7" inputmode="numeric"
                   autocomplete="off" style="width:6rem">
          </label>
          <datalist id="footKeyList"></datalist>
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
// Story Card Maintenance frontend logic (SKU picker, both sides, footer editor)
var STC_PRELOAD = <?php echo $stcPreload ? json_encode($stcPreload) : 'null'; ?>;
// STC_MODE 'footer' opens straight on the footer editor, the old FootMaintenance form
var STC_MODE = '<?php echo $mode; ?>';

// the printed card, straight off the model so nothing here can drift from it
var S1_FIRST = <?php echo STC_S1_FIRST; ?>, S1_LAST = <?php echo STC_S1_LAST; ?>;
var S2_FIRST = <?php echo STC_S2_FIRST; ?>, S2_LAST = <?php echo STC_S2_LAST; ?>;
// SCBTXT and SCFTXT are both CHAR(50); no box on this page may take more than
// the column behind it holds, so the width comes from the model, not from here
var LINE_LEN = <?php echo STC_LINE_LEN; ?>;

// the card as it came back from the server, so Revert has something to go to
var loadedCard = null;
// the footer as loaded, for the strip and for the editor, plus the keys the
// FooterSelect query offers and which one is on screen
var footerLines = [];
var footerKeys = [];
var footerSky = 1;
var suggestTimer = null;
var suggestSeq = 0;


$(document).ready(function () {
    buildSide('#side1Lines', S1_FIRST, S1_LAST);
    buildSide('#side2Lines', S2_FIRST, S2_LAST);
    setCard(null);

    tickClock();
    setInterval(tickClock, 1000);

    // the controller hands the page its footer, and the card too when the URL
    // named a SKU, so nothing has to be fetched before the screen is usable
    if (STC_PRELOAD) {
        footerLines = STC_PRELOAD.footer || [];
        footerKeys  = STC_PRELOAD.keys || [];
        footerSky   = STC_PRELOAD.sky || 1;
        renderFooter();
        if (STC_PRELOAD.card) {
            $('#txtSku').val(STC_PRELOAD.card.sku);
            setCard(STC_PRELOAD.card);
        }
    } else {
        loadFooter();
    }

    if (STC_MODE === 'footer') { openFooterEditor(); }

    $('#btnSave').on('click', saveCard);
    $('#btnFooter').on('click', openFooterEditor);
    $('#btnFootAdd').on('click', function () {
        addFooterRow('');
        $('#footLines .sc-ltxt').last().trigger('focus');
    });
    $('#btnFootSave').on('click', saveFooter);
    // both key pickers load that footer. The editor's key box also ADDS one:
    // type a key with nothing on file and the footer opens empty in insert
    // mode, the same way typing a new SKU starts a new card
    $('#selFootKey').on('change', function () { loadFooter($(this).val()); });
    $('#mdlFootKey').on('change', function () {
        var k = parseInt($(this).val(), 10);
        if (!(k >= 1)) { $(this).val(footerSky); return; }
        // the same key again is not a change - reloading would re-render the
        // rows and wipe anything already typed into them
        if (k === footerSky) { $(this).val(footerSky); return; }
        loadFooter(k, renderFooterEditor);
    });

    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).prop('hidden', true);
    });

    // ----- the SKU picker -----
    $('#txtSku').on('input', function () {
        var inp = $(this);
        clearTimeout(suggestTimer);
        suggestTimer = setTimeout(function () {
            runSkuSearch(inp, inp.val().trim());
        }, 250);
    });
    // focus opens the list, and so does a click once the box already has focus:
    // a second click on an already focused input fires no focus event, which is
    // why clicking it again used to do nothing. Opening always shows the WHOLE
    // list, positioned at the current value - the Access combo only filtered
    // while you typed, never on open
    $('#txtSku').on('focus click', function () {
        if (!$('#scSuggest').length) { openSkuList(); }
    });
    $('#txtSku').on('blur', function () {
        // wait so a click on the list lands before blur hides it
        setTimeout(hideSuggest, 150);
    });
    // the arrow toggles it (mousedown so the input keeps focus). Focusing an
    // unfocused box opens the list through the focus handler; a box that is
    // already focused fires no event, so open it directly
    $('#btnSkuDrop').on('mousedown', function (e) {
        e.preventDefault();
        if ($('#scSuggest').length) { hideSuggest(); return; }
        if (document.activeElement !== $('#txtSku')[0]) { $('#txtSku').trigger('focus'); }
        else { openSkuList(); }
    });
    $('#txtSku').on('keydown', function (e) {
        var box = $('#scSuggest');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (!box.length) { return; }
            e.preventDefault();
            var items = box.children();
            var i = items.index(items.filter('.active'));
            i = (e.key === 'ArrowDown') ? Math.min(i + 1, items.length - 1)
                                        : Math.max(i - 1, 0);
            items.removeClass('active').eq(i).addClass('active');
            scrollActiveIntoView(box);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var act = box.children('.active');
            if (act.length) { act.trigger('mousedown'); }
            else { loadCard($(this).val()); hideSuggest(); }
        } else if (e.key === 'Escape') {
            hideSuggest();
        }
    });

    // ----- the line boxes -----
    $('#side1Lines, #side2Lines').on('input', '.sc-ltxt', function () {
        refreshRow($(this));
        markDirty();
    });
    $('#txtSrk').on('input', markDirty);

    // Enter and the arrows walk the lines, the way tabbing through the old
    // text box felt, without leaving the field on Enter
    $('#side1Lines, #side2Lines, #footLines').on('keydown', '.sc-ltxt', function (e) {
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault();
            stepLine($(this), 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            stepLine($(this), -1);
        }
    });

    // pasting a block of text spreads it down the lines instead of stuffing it
    // all into one box and losing everything past character fifty
    $('#side1Lines, #side2Lines, #footLines').on('paste', '.sc-ltxt', function (e) {
        var text = (e.originalEvent || e).clipboardData;
        if (!text) { return; }
        var raw = text.getData('text');
        if (raw.indexOf('\n') < 0 && raw.length <= LINE_LEN) { return; }
        e.preventDefault();
        spreadPaste($(this), raw);
    });

    // Ctrl+S saves the card
    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            if (!$('#btnSave').prop('disabled')) { e.preventDefault(); saveCard(); }
        }
        if (e.key === 'Escape') {
            $('.sc-overlay').not('[hidden]').last().prop('hidden', true);
        }
    });

    // leaving with unsaved edits should cost a click, not a card
    window.addEventListener('beforeunload', function (e) {
        if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
    });
});


// ---------------------------------------------------------------- building

function buildSide(sel, first, last) {
    var box = $(sel).empty();
    for (var n = first; n <= last; n++) { box.append(lineRow(n, '')); }
}


function lineRow(no, text) {
    return $('<div class="sc-lrow">')
        .append($('<span class="sc-lno">').text(no))
        .append($('<input type="text" class="sc-ltxt" spellcheck="false">')
                    .attr('maxlength', LINE_LEN)
                    .attr('data-line', no)
                    .val(text));
}


// ---------------------------------------------------------------- the card

function setCard(card) {
    loadedCard = card;

    var side1 = (card && card.side1) || [];
    var side2 = (card && card.side2) || [];

    $('#side1Lines .sc-ltxt').each(function (i) { $(this).val(side1[i] || ''); });
    $('#side2Lines .sc-ltxt').each(function (i) { $(this).val(side2[i] || ''); });

    $('#outSku').val(card ? card.sku : '');
    $('#outDesc').val(card ? card.desc : '');
    $('#txtSrk').val(card ? card.searchKey : '').prop('disabled', !card);

    $('#side1Lines .sc-ltxt, #side2Lines .sc-ltxt').prop('disabled', !card);
    $('#btnSave').prop('disabled', !card);

    $('#side1Lines .sc-ltxt, #side2Lines .sc-ltxt').each(function () { refreshRow($(this)); });
    setChip(card && card.isNew ? 'new' : '');
}


// one word in one colour, in place of a sentence
function setChip(state) {
    var chip = $('#lblState').removeClass('sc-on sc-new sc-dirty');
    if (state === 'new')   { chip.addClass('sc-on sc-new').text('New'); }
    if (state === 'dirty') { chip.addClass('sc-on sc-dirty').text('Unsaved'); }
}


function collectSide(sel) {
    var out = [];
    $(sel + ' .sc-ltxt').each(function () { out.push($(this).val()); });
    return out;
}


// true when the boxes no longer match what was loaded
function isDirty() {
    if (!loadedCard) { return false; }
    var s1 = collectSide('#side1Lines'), s2 = collectSide('#side2Lines');
    for (var i = 0; i < s1.length; i++) {
        if (rtrim(s1[i]) !== rtrim((loadedCard.side1 || [])[i] || '')) { return true; }
    }
    for (var j = 0; j < s2.length; j++) {
        if (rtrim(s2[j]) !== rtrim((loadedCard.side2 || [])[j] || '')) { return true; }
    }
    return $('#txtSrk').val().trim() !== (loadedCard.searchKey || '').trim();
}


function markDirty() {
    if (!loadedCard) { return; }
    setChip(isDirty() ? 'dirty' : (loadedCard.isNew ? 'new' : ''));
}


// the line number goes bold once the line carries text, which is the only
// state a line has. The box is exactly the width of the file column, so there
// is nothing to count
function refreshRow(inp) {
    inp.closest('.sc-lrow').toggleClass('sc-filled', inp.val().trim() !== '');
}


function loadCard(sku) {
    sku = String(sku || '').trim().toUpperCase();
    if (sku === '') { return; }

    if (!isDirty()) { fetchCard(sku); return; }

    ask('Unsaved changes',
        'The card on screen has changes that have not been saved.',
        'Load ' + sku, 'Stay here',
        function () { fetchCard(sku); });
}


function fetchCard(sku) {
    postAjax({ action: 'card', sku: sku }, function (resp) {
        $('#txtSku').val(resp.card.sku);
        setCard(resp.card);
    });
}


function saveCard() {
    if (!loadedCard) { return; }

    var payload = {
        sku: loadedCard.sku,
        searchKey: $('#txtSrk').val().trim(),
        side1: collectSide('#side1Lines'),
        side2: collectSide('#side2Lines')
    };

    $('#btnSave').prop('disabled', true);
    postAjax({ action: 'savecard', payload: JSON.stringify(payload) }, function (resp) {
        $('#btnSave').prop('disabled', false);
        // reload rather than trust the screen: what the file holds is the truth
        postAjax({ action: 'card', sku: resp.sku }, function (r2) { setCard(r2.card); });
    }, function () {
        $('#btnSave').prop('disabled', false);
    });
}


// ---------------------------------------------------------------- the footer

function loadFooter(sky, then) {
    postAjax({ action: 'footer', sky: sky || footerSky }, function (resp) {
        footerLines = resp.footer || [];
        footerKeys  = resp.keys || [];
        footerSky   = resp.sky;
        renderFooter();
        if (then) { then(); }
    });
}


function renderFooter() {
    fillKeys('#selFootKey');
    var dl = $('#footKeyList').empty();
    $.each(footerKeys, function (i, k) { dl.append($('<option>').val(k)); });
    var shown = footerLines.slice();
    while (shown.length && rtrim(shown[shown.length - 1]) === '') { shown.pop(); }
    $('#outFooter').text(shown.join('\n'));
}


// the FooterSelect list. It only ever offers the keys that query returns
function fillKeys(sel) {
    var box = $(sel).empty();
    var keys = footerKeys.length ? footerKeys : [footerSky];
    $.each(keys, function (i, k) {
        box.append($('<option>').val(k).text(k));
    });
    box.val(String(footerSky));
}


function openFooterEditor() {
    $('#mdlFootKey').val(footerSky);
    renderFooterEditor();
    $('#mdlFooter').prop('hidden', false);
    $('#footLines .sc-ltxt').first().trigger('focus');
}


function renderFooterEditor() {
    $('#mdlFootKey').val(footerSky);
    $('#footLines').empty();
    var lines = footerLines.slice();

    // the Access rule per key: an empty footer takes any number of lines, a
    // footer that has rows can only have those rows rewritten, never grown.
    // The editor offers exactly what the save can do
    var canGrow = lines.length === 0;
    if (canGrow) { lines.push(''); }
    $.each(lines, function (i, t) { addFooterRow(t); });
    $('#btnFootAdd').prop('hidden', !canGrow);

    var chip = $('#mdlFootNew').removeClass('sc-on sc-new');
    if (canGrow) { chip.addClass('sc-on sc-new').text('New'); }
}


function addFooterRow(text) {
    var no = $('#footLines .sc-lrow').length + 1;
    $('#footLines').append(lineRow(no, text));
    refreshRow($('#footLines .sc-ltxt').last());
}


function saveFooter() {
    var lines = [];
    $('#footLines .sc-ltxt').each(function () { lines.push($(this).val()); });

    $('#btnFootSave').prop('disabled', true);
    postAjax({ action: 'savefooter',
               payload: JSON.stringify({ sky: footerSky, footer: lines }) },
        function () {
            $('#btnFootSave').prop('disabled', false);
            $('#mdlFooter').prop('hidden', true);
            loadFooter();
        },
        function () { $('#btnFootSave').prop('disabled', false); });
}


// ---------------------------------------------------------------- SKU list

// the whole list, regardless of what the box holds
function openSkuList() {
    runSkuSearch($('#txtSku'), '');
}


function runSkuSearch(inp, q) {
    var seq = ++suggestSeq;
    postAjax({ action: 'skusearch', q: q },
        function (resp) {
            if (seq !== suggestSeq) { return; }
            showSuggest(inp, resp.rows, q);
        },
        function (resp) {
            if (seq !== suggestSeq) { return; }
            showSuggestError(inp, (resp && resp.msg) ? resp.msg : 'no reply from the server');
        }, true);
}


// the list could not be fetched: say so in the box where the rows would be,
// instead of silently showing nothing
function showSuggestError(inp, msg) {
    hideSuggest();
    var box = $('<div class="sc-suggest" id="scSuggest">');
    $('<div class="sc-sg-err">').text('SKU list unavailable - ' + msg).appendTo(box);
    var off = inp.offset();
    box.css({ left: off.left, top: off.top + inp.outerHeight() + 2,
              minWidth: Math.max(inp.outerWidth(), 360), maxWidth: 560,
              whiteSpace: 'normal' });
    $('body').append(box);
}


function showSuggest(inp, rows, typed) {
    hideSuggest();
    if (!rows || !rows.length) { return; }

    var box = $('<div class="sc-suggest" id="scSuggest">');
    var up = String(typed || '').toUpperCase();

    $.each(rows, function (i, r) {
        var sku = rtrim(r.SCSKU);
        var shown = up && sku.toUpperCase().indexOf(up) === 0
            ? '<b>' + escHtml(sku.substr(0, up.length)) + '</b>' + escHtml(sku.substr(up.length))
            : escHtml(sku);
        $('<div>').attr('data-sku', sku)
                  .html('<span class="sc-sg-sku">' + shown + '</span>' +
                        escHtml(rtrim(r.SCDESC)))
                  .appendTo(box);
    });

    // mousedown, not click, so the pick lands before the input blurs
    box.on('mousedown', 'div', function (e) {
        e.preventDefault();
        var sku = $(this).data('sku');
        hideSuggest();
        $('#txtSku').val(sku);
        loadCard(sku);
    });

    var off = inp.offset();
    box.css({ left: off.left, top: off.top + inp.outerHeight() + 2,
              minWidth: Math.max(inp.outerWidth(), 360) });
    $('body').append(box);

    // a full-list open lands on the current value, the way the combo did
    var cur = inp.val().trim().toUpperCase();
    if (typed === '' && cur !== '') {
        box.children().filter(function () {
            return String($(this).data('sku')).toUpperCase() === cur;
        }).first().addClass('active');
        scrollActiveIntoView(box);
    }
}


function hideSuggest() { $('#scSuggest').remove(); }


function scrollActiveIntoView(box) {
    var act = box.children('.active');
    if (!act.length) { return; }
    var top = act.position().top;
    if (top < 0) { box.scrollTop(box.scrollTop() + top); }
    else if (top + act.outerHeight() > box.height()) {
        box.scrollTop(box.scrollTop() + top + act.outerHeight() - box.height());
    }
}


// ---------------------------------------------------------------- helpers

// move focus up or down the column of line boxes
function stepLine(inp, delta) {
    var boxes = inp.closest('.sc-lines').find('.sc-ltxt');
    var i = boxes.index(inp) + delta;
    if (i >= 0 && i < boxes.length) {
        boxes.eq(i).trigger('focus').each(function () {
            this.setSelectionRange(this.value.length, this.value.length);
        });
    }
}


// spread pasted text down the boxes: each newline starts a new line, and any
// line longer than fifty characters wraps on a word boundary rather than being
// chopped. Whatever does not fit is reported instead of vanishing
function spreadPaste(inp, raw) {
    var boxes = inp.closest('.sc-lines').find('.sc-ltxt');
    var start = boxes.index(inp);

    var out = [];
    $.each(String(raw).replace(/\r\n?/g, '\n').split('\n'), function (i, para) {
        var rest = rtrim(para);
        if (rest === '') { out.push(''); return; }
        while (rest.length > LINE_LEN) {
            var cut = rest.lastIndexOf(' ', LINE_LEN);
            if (cut <= 0) { cut = LINE_LEN; }
            out.push(rtrim(rest.substr(0, cut)));
            rest = rest.substr(cut).replace(/^ +/, '');
        }
        out.push(rest);
    });

    var room = boxes.length - start;
    var dropped = Math.max(0, out.length - room);

    for (var k = 0; k < out.length && k < room; k++) {
        boxes.eq(start + k).val(out[k]);
        refreshRow(boxes.eq(start + k));
    }

    markDirty();

    if (dropped > 0) {
        swal('Too much text',
             dropped + ' line' + (dropped === 1 ? '' : 's') + ' would not fit. This side holds ' +
             boxes.length + ' lines of ' + LINE_LEN + ' characters.',
             'warning');
    }
}


// a yes or no the user has to answer before anything happens, in the same swal
// box as every other message on the page
function ask(title, text, yes, no, onYes) {
    swal({
        title: title,
        text: text,
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: yes,
        cancelButtonText: no,
        closeOnConfirm: true
    }, function (ok) { if (ok) { onYes(); } });
}


function postAjax(data, onOk, onFail, silent) {
    $.post('StoryCard_ajax.php', data, function (resp) {
        if (resp && resp.ok) { onOk(resp); }
        else {
            if (onFail) { onFail(resp); }
            if (!silent) {
                swal('Error', (resp && resp.msg) ? resp.msg : 'Request failed.', 'error');
            }
        }
    }, 'json').fail(function () {
        if (onFail) { onFail(null); }
        if (!silent) { swal('Error', 'Server error - see the log.', 'error'); }
    });
}


function rtrim(s) { return String(s === null || s === undefined ? '' : s).replace(/\s+$/, ''); }


function escHtml(s) {
    return String(s === null || s === undefined ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}


function tickClock() {
    var d = new Date();
    $('#scClock').text(d.toLocaleDateString() + '  ' + d.toLocaleTimeString());
}
</script>

<?php
} // end dspStoryCard
?>
