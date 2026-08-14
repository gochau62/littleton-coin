<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoaderAdmin_ctl.php *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 08/13/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
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

    include "SellbriteBulkLoaderAdmin_dsp.php";
    dspBulkLoaderAdmin();
?>

<script>
// Sellbrite Data frontend: three tabs over the SBLCONFIGT override rows,
// talking to the loader's own service (the cfg* actions)
var sbaFields = [], sbaCats = [], sbaCols = [], sbaOvValues = [];

function postA(data, onOk){
    $.post('SellbriteBulkLoader_ajax.php', data, function(r){
        if (r && r.returnClass === 'success') { onOk(r); }
        else { swal('Error', (r && r.message) || 'Request failed.', 'error'); }
    }, 'json').fail(function(){ swal('Error', 'Server error - see the log.', 'error'); });
}

function loadAll(){
    postA({ action:'cfgLoad' }, function(r){
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
    postA({ action:'cfgSaveValues', field:$('#v-field').val(), values:$('#v-ta').val() }, function(){
        $('#v-msg').text('Saved - the loader uses this list now.'); loadAll();
    });
}
function resetValues(){
    postA({ action:'cfgResetValues', field:$('#v-field').val() }, function(){
        $('#v-msg').text('Back to the standard list.'); loadAll();
    });
}

function fillCopy(){
    var c = null, cat = $('#c-cat').val();
    $.each(sbaCats, function(i, x){ if (x.category === cat) c = x; });
    $('#c-copy').val(c ? c.copy : ''); $('#c-alt1').val(c ? c.alt1 : ''); $('#c-alt2').val(c ? c.alt2 : '');
    $('#c-msg').text('');
}
function saveCopy(){
    postA({ action:'cfgSaveCopy', category:$('#c-cat').val(), copy:$('#c-copy').val(),
            alt1:$('#c-alt1').val(), alt2:$('#c-alt2').val() }, function(){
        $('#c-msg').text('Saved - new listings use this copy.'); loadAll();
    });
}
function resetCopy(){
    postA({ action:'cfgResetCopy', category:$('#c-cat').val() }, function(){
        $('#c-msg').text("Back to Des's original."); loadAll();
    });
}

function fillMarkets(){
    var tb = $('#m-body').empty();
    $.each(sbaCols, function(i, c){
        var sel = $('<select>').attr('data-col', c.name).attr('data-home', c.home)
            .append($('<option>').val('all').text('All'))
            .append($('<option>').val('amazon').text('Amazon only'))
            .append($('<option>').val('ebay').text('eBay only'))
            .append($('<option>').val('walmart').text('Walmart only'));
        sel.val(c.set || c.home);
        tb.append($('<tr>').append($('<td>').text(c.name))
                           .append($('<td>').text(c.label))
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
    // a market pick saves the moment it is made; picking the standard value clears the override
    $('#m-body').on('change', 'select', function(){
        var sel = $(this);
        var v = sel.val() === sel.data('home') ? 'base' : sel.val();
        postA({ action:'cfgSaveMarket', column:sel.data('col'), market:v }, function(){
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
