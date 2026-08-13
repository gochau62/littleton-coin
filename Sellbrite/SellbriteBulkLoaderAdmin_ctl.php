<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoaderAdmin_ctl.php *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 08/13/2026                         *  -->
<!--  ***************************************************  -->
<!--  * The Sellbrite Data screen: staff edit the       *  -->
<!--  * dropdown value lists, the per-category listing  *  -->
<!--  * copy, and which upload columns go to which      *  -->
<!--  * market - no code change, no steering committee. *  -->
<!--  * Project   - 260064                              *  -->
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

    document.title = "Sellbrite Data";

    /* ---- message helpers (LCC convention) ---- */
    function showErrorMessage(m){ $("#errorMsg").text(m).show(); }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
</script>

<!--  Begin Content Here -->
<div id="errorMsg" style="display:none; padding:1rem; color:#c0392b; font-weight:bold;"></div>

<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

// check users authority (10 is the minimum to use LCCOnline)
// nobody signed in: skip the check - chkAutUsr prints its raw error for an
// empty profile, and the shell's Sign In header is the login screen
$authorized = "yes";
if ($user === '') {
    $authorized = "signin";
} elseif (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 10);
}

if ($authorized === "signin") {
    echo '<script>showErrorMessage("Please sign in (top right) to use this tool.");</script>';
} elseif ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {
?>

<style>
#stdPage { background:#f8f8f8; padding:20px 28px 32px; font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif; color:#344054; position:relative; }
.sba-topbar { display:flex; align-items:center; background:#1C4532; color:#fff;
              padding:.6rem 1.25rem; margin:-20px -28px 18px; position:relative; }
.sba-topbar h1 { font-size:1.15rem; font-weight:600; color:#fff; margin:0; flex:1; text-align:center; }
.sba-back { position:absolute; top:5px; left:12px; }
.sba-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border:none; background:#1e6e43;
           color:#fff; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; }
.sba-btn:hover { background:#16563a; }
.sba-btn.ghost { background:#fff; color:#475467; border:1px solid #d0d5dd; }
.sba-btn.ghost:hover { background:#f8f8f8; color:#101828; }
.sba-tabs { display:flex; gap:8px; margin-bottom:16px; }
.sba-tab { padding:8px 18px; border:1px solid #d0d5dd; border-radius:8px; background:#fff;
           font-size:13px; font-weight:600; color:#475467; cursor:pointer; }
.sba-tab.on { background:#1C4532; border-color:#1C4532; color:#fff; }
.sba-card { background:#fff; border:1px solid #e4e7ec; border-radius:10px; padding:16px;
            box-shadow:0 1px 3px rgba(16,24,40,.06); max-width:1000px; }
.sba-note { font-size:12px; color:#667085; margin:0 0 12px; }
.sba-row { display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; }
.sba-pick { padding:8px 10px; border:1px solid #d0d5dd; border-radius:8px; font-size:13px;
            background:#fff; min-width:280px; }
.sba-ta { width:100%; min-height:220px; border:1px solid #d0d5dd; border-radius:8px; padding:8px 10px;
          font-size:12.5px; font-family:Consolas,Menlo,monospace; }
.sba-ta.copy { min-height:90px; font-family:inherit; }
.sba-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
           color:#667085; display:block; margin:10px 0 4px; }
.sba-ovtag { font-size:9.5px; font-weight:700; text-transform:uppercase; padding:2px 7px;
             border-radius:50px; background:#e8f2ec; color:#1e6e43; margin-left:6px; }
table.sba-grid { width:100%; border-collapse:collapse; font-size:12.5px; }
.sba-grid th { text-align:left; padding:8px 10px; font-size:11px; font-weight:600; text-transform:uppercase;
               letter-spacing:.4px; color:#475467; background:#f2f7f3; border-bottom:1px solid #e4e7ec; }
.sba-grid td { padding:6px 10px; border-bottom:1px solid #eef1f4; }
.sba-grid select { padding:5px 8px; border:1px solid #d0d5dd; border-radius:6px; font-size:12.5px; background:#fff; }
.sba-msg { font-size:12px; color:#1e6e43; margin-left:10px; }
</style>

<div id='stdPage'>
    <header class="sba-topbar">
        <h1>Sellbrite Data</h1>
    </header>
    <button type="button" class="sba-btn ghost sba-back" onclick="window.location='SellbriteBulkLoader_ctl.php'">&larr; Bulk Loader</button>

    <div class="sba-tabs">
        <button type="button" class="sba-tab on" data-tab="values">Dropdown Values</button>
        <button type="button" class="sba-tab" data-tab="copy">Category Copy</button>
        <button type="button" class="sba-tab" data-tab="markets">Market Columns</button>
    </div>

    <!-- one value per line; saving replaces the whole list for that field -->
    <div class="sba-card" id="tab-values">
        <p class="sba-note">The choices each dropdown on the loader offers. One value per line - saving
           replaces the whole list for that field, Reset returns the built-in list. Operators can always
           type values that are not listed; these lists are the suggestions.</p>
        <div class="sba-row">
            <select id="v-field" class="sba-pick"></select>
            <span id="v-ov"></span>
        </div>
        <label class="sba-lbl">Values (one per line)</label>
        <textarea id="v-ta" class="sba-ta" spellcheck="false"></textarea>
        <div style="margin-top:10px">
            <button type="button" class="sba-btn" onclick="saveValues()">Save List</button>
            <button type="button" class="sba-btn ghost" onclick="resetValues()">Reset to Built-in</button>
            <span class="sba-msg" id="v-msg"></span>
        </div>
    </div>

    <!-- Des's per-category descriptions; the Extended Description fills from these -->
    <div class="sba-card" id="tab-copy" style="display:none">
        <p class="sba-note">The listing copy per store category, from Des's sheet. The Extended
           Description box fills with the Description below when it is empty; the alternates stand in
           when the main one is blank. Saving stores your version; Reset returns Des's original.</p>
        <div class="sba-row">
            <select id="c-cat" class="sba-pick"></select>
        </div>
        <label class="sba-lbl">Description</label>
        <textarea id="c-copy" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 1</label>
        <textarea id="c-alt1" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 2</label>
        <textarea id="c-alt2" class="sba-ta copy"></textarea>
        <div style="margin-top:10px">
            <button type="button" class="sba-btn" onclick="saveCopy()">Save Copy</button>
            <button type="button" class="sba-btn ghost" onclick="resetCopy()">Reset to Original</button>
            <span class="sba-msg" id="c-msg"></span>
        </div>
    </div>

    <!-- which upload columns each market's spreadsheet carries -->
    <div class="sba-card" id="tab-markets" style="display:none">
        <p class="sba-note">Which markets each upload column exports to. "Built-in" follows the standard
           layout; picking a market sends that column only to that market's spreadsheet; All sends it to
           every one. Changes apply to the next export immediately.</p>
        <table class="sba-grid"><thead><tr><th>Column</th><th>Header</th><th>Built-in</th><th>Exports To</th></tr></thead>
            <tbody id="m-body"></tbody></table>
        <span class="sba-msg" id="m-msg"></span>
    </div>
</div>

<script>
// Sellbrite Data frontend: three tabs over the SBLCONFIGT override rows
var sbaFields = [], sbaCats = [], sbaCols = [], sbaOvValues = [];

function esc(s){ return $('<span>').text(s == null ? '' : String(s)).html(); }

function postA(data, onOk){
    $.post('SellbriteBulkLoaderAdmin_ajax.php', data, function(r){
        if (r && r.returnClass === 'success') { onOk(r); }
        else { swal('Error', (r && r.message) || 'Request failed.', 'error'); }
    }, 'json').fail(function(){ swal('Error', 'Server error - see the log.', 'error'); });
}

function loadAll(){
    postA({ action:'load' }, function(r){
        sbaFields = r.fields || []; sbaCats = r.cats || []; sbaCols = r.cols || [];
        sbaOvValues = r.valueOverrides || [];
        var vf = $('#v-field').empty();
        $.each(sbaFields, function(i, f){ vf.append($('<option>').val(f.name).text(f.label)); });
        var cc = $('#c-cat').empty();
        $.each(sbaCats, function(i, c){ cc.append($('<option>').val(c.category).text(c.category)); });
        fillValues(); fillCopy(); fillMarkets();
    });
}

function fillValues(){
    var f = null, name = $('#v-field').val();
    $.each(sbaFields, function(i, x){ if (x.name === name) f = x; });
    $('#v-ta').val(f ? f.options.join('\n') : '');
    $('#v-ov').html(sbaOvValues.indexOf(name) >= 0 ? '<span class="sba-ovtag">staff list</span>' : '');
    $('#v-msg').text('');
}

function saveValues(){
    postA({ action:'saveValues', field:$('#v-field').val(), values:$('#v-ta').val() }, function(){
        $('#v-msg').text('Saved - the loader uses this list now.'); loadAll();
    });
}
function resetValues(){
    postA({ action:'resetValues', field:$('#v-field').val() }, function(){
        $('#v-msg').text('Back to the built-in list.'); loadAll();
    });
}

function fillCopy(){
    var c = null, cat = $('#c-cat').val();
    $.each(sbaCats, function(i, x){ if (x.category === cat) c = x; });
    $('#c-copy').val(c ? c.copy : ''); $('#c-alt1').val(c ? c.alt1 : ''); $('#c-alt2').val(c ? c.alt2 : '');
    $('#c-msg').text('');
}
function saveCopy(){
    postA({ action:'saveCopy', category:$('#c-cat').val(), copy:$('#c-copy').val(),
            alt1:$('#c-alt1').val(), alt2:$('#c-alt2').val() }, function(){
        $('#c-msg').text('Saved - new listings use this copy.'); loadAll();
    });
}
function resetCopy(){
    postA({ action:'resetCopy', category:$('#c-cat').val() }, function(){
        $('#c-msg').text("Back to Des's original."); loadAll();
    });
}

function fillMarkets(){
    var tb = $('#m-body').empty();
    $.each(sbaCols, function(i, c){
        var sel = $('<select>').attr('data-col', c.name)
            .append($('<option>').val('base').text('Built-in (' + c.home + ')'))
            .append($('<option>').val('all').text('All'))
            .append($('<option>').val('amazon').text('Amazon only'))
            .append($('<option>').val('ebay').text('eBay only'))
            .append($('<option>').val('walmart').text('Walmart only'));
        sel.val(c.set || 'base');
        tb.append($('<tr>').append($('<td>').text(c.name))
                           .append($('<td>').text(c.label))
                           .append($('<td>').text(c.home))
                           .append($('<td>').append(sel)));
    });
}

$(document).ready(function(){
    loadAll();
    $('.sba-tab').on('click', function(){
        $('.sba-tab').removeClass('on'); $(this).addClass('on');
        var t = $(this).data('tab');
        $('#tab-values').toggle(t === 'values');
        $('#tab-copy').toggle(t === 'copy');
        $('#tab-markets').toggle(t === 'markets');
    });
    $('#v-field').on('change', fillValues);
    $('#c-cat').on('change', fillCopy);
    // a market pick saves the moment it is made
    $('#m-body').on('change', 'select', function(){
        var sel = $(this);
        postA({ action:'saveMarket', column:sel.data('col'), market:sel.val() }, function(){
            $('#m-msg').text(sel.data('col') + ' saved.');
        });
    });
});
</script>

<!--  End Content Here -->
<?php
} // end authority check

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
