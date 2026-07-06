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
        // reset the GreySheet lookup
        $('#gs-node').val('');
        $('#gs-coin').val('').prop('disabled', true);
        $('#gs-autofill').prop('disabled', true);
        sblMarkGsFields(false);
        sblCurNode = 0; sblCurPath = ''; sblPendingGsId = 0;
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
    function sblEsc(s){ return $('<div>').text(s == null ? '' : s).html().replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    function sblFillFromRow(row){
        $.each(row || {}, function(k,v){
            var el = document.getElementById('f_' + k);
            if (el && v !== null && v !== '') { el.value = v; }
        });
        sblYearRefresh(row && row.year);   // constrain Year to the series' real years
        sblRecompute();
    }

    /* ---- dynamic Year dropdown: only the years the series exists for ---- */
    function sblYearApply(years, keep){
        var cur = (keep !== undefined && keep !== null && keep !== '') ? keep : $('#f_year').val();
        if (!years || !years.length){
            // No data for this series: fall back to free typing.
            if ($('#f_year').is('select')){
                $('#f_year').replaceWith('<input type="text" id="f_year" name="year" value="' + sblEsc(cur) + '" data-name="year">');
            }
            return;
        }
        var h = '<select id="f_year" name="year" data-name="year"><option value="">&mdash; select &mdash;</option>';
        for (var i = 0; i < years.length; i++){
            h += '<option value="' + years[i] + '"' + (String(cur) === String(years[i]) ? ' selected' : '') + '>' + years[i] + '</option>';
        }
        $('#f_year').replaceWith(h + '</select>');
    }
    function sblYearRefresh(keep){
        var cat = $('#f_category_name').val();
        if (!cat){ sblYearApply([], keep); return; }
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsYears', category:cat }, function(res){
            sblYearApply(res.years || [], keep);
        }, 'json');
    }
    jQuery(document).ready(function(){
        // Delegated: survives the input<->select swap and fires on category picks.
        $('#sku-form').on('change', '#f_category_name', function(){ sblYearRefresh(); });
    });
    /* ---- lookup: searchable Node box -> searchable Coin box -> Autofill ---- */
    var sblCurNode = 0, sblCurPath = '', sblPendingGsId = 0;

    /* Fields the GreySheet autofill populates (mirrors gsMapToProduct + pricing).
       These get the blue "AUTO" preview when a coin is picked. */
    var SBL_GS_FIELDS = ['category_name','year','mint_mark','denomination','coin_variety_1',
        'coin_variety_2','composition','fineness','strike_type','package_weight','price','cost'];

    function sblMarkGsFields(on){
        $.each(SBL_GS_FIELDS, function(i, name){
            var el = document.querySelector('#sku-form [data-name="' + name + '"]');
            if (!el) return;
            var f = el.closest('.field'); if (!f) return;
            f.classList.toggle('is-gsauto', !!on);
            var lbl = f.querySelector('label'); if (!lbl) return;
            var b = lbl.querySelector('.badge.gsauto');
            if (on && !b && !lbl.querySelector('.badge.auto')){   // don't double-badge formula-auto fields
                b = document.createElement('span'); b.className = 'badge gsauto'; b.textContent = 'AUTO';
                b.title = 'Auto-filled from GreySheet when you click Autofill.';
                lbl.appendChild(document.createTextNode(' ')); lbl.appendChild(b);
            } else if (!on && b){ b.remove(); }
        });
    }

    /* Box #1 - searchable dropdown over every catalog NODE in memory: broad
       roots (US Coins, US Currency, World Coins, World Currency), the specific
       series under them, right down to the leaf. 0 API calls. */
    function sblNodeAutocomplete(){
        $('#gs-node').autocomplete({
            minLength: 1, delay: 200,
            source: function(req, resp){
                $.post('SellbriteBulkLoader_ajax.php', { action:'gsCategories', q:req.term }, function(res){
                    resp($.map(res.matches || [], function(c){
                        return { label: c.name, value: c.name, id: c.node_id, path: c.path, count: c.count };
                    }));
                }, 'json');
            },
            focus: function(){ return false; },   // don't overwrite the box on arrow-key hover
            select: function(e, ui){
                sblCurNode = ui.item.id; sblCurPath = ui.item.path || '';
                $('#gs-node').val(ui.item.value);
                $('#f_category_name').val(ui.item.value).trigger('change');   // sets category + refreshes Year
                sblPendingGsId = 0; $('#gs-autofill').prop('disabled', true); sblMarkGsFields(false);
                $('#gs-coin').val('').prop('disabled', false).focus();
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.label)
                     + '<div class="gs-path">' + sblEsc(item.path) + (item.count ? '  &middot; ' + item.count + ' coins' : '') + '</div></div>')
                   .appendTo(ul);
        };
    }
    /* Box #2 - searchable dropdown of the coins under the chosen node (any level;
       matches by catalog path). Picking one previews the fields, does NOT call
       the API yet. 0 API calls. */
    function sblCoinAutocomplete(){
        $('#gs-coin').autocomplete({
            minLength: 0, delay: 200,
            source: function(req, resp){
                if (!sblCurPath){ resp([]); return; }
                $.post('SellbriteBulkLoader_ajax.php', { action:'gsCoins', path:sblCurPath, q:req.term }, function(res){
                    resp($.map(res.matches || [], function(c){
                        return { label: c.label, value: c.label, gs_id: c.gs_id };
                    }));
                }, 'json');
            },
            select: function(e, ui){
                sblPendingGsId = ui.item.gs_id;
                $('#gs-coin').val(ui.item.value);
                $('#gs-autofill').prop('disabled', !sblPendingGsId);
                sblMarkGsFields(!!sblPendingGsId);
                return false;
            }
        });
        // show the whole series on focus even before typing
        $('#gs-coin').on('focus', function(){ if (sblCurPath) $(this).autocomplete('search', $(this).val()); });
    }
    /* Autofill button: pull full collectible + pricing from GreySheet and fill. */
    function sblGsAutofill(){
        if (!sblPendingGsId) return;
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsImport', gs_id:sblPendingGsId, grade:$('#f_grade').val() || '' }, function(res){
            sblGsHandle(res, $('#gs-coin').val());
        }, 'json');
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

        // searchable Node / Coin dropdowns (jQuery UI autocomplete)
        if ($.fn.autocomplete){ sblNodeAutocomplete(); sblCoinAutocomplete(); }
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
