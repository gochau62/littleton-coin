<?php
/*    ***************************************************  -->
<!--  * Program Name - StoryCard_ctl.php                 *  -->
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
?>

<?php
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Story Card Maintenance";

// Story Card Maintenance frontend logic (SKU picker, both sides, footer editor)
// the card as it came back from the server, so Revert has something to go to
var loadedCard = null;
// the footer as loaded, for the strip and for the editor, plus the keys there are to choose from and which one is on screen. Key 1 is the default,
// the one the Access body form always read, and the strip only fills once a SKU is picked - LoadText read the footer right after the item description
var footerLines = [];
var footerKeys = [];
var footerSky = 1;
// the editor works on its own copy of a footer, so the strip on the page only changes when a save lands or its own selector is used
var edLines = [];
var edSky = 1;
var suggestTimer = null;
var suggestSeq = 0;

$(document).ready(function () {
    // this script is on the page whether or not the screen is, so when a profile is turned away there is nothing here to wire up
    if (typeof S1_FIRST === 'undefined') { return; }

    buildSide('#side1Lines', S1_FIRST, S1_LAST);
    buildSide('#side2Lines', S2_FIRST, S2_LAST);
    setCard(null);

    tickClock();
    setInterval(tickClock, 1000);

    // the controller hands the page its footer, and the card too when the URL named a SKU, so nothing has to be fetched before the screen is usable
    if (STC_PRELOAD) {
        footerLines = STC_PRELOAD.footer || [];
        footerKeys  = STC_PRELOAD.keys || [];
        footerSky   = STC_PRELOAD.sky || 1;
        renderFooter();
        if (STC_PRELOAD.card) {
            $('#txtSku').val(STC_PRELOAD.card.sku);
            setCard(STC_PRELOAD.card);
        }
    } else { loadFooter(); }

    if (STC_MODE === 'footer') { openFooterEditor(); }

    $('#btnSave').on('click', saveCard);
    $('#btnFooter').on('click', openFooterEditor);
    $('#btnFootAdd').on('click', function () {
        if ($('#footLines .sc-lrow').length >= FOOT_MAX) { return; }
        addFooterRow('');
        $('#btnFootAdd').prop('hidden', $('#footLines .sc-lrow').length >= FOOT_MAX);
        $('#footLines .sc-ltxt').last().trigger('focus');
    });
    $('#btnFootSave').on('click', saveFooter);
    // the page's selector swaps which saved footer the strip shows
    $('#selFootKey').on('change', function () { loadFooter($(this).val()); });
    // the editor's key box works on its own copy, so nothing on the page moves until Save. It also ADDS a key: type one with nothing on file and the footer
    // opens empty in insert mode, the same way typing a new SKU starts a new card. Its list opens the same way the SKU box does: focus, a click while
    // focused, or the arrow
    $('#mdlFootKey').on('focus click', function () {
        if (!$('#scKeySuggest').length) { showKeySuggest(); }
    });
    $('#mdlFootKey').on('input', hideKeySuggest);
    $('#mdlFootKey').on('blur', function () {
        // wait so a click on the list lands before blur hides it
        setTimeout(hideKeySuggest, 150);
    });
    $('#btnFootKeyDrop').on('mousedown', function (e) {
        e.preventDefault();
        if ($('#scKeySuggest').length) { hideKeySuggest(); return; }
        if (document.activeElement !== $('#mdlFootKey')[0]) { $('#mdlFootKey').trigger('focus'); }
        else { showKeySuggest(); }
    });
    $('#mdlFootKey').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); hideKeySuggest(); $(this).trigger('change'); }
        else if (e.key === 'Escape') { hideKeySuggest(); }
    });
    $('#mdlFootKey').on('change', function () {
        var k = parseInt($(this).val(), 10);
        if (!(k >= 1)) { $(this).val(edSky); return; }
        // the same key again is not a change - reloading would re-render the rows and wipe anything already typed into them
        if (k === edSky) { $(this).val(edSky); return; }
        loadEditorFooter(k);
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
    // focus opens the list, and so does a click once the box already has focus: a second click on an already focused input fires no focus event, which is why
    // clicking it again used to do nothing. Opening always shows the WHOLE list, positioned at the current value - the Access combo only filtered while you
    // typed, never on open
    $('#txtSku').on('focus click', function () {
        if (!$('#scSuggest').length) { openSkuList(); }
    });
    $('#txtSku').on('blur', function () {
        // wait so a click on the list lands before blur hides it
        setTimeout(hideSuggest, 150);
    });
    // the arrow toggles it (mousedown so the input keeps focus). Focusing an unfocused box opens the list through the focus handler; a box that is already
    // focused fires no event, so open it directly
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
        } else if (e.key === 'Escape') { hideSuggest(); }
    });

    // ----- the line boxes -----
    $('#side1Lines, #side2Lines').on('input', '.sc-ltxt', function () {
        refreshRow($(this));
        markDirty();
    });
    $('#txtSrk').on('input', markDirty);

    // Enter and the arrows walk the lines, the way tabbing through the old text box felt, without leaving the field on Enter
    $('#side1Lines, #side2Lines, #footLines').on('keydown', '.sc-ltxt', function (e) {
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault();
            stepLine($(this), 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            stepLine($(this), -1);
        }
    });

    // pasting a block of text spreads it down the lines instead of stuffing it all into one box and losing everything past character fifty
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
        if (e.key === 'Escape') { $('.sc-overlay').not('[hidden]').last().prop('hidden', true); }
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

    // the footer strip follows the card, the way LoadText filled Text13 right
    // after the item description
    renderFooter();
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

// the line number goes bold once the line carries text, which is the only state a line has. The box is exactly the width of the file column, so there is
// nothing to count
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
    }, function () { $('#btnSave').prop('disabled', false); });
}

// ---------------------------------------------------------------- the footer

function loadFooter(sky) {
    postAjax({ action: 'footer', sky: sky || footerSky }, function (resp) {
        footerLines = resp.footer || [];
        footerKeys  = resp.keys || [];
        footerSky   = resp.sky;
        renderFooter();
    });
}

// the editor's own load - it never touches what the page is showing
function loadEditorFooter(sky) {
    postAjax({ action: 'footer', sky: sky }, function (resp) {
        edLines    = resp.footer || [];
        footerKeys = resp.keys || [];
        edSky      = resp.sky;
        renderFooterEditor();
    });
}

function renderFooter() {
    fillKeys('#selFootKey');

    // the same numbered boxes the sides use, read only, and empty until a SKU
    // is picked - the Access box sat blank until LoadText ran
    var shown = loadedCard ? footerLines.slice() : [];
    while (shown.length && rtrim(shown[shown.length - 1]) === '') { shown.pop(); }
    var box = $('#outFooter').empty();
    for (var i = 0; i < FOOT_MAX; i++) {
        var t = shown[i] || '';
        $('<div class="sc-lrow">').toggleClass('sc-filled', rtrim(t) !== '')
            .append($('<span class="sc-lno">').text(i + 1))
            .append($('<input type="text" class="sc-ltxt" readonly tabindex="-1">').val(t))
            .appendTo(box);
    }
}

// the FooterSelect list. It only ever offers the keys that query returns
function fillKeys(sel) {
    var box = $(sel).empty();
    var keys = footerKeys.length ? footerKeys : [footerSky];
    $.each(keys, function (i, k) { box.append($('<option>').val(k).text(k)); });
    box.val(String(footerSky));
}

function openFooterEditor() {
    // start from the key and rows the page is showing - saved state only
    edSky   = footerSky;
    edLines = footerLines.slice();
    renderFooterEditor();
    $('#mdlFooter').prop('hidden', false);
    $('#footLines .sc-ltxt').first().trigger('focus');
}

function renderFooterEditor() {
    $('#mdlFootKey').val(edSky);
    $('#footLines').empty();
    var lines = edLines.slice();

    // the Access rule per key: an empty footer takes new lines, up to the two the footer box holds, and a footer that has rows can only have those rows
    // rewritten, never grown. The editor offers exactly what the save can do
    var canGrow = lines.length === 0;
    if (canGrow) { lines.push(''); }
    $.each(lines, function (i, t) { addFooterRow(t); });
    $('#btnFootAdd').prop('hidden', !canGrow || lines.length >= FOOT_MAX);

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
               payload: JSON.stringify({ sky: edSky, footer: lines }) },
        function () {
            $('#btnFootSave').prop('disabled', false);
            $('#mdlFooter').prop('hidden', true);
            loadFooter();
        },
        function () { $('#btnFootSave').prop('disabled', false); });
}

// ---------------------------------------------------------------- SKU list

// the whole list, regardless of what the box holds
function openSkuList() { runSkuSearch($('#txtSku'), ''); }

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

// the list could not be fetched: say so in the box where the rows would be, instead of silently showing nothing
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

// the footer key list. The keys come back with every footer response, so a key saved a moment ago is already here to grab
function showKeySuggest() {
    hideKeySuggest();
    if (!footerKeys.length) { return; }
    var inp = $('#mdlFootKey');
    var box = $('<div class="sc-suggest" id="scKeySuggest">');
    $.each(footerKeys, function (i, k) {
        var d = $('<div>').attr('data-key', k).text(k);
        if (k === edSky) { d.addClass('active'); }
        box.append(d);
    });
    // mousedown, not click, so the pick lands before the input blurs
    box.on('mousedown', 'div', function (e) {
        e.preventDefault();
        var k = $(this).data('key');
        hideKeySuggest();
        $('#mdlFootKey').val(k).trigger('change');
    });
    var off = inp.offset();
    box.css({ left: off.left, top: off.top + inp.outerHeight() + 2,
              minWidth: Math.max(inp.outerWidth(), 110) });
    $('body').append(box);
}

function hideKeySuggest() { $('#scKeySuggest').remove(); }

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

// spread pasted text down the boxes: each newline starts a new line, and any line longer than fifty characters wraps on a word boundary rather than being
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

// a yes or no the user has to answer before anything happens, in the same swal box as every other message on the page
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
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// an unsigned visit is about to be refused or bounced to the sign on, so the address asked for is kept in the session first
// the sign on reads it back and lands the person here instead of on the home page, which is what makes a bookmark straight
// to this page work even when the sign on itself happens over on index
if ($user === '') { $_SESSION['return_after_logon'] = $_SERVER['REQUEST_URI'] ?? ''; }

// check users authority (10 is the minimum to use LCCOnline)
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 10);
}

if ($authorized != "yes") {
    if ($user === '') {
        // nobody is signed in, so this is a sign on matter rather than a refusal: hand them to the sign on screen,
        // and the address stashed above brings them straight back here the moment they sign in
        // the page is already part drawn by the framework blocks, so the browser is moved by script with the header
        // as a bonus when it can still be sent, and a plain link stands for anyone with scripting off
        if (!headers_sent()) { header('Location: index.php', true, 302); }
        echo '<script>window.location.replace("index.php");</script>' .
             '<p style="padding:1rem;"><a href="index.php">Sign in to LCC Online</a> to open this page.</p>';
    } else {
        // signed in but without the level this screen asks for: the standard refusal, drawn right here rather than
        // through the framework's call because that call renders on one instance and redirects on another
        echo '<div style="background:#eef0fa;padding:1.5rem 1.75rem;margin:.5rem 0;' .
             'color:#e01b24;font-style:italic;font-weight:bold;font-size:1.5rem;">' .
             'You are not authorized to view the page requested</div>';
    }
} else {

    require_once __DIR__ . '/StoryCard_model.php';

    // preload the card the URL asked for and the shared footer, so the page
    // arrives ready instead of opening empty and then fetching twice. The ajax
    // card and footer actions are the fallback
    $stcPreload = null;
    if (isset($authConn) && $authConn) {
        $sku      = stcCleanSku($_GET['sku'] ?? '');
        $footer   = stcGetFooter($authConn);
        $footKeys = stcFooterKeys($authConn);

        $card = null;
        if ($sku !== '') {
            $item = stcGetSku($authConn, $sku);
            if ($item !== false && $item !== null) {
                $rows = stcGetCard($authConn, $sku);
                if ($rows !== false) {
                    $card = stcCardToSides($rows);
                    $card['sku']  = rtrim($item['SCSKU']);
                    $card['desc'] = rtrim($item['SCDESC']);
                    $card['isNew'] = (count($rows) === 0);
                }
            }
        }

        if ($footer !== false) {
            $foot = array();
            foreach ($footer as $f) { $foot[] = rtrim($f['SCFTXT']); }
            // the key list failing must not cost the page its preload
            $keyList = array();
            if ($footKeys === false) {
                error_log('StoryCard footer keys unavailable (' . $GLOBALS['stcErr'] .
                          ') - is STYCRD001S built with the KEYS type?');
                $keyList[] = STC_FOOT_KEY;
            } else {
                foreach ($footKeys as $k) { $keyList[] = intval($k['SCFSKY']); }
            }
            $stcPreload = array("ok" => true, "sky" => STC_FOOT_KEY,
                                "footer" => $foot, "keys" => $keyList,
                                "card" => $card);
        }
    }

    // mode footer opens straight on the footer editor, the old FootMaintenance
    // form; the plain URL is the body screen the work actually happens on
    $stcMode = (($_GET['mode'] ?? '') === 'footer') ? 'footer' : '';

    stcActLog($user, 'OPEN', $stcMode === 'footer' ? 'footer editor' : 'card editor');

    include "StoryCard_dsp.php";
    dspStoryCard($user, $stcPreload, $stcMode);
?>
<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
