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

    /* ---- coin finder: memory dropdown -> API auto-fill ---- */
    function sblEsc(s){ return $('<div>').text(s == null ? '' : s).html(); }
    function sblFillFromRow(row){
        $.each(row || {}, function(k,v){
            var el = document.getElementById('f_' + k);
            if (el && v !== null && v !== '') { el.value = v; }
        });
        sblRecompute();
    }
    /* Search the learned coin memory; matches pop into the dropdown. */
    function sblGsFind(){
        var q = $('#gs-find').val().trim();
        if (!q){ $('#gs-find').focus(); return; }
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsSearch', q:q }, function(res){
            var sel = $('#gs-matches').empty(), m = res.matches || [];
            if (!m.length){ sel.hide(); sblGsLookupLive(q); return; }   // not remembered -> live lookup
            sel.append('<option value="">' + m.length + ' match(es) — pick one…</option>');
            $.each(m, function(i, c){
                sel.append('<option value="' + c.gs_id + '" title="' + sblEsc(c.path) + '">' + sblEsc(c.label) + '</option>');
            });
            sel.show();
        }, 'json');
    }
    /* Picking a coin from the dropdown calls the API and auto-fills the form. */
    function sblGsPick(){
        var id = $('#gs-matches').val();
        if (!id) return;
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsImport', gs_id:id, grade:$('#f_grade').val() }, function(res){
            sblGsHandle(res, $('#gs-find').val());
        }, 'json');
    }
    /* Unknown coin: navigate GreySheet live using whatever is in the form + the search text. */
    function sblGsLookupLive(q){
        swal({ title:'Not in memory yet', text:'Look this coin up on GreySheet now? (It will be remembered.)',
               icon:'info', buttons:['Cancel','Look up'] })
        .then(function(go){
            if (!go) return;
            if (!$('#f_category_name').val()) { $('#f_category_name').val(q); }
            $.post('SellbriteBulkLoader_ajax.php', $('#sku-form').serialize() + '&action=gsImport', function(res){
                sblGsHandle(res, q);
            }, 'json');
        });
    }
    function sblGsHandle(res, hint){
        if (res.returnClass === 'notfound'){
            swal({ title:"GreySheet doesn't have this coin",
                   text:'Would you like the AI to generate this listing?',
                   icon:'info', buttons:['Cancel','Generate with AI'] })
            .then(function(go){ if (go) sblGsGenerate(hint); });
            return;
        }
        if (res.returnClass === 'error'){ swal('Import failed', res.message || 'GreySheet returned nothing.', 'error'); return; }
        sblFillFromRow(res.row);
        swal({ title:'Imported', text:'Review the highlighted fields, then Save.',
               icon: res.returnClass === 'success' ? 'success' : 'warning', timer:1600, buttons:false });
    }
    function sblGsGenerate(hint){
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsGenerate', hint:hint }, function(res){
            if (res.returnClass === 'error'){ swal('Generation failed', res.message || 'The AI returned nothing.', 'error'); return; }
            sblFillFromRow(res.row);
            swal({ title:'AI draft ready', text:'Double-check the facts, then Save.',
                   icon: res.returnClass === 'success' ? 'success' : 'warning', timer:1800, buttons:false });
        }, 'json');
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

    $screenData = ['skus' => sblGetAll($_GET['q'] ?? '')];

    include "SellbriteBulkLoader_dsp.php";
    dspBulkLoader($screenData);
?>
<!--  End Content Here -->
<?php
} // end authority check

if (file_exists('EndBlock.php')) { include "EndBlock.php"; }
?>
