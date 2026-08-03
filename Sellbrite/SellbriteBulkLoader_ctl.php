<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ctl.php      *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/01/2026                         *  -->
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
    var sblPreviewImg = '';
    var sblAutofilled = false;

    // after autofill only boxes that actually got a value keep the AUTO look
    function sblSyncAutoBadges(){
        $('#sku-form [data-name]').each(function(){
            var name = this.getAttribute('data-name');
            if (SBL_GS_FIELDS.indexOf(name) < 0) return;
            var field = this.closest('.field'); if (!field) return;
            var has = String(this.value || '').trim() !== '';
            field.classList.toggle('is-auto', has);
            if (!has) field.classList.remove('is-gsauto');
            var badge = field.querySelector('.badge.auto, .badge.gsauto');
            if (badge) badge.style.display = has ? '' : 'none';
        });
    }
    
    // restore the AUTO look on all auto-eligible fields (blank form)
    function sblResetAutoBadges(){
        $('#sku-form [data-name]').each(function(){
            var name = this.getAttribute('data-name');
            if (SBL_GS_FIELDS.indexOf(name) < 0) return;
            var field = this.closest('.field'); if (!field) return;
            field.classList.add('is-auto'); field.classList.remove('is-gsauto');
            var badge = field.querySelector('.badge.auto, .badge.gsauto');
            if (badge) badge.style.display = '';
        });
    }

    /* ---- view switching ---- */
    function sblShow(view){
        $("#listView").toggle(view === 'list');
        $("#formView").toggle(view === 'form');
    }

    function sblBackToList(){ sblShow('list'); }
    
    // export only the picked market's SKUs and columns
    function sblExport(){
        var m = $('#export-market').val() || 'all';
        window.location = 'SellbriteBulkLoader_ajax.php?action=export&market=' + encodeURIComponent(m);
    }

    function sblSearch(){ window.location = '?q=' + encodeURIComponent($('#sbl-search').val()); }

     // Gemini writes ONLY the empty description / extended description / feature 4
    function sblListingGenerate(){
        var need = ['description','extended_description','feature_4'].filter(function(n){
            var el = document.getElementById('f_' + n);
            var v = el ? String(el.value || '').trim() : '';
            return el && (v === '' || v.indexOf('***') === 0);
        });
        if (!need.length){ $('#genai-msg').text('Nothing empty - Description, Extended Description and Feature 4 are all filled.'); return; }
        $('#genai-btn').prop('disabled', true);
        $('#genai-msg').text('Writing ' + need.join(', ') + '…');
        $.post('SellbriteBulkLoader_ajax.php', sblFormSerialize() + '&action=gsListingFill', function(res){
            $('#genai-btn').prop('disabled', false);
            if (res.returnClass !== 'success'){ $('#genai-msg').text(res.message || 'Generation failed.'); return; }
            var wrote = [];
            $.each(res.row || {}, function(k, v){
                var el = document.getElementById('f_' + k);
                var cur = el ? String(el.value || '').trim() : 'x';
                if (el && v && (cur === '' || cur.indexOf('***') === 0)){ el.value = v; wrote.push(k); }
            });
            $('#genai-msg').text(wrote.length ? 'Wrote ' + wrote.join(', ') + '.' : 'Nothing came back - fill manually.');
            sblRecompute();
        }, 'json').fail(function(){
            $('#genai-btn').prop('disabled', false);
            $('#genai-msg').text('Generation failed - server error.');
        });
    }

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
        sblPreviewImg = '';
        var pv = document.getElementById('pv-img'); if (pv){ pv.removeAttribute('src'); pv.classList.add('broken'); }
        $('#f_marketplace').val('');
        sblAutofilled = false;
        sblResetAutoBadges();
        sblFieldVisibility();
        sblMarketApply();
        sblCertNumGate(false);
    }
    // Emptying the LCC SKU or the Series box by hand means "start this one over".
    // Only a box that HELD something and was cleared counts, so the ordinary path -
    // look up an LCC SKU, then Autofill from GreySheet - never triggers it.
    // Editing an existing SKU keeps its id and title so Save still updates that row.
    function sblClearEntry(){
        var id = $('#f_id').val(), title = $('#formTitle').text();
        sblClearForm();
        if (id) { $('#f_id').val(id); $('#formTitle').text(title); }
        $('#lcc-sku').val('');
        sblLccData = null; sblLccFields = {};
        $('#lcc-sku, #gs-series').data('sblHad', false);
        sblRecompute();
    }

    // clearing is driven by the box going empty, so remember what was in it
    function sblClearOnEmpty(sel){
        $(sel).each(function(){ $(this).data('sblHad', String(this.value || '').trim() !== ''); });
        $(sel).on('input change', function(){
            var has = String(this.value || '').trim() !== '';
            if (!has && $(this).data('sblHad')) { sblClearEntry(); }
            $(this).data('sblHad', has);
        });
    }

    function sblNew(){
        sblClearForm();
        // market starts as All; picked with the form's own Market picker
        sblMarketApply();
        $('#formTitle').text('New SKU');
        sblShow('form');
        sblRecompute();
    }
    
    // show only the chosen market's specific fields ("All" shows every one)
    var SBL_MARKET_FIELDS = {
        amazon: ['search_terms'],
        ebay:   ['ebay_coin_condition_type',
                 'ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
                 'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'],
        walmart: []
    };

    var SBL_ALL_MARKET_FIELDS = [
        'search_terms',
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'];
    
    // eBay grading fields are coin-only; search terms are Amazon-wide
    var SBL_COIN_ONLY_MARKET_FIELDS = [
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'];

    function sblMarketApply(){
        var m = $('#f_marketplace').val() || '';
        var cat = (($('#f_category_name').val() || '') + ' ' + sblCurPath + ' ' + sblRootPath).toLowerCase();
        var paper = /currency|paper money|banknote|\bnote\b/.test(cat);
        var show = (m === '') ? SBL_ALL_MARKET_FIELDS.slice() : (SBL_MARKET_FIELDS[m] || []).slice();
        if (paper) show = show.filter(function(n){ return SBL_COIN_ONLY_MARKET_FIELDS.indexOf(n) < 0; });
        SBL_ALL_MARKET_FIELDS.forEach(function(n){
            var el = document.querySelector('#sku-form [data-name="' + n + '"]');
            if (!el) return; var field = el.closest('.field'); if (!field) return;
            field.style.display = (show.indexOf(n) >= 0) ? '' : 'none';
        });
        sblRecompute();
    }
    
    // delete every SKU (home menu)
    function sblDeleteAll(){
        swal({ title:'Delete ALL SKUs?', text:'This permanently removes every record and cannot be undone.',
               type:'warning', showCancelButton:true, confirmButtonColor:'#c0392b',
               confirmButtonText:'Delete all', cancelButtonText:'Cancel', closeOnConfirm:true },
        function(ok){
            if (!ok) return;
            $.post('SellbriteBulkLoader_ajax.php', { action:'deleteAll' }, function(res){
                if (res && res.returnClass === 'error'){ swal('Not deleted', res.message || 'Database error.', 'error'); return; }
                $('#sku-tbody').empty(); $('#list-table').hide(); $('#list-empty').show();
                swal({ title:'Deleted', text:'All SKUs removed.', type:'success', timer:1500, showConfirmButton:false });
            }, 'json');
        });
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
            $('#f_marketplace').val(res.row.marketplace || '');
            sblMarketApply();
            $('#formTitle').text('Edit SKU - ' + (res.row.sku || ''));
            sblShow('form');
            sblRecompute();
        }, 'json');
    }

    /* ---- save / delete (AJAX, no page reload) ---- */
    // form fields + the toolbar marketplace picker
    function sblFormSerialize(){
        return $('#sku-form').serialize() + '&marketplace=' + encodeURIComponent($('#f_marketplace').val() || '');
    }

    function sblSave(){
        var data = sblFormSerialize() + '&action=save';
        $.post('SellbriteBulkLoader_ajax.php', data, function(res){
            if (!res || res.returnClass === 'error'){
                swal('Not saved', (res && res.message) || 'The database rejected the save (no DB connection?).', 'error');
                return;
            }
            // update the inventory row in place
            sblUpsertListRow(res.row);
            // back to the main loader sheet page
            sblBackToList();
            if (res.returnClass === 'warning'){
                swal({ title:'Saved with warnings',
                       text:'Still empty: ' + (res.missing || []).slice(0, 12).join(', ')
                            + ((res.missing || []).length > 12 ? ' …' : ''),
                       type:'warning' });
            } else {
                swal({ title:'Saved', text:'SKU saved.', type:'success', timer:1500, showConfirmButton:false });
            }
        }, 'json');
    }
    function sblDelete(id, sku){
        swal({ title:'Delete ' + sku + '?', text:'This permanently removes the record.',
               type:'warning', showCancelButton:true, confirmButtonColor:'#c0392b',
               confirmButtonText:'Delete', cancelButtonText:'Cancel', closeOnConfirm:true },
        function(ok){
            if (!ok) return;
            $.post('SellbriteBulkLoader_ajax.php', { action:'delete', id:id }, function(res){
                if (res && res.returnClass === 'error'){ swal('Not deleted', res.message || 'Database error.', 'error'); return; }
                var tr = document.getElementById('sku-row-' + id); if (tr) tr.remove();
                if (!document.querySelector('#sku-tbody tr')){ $('#list-table').hide(); $('#list-empty').show(); }
            }, 'json');
        });
    }
    
    // insert or update one grid row without reloading
    function sblUpsertListRow(row){
        if (!row || !row.id) return;
        var price = row.price ? '$' + sblEsc(row.price) : '—';
        var qty   = (row.quantity !== undefined && row.quantity !== null && row.quantity !== '') ? sblEsc(row.quantity) : '—';
        var mkt = row.marketplace ? row.marketplace.charAt(0).toUpperCase() + row.marketplace.slice(1) : 'All';
        var cells = '<td>' + sblEsc(mkt) + '</td>'
                  + '<td><span class="sku-link" onclick="sblEdit(' + row.id + ')">' + sblEsc(row.sku) + '</span></td>'
                  + '<td>' + sblEsc(row.category_name || '') + '</td>'
                  + '<td>' + sblEsc(row.name || '') + '</td>'
                  + '<td>' + sblEsc(row.grade || '') + '</td>'
                  + '<td class="num">' + price + '</td><td class="num">' + qty + '</td>'
                  + '<td>' + sblEsc(row.updated_at || '') + '</td>'
                  + '<td style="text-align:right"><button type="button" class="mini" onclick="sblEdit(' + row.id + ')">Edit</button> '
                  + '<button type="button" class="mini danger" onclick="sblDelete(' + row.id + ',&quot;' + sblEsc(row.sku) + '&quot;)">Delete</button></td>';
        var tr = document.getElementById('sku-row-' + row.id);
        if (tr){ tr.innerHTML = cells; return; }
        $('#list-empty').hide(); $('#list-table').show();
        tr = document.createElement('tr'); tr.id = 'sku-row-' + row.id; tr.innerHTML = cells;
        var tb = document.getElementById('sku-tbody'); if (tb) tb.insertBefore(tr, tb.firstChild);
    }

    /* ---- coin finder: memory dropdown -> API auto-fill ---- */
    function sblEsc(s){ return $('<div>').text(s == null ? '' : s).html().replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    
    // cert number only exists once a grading service is picked (server sends the yellow nudge)
    function sblCertNumGate(clearIt){
        var box = $('#f_certification_number'); if (!box.length) return;
        var cert = String($('#f_certification').val() || '').trim().toLowerCase();
        var certified = cert !== '' && cert !== 'uncertified' && cert !== 'u.s. mint';
        box.closest('.field').toggleClass('cert-locked', !certified);

        // readonly not disabled - serialize() drops disabled inputs
        box.prop('readOnly', !certified);

        // wipe only when the operator switches back to raw
        if (!certified && clearIt && box.val()) box.val('');
    }
    function sblFillFromRow(row){
        $.each(row || {}, function(k,v){
            var el = document.getElementById('f_' + k);

            // anything already filled is left alone - typed, LCC or a previous import
            if (el && String(el.value || '').trim() !== '') return;
            if (el && v !== null && v !== '') {

                 // selects: add missing options so unmatched names still land
                if (el.tagName === 'SELECT' && !el.querySelector('option[value="' + CSS.escape(String(v)) + '"]')){
                    var o = document.createElement('option'); o.value = o.textContent = v; el.appendChild(o);
                }
                el.value = v;
            }
        });
        sblYearRefresh(row && row.year);   // constrain Year to the series' real years
        sblFieldVisibility();              // show only this category's boxes
        sblMarketApply();                  // and only the chosen market's boxes
        sblCertNumGate(false);             // lock/unlock Cert Number for this row
        sblLccApply();                     // LCC values fill only what GreySheet left blank
        sblAutofilled = true;              // AUTO badges now track what actually filled
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
        $('#sku-form').on('change', '#f_category_name', function(){ sblYearRefresh(); sblFieldVisibility(); });
        $('#sku-form').on('change', '#f_single_coin_or_set', sblFieldVisibility);
        sblFieldVisibility();
    });

    /* ---- drill-down: Tree -> Series -> Year -> Coin -> Autofill ---- */
    var sblRootPath = '', sblCurPath = '', sblCurYear = '', sblPendingGsId = 0;

    // store categories never carry dates - strip "(2022-2025)" style ranges
    function sblCleanCategory(name){
        return String(name || '')
            .replace(/\([^)]*\d{4}[^)]*\)/g, ' ')
            .replace(/\b\d{4}\s*[-–]\s*(?:\d{2,4}|present|date)\b/gi, ' ')
            .replace(/\s+/g, ' ').replace(/^[\s-]+|[\s-]+$/g, '');
    }

    // fields that get the blue AUTO badge on autofill (operator-owned picks carry no badge)
    var SBL_GS_FIELDS = ['category_name','year','mint_mark','mint_location','denomination',
        'coin_variety_1','coin_variety_2','designation_abbrivation','strike_type',
        'circulated_or_uncirculated','composition','fineness','diameter','weight',
        'precious_metal_content','total_precious_metal_content','single_coin_or_set','set_count',
        'country_of_manufacture','bullion_shape','coin_design',
        'paper_money_grade_designation','paper_money_type','paper_money_series_designation',
        'package_weight','exact_image','name','description','extended_description',
        'feature_1','feature_2','feature_3','feature_4','feature_5','search_terms',
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'];

     // category-specific boxes: only show the fields that apply to the picked tree/series
    var SBL_CAT_FIELDS = {
        paper:   ['paper_money_grade_designation','paper_money_type','paper_money_series_designation'],
        // the coin block hides for Currency trees; coin_type is NOT here (shows for every tree)
        coin:    ['mint_mark','mint_location','designation_abbrivation','strike_type',
                  'fineness','precious_metal_content','total_precious_metal_content',
                  'diameter','weight'],
        bullion: ['bullion_shape'],
        cob:     ['coin_design'],
        set:     ['set_count'],
        advent:  ['advent_calendar_type','advent_calendar_occasion','advent_calendar_material',
                  'advent_calendar_number_of_items','advent_calendar_shape','advent_calendar_theme',
                  'advent_calendar_item_height','advent_calendar_item_length',
                  'advent_calendar_item_width','advent_calendar_item_weight'],
        watch:   ['watch_band_material','watch_band_type','watch_band_width','watch_case_material',
                  'watch_case_size','watch_department','watch_display_type',
                  'watch_manufacturer_warranty','watch_movement_type','watch_water_resistance'],
        stamp:   ['stamp_color','stamp_quality','stamp_type'],
        nativity:['nativity_item_type']
    };

    function sblFieldVisibility(){
        var cat  = (($('#f_category_name').val() || '') + ' ' + sblCurPath + ' ' + sblRootPath).toLowerCase();
        var paper = /currency|paper money|banknote|\bnote\b/.test(cat);
        var show = {
            paper:   paper,
            coin:    !paper,   // coin-only boxes disappear for the Currency trees
            bullion: !paper && /bullion/.test(cat),
            cob:     !paper && (/\bcob\b|pillar|spanish colonial/.test(cat)),
            set:     ($('#f_single_coin_or_set').val() || '') === 'Set' || /proof set|mint set/.test(cat),
            // Other product types (Des's store categories beyond coins/notes):
            advent:  /advent/.test(cat),
            watch:   /watch|wristwatch/.test(cat),
            stamp:   /\bstamp|postage/.test(cat),
            nativity:/nativity/.test(cat)
        };
        $.each(SBL_CAT_FIELDS, function(group, names){
            $.each(names, function(i, n){
                var el = document.querySelector('#sku-form [data-name="' + n + '"]');
                if (!el) return;
                var f = el.closest('.field');
                if (f) f.style.display = show[group] ? '' : 'none';
            });
        });
        // The whole "Other product types" section only exists when one applies.
        var other = document.getElementById('other-products-sec');
        if (other){
            var any = show.advent || show.watch || show.stamp || show.nativity;
            other.style.display = any ? '' : 'none';
            if (any) other.open = true;
        }
    }

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

    // strip shared leading words so the coin menu shows only what differs
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

    // level 1: the four trees; native select, 0 API calls
    function sblLoadRoots(){
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsRoots' }, function(res){
            var sel = $('#gs-root').empty().append('<option value="">1. Tree&hellip;</option>');
            $.each(res.matches || [], function(i, r){
                sel.append('<option value="' + sblEsc(r.path) + '">' + sblEsc(r.name) + '</option>');
            });
        }, 'json');
        $('#gs-root').on('change', function(){
            sblRootPath = $(this).val();
            $('#gs-series').val('').data('sblPicked', 0).prop('disabled', !sblRootPath);
            sblResetBelowSeries();

           // country set ONCE from the tree; world trees get it from the series pick
            if (sblRootPath) $('#f_country_of_manufacture').val(/world/i.test(sblRootPath) ? '' : 'United States');
            sblFieldVisibility();   // Currency trees swap in the paper-money boxes right away
            sblMarketApply();       // and drop the coin-only market fields
            if (sblRootPath) $('#gs-series').focus();
        });
    }

    // level 2: series under the tree, searchable, 0 API calls
    function sblSeriesAutocomplete(){
        $('#gs-series').autocomplete({
            minLength: 0, delay: 200,
            source: function(req, resp){
                if (!sblRootPath){ resp([]); return; }
                $.post('SellbriteBulkLoader_ajax.php', { action:'gsSeries', root:sblRootPath, q:req.term }, function(res){

                    // swallow late answers so the menu doesn't reopen after a pick
                    if ($('#gs-series').data('sblPicked')){ resp([]); return; }
                    resp($.map(res.matches || [], function(c){
                        return { label: c.name, value: c.name, path: c.path, count: c.count };
                    }));
                }, 'json');
            },
            select: function(e, ui){
                sblCurPath = ui.item.path || '';
                $('#gs-series').data('sblPicked', 1).data('sblHad', true).val(ui.item.value).autocomplete('close').blur();
                
                // strip date ranges from the series name right at pick time
                var cat = sblCleanCategory(ui.item.value);
                var cel = document.getElementById('f_category_name');
                if (cel && cel.tagName === 'SELECT' && !cel.querySelector('option[value="' + CSS.escape(cat) + '"]')){
                    var co = document.createElement('option'); co.value = co.textContent = cat; cel.appendChild(co);
                }
                $('#f_category_name').val(cat).trigger('change');
                
                 // country from the memory path: world = 2nd node, U.S. = United States
                var seg = (sblCurPath || '').split(' > ');
                var country = '';
                if (/^world/i.test(seg[0] || '')) country = (seg[1] || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
                else if (/^u\.?s\.?/i.test(seg[0] || '')) country = 'United States';
                if (country) $('#f_country_of_manufacture').val(country);
                sblResetBelowSeries();
                sblLoadYears();
                $('#gs-year, #gs-coin').prop('disabled', false);
                setTimeout(function(){ $('#gs-coin').focus(); }, 0);
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.label)
                     + (item.count ? ' <span class="gs-path">' + item.count + ' coins</span>' : '') + '</div>').appendTo(ul);
        };
        $('#gs-series').on('focus', function(){
            if (sblRootPath && !$(this).data('sblPicked')) $(this).autocomplete('search', $(this).val());
        });
        // Typing or clicking back in means the user wants the list again.
        $('#gs-series').on('input mousedown', function(){ $(this).data('sblPicked', 0); });
    }

    // year combo for the chosen series, searchable
    var sblYearList = [];
    function sblLoadYears(){
        sblYearList = [];
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsNodeYears', path:sblCurPath }, function(res){
            sblYearList = $.map(res.years || [], function(y){ return String(y); });
        }, 'json');
    }

    function sblYearPicked(y){
        sblCurYear = y;
        $('#gs-coin').val('').data('sblPicked', 0);
        sblPendingGsId = 0; $('#gs-autofill').prop('disabled', true); sblMarkGsFields(false);
    }

    function sblYearAutocomplete(){
        $('#gs-year').autocomplete({
            minLength: 0, delay: 0,
            source: function(req, resp){
                var t = (req.term || '').trim();
                resp($.grep(sblYearList, function(y){ return !t || y.indexOf(t) !== -1; }));
            },
            select: function(e, ui){
                $('#gs-year').data('sblPicked', 1).val(ui.item.value).autocomplete('close');
                sblYearPicked(String(ui.item.value));
                setTimeout(function(){ $('#gs-coin').focus(); }, 0);
                return false;
            }
        }).autocomplete('widget').addClass('sbl-combo');
        $('#gs-year').on('focus mousedown', function(){
            if (!sblCurPath || $(this).data('sblPicked') || $(this).autocomplete('widget').is(':visible')) return;
            $(this).autocomplete('search', $(this).val());
        });
        // Typing (or clearing) filters the coins too, even without a pick.
        $('#gs-year').on('input', function(){
            $(this).data('sblPicked', 0);
            sblYearPicked(this.value.trim());
        });
    }

    /* Level 4 - coins under the series (optionally one year). Labels are trimmed
       to just the distinguishing part. Opens on focus. 0 API calls. */
    function sblCoinAutocomplete(){
        $('#gs-coin').autocomplete({
            minLength: 0, delay: 200,
            source: function(req, resp){
                // an LCC lookup already picked the candidates - offer those instead of the tree's coins
                if (sblLccMatches.length){
                    var t = (req.term || '').toLowerCase();
                    resp(sblCoinDisplays($.map(sblLccMatches, function(c){
                        return { label: c.label, value: c.label, gs_id: c.gs_id };
                    })).filter(function(it){ return !t || it.label.toLowerCase().indexOf(t) !== -1; }));
                    return;
                }
                if (!sblCurPath){ resp([]); return; }
                $.post('SellbriteBulkLoader_ajax.php',
                    { action:'gsCoins', path:sblCurPath, year:sblCurYear, q:req.term }, function(res){
                    // Late answer after the user already picked - swallow it
                    // so the menu doesn't pop back open.
                    if ($('#gs-coin').data('sblPicked')){ resp([]); return; }
                    var items = $.map(res.matches || [], function(c){
                        return { label: c.label, value: c.label, gs_id: c.gs_id };
                    });
                    resp(sblCoinDisplays(items));
                }, 'json');
            },
            select: function(e, ui){
                sblPendingGsId = ui.item.gs_id;
                $('#gs-coin').data('sblPicked', 1).val(ui.item.display || ui.item.label).autocomplete('close');
                $('#gs-autofill').prop('disabled', !sblPendingGsId);
                sblMarkGsFields(!!sblPendingGsId);
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.display || item.label) + '</div>').appendTo(ul);
        };
        $('#gs-coin').on('focus', function(){
            if ((sblCurPath || sblLccMatches.length) && !$(this).data('sblPicked')) $(this).autocomplete('search', $(this).val());
        });
        $('#gs-coin').on('input mousedown', function(){ $(this).data('sblPicked', 0); });
    }

    /* The valid-value form fields (Grade, Brand, Designation...) use the same
       compact jQuery UI menu as Series/Coin instead of the browser's native
       datalist popup (which can't be styled and renders huge). The operator
       can still type any value manually - the list is only suggestions. */
    function sblFieldCombos(){
        $('#sku-form input[list]').each(function(){
            var inp = $(this), dl = document.getElementById(inp.attr('list'));
            if (!dl) return;
            var opts = $.map(dl.querySelectorAll('option'), function(o){ return o.value; });
            inp.removeAttr('list');   // drop the native popup
            inp.autocomplete({
                minLength: 0, delay: 0,
                source: function(req, resp){
                    var t = (req.term || '').toLowerCase();
                    var pool = opts;
                    // Coin Type pools by the drill-down tree: U.S. Coins vs
                    // U.S. Currency vs World Coins vs World Currency. No tree
                    // picked (manual SKU) = the full list.
                    if (inp.attr('name') === 'coin_type' && typeof SBL_COINTYPE_POOLS !== 'undefined' && sblRootPath){
                        var world = /world/i.test(sblRootPath);
                        var curr  = /currency/i.test(sblRootPath);
                        var tp = SBL_COINTYPE_POOLS[(world ? 'world' : 'us') + '_' + (curr ? 'currency' : 'coins')];
                        if (tp && tp.length) pool = tp;
                    }
                    // Country pools by the tree: U.S. trees are United States;
                    // World trees offer the world countries (the DB2 path
                    // usually fills it before the menu is even needed).
                    if (inp.attr('name') === 'country_of_manufacture' && sblRootPath){
                        pool = /world/i.test(sblRootPath)
                            ? $.grep(opts, function(v){ return v !== 'United States'; })
                            : ['United States'];
                    }
                    // Grade offers only what fits: paper grades for paper money,
                    // coin grades for everything else (certified + raw merged).
                    if (inp.attr('name') === 'grade' && typeof SBL_GRADE_POOLS !== 'undefined'){
                        var cat = (($('#f_category_name').val() || '') + ' ' + ($('#f_paper_money_type').val() || '')
                                   + ' ' + sblCurPath + ' ' + sblRootPath).toLowerCase();
                        var paper = /currency|paper money|banknote|\bnote\b/.test(cat);
                        var base = paper ? 'paper' : 'coin';
                        var gp = (SBL_GRADE_POOLS[base + '_uncertified'] || [])
                                     .concat(SBL_GRADE_POOLS[base + '_certified'] || []);
                        if (gp.length) pool = gp;
                    }
                    resp($.grep(pool, function(v){ return !t || v.toLowerCase().indexOf(t) !== -1; }));
                },
                select: function(){
                    // Value is applied right after this handler - recompute then.
                    var el = $(this); setTimeout(function(){ el.trigger('change'); }, 0);
                }
            }).autocomplete('widget').addClass('sbl-combo');
            // a valid pick shows the whole list on click; otherwise filter by the text
            inp.on('mousedown focus', function(){
                if (inp.prop('disabled') || inp.autocomplete('widget').is(':visible')) return;
                var v = inp.val();
                inp.autocomplete('search', (v && opts.indexOf(v) !== -1) ? '' : v);
            });
        });
    }

    function sblResetBelowSeries(){
        sblCurYear = ''; sblPendingGsId = 0; sblYearList = []; sblLccMatches = []; sblLccData = null;
        $('#gs-year').val('').data('sblPicked', 0).prop('disabled', true);
        $('#gs-coin').val('').data('sblPicked', 0).prop('disabled', true);
        $('#gs-autofill').prop('disabled', true);
        sblMarkGsFields(false);
    }

    /* ---- LCC SKU lookup: find the coin in our own inventory, then hand it to the coin box ---- */
    var sblLccMatches = [], sblLccData = null, sblLccFields = {};

    /* The item master fills EMPTY boxes only - it never edits the LCC SKU box,
       never touches the PCC SKU, and never overwrites anything already typed or
       filled by GreySheet. Runs once at lookup and again after Autofill, since
       Autofill clears the form and GreySheet may leave these blank. */
    function sblLccApply(){
        if (!sblLccData) return;
        var fill = { name:               sblLccData.description,        // IIDESC
                     year:               sblLccData.year,               // IICDAT
                     condition_note:     sblLccData.comment,            // IIICMT
                     original_retail:    sblLccData.retail,             // IIPRCE
                     cost:               sblLccData.cost,               // IIAVGC
                     quantity:           sblLccData.quantity };         // IIQTOH
        // whatever the AI read out of the inventory description, under the same rule
        $.each(sblLccFields || {}, function(name, val){ if (!fill[name]) fill[name] = val; });
        $.each(fill, function(name, val){
            if (!val) return;
            var el = document.getElementById('f_' + name);
            if (!el || String(el.value || '').trim() !== '') return;
            // a select needs the option to exist before the value will take
            if (el.tagName === 'SELECT' && !el.querySelector('option[value="' + CSS.escape(String(val)) + '"]')){
                var o = document.createElement('option'); o.value = o.textContent = val; el.appendChild(o);
            }
            el.value = val;
        });
    }

    // SKU box type-ahead: item numbers straight from the LCC item master
    function sblLccAutocomplete(){
        $('#lcc-sku').autocomplete({
            minLength: 0, delay: 250,
            source: function(req, resp){
                $.post('SellbriteBulkLoader_ajax.php', { action:'lccSearch', q:req.term }, function(res){
                    // swallow a late answer so the menu cannot reopen after a pick
                    if ($('#lcc-sku').data('sblPicked')){ resp([]); return; }
                    resp($.map(res.matches || [], function(r){
                        return { label: r.sku, value: r.sku, desc: r.description, date: r.date };
                    }));
                }, 'json');
            },
            select: function(e, ui){
                $('#lcc-sku').data('sblPicked', 1).data('sblHad', true).val(ui.item.value).autocomplete('close');
                sblLccLookup();
                return false;
            }
        }).autocomplete('instance')._renderItem = function(ul, item){
            return $('<li>').append('<div>' + sblEsc(item.label)
                     + (item.desc ? '<span class="lcc-desc">' + sblEsc(item.desc)
                                  + (item.date ? '  &middot; ' + sblEsc(item.date) : '') + '</span>' : '')
                     + '</div>').appendTo(ul);
        };
        $('#lcc-sku').autocomplete('widget').addClass('sbl-combo');
        $('#lcc-sku').on('input', function(){ $(this).data('sblPicked', 0); });
        // clicking or tabbing into the box opens the list, empty or not.
        // click, not mousedown - the widget closes the menu on a document
        // mousedown, which would shut a menu opened in the same event.
        $('#lcc-sku').on('click focus', function(){
            var $i = $(this);
            if ($i.autocomplete('widget').is(':visible')) return;
            $i.data('sblPicked', 0);
            $i.autocomplete('search', $i.val());
        });
    }

    // Walk the GreySheet drill-down to where the matched coin lives, so the
    // operator is not left to find the tree, series and year by hand.  Skipped
    // when a series is already picked, and the form boxes (category, country)
    // fill only when empty - nothing the LCC item set is overwritten.
    function sblLccDrill(m){
        var path = m.path || '';
        if (!path || String($('#gs-series').val() || '').trim() !== '') return;
        var seg = path.split(' > ');

        // tree: the root option the path starts with
        $('#gs-root option').each(function(){
            if (this.value && path.indexOf(this.value) === 0){
                $('#gs-root').val(this.value); sblRootPath = this.value; return false;
            }
        });

        // series: the node the coin lives under
        sblCurPath = path;
        $('#gs-series').prop('disabled', false)
                       .data('sblPicked', 1).data('sblHad', true)
                       .val(seg[seg.length - 1] || '');
        var cat = sblCleanCategory(seg[seg.length - 1] || '');
        var cel = document.getElementById('f_category_name');
        if (cat && cel && String(cel.value || '').trim() === ''){
            if (cel.tagName === 'SELECT' && !cel.querySelector('option[value="' + CSS.escape(cat) + '"]')){
                var co = document.createElement('option'); co.value = co.textContent = cat; cel.appendChild(co);
            }
            $('#f_category_name').val(cat).trigger('change');
        }

        // country from the path, into an empty box only
        var country = '';
        if (/^world/i.test(seg[0] || '')) country = (seg[1] || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
        else if (/^u\.?s\.?/i.test(seg[0] || '')) country = 'United States';
        var uel = document.getElementById('f_country_of_manufacture');
        if (country && uel && String(uel.value || '').trim() === '') $(uel).val(country);

        sblLoadYears();
        $('#gs-year, #gs-coin').prop('disabled', false);
        // the LCC coin date is the year the pricing call should use
        var y = (sblLccData && sblLccData.year) || '';
        if (y){ sblCurYear = y; $('#gs-year').data('sblPicked', 1).val(y); }
        sblFieldVisibility();
        sblMarketApply();
    }

    function sblLccLookup(){
        var sku = String($('#lcc-sku').val() || '').trim();
        sblLccMatches = []; sblLccData = null; sblLccFields = {};
        if (!sku) return;
        $.post('SellbriteBulkLoader_ajax.php', { action:'lccLookup', sku:sku }, function(res){
            if (res.returnClass !== 'success') return;
            sblLccData = res.item || {};
            sblLccFields = res.fields || {};
            sblLccMatches = res.matches || [];
            sblLccApply();
            sblRecompute();
            if (!sblLccMatches.length) return;
            sblLccDrill(sblLccMatches[0]);
            $('#gs-coin').prop('disabled', false).data('sblPicked', 0).val('');
            if (sblLccMatches.length === 1){
                // exactly one coin fits: pick it and arm Autofill
                sblPendingGsId = sblLccMatches[0].gs_id;
                $('#gs-coin').data('sblPicked', 1).val(sblLccMatches[0].label);
                $('#gs-autofill').prop('disabled', false);
                sblMarkGsFields(true);
            } else {
                setTimeout(function(){ $('#gs-coin').focus(); }, 0);
            }
        }, 'json');
    }

    // Autofill: pull collectible + pricing from GreySheet and fill the form
    function sblGsAutofill(){
        if (!sblPendingGsId) return;
        // Autofill ADDS, it never removes: anything already in a box stays put,
        // so an LCC lookup or a typed correction survives the import.
        var grade = $('#f_grade').val() || '';
        $('#sku-form .field').removeClass('is-ok is-error is-action');
        $('#sku-form .field-msg').text('');
        sblResetAutoBadges();
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsImport', gs_id:sblPendingGsId, grade:grade }, function(res){
            sblRenderCalls(res.calls, res.total_calls);
            sblRenderRaw(res.raw);
            sblGsHandle(res, $('#gs-coin').val());
        }, 'json');
    }

    // full GreySheet response for the raw panel
    function sblRenderRaw(raw){
        $('#gs-raw').text(raw ? JSON.stringify(raw, null, 2) : 'No data returned.');
    }
    
    // the API calls Autofill made + the running session total
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
        // no catalog entry: say so and stop. The AI is not asked to invent a
        // listing for a coin nothing has been read about.
        if (res.returnClass === 'notfound'){
            swal("GreySheet doesn't have this coin", 'Fill the listing in by hand.', 'info');
            return;
        }
        if (res.returnClass === 'error'){ swal('Import failed', res.message || 'GreySheet returned nothing.', 'error'); return; }
        sblPreviewImg = res.preview_image || '';   // GreySheet image, display only
        sblFillFromRow(res.row);
        swal({ title:'Imported', text:'Review the highlighted fields, then Save.',
               type: res.returnClass === 'success' ? 'success' : 'warning', timer:1800, showConfirmButton:false });
    }

    /* ---- live recompute (mirrors the spreadsheet formulas) ---- */
    function sblRecompute(){
        var data = sblFormSerialize() + '&action=compute';
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
            if (sblAutofilled) sblSyncAutoBadges();
        }, 'json');
    }

    function sblPreview(f){
        $('#pv-title').text(f.name || 'Product title appears here');
        $('#pv-desc').text(f.description || '');
        $('#pv-price').text(f.price ? '$' + f.price : '');
        $('#pv-qty').text(f.quantity ? 'Qty ' + f.quantity : '');
        // preview always shows the GreySheet reference image (display only)
        var img = document.getElementById('pv-img');
        if (img && sblPreviewImg && img.getAttribute('src') !== sblPreviewImg){ img.classList.remove('broken'); img.src = sblPreviewImg; }
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

        // certification opens/locks the Cert Number box
        $('#sku-form').on('change', '#f_certification', function(){ sblCertNumGate(true); });
        $('#sku-form').on('input',  '#f_certification', function(){ sblCertNumGate(false); });
        sblCertNumGate(false);

        // LCC SKU box: Enter or leaving the box runs the lookup
        $('#lcc-sku').on('change', sblLccLookup)
                     .on('keydown', function(e){ if (e.which === 13){ e.preventDefault(); sblLccLookup(); } });

        // Tree -> Series -> Year -> Coin drill-down
        sblLoadRoots();
        if ($.fn.autocomplete){ sblSeriesAutocomplete(); sblYearAutocomplete(); sblCoinAutocomplete();
                                sblLccAutocomplete(); sblFieldCombos(); }

        // emptying either starting box means the operator is re-entering this SKU
        sblClearOnEmpty('#lcc-sku, #gs-series');
    });
</script>

<!--  Begin Content Here -->
<?php
if (file_exists('StartBlockScriptB.php')) { require_once 'StartBlockScriptB.php'; }

//***--- Check users authority (10 is the minimum to use LCCOnline) ---***
$authorized = "yes";
if (function_exists('getDB2PConn') && function_exists('chkAutUsr')) {
    $authConn   = getDB2PConn($user, $password);
    $authorized = chkAutUsr($authConn, $user, "LCCONLINE", 10);
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