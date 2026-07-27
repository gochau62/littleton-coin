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
<!--  * Project   -                                     *  -->
<!--  ***************************************************   */

function dspStoryCard($user, $stcPreload = null, $mode = '') {
?>

<style>
/* Story Card Maintenance styling - inline: the display owns everything visual */
:root {
  --sc-green-dk: #1C4532;
  --sc-green:    #2e8b57;
  --sc-green-hv: #1e6e43;
  --sc-blue:     #007bff;
  --sc-blue-hv:  #0056b3;
  --sc-accent:   #eaf6ee;
  --sc-bg:       #f8f8f8;
  --sc-line:     #dfe6e1;
  --sc-text:     #222;
  --sc-muted:    #5f6b62;
  --sc-amber:    #9a6a14;
  --sc-red:      #c0392b;
  --sc-card:     #fdfbf4;
}

.sc-app {
  font-family: "Segoe UI", system-ui, -apple-system, Arial, sans-serif;
  color: var(--sc-text);
  background: var(--sc-bg);
  padding: 0 0 2rem 0;
}

/* ----- top bar ----- */
.sc-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--sc-green-dk);
  color: #fff;
  padding: .6rem 1.25rem;
}
.sc-topbar h1 { font-size: 1.15rem; font-weight: 600; margin: 0; }
.sc-topbar-right { display: flex; gap: 1rem; font-size: .85rem; opacity: .9; }

/* ----- toolbar ----- */
.sc-toolbar {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .75rem 1.25rem;
  flex-wrap: wrap;
}
.sc-skubox { position: relative; display: inline-flex; align-items: center; }
.sc-sku {
  width: 190px;
  padding: .45rem 1.6rem .45rem .7rem;
  border: 1px solid var(--sc-line);
  border-radius: 6px;
  font-family: "Consolas", "Courier New", monospace;
  font-size: .95rem;
  text-transform: uppercase;
}
.sc-skudd {
  position: absolute; right: .45rem;
  color: var(--sc-muted); cursor: pointer; user-select: none; font-size: .8rem;
}
.sc-scope { color: var(--sc-muted); font-size: .85rem; user-select: none; }
.sc-scopesel { margin-left: .3rem; padding: .35rem .5rem; font-size: .85rem;
               border: 1px solid var(--sc-line); border-radius: 6px; background: #fff;
               color: var(--sc-text); }
.sc-state { margin-left: auto; font-size: .85rem; color: var(--sc-muted); }
.sc-state.sc-dirty { color: var(--sc-amber); font-weight: 700; }
.sc-state.sc-new   { color: var(--sc-green); font-weight: 700; }

/* ----- SKU type-ahead dropdown ----- */
.sc-suggest {
  position: fixed;
  z-index: 200;
  background: #fff;
  border: 1px solid #999;
  border-radius: 4px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, .18);
  max-height: 260px;
  overflow-y: auto;
  font-size: .85rem;
}
.sc-suggest div { padding: .3rem .6rem; cursor: pointer; white-space: nowrap; }
.sc-suggest div b { color: var(--sc-blue); }
.sc-suggest div.active, .sc-suggest div:hover { background: var(--sc-accent); }
.sc-suggest .sc-sg-sku { font-family: "Consolas", "Courier New", monospace;
                         display: inline-block; min-width: 90px; }
.sc-suggest .sc-sg-no  { color: var(--sc-muted); font-style: italic; }

