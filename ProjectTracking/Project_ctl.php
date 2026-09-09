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

    include "ProjectTracking_dsp.php";
    include "Project_dsp.php";
    dspProject($projNum);
?>

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
