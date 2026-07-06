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
        // reset the GreySheet drill-down
        $('#gs-root').val('');
        $('#gs-series').val('').prop('disabled', true);
        sblRootPath = ''; sblCurPath = '';
        sblResetBelowSeries();
        $('#gs-apilog').empty().append('<li style="color:#5f6b62">Autofill a coin to see the GreySheet calls&hellip;</li>');
        $('#gs-raw').text('Autofill a coin to see the full API response…');
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
    /* ---- drill-down: Tree -> Series -> Year -> Coin -> Autofill ---- */
    var sblRootPath = '', sblCurPath = '', sblCurYear = '', sblPendingGsId = 0;

    /* Auto fields that get the blue "AUTO" preview when a coin is picked.
       SKU/Price/Quantity/Cost are excluded - they're the required fields the
       operator confirms (autofill still suggests price/cost). */
    var SBL_GS_FIELDS = ['category_name','coin_type','year','mint_mark','mint_location','denomination',
        'coin_variety_1','coin_variety_2','grade','designation_abbrivation','strike_type',
        'circulated_or_uncirculated','style','composition','fineness','single_coin_or_set',
        'country_of_manufacture','title_suffix','total_precious_metal_content','package_weight'];

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

    /* Strip the shared leading words so the coin box shows only what DIFFERS
       (e.g. "VAM-38 MS" instead of the whole "1878 7/8TF $1 Strong, 7/5, ..."). */
    function sblCoinDisplays(items){
        if (items.length < 2){ items.forEach(function(it){ it.display = it.label; }); return items; }
        var toks = items.map(function(it){ return String(it.label).split(/\s+/); });
        var min = Math.min.apply(null, toks.map(function(t){ return t.length; }));
        var common = 0;
        for (var i = 0; i < min; i++){
            var w = toks[0][i];
            if (toks.every(function(t){ return t[i] === w; })) common++; else break;
        }
        if (common >= min) common = min - 1;   // never blank out an entry entirely
        items.forEach(function(it, idx){
            it.display = (common > 0 ? toks[idx].slice(common).join(' ') : it.label) || it.label;
        });
        return items;
    }

    /* Level 1 - the broad trees (US Coins, US Currency, World Coins, World
       Currency). Native <select>: opens on click, no typing. 0 API calls. */
    function sblLoadRoots(){
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsRoots' }, function(res){
            var sel = $('#gs-root').empty().append('<option value="">1. Tree&hellip;</option>');
            $.each(res.matches || [], function(i, r){
                sel.append('<option value="' + sblEsc(r.path) + '">' + sblEsc(r.name) + '</option>');
            });
        }, 'json');
        $('#gs-root').on('change', function(){
            sblRootPath = $(this).val();
            $('#gs-series').val('').prop('disabled', !sblRootPath);
            sblResetBelowSeries();
            if (sblRootPath) $('#gs-series').focus();
        });
    }
    /* Level 2 - the coin-holding series under the chosen tree. Searchable box
       that opens its dropdown on focus. 0 API calls. */
    function sblSeriesAutocomplete(){
        $('#gs-series').autocomplete({
            minLength: 0, delay: 200,
            source: function(req, resp){
                if (!sblRootPath){ resp([]); return; }
                $.post('SellbriteBulkLoader_ajax.php', { action:'gsSeries', root:sblRootPath, q:req.term }, function(res){
                    resp($.map(res.matches || [], function(c){
                        return { label: c.name, value: c.name, path: c.path, count: c.count };
                    }));
                }, 'json');
            },
            select: function(e, ui){
                sblCurPath = ui.item.path || '';
                $('#gs-series').val(ui.item.value);
                $('#f_category_name').val(ui.item.value).trigger('change');
                sblResetBelowSeries();
                sblLoadYears();
                $('#gs-year, #gs-coin').prop('disabled', false);
                $('#gs-coin').focus();
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.label)
                     + (item.count ? ' <span class="gs-path">' + item.count + ' coins</span>' : '') + '</div>').appendTo(ul);
        };
        $('#gs-series').on('focus', function(){ if (sblRootPath) $(this).autocomplete('search', $(this).val()); });
    }
    /* Year dropdown for the chosen series (distinct, deduplicated). */
    function sblLoadYears(){
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsNodeYears', path:sblCurPath }, function(res){
            var sel = $('#gs-year').empty().append('<option value="">Year (all)</option>');
            $.each(res.years || [], function(i, y){ sel.append('<option value="' + y + '">' + y + '</option>'); });
        }, 'json');
        $('#gs-year').off('change').on('change', function(){
            sblCurYear = $(this).val();
            $('#gs-coin').val('');
            sblPendingGsId = 0; $('#gs-autofill').prop('disabled', true); sblMarkGsFields(false);
            $('#gs-coin').focus();
        });
    }
    /* Level 4 - coins under the series (optionally one year). Labels are trimmed
       to just the distinguishing part. Opens on focus. 0 API calls. */
    function sblCoinAutocomplete(){
        $('#gs-coin').autocomplete({
            minLength: 0, delay: 200,
            source: function(req, resp){
                if (!sblCurPath){ resp([]); return; }
                $.post('SellbriteBulkLoader_ajax.php',
                    { action:'gsCoins', path:sblCurPath, year:sblCurYear, q:req.term }, function(res){
                    var items = $.map(res.matches || [], function(c){
                        return { label: c.label, value: c.label, gs_id: c.gs_id };
                    });
                    resp(sblCoinDisplays(items));
                }, 'json');
            },
            select: function(e, ui){
                sblPendingGsId = ui.item.gs_id;
                $('#gs-coin').val(ui.item.display || ui.item.label);
                $('#gs-autofill').prop('disabled', !sblPendingGsId);
                sblMarkGsFields(!!sblPendingGsId);
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.display || item.label) + '</div>').appendTo(ul);
        };
        $('#gs-coin').on('focus', function(){ if (sblCurPath) $(this).autocomplete('search', $(this).val()); });
    }
    function sblResetBelowSeries(){
        sblCurYear = ''; sblPendingGsId = 0;
        $('#gs-year').empty().append('<option value="">Year (all)</option>').prop('disabled', true);
        $('#gs-coin').val('').prop('disabled', true);
        $('#gs-autofill').prop('disabled', true);
        sblMarkGsFields(false);
    }
    /* Autofill button: pull full collectible + pricing from GreySheet and fill. */
    function sblGsAutofill(){
        if (!sblPendingGsId) return;
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsImport', gs_id:sblPendingGsId, grade:$('#f_grade').val() || '' }, function(res){
            sblRenderCalls(res.calls, res.total_calls);
            sblRenderRaw(res.raw);
            sblGsHandle(res, $('#gs-coin').val());
        }, 'json');
    }
    /* Full GreySheet collectible + pricing response, for reference. */
    function sblRenderRaw(raw){
        $('#gs-raw').text(raw ? JSON.stringify(raw, null, 2) : 'No data returned.');
    }
    /* Small box: the API calls that Autofill made and what each returned, plus
       the running total of GreySheet calls used this session. */
    function sblRenderCalls(calls, total){
        if (total !== undefined && total !== null){
            $('#gs-total').text('· ' + Number(total).toLocaleString() + ' used this session');
        }
        var ul = $('#gs-apilog').empty();
        if (!calls || !calls.length){ ul.append('<li style="color:#5f6b62">No calls recorded.</li>'); return; }
        $.each(calls, function(i, c){
            ul.append('<li><div class="ep">' + sblEsc(c.call) + (c.ms ? ' <span class="ms">' + c.ms + 'ms</span>' : '')
                    + '</div><div class="got">&rarr; ' + sblEsc(c.got) + '</div></li>');
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

        // Tree -> Series -> Year -> Coin drill-down
        sblLoadRoots();
        if ($.fn.autocomplete){ sblSeriesAutocomplete(); sblCoinAutocomplete(); }
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