/* ----- buttons ----- */
.sc-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: .45rem 1.1rem;
  border: 1px solid #b4b4b4;
  /* house pill buttons */
  border-radius: 50px;
  background: #fff;
  color: var(--sc-text);
  font-size: .9rem;
  font-weight: 700;
  cursor: pointer;
}
/* ghost hover, same as the Sellbrite buttons */
.sc-btn:hover { border-color: var(--sc-blue); color: var(--sc-blue); }
.sc-btn:disabled { opacity: .45; cursor: default; border-color: #b4b4b4; color: var(--sc-text); }
.sc-btn-primary { background: var(--sc-blue); border-color: var(--sc-blue); color: #fff; }
.sc-btn-primary:hover { background: var(--sc-blue-hv); border-color: var(--sc-blue-hv); color: #fff; }
.sc-btn-green { background: var(--sc-green); border-color: var(--sc-green); color: #fff; }
.sc-btn-green:hover { background: var(--sc-green-hv); border-color: var(--sc-green-hv); color: #fff; }
.sc-btn-danger { color: var(--sc-red); border-color: #e2b6b0; }
.sc-btn-danger:hover { background: var(--sc-red); border-color: var(--sc-red); color: #fff; }

/* ----- cards ----- */
.sc-card {
  background: #fff;
  border: 1px solid var(--sc-line);
  border-radius: 8px;
  margin: 0 1.25rem 1rem;
  overflow: hidden;
}
.sc-card-head {
  display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
  padding: .55rem .9rem;
  background: var(--sc-accent);
  border-bottom: 1px solid var(--sc-line);
  font-weight: 700; font-size: .92rem;
}
.sc-card-head .sc-sub { font-weight: 400; color: var(--sc-muted); font-size: .82rem; }
.sc-card-body { padding: .9rem; }

/* ----- item bar ----- */
.sc-itembar { display: flex; gap: 1.25rem; align-items: flex-end; flex-wrap: wrap; }
.sc-field { display: flex; flex-direction: column; gap: .25rem; }
.sc-field > span { font-size: .78rem; color: var(--sc-muted); text-transform: uppercase;
                   letter-spacing: .04em; }
.sc-field input {
  padding: .45rem .6rem; border: 1px solid var(--sc-line); border-radius: 6px;
  font-size: .92rem; background: #fff;
}
.sc-field input[readonly] { background: #f3f5f4; color: var(--sc-muted); }
.sc-desc  { width: 340px; }
.sc-srk   { width: 170px; font-family: "Consolas", "Courier New", monospace;
            text-transform: uppercase; }
.sc-skuro { width: 130px; font-family: "Consolas", "Courier New", monospace; }

/* ----- the two printed sides ----- */
.sc-sides { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-start; }
.sc-side { flex: 1 1 430px; min-width: 400px; }
.sc-side h3 {
  margin: 0 0 .35rem 0; font-size: .85rem; color: var(--sc-muted);
  text-transform: uppercase; letter-spacing: .05em;
}
.sc-side h3 span { text-transform: none; letter-spacing: 0; font-weight: 400; }

/* the ruler replaces the Access "1234567890" text boxes: a real 50 column
   scale. It only means anything if its glyphs sit exactly over the ones in
   the boxes, so the font, size and letter spacing match .sc-ltxt exactly and
   the left margin is the line number column plus the gap plus the input's own
   border and padding */
.sc-ruler {
  font-family: "Consolas", "Courier New", monospace;
  font-size: .84rem; color: #b3bdb8; white-space: pre;
  margin-left: calc(2rem + .35rem + 1px + .45rem);
  letter-spacing: 0; line-height: 1.2;
  user-select: none;
}
.sc-lines { display: flex; flex-direction: column; gap: 2px; }
.sc-lrow  { display: flex; align-items: center; gap: .35rem; }
.sc-lno {
  width: 2rem; text-align: right; font-size: .74rem; color: #9aa5a0;
  font-variant-numeric: tabular-nums; user-select: none;
}
.sc-ltxt {
  flex: 1;
  padding: .3rem .45rem;
  border: 1px solid var(--sc-line);
  border-radius: 4px;
  font-family: "Consolas", "Courier New", monospace;
  font-size: .84rem;
  letter-spacing: 0;
}
.sc-ltxt:focus { outline: 2px solid var(--sc-blue); outline-offset: -1px; border-color: var(--sc-blue); }
.sc-lrow.sc-filled .sc-lno { color: var(--sc-text); font-weight: 700; }
.sc-lcnt {
  width: 2.2rem; text-align: right; font-size: .7rem; color: #b3bdb8;
  font-variant-numeric: tabular-nums; user-select: none;
}
.sc-lcnt.sc-full { color: var(--sc-amber); font-weight: 700; }

/* a line the file carries that no printed side owns */
.sc-stray { margin-top: .6rem; padding: .5rem .7rem; border-radius: 6px;
            background: #fdf4e3; border: 1px solid #e8d6ac; font-size: .82rem;
            color: var(--sc-amber); }
.sc-stray code { font-family: "Consolas", "Courier New", monospace; }

/* ----- footer strip ----- */
.sc-footlines {
  font-family: "Consolas", "Courier New", monospace; font-size: .84rem;
  white-space: pre-wrap; color: var(--sc-text); margin: 0;
}
.sc-footempty { color: var(--sc-muted); font-style: italic; font-size: .85rem; }

/* ----- printed preview ----- */
.sc-preview { display: flex; gap: 1rem; flex-wrap: wrap; }
.sc-pv {
  flex: 1 1 320px; min-width: 300px;
  background: var(--sc-card);
  border: 1px solid #ddd3b8;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,.08);
  padding: .8rem 1rem;
  font-family: Georgia, "Times New Roman", serif;
  font-size: .84rem;
  line-height: 1.45;
  min-height: 150px;
}
.sc-pv h4 { margin: 0 0 .4rem 0; font-size: .68rem; letter-spacing: .09em;
            text-transform: uppercase; color: #a08b52; font-family: "Segoe UI", sans-serif; }
.sc-pv p  { margin: 0; white-space: pre-wrap; }
.sc-pv .sc-pv-foot { margin-top: .7rem; padding-top: .5rem; border-top: 1px solid #e2d7bb;
                     color: #6b6353; font-size: .78rem; white-space: pre-wrap; }
.sc-pv-blank { color: #b6ab8e; font-style: italic; }

/* ----- modal ----- */
.sc-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  display: flex; align-items: flex-start; justify-content: center;
  overflow-y: auto; z-index: 150; padding: 2rem 1rem;
}
.sc-overlay[hidden] { display: none; }
.sc-modal { background: #fff; border-radius: 8px; width: 100%; max-width: 640px;
            box-shadow: 0 12px 40px rgba(0,0,0,.3); }
.sc-modal-head { display: flex; align-items: center; justify-content: space-between;
                 padding: .8rem 1.1rem; border-bottom: 1px solid var(--sc-line); }
.sc-modal-head h2 { margin: 0; font-size: 1.02rem; }
.sc-x { border: 0; background: none; font-size: 1.4rem; line-height: 1;
        cursor: pointer; color: var(--sc-muted); }
.sc-modal-body { padding: 1rem 1.1rem; }
.sc-modal-foot { display: flex; justify-content: flex-end; gap: .6rem;
                 padding: .8rem 1.1rem; border-top: 1px solid var(--sc-line); }
.sc-note { color: var(--sc-muted); font-size: .82rem; margin: 0 0 .7rem 0; }
</style>

<div class="sc-app">

  <header class="sc-topbar">
    <h1>Story Card Maintenance</h1>
    <div class="sc-topbar-right">
      <span id="scUser"><?php echo htmlspecialchars($user); ?></span>
      <span id="scClock"></span>
    </div>
  </header>

  <div class="sc-toolbar">
    <span class="sc-skubox">
      <input type="text" id="txtSku" class="sc-sku" placeholder="SKU"
             maxlength="10" autocomplete="off" spellcheck="false">
      <span class="sc-skudd" id="btnSkuDrop" title="Show the list">&#9662;</span>
    </span>
    <label class="sc-scope">Look in
      <select id="selScope" class="sc-scopesel">
        <option value="CARDS" selected>Existing story cards</option>
        <option value="ITEMS">All items</option>
      </select>
    </label>
    <button type="button" class="sc-btn sc-btn-primary" id="btnSave" disabled>Save Card</button>
    <button type="button" class="sc-btn" id="btnRevert" disabled>Revert</button>
    <button type="button" class="sc-btn" id="btnFooter">Footer Text&hellip;</button>
    <button type="button" class="sc-btn sc-btn-danger" id="btnDelete" disabled>Delete Card</button>
    <span class="sc-state" id="lblState">Pick a SKU to begin.</span>
  </div>

  <!-- ============ the item this card belongs to ============ -->
  <div class="sc-card">
    <div class="sc-card-head">
      Item
      <span class="sc-sub" id="lblCardState"></span>
    </div>
    <div class="sc-card-body">
      <div class="sc-itembar">
        <label class="sc-field"><span>SKU</span>
          <input type="text" id="outSku" class="sc-skuro" readonly tabindex="-1">
        </label>
        <label class="sc-field"><span>Description</span>
          <input type="text" id="outDesc" class="sc-desc" readonly tabindex="-1">
        </label>
        <label class="sc-field"><span>Search text</span>
          <input type="text" id="txtSrk" class="sc-srk" maxlength="15"
                 autocomplete="off" spellcheck="false" disabled>
        </label>
      </div>
    </div>
  </div>

  <!-- ============ the two printed sides ============ -->
  <div class="sc-card">
    <div class="sc-card-head">
      Card text
      <span class="sc-sub">50 characters a line &middot; side 1 holds 12 lines, side 2 holds 10</span>
    </div>
    <div class="sc-card-body">
      <div class="sc-sides">
        <div class="sc-side">
          <h3>Side 1 <span>lines 1&ndash;12</span></h3>
          <div class="sc-ruler" id="ruler1"></div>
          <div class="sc-lines" id="side1Lines"></div>
        </div>
        <div class="sc-side">
          <h3>Side 2 <span>lines 13&ndash;22</span></h3>
          <div class="sc-ruler" id="ruler2"></div>
          <div class="sc-lines" id="side2Lines"></div>
        </div>
      </div>
      <div class="sc-stray" id="boxStray" hidden></div>
    </div>
  </div>

  <!-- ============ the shared footer, read only here ============ -->
  <div class="sc-card">
    <div class="sc-card-head">
      Footer
      <span class="sc-sub">shared by every story card &middot; edit it with the Footer Text button</span>
    </div>
    <div class="sc-card-body">
      <pre class="sc-footlines" id="outFooter"></pre>
    </div>
  </div>

  <!-- ============ how the card reads when it prints ============ -->
  <div class="sc-card">
    <div class="sc-card-head">Preview</div>
    <div class="sc-card-body">
      <div class="sc-preview">
        <div class="sc-pv">
          <h4>Side 1</h4>
          <p id="pv1"></p>
        </div>
        <div class="sc-pv">
          <h4>Side 2</h4>
          <p id="pv2"></p>
          <div class="sc-pv-foot" id="pvFoot"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ footer editor (replaces the FootMaintenance form) ============ -->
  <div class="sc-overlay" id="mdlFooter" hidden>
    <div class="sc-modal">
      <div class="sc-modal-head">
        <h2>Footer Text</h2>
        <button type="button" class="sc-x" data-close="mdlFooter">&times;</button>
      </div>
      <div class="sc-modal-body">
        <p class="sc-note">
          These lines print at the bottom of every story card, so a change here
          changes every card. 50 characters a line. Empty lines at the end are
          dropped when it saves.
        </p>
        <div class="sc-ruler" id="rulerF"></div>
        <div class="sc-lines" id="footLines"></div>
        <button type="button" class="sc-btn" id="btnFootAdd"
                style="margin-top:.6rem">+ Add line</button>
      </div>
      <div class="sc-modal-foot">
        <button type="button" class="sc-btn" data-close="mdlFooter">Cancel</button>
        <button type="button" class="sc-btn sc-btn-primary" id="btnFootSave">Save Footer</button>
      </div>
    </div>
  </div>

</div>

<script>
// Story Card Maintenance frontend logic (SKU picker, both sides, footer editor)
var STC_PRELOAD = <?php echo $stcPreload ? json_encode($stcPreload) : 'null'; ?>;
// STC_MODE 'footer' opens straight on the footer editor, the old FootMaintenance form
var STC_MODE = '<?php echo $mode; ?>';

// the printed card. These four numbers are the whole layout, and they match
// STYCRD004S and StoryCard_model.php - change them in all three or nowhere
var S1_FIRST = 1, S1_LAST = 12;
var S2_FIRST = 13, S2_LAST = 22;
var LINE_LEN = 50;

// the card as it came back from the server, so Revert has something to go to
var loadedCard = null;
// the footer as loaded, both for the read only strip and for the editor
var footerLines = [];
var suggestTimer = null;


$(document).ready(function () {
    buildRuler('#ruler1');
    buildRuler('#ruler2');
    buildRuler('#rulerF');
    buildSide('#side1Lines', S1_FIRST, S1_LAST);
    buildSide('#side2Lines', S2_FIRST, S2_LAST);
    setCard(null);

    tickClock();
    setInterval(tickClock, 1000);

    // the controller hands the page its footer, and the card too when the URL
    // named a SKU, so nothing has to be fetched before the screen is usable
    if (STC_PRELOAD) {
        footerLines = STC_PRELOAD.footer || [];
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
    $('#btnRevert').on('click', function () {
        if (!loadedCard) { return; }
        setCard(loadedCard);
    });
    $('#btnDelete').on('click', deleteCard);
    $('#btnFooter').on('click', openFooterEditor);
    $('#btnFootAdd').on('click', function () {
        addFooterRow('');
        $('#footLines .sc-ltxt').last().trigger('focus');
    });
    $('#btnFootSave').on('click', saveFooter);

    $('[data-close]').on('click', function () {
        $('#' + $(this).data('close')).prop('hidden', true);
    });

    // ----- the SKU picker -----
    $('#txtSku').on('input', function () {
        var inp = $(this);
        clearTimeout(suggestTimer);
        suggestTimer = setTimeout(function () { runSkuSearch(inp); }, 250);
    });
    $('#txtSku').on('focus', function () { runSkuSearch($(this)); });
    $('#txtSku').on('blur', function () {
        // wait so a click on the list lands before blur hides it
        setTimeout(hideSuggest, 150);
    });
    // the arrow toggles the list (mousedown so the input keeps focus)
    $('#btnSkuDrop').on('mousedown', function (e) {
        e.preventDefault();
        if ($('#scSuggest').length) { hideSuggest(); }
        else { $('#txtSku').trigger('focus'); }
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
    // scope change re-runs whatever is in the box
    $('#selScope').on('change', function () { runSkuSearch($('#txtSku')); });

    // ----- the line boxes -----
    $('#side1Lines, #side2Lines').on('input', '.sc-ltxt', function () {
        refreshRow($(this));
        markDirty();
        renderPreview();
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

// the 50 column scale that sits over each side, replacing the Access ruler
// text boxes that were filled with 1234567890 in Form_Load
function buildRuler(sel) {
    var s = '';
    for (var c = 1; c <= LINE_LEN; c++) {
        s += (c % 10 === 0) ? String(c / 10 % 10)
           : (c % 5 === 0)  ? '+' : '.';
    }
    $(sel).text(s);
}


function buildSide(sel, first, last) {
    var box = $(sel).empty();
    for (var n = first; n <= last; n++) {
        box.append(lineRow(n, ''));
    }
}


function lineRow(no, text) {
    return $('<div class="sc-lrow">')
        .append($('<span class="sc-lno">').text(no))
        .append($('<input type="text" class="sc-ltxt" spellcheck="false">')
                    .attr('maxlength', LINE_LEN)
                    .attr('data-line', no)
                    .val(text))
        .append($('<span class="sc-lcnt">'));
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
    $('#btnRevert').prop('disabled', true);
    $('#btnDelete').prop('disabled', !card || card.isNew);

    // a line the file carries that no printed side owns. The old screen simply
    // did not read it, so it stayed in the file unseen
    var stray = (card && card.stray) || [];
    if (stray.length) {
        var parts = stray.map(function (s) {
            return 'line ' + s.line + ' "' + s.text + '"';
        });
        var one = (stray.length === 1);
        $('#boxStray').prop('hidden', false).html(
            'This card carries ' + stray.length + (one ? ' line' : ' lines') +
            ' outside the printed sides, ' + (one ? 'left' : 'all left') +
            ' untouched by this screen: <code>' + escHtml(parts.join(', ')) + '</code>');
    } else {
        $('#boxStray').prop('hidden', true).empty();
    }

    if (!card) {
        $('#lblCardState').text('');
        $('#lblState').removeClass('sc-dirty sc-new').text('Pick a SKU to begin.');
    } else if (card.isNew) {
        $('#lblCardState').text('no story card yet - saving will create one');
        $('#lblState').removeClass('sc-dirty').addClass('sc-new').text('New card');
    } else {
        $('#lblCardState').text('');
        $('#lblState').removeClass('sc-dirty sc-new').text('Loaded');
    }

    $('#side1Lines .sc-ltxt, #side2Lines .sc-ltxt').each(function () { refreshRow($(this)); });
    renderPreview();
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
    var dirty = isDirty();
    $('#btnRevert').prop('disabled', !dirty);
    if (!loadedCard) { return; }
    if (dirty) {
        $('#lblState').removeClass('sc-new').addClass('sc-dirty').text('Unsaved changes');
    } else if (loadedCard.isNew) {
        $('#lblState').removeClass('sc-dirty').addClass('sc-new').text('New card');
    } else {
        $('#lblState').removeClass('sc-dirty sc-new').text('Loaded');
    }
}


function refreshRow(inp) {
    var len = inp.val().length;
    var row = inp.closest('.sc-lrow');
    row.toggleClass('sc-filled', inp.val().trim() !== '');
    row.find('.sc-lcnt').text(len ? len : '')
                        .toggleClass('sc-full', len >= LINE_LEN);
}


function loadCard(sku) {
    sku = String(sku || '').trim().toUpperCase();
    if (sku === '') { return; }

    if (!isDirty()) { fetchCard(sku); return; }

    ask('Unsaved changes',
        'The card on screen has changes that have not been saved. Load ' + sku +
        ' and lose them?',
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

    // an empty card is almost always a mistake, so it takes a confirmation
    var any = payload.side1.concat(payload.side2).some(function (t) { return t.trim() !== ''; });
    if (!any) {
        ask('Nothing on the card',
            'Every line is empty. Saving clears the whole story card for ' +
            payload.sku + '.',
            'Save it empty', 'Go back',
            function () { doSaveCard(payload); });
        return;
    }

    doSaveCard(payload);
}


function doSaveCard(payload) {
    $('#btnSave').prop('disabled', true);
    postAjax({ action: 'savecard', payload: JSON.stringify(payload) }, function (resp) {
        $('#btnSave').prop('disabled', false);
        swal('Saved',
             'Story card ' + resp.sku + ' ' + (resp.isNew ? 'created' : 'updated') +
             ' with ' + resp.lines + ' line' + (resp.lines === 1 ? '' : 's') + '.',
             'success');
        // reload rather than trust the screen: what the file holds is the truth
        postAjax({ action: 'card', sku: resp.sku }, function (r2) { setCard(r2.card); });
    }, function () {
        $('#btnSave').prop('disabled', false);
    });
}


function deleteCard() {
    if (!loadedCard || loadedCard.isNew) { return; }
    var sku = loadedCard.sku;

    ask('Delete story card ' + sku + '?',
        'Every line of the card goes, and there is no undo.',
        'Delete it', 'Keep it',
        function () {
            postAjax({ action: 'deletecard', sku: sku }, function () {
                swal('Deleted', 'Story card ' + sku + ' has been removed.', 'success');
                postAjax({ action: 'card', sku: sku }, function (r2) { setCard(r2.card); });
            });
        });
}


// ---------------------------------------------------------------- the footer

function loadFooter() {
    postAjax({ action: 'footer' }, function (resp) {
        footerLines = resp.footer || [];
        renderFooter();
    });
}


function renderFooter() {
    if (!footerLines.length) {
        $('#outFooter').html('<span class="sc-footempty">No footer text on file.</span>');
    } else {
        $('#outFooter').text(footerLines.join('\n'));
    }
    renderPreview();
}


function openFooterEditor() {
    var box = $('#footLines').empty();
    var lines = footerLines.slice();
    // always leave one empty line to type into
    if (!lines.length || rtrim(lines[lines.length - 1]) !== '') { lines.push(''); }
    $.each(lines, function (i, t) { addFooterRow(t); });
    $('#mdlFooter').prop('hidden', false);
    $('#footLines .sc-ltxt').first().trigger('focus');
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
    postAjax({ action: 'savefooter', payload: JSON.stringify({ footer: lines }) },
        function (resp) {
            $('#btnFootSave').prop('disabled', false);
            $('#mdlFooter').prop('hidden', true);
            swal('Saved',
                 'The footer now has ' + resp.lines + ' line' +
                 (resp.lines === 1 ? '' : 's') + ' and prints on every story card.',
                 'success');
            loadFooter();
        },
        function () { $('#btnFootSave').prop('disabled', false); });
}


// ---------------------------------------------------------------- preview

function renderPreview() {
    setPreview('#pv1', collectSide('#side1Lines'));
    setPreview('#pv2', collectSide('#side2Lines'));

    var foot = footerLines.filter(function (t) { return rtrim(t) !== ''; });
    $('#pvFoot').text(foot.join('\n'));
}


function setPreview(sel, lines) {
    var text = lines.map(rtrim).join('\n').replace(/\n+$/, '');
    if (text === '') {
        $(sel).html('<span class="sc-pv-blank">(blank)</span>');
    } else {
        $(sel).text(text);
    }
}


// ---------------------------------------------------------------- SKU list

function runSkuSearch(inp) {
    postAjax({ action: 'skusearch',
               type: $('#selScope').val(),
               q: inp.val().trim() },
        function (resp) { showSuggest(inp, resp.rows, inp.val().trim()); },
        null, true);
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
        var tail = r.SCCARD === 'Y' ? '' : ' <span class="sc-sg-no">no card yet</span>';
        $('<div>').attr('data-sku', sku)
                  .html('<span class="sc-sg-sku">' + shown + '</span>' +
                        escHtml(rtrim(r.SCDESC)) + tail)
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
    renderPreview();

    if (dropped > 0) {
        swal('Too much text',
             dropped + ' line' + (dropped === 1 ? '' : 's') +
             ' would not fit and were not pasted. This side holds ' +
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
