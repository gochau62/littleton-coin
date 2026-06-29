<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ctl.php      *  -->
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

<!-- includes css and javascript libraries (local copies, same as the other LCC tools) -->
<link href="jQuery/jquery-ui-custom.css" rel="stylesheet" type="text/css" />
<script type='text/javascript' src='jQuery/jquery.js'></script>
<script type='text/javascript' src='jQuery/jquery-ui.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.core.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.position.js'></script>
<script type='text/javascript' src='jQuery/jquery.ui.widget.js'></script>
<script type='text/javascript' src='swal/sweetalert-dev.js'></script>
<script type='text/javascript' src='swal/sweetalert.min.js'></script>
<link href="swal/sweetalert.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
    
    document.title = "Sellbrite Bulk Loader";

    /* ---- message helpers (jQuery-UI state boxes, LCC convention) ---- */
    function showErrorMessage(m){ $("#errorMsg").text(m).show(); }
    function hideErrorMessage(){ $("#errorMsg").text('').hide(); }
    function showSuccessMessage(m){ $("#successMsg").text(m).show(); }
    function showNotAuthorized(){ showErrorMessage("Current user profile is not authorized to use this tool."); }

    var SBL_LABELS = {};

    /* ---- view switching ---- */
    function sblShow(view){
        $("#listView").toggle(view === 'list');
        $("#formView").toggle(view === 'form');
        $("#adminView").toggle(view === 'admin');
    }
    function sblBackToList(){ sblShow('list'); }
    function sblExport(){ window.location = 'SellbriteBulkLoader_ajax.php?action=export'; }
    function sblSearch(){ window.location = '?q=' + encodeURIComponent($('#sbl-search').val()); }

    /* ---- new / edit ---- */
    function sblClearForm(){
        $('#sku-form')[0].reset();
        $('#f_id').val('');
        $('#sku-form .field').removeClass('is-ok is-error is-action');
        $('#sku-form .field-msg').text('');
    }
    function sblNew(){
        sblClearForm();
        $('#formTitle').text('New SKU');
        sblShow('form');
        sblRecompute();
    }
    function sblEdit(id){
        $.post('SellbriteBulkLoader_ajax.php', { action:'find', id:id }, function(res){
            if (res.returnClass !== 'success' || !res.row){ swal('Not found','That record could not be loaded.','error'); return; }
            sblClearForm();
            $.each(res.row, function(k,v){
                var el = document.getElementById('f_' + k);
                if (el) { el.value = (v === null ? '' : v); }
            });
            $('#f_id').val(res.row.id);
            $('#formTitle').text('Edit SKU - ' + (res.row.sku || ''));
            sblShow('form');
            sblRecompute();
        }, 'json');
    }

    /* ---- save / delete ---- */
    function sblSave(){
        var data = $('#sku-form').serialize() + '&action=save';
        $.post('SellbriteBulkLoader_ajax.php', data, function(res){
            if (res.returnClass === 'success'){
                swal({title:'Saved', text:'SKU saved and derived fields recomputed.', icon:'success', timer:1500, buttons:false});
            } else {
                swal('Saved with warnings','The SKU was saved but still needs attention (see the highlighted fields).','warning');
            }
            setTimeout(function(){ window.location = '?'; }, 800);
        }, 'json');
    }
    function sblDelete(id, sku){
        swal({ title:'Delete ' + sku + '?', text:'This permanently removes the record.',
               icon:'warning', buttons:['Cancel','Delete'], dangerMode:true })
        .then(function(ok){
            if (!ok) return;
            $.post('SellbriteBulkLoader_ajax.php', { action:'delete', id:id }, function(){
                window.location = '?';
            }, 'json');
        });
    }

    /* ---- live recompute (mirrors the spreadsheet formulas) ---- */
    function sblRecompute(){
        var data = $('#sku-form').serialize() + '&action=compute';
        $.post('SellbriteBulkLoader_ajax.php', data, function(res){
            var active = document.activeElement;
            $('#sku-form [data-auto="1"]').each(function(){
                if (this === active) return;
                var v = res.fields[this.name];
                if (v !== undefined && this.value !== v) this.value = v;
            });
            $.each(res.statuses, function(name, st){
                var el = document.querySelector('#sku-form [data-name="' + name + '"]');
                if (!el) return;
                var f = el.closest('.field'); if (!f) return;
                f.classList.remove('is-ok','is-error','is-action');
                if (st) f.classList.add('is-' + st);
                var m = f.querySelector('.field-msg'); if (m) m.textContent = res.messages[name] || '';
            });
            sblPreview(res.fields);
            sblValidity(res);
        }, 'json');
    }
    function sblPreview(f){
        $('#pv-title').text(f.name || 'Product title appears here');
        $('#pv-desc').text(f.description || '');
        $('#pv-price').text(f.price ? '$' + f.price : '');
        $('#pv-qty').text(f.quantity ? 'Qty ' + f.quantity : '');
        var img = document.getElementById('pv-img');
        if (img && f.product_image_1 && img.getAttribute('src') !== f.product_image_1){ img.classList.remove('broken'); img.src = f.product_image_1; }
    }
    function sblValidity(res){
        var pill = $('#valid-pill');
        pill.removeClass('ok err').addClass(res.valid ? 'ok' : 'err').text(res.valid ? 'Ready' : 'Needs attention');
        var list = $('#issue-list').empty(), any = false;
        $.each(res.statuses, function(name, st){
            if (st === 'error' || st === 'action'){
                any = true;
                $('<li>').addClass(st).text((SBL_LABELS[name] || name) + (res.messages[name] ? ' — ' + res.messages[name] : '')).appendTo(list);
            }
        });
        if (!any) $('<li>').addClass('ok').text('All checks passed.').appendTo(list);
    }

    /* ---- reference-data admin (Manage Lists / Categories) ---- */
    function sblEsc(s){ return $('<div>').text(s == null ? '' : s).html(); }
    function sblTrunc(s, n){ s = (s == null ? '' : String(s)); return s.length > n ? s.slice(0, n) + '…' : s; }

    function sblAdmin(){ sblShow('admin'); sblRefLoad(); }

    function sblRefLoad(){
        var data = { action:'ref_grid',
                     list:   $('#rv-filter').val()   || '',
                     lookup: $('#rl-filter').val()   || '' };
        $.post('SellbriteBulkLoader_ajax.php', data, function(res){
            if (res.returnClass !== 'success') return;
            sblFillSelect($('#rv-filter'), res.lists, 'All lists', $('#rv-filter').val());
            sblFillSelect($('#rv-list'),   res.lists, '— list —', $('#rv-list').val());
            sblFillSelect($('#rl-filter'), ['category_copy','category_meta','grade_circ'], 'All lookups', $('#rl-filter').val());
            sblFillSelect($('#rl-name'),   ['category_copy','category_meta','grade_circ'], '— lookup —', $('#rl-name').val());
            sblFillSelect($('#rr-field'),  res.fields, '— field —', $('#rr-field').val());
            sblRenderValues(res.values);
            sblRenderLookups(res.lookups);
            sblRenderRules(res.rules);
        }, 'json');
    }
    function sblFillSelect($sel, items, placeholder, keep){
        if (!$sel.length) return;
        var h = '<option value="">' + sblEsc(placeholder) + '</option>';
        $.each(items || [], function(i, v){ h += '<option value="' + sblEsc(v) + '">' + sblEsc(v) + '</option>'; });
        $sel.html(h).val(keep || '');
    }
    function sblRenderValues(rows){
        var b = $('#ref-values-body').empty();
        if (!rows || !rows.length){ b.append('<tr><td colspan="5" class="empty">No options. Add one, or click "Seed from spreadsheet".</td></tr>'); return; }
        $.each(rows, function(i, r){
            b.append('<tr><td>' + sblEsc(r.list_name) + '</td><td>' + sblEsc(r.option_value) +
                '</td><td class="num">' + sblEsc(r.sort_order) + '</td><td>' + sblEsc(r.active) +
                '</td><td style="text-align:right"><button type="button" class="mini danger" onclick="sblValueDel(' + (+r.id) + ')">Delete</button></td></tr>');
        });
    }
    function sblRenderLookups(rows){
        var b = $('#ref-lookups-body').empty();
        if (!rows || !rows.length){ b.append('<tr><td colspan="5" class="empty">No lookups yet.</td></tr>'); return; }
        $.each(rows, function(i, r){
            b.append('<tr><td>' + sblEsc(r.lookup_name) + '</td><td>' + sblEsc(r.lookup_key) +
                '</td><td>' + sblEsc(r.attr_name) + '</td><td>' + sblEsc(sblTrunc(r.attr_value, 80)) +
                '</td><td style="text-align:right"><button type="button" class="mini danger" onclick="sblLookupDel(' + (+r.id) + ')">Delete</button></td></tr>');
        });
    }
    function sblRenderRules(rows){
        var b = $('#ref-rules-body').empty();
        if (!rows || !rows.length){ b.append('<tr><td colspan="5" class="empty">No rules yet.</td></tr>'); return; }
        $.each(rows, function(i, r){
            b.append('<tr><td>' + sblEsc(r.field_name) + '</td><td>' + sblEsc(r.rule_type) +
                '</td><td>' + sblEsc(r.message) + '</td><td><code>' + sblEsc(sblTrunc(r.condition_json, 70)) +
                '</code></td><td style="text-align:right"><button type="button" class="mini danger" onclick="sblRuleDel(' + (+r.id) + ')">Delete</button></td></tr>');
        });
    }

    function sblRefPost(data, msg){
        $.post('SellbriteBulkLoader_ajax.php', data, function(res){
            if (res.returnClass !== 'success'){ swal('Could not save', res.message || 'Check the database tables exist and try again.', 'error'); return; }
            sblRefLoad();
        }, 'json');
    }
    function sblValueAddRow(){
        if (!$('#rv-list').val() || !$('#rv-option').val()){ swal('Missing info','Pick a list and enter an option value.','warning'); return; }
        sblRefPost({ action:'value_add', list_name:$('#rv-list').val(), option_value:$('#rv-option').val(), sort_order:$('#rv-sort').val() || 0 });
        $('#rv-option').val(''); $('#rv-sort').val('');
    }
    function sblValueDel(id){ sblRefPost({ action:'value_delete', id:id }); }
    function sblLookupAddRow(){
        if (!$('#rl-name').val() || !$('#rl-key').val()){ swal('Missing info','Pick a lookup and enter a key (category/grade).','warning'); return; }
        sblRefPost({ action:'lookup_add', lookup_name:$('#rl-name').val(), lookup_key:$('#rl-key').val(), attr_name:$('#rl-attr').val(), attr_value:$('#rl-val').val() });
        $('#rl-key').val(''); $('#rl-attr').val(''); $('#rl-val').val('');
    }
    function sblLookupDel(id){ sblRefPost({ action:'lookup_delete', id:id }); }
    function sblRuleAddRow(){
        if (!$('#rr-field').val() || !$('#rr-cond').val()){ swal('Missing info','Pick a field and enter the condition JSON.','warning'); return; }
        sblRefPost({ action:'rule_add', field_name:$('#rr-field').val(), rule_type:$('#rr-type').val(), message:$('#rr-msg').val(), condition_json:$('#rr-cond').val() });
        $('#rr-msg').val(''); $('#rr-cond').val('');
    }
    function sblRuleDel(id){ sblRefPost({ action:'rule_delete', id:id }); }

    function sblSeed(){
        swal({ title:'Seed reference tables?', text:'Copies the spreadsheet dropdowns, lookups and rules into the DB tables. Tables that already have rows are skipped.',
               icon:'info', buttons:['Cancel','Seed'] })
        .then(function(go){
            if (!go) return;
            $.post('SellbriteBulkLoader_ajax.php', { action:'ref_seed' }, function(res){
                if (!res.ok){ swal('Not seeded', res.msg || 'No database connection.', 'error'); return; }
                var c = res.counts || {};
                swal('Seeded', 'Values: ' + (c.values||0) + ', Lookups: ' + (c.lookups||0) + ', Rules: ' + (c.rules||0) +
                     ((c.skipped && c.skipped.length) ? '\nSkipped: ' + c.skipped.join(', ') : ''), 'success');
                sblRefLoad();
            }, 'json');
        });
    }

    /* ---- document ready: spinner + live recompute binding ---- */
    var sblTimer = null;
    jQuery(document).ready(function(){
        $('#sbl-spinner').ajaxStart(function(){ $(this).addClass('progress'); })
                         .ajaxStop(function(){ $(this).removeClass('progress'); });

        $('#sku-form [data-name]').each(function(){
            var lbl = $(this).closest('.field').find('label').text().replace('*','').trim();
            SBL_LABELS[this.name] = lbl;
        });
        $('#sku-form').on('input', function(){ clearTimeout(sblTimer); sblTimer = setTimeout(sblRecompute, 250); });
        $('#sku-form').on('change', sblRecompute);
    });
</script>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

//***--- Check users authority (10 is the minimum to use LCCOnline) ---***
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
    require_once __DIR__ . '/SellbriteBulkLoader_admin_model.php';

    // Reference data (dropdowns / lookups / rules) from DB2 when the tables
    // exist; falls back to SellbriteBulkLoader_data.php otherwise.  Must run
    // before the display so the form's <select> options reflect live values.
    sblLoadReferenceOverrides();

    $screenData = ['skus' => sblGetAll($_GET['q'] ?? '')];

    include "SellbriteBulkLoader_dsp.php";
    dspBulkLoader($screenData);
?>
<!--  End Content Here -->
<?php
} // end authority check

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
