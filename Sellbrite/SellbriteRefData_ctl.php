<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteRefData_ctl.php         *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
    // retrieves and sets password and username
    if (file_exists('StartBlockScriptA.php')) { require_once 'StartBlockScriptA.php'; }
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
?>

<link href="jQuery/jquery-ui-custom.css" rel="stylesheet" type="text/css" />
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='jQuery/jquery-ui.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

    document.title = "Sellbrite Reference Data";
    var RD = 'SellbriteRefData_ajax.php';

    function showErrorMessage(m){ $("#errorMsg").text(m).show(); }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }
    function rdEsc(s){ return $('<div>').text(s == null ? '' : s).html(); }

    /* ---- tab switching ---- */
    function rdTab(name){
        $('.rd-tab').removeClass('active').filter('[data-tab="'+name+'"]').addClass('active');
        $('.rd-pane').removeClass('active'); $('#pane-'+name).addClass('active');
        if (name === 'autos') rdAutoInit();
    }

    /* ============ DROPDOWN LISTS ============ */
    function rdLoadValues(){
        var list = $('#rd-list').val();
        if (!list){ $('#rd-value-rows').html('<tr><td colspan="5" class="empty">Choose a list above.</td></tr>'); return; }
        $.post(RD, { action:'valueRows', list:list }, function(res){
            var rows = (res.rows || []);
            if (!rows.length){ $('#rd-value-rows').html('<tr><td colspan="5" class="empty">No options yet. Add one above.</td></tr>'); return; }
            var h = '';
            $.each(rows, function(i, r){
                h += '<tr'+(r.is_header==='Y'?' class="hdr-row"':'')+' data-id="'+r.id+'">'
                  +  '<td><input type="number" value="'+rdEsc(r.sort_seq)+'" data-k="sort_seq" style="width:70px"></td>'
                  +  '<td><input type="text" value="'+rdEsc(r.value_text)+'" data-k="value_text"></td>'
                  +  '<td style="text-align:center"><input type="checkbox" data-k="is_header"'+(r.is_header==='Y'?' checked':'')+'></td>'
                  +  '<td style="text-align:center"><input type="checkbox" data-k="active"'+(r.active!=='N'?' checked':'')+'></td>'
                  +  '<td style="text-align:right">'
                  +  '<button type="button" class="mini save" onclick="rdSaveValue(this)">Save</button> '
                  +  '<button type="button" class="mini danger" onclick="rdDeleteValue(this,\''+rdEsc(r.value_text).replace(/\x27/g,"")+'\')">Delete</button></td></tr>';
            });
            $('#rd-value-rows').html(h);
        }, 'json');
    }
    function rdAddValue(){
        var list = $('#rd-list').val();
        if (!list){ swal('Pick a list','Choose which list to add the option to.','info'); return; }
        var v = $('#rd-new-value').val().trim();
        if (!v){ $('#rd-new-value').focus(); return; }
        $.post(RD, { action:'valueAdd', list:list, value_text:v, is_header:$('#rd-new-header').is(':checked')?'Y':'N' }, function(res){
            if (res.returnClass !== 'success'){ swal('Not added', res.message || 'Failed.', 'error'); return; }
            $('#rd-new-value').val(''); $('#rd-new-header').prop('checked', false); rdLoadValues();
        }, 'json');
    }
    function rdSaveValue(btn){
        var $tr = $(btn).closest('tr'), data = { action:'valueUpdate', id:$tr.data('id') };
        $tr.find('[data-k]').each(function(){
            data[$(this).data('k')] = this.type === 'checkbox' ? (this.checked ? 'Y':'N') : this.value;
        });
        $.post(RD, data, function(res){
            if (res.returnClass === 'success'){ swal({title:'Saved', icon:'success', timer:900, buttons:false}); rdLoadValues(); }
            else swal('Error', res.message || 'Save failed.', 'error');
        }, 'json');
    }
    function rdDeleteValue(btn, label){
        swal({ title:'Delete this option?', text:label, icon:'warning', buttons:['Cancel','Delete'], dangerMode:true })
        .then(function(ok){ if(!ok) return;
            $.post(RD, { action:'valueDelete', id:$(btn).closest('tr').data('id') }, function(){ rdLoadValues(); }, 'json');
        });
    }

    /* ============ AUTO-FILL RULES ============ */
    var RD_AUTO_INIT = false;
    function rdAutoInit(){
        if (RD_AUTO_INIT){ rdAutoLoad(); return; }
        $.post(RD, { action:'autoFields' }, function(res){
            var sel = $('#rd-auto-field');
            $.each(res.fields || [], function(i, f){ sel.append('<option>'+rdEsc(f)+'</option>'); });
            RD_AUTO_INIT = true; rdAutoLoad();
        }, 'json');
    }
    function rdAutoLoad(){
        $.post(RD, { action:'autoRows', when_field:$('#rd-auto-field').val(), q:$('#rd-auto-q').val() }, function(res){
            var rows = res.rows || [];
            if (!rows.length){ $('#rd-auto-rows').html('<tr><td colspan="7" class="empty">No rules. Add one below.</td></tr>'); return; }
            var h = '';
            $.each(rows, function(i, r){
                h += '<tr data-id="'+r.id+'">'
                  +  '<td><input type="text" value="'+rdEsc(r.when_field)+'" data-k="when_field"></td>'
                  +  '<td><input type="text" value="'+rdEsc(r.when_value)+'" data-k="when_value"></td>'
                  +  '<td><input type="text" value="'+rdEsc(r.set_field)+'" data-k="set_field"></td>'
                  +  '<td><input type="text" value="'+rdEsc(r.set_value)+'" data-k="set_value"></td>'
                  +  '<td><input type="number" value="'+rdEsc(r.priority)+'" data-k="priority" style="width:55px"></td>'
                  +  '<td style="text-align:center"><input type="checkbox" data-k="active"'+(r.active!=='N'?' checked':'')+'></td>'
                  +  '<td style="text-align:right"><button type="button" class="mini save" onclick="rdAutoSave(this)">Save</button> '
                  +  '<button type="button" class="mini danger" onclick="rdAutoDelete(this)">Delete</button></td></tr>';
            });
            $('#rd-auto-rows').html(h);
        }, 'json');
    }
    function rdAutoAdd(){
        var data = { action:'autoSave', when_field:$('#na-wf').val().trim(), when_value:$('#na-wv').val().trim(),
                     set_field:$('#na-sf').val().trim(), set_value:$('#na-sv').val(), priority:$('#na-pr').val() };
        if (!data.when_field || !data.when_value || !data.set_field){ swal('Missing fields','when_field, when_value and set_field are required.','info'); return; }
        $.post(RD, data, function(res){
            if (res.returnClass !== 'success'){ swal('Not added', res.message || 'Failed.', 'error'); return; }
            $('#na-wv').val(''); $('#na-sf').val(''); $('#na-sv').val(''); rdAutoLoad();
        }, 'json');
    }
    function rdAutoSave(btn){
        var $tr = $(btn).closest('tr'), data = { action:'autoSave', id:$tr.data('id') };
        $tr.find('[data-k]').each(function(){
            data[$(this).data('k')] = this.type === 'checkbox' ? (this.checked ? 'Y':'N') : this.value;
        });
        $.post(RD, data, function(res){
            if (res.returnClass === 'success'){ swal({title:'Saved', icon:'success', timer:900, buttons:false}); rdAutoLoad(); }
            else swal('Error', res.message || 'Save failed.', 'error');
        }, 'json');
    }
    function rdAutoDelete(btn){
        swal({ title:'Delete this rule?', icon:'warning', buttons:['Cancel','Delete'], dangerMode:true })
        .then(function(ok){ if(!ok) return;
            $.post(RD, { action:'autoDelete', id:$(btn).closest('tr').data('id') }, function(){ rdAutoLoad(); }, 'json');
        });
    }

    jQuery(document).ready(function(){
        $('#rd-spinner').ajaxStart(function(){ $(this).addClass('progress'); }).ajaxStop(function(){ $(this).removeClass('progress'); });
    });
</script>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

//***--- Check users authority (50 is the minimum to use LCCOnline) ---***
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 50);
}

if ($authorized != "yes") {
    echo '<script>showNotAuthorized();</script>';
} else {

    require_once __DIR__ . '/SellbriteBulkLoader_logic.php';
    require_once __DIR__ . '/SellbriteBulkLoader_model.php';

    $screenData = ['lists' => sblValueLists()];

    include "SellbriteRefData_dsp.php";
    dspRefData($screenData);
?>
<!--  End Content Here -->
<?php
} // end authority check

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
