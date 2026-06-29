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
        if (name === 'cats')   rdCatLoad();
        if (name === 'grades') rdGradeLoad();
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

    /* ============ CATEGORY DEFAULTS ============ */
    var RD_CATS = [];
    function rdCatLoad(){
        $.post(RD, { action:'catRows' }, function(res){ RD_CATS = res.rows || []; rdCatRender(); }, 'json');
    }
    function rdCatRender(){
        var f = ($('#rd-cat-filter').val()||'').toLowerCase();
        var rows = RD_CATS.filter(function(r){ return !f || (r.category_name||'').toLowerCase().indexOf(f) > -1; });
        if (!rows.length){ $('#rd-cat-rows').html('<tr><td colspan="6" class="empty">No categories.</td></tr>'); return; }
        var h = '';
        $.each(rows, function(i, r){
            h += '<tr><td>'+rdEsc(r.category_name)+'</td><td>'+rdEsc(r.coin_type)+'</td><td>'+rdEsc(r.denomination)
              +  '</td><td>'+rdEsc(r.composition)+'</td><td>'+rdEsc(r.weight_lb)+'</td>'
              +  '<td style="text-align:right"><button type="button" class="mini" onclick="rdCatEdit('+r.id+')">Edit</button> '
              +  '<button type="button" class="mini danger" onclick="rdCatDelete('+r.id+',\''+rdEsc(r.category_name).replace(/\x27/g,"")+'\')">Delete</button></td></tr>';
        });
        $('#rd-cat-rows').html(h);
    }
    function rdCatFilter(){ rdCatRender(); }
    function rdCatForm(r){
        r = r || {};
        var fields = [['category_name','Category Name'],['coin_type','Coin Type'],['denomination','Denomination'],
            ['composition','Composition'],['fineness','Fineness'],['country','Country'],['brand','Brand'],
            ['weight_lb','Weight (lb)'],['search_terms','Search Terms'],['copy_description','Copy Description'],
            ['collector_note','Collector Note']];
        var h = '<div style="text-align:left;max-height:60vh;overflow:auto">';
        $.each(fields, function(i, f){
            var big = (f[0]==='copy_description'||f[0]==='collector_note'||f[0]==='search_terms');
            h += '<div style="margin:6px 0"><label style="font-size:12px;font-weight:700;color:#5f6b62">'+f[1]+'</label>'
              + (big ? '<textarea class="rd-in" data-k="'+f[0]+'" style="width:100%;height:60px">'+rdEsc(r[f[0]])+'</textarea>'
                     : '<input class="rd-in" data-k="'+f[0]+'" style="width:100%" value="'+rdEsc(r[f[0]])+'">')+'</div>';
        });
        return h + '<input type="hidden" data-k="id" value="'+(r.id||'')+'"></div>';
    }
    function rdCatNew(){ rdCatModal({}); }
    function rdCatEdit(id){
        $.post(RD, { action:'catFind', id:id }, function(res){ if(res.row) rdCatModal(res.row); }, 'json');
    }
    function rdCatModal(r){
        swal({ title:(r.id?'Edit category':'New category'), content:{ element:'div', attributes:{ innerHTML: rdCatForm(r) } },
               buttons:['Cancel','Save'], className:'rd-wide' })
        .then(function(ok){ if(!ok) return;
            var data = { action:'catSave' };
            $('.swal-content [data-k]').each(function(){ data[$(this).data('k')] = this.value; });
            $.post(RD, data, function(res){
                if (res.returnClass==='success'){ rdCatLoad(); } else swal('Error', res.message||'Save failed.','error');
            }, 'json');
        });
    }
    function rdCatDelete(id, name){
        swal({ title:'Delete '+name+'?', icon:'warning', buttons:['Cancel','Delete'], dangerMode:true })
        .then(function(ok){ if(!ok) return; $.post(RD, { action:'catDelete', id:id }, function(){ rdCatLoad(); }, 'json'); });
    }

    /* ============ GRADES ============ */
    var RD_GRADES = [];
    function rdGradeLoad(){ $.post(RD, { action:'gradeRows' }, function(res){ RD_GRADES = res.rows || []; rdGradeRender(); }, 'json'); }
    function rdGradeRender(){
        var f = ($('#rd-grade-filter').val()||'').toLowerCase();
        var rows = RD_GRADES.filter(function(r){ return !f || (r.grade||'').toLowerCase().indexOf(f) > -1; });
        if (!rows.length){ $('#rd-grade-rows').html('<tr><td colspan="3" class="empty">No grades.</td></tr>'); return; }
        var h = '';
        $.each(rows, function(i, r){
            h += '<tr data-id="'+r.id+'"><td><input type="text" value="'+rdEsc(r.grade)+'" data-k="grade"></td>'
              +  '<td><select data-k="circ_status"><option value=""></option>'
              +  '<option'+(r.circ_status==='Circulated'?' selected':'')+'>Circulated</option>'
              +  '<option'+(r.circ_status==='Uncirculated'?' selected':'')+'>Uncirculated</option></select></td>'
              +  '<td style="text-align:right"><button type="button" class="mini save" onclick="rdGradeSave(this)">Save</button> '
              +  '<button type="button" class="mini danger" onclick="rdGradeDelete(this,\''+rdEsc(r.grade).replace(/\x27/g,"")+'\')">Delete</button></td></tr>';
        });
        $('#rd-grade-rows').html(h);
    }
    function rdGradeFilter(){ rdGradeRender(); }
    function rdGradeAdd(){
        var grade = $('#rd-new-grade').val().trim();
        if (!grade){ $('#rd-new-grade').focus(); return; }
        $.post(RD, { action:'gradeSave', grade:grade, circ_status:$('#rd-new-circ').val() }, function(res){
            if (res.returnClass==='success'){ $('#rd-new-grade').val(''); $('#rd-new-circ').val(''); rdGradeLoad(); }
            else swal('Not added', res.message||'Failed.','error');
        }, 'json');
    }
    function rdGradeSave(btn){
        var $tr = $(btn).closest('tr');
        $.post(RD, { action:'gradeSave', id:$tr.data('id'), grade:$tr.find('[data-k=grade]').val(), circ_status:$tr.find('[data-k=circ_status]').val() },
        function(res){ if(res.returnClass==='success'){ swal({title:'Saved',icon:'success',timer:900,buttons:false}); rdGradeLoad(); } else swal('Error',res.message||'Failed.','error'); }, 'json');
    }
    function rdGradeDelete(btn, label){
        swal({ title:'Delete grade '+label+'?', icon:'warning', buttons:['Cancel','Delete'], dangerMode:true })
        .then(function(ok){ if(!ok) return; $.post(RD, { action:'gradeDelete', id:$(btn).closest('tr').data('id') }, function(){ rdGradeLoad(); }, 'json'); });
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
