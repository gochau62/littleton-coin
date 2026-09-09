<?php
/*    ***************************************************  -->
<!--  * Program Name - Project_ctl.php                   *  -->
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
?>

<?php
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<!-- includes css and javascript libraries -->
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type="text/javascript">

    document.title = "Project Tracking";

    // show the red error box with a message
    function showErrorMessage(m){ var d = document.getElementById("errorMsg"); d.innerHTML = m; d.style.display = "block"; }


    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// record where the person was headed so sign-on can send them back
if ($user === '') { $_SESSION['return_after_logon'] = $_SERVER['REQUEST_URI'] ?? ''; }

// authority level 20, the developers group
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 20);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/ProjectTracking_model.php';

    $projNum = intval($_GET['projnum'] ?? $_GET['num'] ?? 0);

    // shared styles, header and lookup; the page itself follows
    include "ProjectTracking_dsp.php";
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

<script>
// the project number this screen is showing
var scrNum = <?php echo intval($projNum); ?>;
// the record as it came back, and the edits sitting on top of it
var scrData = null;
var scrEdits = {};

// the General tab's editable fields, in the order they read
var scrGeneral = [
    { key: 'name',    label: 'Project name', wide: true, max: 50,
      hint: 'Descriptive, clear, 50 chars max' },
    { key: 'desc',    label: 'Description', wide: true, ro: true, area: true,
      hint: 'Edit to add a description...' },
    { key: 'rqst',    label: 'Requestor', list: 'rqst' },
    { key: 'sponsor', label: 'Sponsor', list: 'sponsor' },
    { key: 'sub',     label: 'Created date', ro: true },
    { key: 'dept',    label: 'Requesting dept', list: 'dept' }
];

$(document).ready(function () {
    if (scrNum <= 0) {
        paneError('No project number. Open this screen as Project_ctl.php?projnum=260084');
        return;
    }
    loadProject();

    $('#scrTabs').on('click', '.pt-tab', function () {
        $('#scrTabs .pt-tab').removeClass('pt-on');
        $(this).addClass('pt-on');
        var pane = $(this).data('pane');
        $('.pt-pane').each(function () {
            this.hidden = (this.id !== 'pane-' + pane);
        });
    });

    // the header lookup reaches any project from here
    ptLookup({});

    $('#btnSave').on('click', saveProject);
    $('#btnDiscard').on('click', function () {
        scrEdits = {};
        renderAll();
        markClean();
    });
});


// HTML escape for element text
function esc(s) {
    return $('<span>').text(s == null ? '' : String(s)).html();
}


// esc for attribute values, quotes escaped too
function attr(s) {
    return esc(s).replace(/"/g, '&quot;');
}


function paneError(msg) {
    $('#scrErr').text(msg).prop('hidden', false);
}


function loadProject() {
    $.post('ProjectTracking_ajax.php', { action: 'project', num: scrNum },
        function (resp) {
            if (!resp || !resp.ok) {
                paneError((resp && resp.msg) ? resp.msg : 'Request failed.');
                return;
            }
            scrData = resp;
            scrEdits = {};
            $('#ptUpdated').text('updated ' + resp.updated);
            renderHead();
            renderAll();
            markClean();
        }, 'json').fail(function () {
            paneError('Server error - see the log.');
        });
}


// the number, and the stage it sits at, at the top of the card
function renderHead() {
    var p = scrData.proj;
    var stage = (scrData.stages && scrData.stages[p.stage]) || p.stage;
    $('#scrNum').html(esc(p.num) + ' <span class="pt-chip pt-chip-' +
                      esc(p.stage) + '">' + esc(stage) + '</span>');
}


// the current value of a field: the edit if there is one, else the record
function val(key) {
    return (key in scrEdits) ? scrEdits[key] : (scrData.proj[key] || '');
}


// one labelled field, as an input, a dropdown or plain text
function field(f) {
    var v = val(f.key);
    var html = '<div class="pt-fld' + (f.wide ? ' pt-fld-wide' : '') + '">' +
               '<label for="fld_' + esc(f.key) + '">' + esc(f.label) + '</label>';

    if (f.ro && f.area) {
        // the long write-up lives with the project's comments
        html += '<textarea readonly placeholder="' + attr(f.hint || '') + '">' +
                esc(v) + '</textarea>';
    } else if (f.ro) {
        html += '<div class="pt-ro">' + esc(v) + '</div>';
    } else if (f.list) {
        var opts = scrData.lists[f.list] || [];
        html += '<select id="fld_' + esc(f.key) + '" data-key="' + esc(f.key) + '">';
        // a value the list does not carry still shows, so nothing is lost
        var seen = false;
        html += '<option value=""></option>';
        if (Array.isArray(opts)) {
            $.each(opts, function (i, o) {
                if (o === v) { seen = true; }
                html += '<option value="' + attr(o) + '"' +
                        (o === v ? ' selected' : '') + '>' + esc(o) + '</option>';
            });
        } else {
            $.each(opts, function (code, label) {
                if (code === v) { seen = true; }
                html += '<option value="' + attr(code) + '"' +
                        (code === v ? ' selected' : '') + '>' + esc(label) + '</option>';
            });
        }
        if (!seen && v !== '') {
            html += '<option value="' + attr(v) + '" selected>' + esc(v) + '</option>';
        }
        html += '</select>';
    } else {
        html += '<input type="text" id="fld_' + esc(f.key) + '" data-key="' + esc(f.key) +
                '" value="' + attr(v) + '"' +
                (f.max ? ' maxlength="' + f.max + '"' : '') +
                (f.hint ? ' placeholder="' + attr(f.hint) + '"' : '') + '>';
    }
    return html + '</div>';
}


// a read-only label and value, for the tabs that are not editable yet
function ro(label, v) {
    return '<div class="pt-fld"><label>' + esc(label) + '</label>' +
           '<div class="pt-ro">' + esc(v) + '</div></div>';
}


function renderAll() {
    var p = scrData.proj;

    var g = '<div class="pt-row">';
    $.each(scrGeneral, function (i, f) { g += field(f); });
    g += '</div>';
    $('#pane-general').html(g);

    // the wording the file carries, not the stored code
    var status = p.wrklabel || p.wrksts;

    $('#pane-it').html('<p class="pt-note">Read-only for now - edit these on ' +
        'the legacy screen until this tab is wired up.</p><div class="pt-row">' +
        ro('Programmer', p.pgmr) + ro('Work status', status) +
        ro('Estimator', p.estmtr) + ro('Development group', p.devgrp) +
        ro('Project type', p.type) + ro('Plan type', p.plan) +
        ro('Scheduled start', p.start) + ro('Est. completion', p.ecom) +
        ro('Actual completion', p.acom) + ro('Implemented', p.impl) +
        '</div>');

    $('#pane-payback').html('<p class="pt-note">Read-only for now - edit these on ' +
        'the legacy screen until this tab is wired up.</p><div class="pt-row">' +
        ro('Payback type', p.paybktyp) + ro('User accepted', p.usracpt) +
        '</div>');

    $('#pane-sc').html('<p class="pt-note">Read-only for now - edit these on ' +
        'the legacy screen until this tab is wired up.</p><div class="pt-row">' +
        ro('Resolution code', p.rescod) + ro('SC stage',
            (scrData.stages && scrData.stages[p.stage]) || p.stage) +
        ro('Dept priority', p.deptpr) + ro('SC priority', p.scpr) +
        ro('Sponsor approved', p.spapv) + ro('SC reviewed', p.screv) +
        ro('Forced to SC', p.force2sc) + ro('Need date', p.need) +
        '</div>');

    // every editable field reports its own changes
    $('#pane-general').off('input change').on('input change', '[data-key]', function () {
        var k = $(this).data('key');
        scrEdits[k] = $(this).val();
        if (String(scrEdits[k]) === String(scrData.proj[k] || '')) { delete scrEdits[k]; }
        markDirty();
    });
}


function markDirty() {
    var dirty = false;
    $.each(scrEdits, function () { dirty = true; return false; });
    $('#btnSave').prop('disabled', !dirty);
    $('#btnDiscard').prop('disabled', !dirty);
    $('#scrSaved').prop('hidden', true);
}


function markClean() {
    $('#btnSave').prop('disabled', true);
    $('#btnDiscard').prop('disabled', true);
}


function saveProject() {
    var data = { action: 'projectsave', num: scrNum };
    $.each(scrEdits, function (k, v) { data[k] = v; });

    $('#btnSave').prop('disabled', true).text('Saving...');
    $('#scrErr').prop('hidden', true);

    $.post('ProjectTracking_ajax.php', data, function (resp) {
        $('#btnSave').text('Save project');
        if (!resp || !resp.ok) {
            paneError((resp && resp.msg) ? resp.msg : 'The save failed.');
            markDirty();
            return;
        }
        // the screen redraws from what the file now holds
        if (resp.proj) { scrData.proj = resp.proj; }
        scrEdits = {};
        renderHead();
        renderAll();
        markClean();
        $('#scrSaved').prop('hidden', false);
    }, 'json').fail(function () {
        $('#btnSave').text('Save project');
        paneError('Server error - the save may not have gone through. Refresh before trying again.');
    });
}
</script>

<!--  End Content Here -->
<?php
// end authority check
}

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
