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
    var sblPreviewImg = '';    // GreySheet reference image for the preview pane (display only)
    var sblAutofilled = false; // once true, AUTO badges track actual values

    /* After an autofill, only fields that ACTUALLY got a value keep the blue
       AUTO look - GreySheet didn't provide the empty ones. */
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
    /* Restore the default AUTO look on all auto-eligible fields (blank form). */
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
    /* Export the market picked in the home-screen dropdown: only that market's
       SKUs (All-markets ones included) and only that market's columns. */
    function sblExport(){
        var m = $('#export-market').val() || 'all';
        window.location = 'SellbriteBulkLoader_ajax.php?action=export&market=' + encodeURIComponent(m);
    }
    function sblSearch(){ window.location = '?q=' + encodeURIComponent($('#sbl-search').val()); }

    /* Listing content only: Gemini writes the EMPTY Description / Extended
       Description / Feature 4 (collector's note) in the house layout. Typed
       text is never touched; Name and features 1/2/3/5 stay formula-built. */
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
    }
    function sblNew(){
        sblClearForm();
        // The SKU's market is picked with the form's own Market picker (the
        // home-screen dropdown belongs to Export now); starts as All markets.
        sblMarketApply();
        $('#formTitle').text('New SKU');
        sblShow('form');
        sblRecompute();
    }
    /* Marketplace picker: reveal only the chosen market's specific fields.
       "All" shows every market field; a specific market shows just its own. */
    var SBL_MARKET_FIELDS = {
        amazon: ['search_terms'],                      // Search Terms are Amazon-specific
        ebay:   ['ebay_coin_condition_type',
                 'ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
                 'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'],
        walmart: []
    };
    var SBL_ALL_MARKET_FIELDS = [
        'search_terms',
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'];
    // The eBay grading fields are coin-specific and drop off for currency;
    // Search Terms apply to anything sold on Amazon.
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
        sblRecompute();   // re-validate for the chosen market
    }
    /* Delete every SKU from the inventory (home menu). */
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
    /* Form fields + the marketplace picker (which lives in the toolbar). */
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
            sblUpsertListRow(res.row);       // update the inventory row in place (AJAX, no reload)
            sblBackToList();                 // back to the main inventory page
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
    /* Insert or update one row in the inventory table without reloading. */
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
    function sblFillFromRow(row){
        $.each(row || {}, function(k,v){
            var el = document.getElementById('f_' + k);
            if (el && v !== null && v !== '') {
                // Selects (e.g. SKU of Parent Product): add the option if missing
                // so unmatched GreySheet names still land.
                if (el.tagName === 'SELECT' && !el.querySelector('option[value="' + CSS.escape(String(v)) + '"]')){
                    var o = document.createElement('option'); o.value = o.textContent = v; el.appendChild(o);
                }
                el.value = v;
            }
        });
        sblYearRefresh(row && row.year);   // constrain Year to the series' real years
        sblFieldVisibility();              // show only this category's boxes
        sblMarketApply();                  // and only the chosen market's boxes
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

    /* Auto fields that get the blue "AUTO" preview when a coin is picked.
       SKU/Price/Quantity/Cost are excluded - they're the required fields the
       operator confirms (autofill still suggests price/cost). */
    /* coin_type / grade / brand still autofill but carry NO badge - they are
       operator-owned picks (also skipped in the display's badge rendering). */
    var SBL_GS_FIELDS = ['category_name','year','mint_mark','mint_location','denomination',
        'coin_variety_1','coin_variety_2','designation_abbrivation','strike_type',
        'circulated_or_uncirculated','composition','fineness','diameter','weight',
        'precious_metal_content','total_precious_metal_content','single_coin_or_set','set_count',
        'country_of_manufacture','bullion_shape','coin_design','condition',
        'paper_money_grade_designation','paper_money_type','paper_money_series_designation',
        'package_weight','exact_image','name','description','extended_description',
        'feature_1','feature_2','feature_3','feature_4','feature_5','search_terms',
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition'];

    /* Category-specific boxes (the spreadsheet's column annotations): only show
       the fields that apply to what was picked - starting with the TREE
       (U.S./World Coins vs U.S./World Currency), then the series/category. */
    var SBL_CAT_FIELDS = {
        paper:   ['paper_money_grade_designation','paper_money_type','paper_money_series_designation'],
        // The sheet's whole "US Coin and World Coin" block - none of it shows
        // for the Currency trees.
        coin:    ['coin_type','denomination','year','mint_mark','mint_location',
                  'coin_variety_1','coin_variety_2','grade','designation_abbrivation',
                  'circulated_or_uncirculated','strike_type','certification','certification_number',
                  'composition','fineness','precious_metal_content','single_coin_or_set',
                  'total_precious_metal_content','diameter','weight',
                  'ebay_coin_condition_type','ebay_graded_coin_letter_grade',
                  'ebay_graded_coin_numerical_grade','ebay_graded_coin_professional_grader',
                  'z_ebay_ungraded_coin_condition'],
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
            $('#gs-series').val('').data('sblPicked', 0).prop('disabled', !sblRootPath);
            sblResetBelowSeries();
            sblFieldVisibility();   // Currency trees swap in the paper-money boxes right away
            sblMarketApply();       // and drop the coin-only market fields
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
                    // A search answer that lands AFTER the user already picked
                    // would re-open the menu - swallow it.
                    if ($('#gs-series').data('sblPicked')){ resp([]); return; }
                    resp($.map(res.matches || [], function(c){
                        return { label: c.name, value: c.name, path: c.path, count: c.count };
                    }));
                }, 'json');
            },
            select: function(e, ui){
                sblCurPath = ui.item.path || '';
                $('#gs-series').data('sblPicked', 1).val(ui.item.value).autocomplete('close').blur();
                var cel = document.getElementById('f_category_name');
                if (cel && cel.tagName === 'SELECT' && !cel.querySelector('option[value="' + CSS.escape(ui.item.value) + '"]')){
                    var co = document.createElement('option'); co.value = co.textContent = ui.item.value; cel.appendChild(co);
                }
                $('#f_category_name').val(ui.item.value).trigger('change');
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
    /* Year combo for the chosen series (distinct, deduplicated) - searchable
       by typing, same as Series and Coin. */
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
                // Default state is every year; picking "All years" restores it.
                if (!t || t.toLowerCase() === 'all years'){ resp(['All years'].concat(sblYearList)); return; }
                resp($.grep(sblYearList, function(y){ return y.indexOf(t) !== -1; }));
            },
            select: function(e, ui){
                var all = ui.item.value === 'All years';
                $('#gs-year').data('sblPicked', 1).val(all ? '' : ui.item.value).autocomplete('close');
                sblYearPicked(all ? '' : String(ui.item.value));
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
            if (sblCurPath && !$(this).data('sblPicked')) $(this).autocomplete('search', $(this).val());
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
                    // Coin Type / Denomination narrow to the chosen country's
                    // valid values; unknown/blank country keeps the full list.
                    if (typeof SBL_COUNTRY_OPTS !== 'undefined' && SBL_COUNTRY_OPTS[inp.attr('name')]){
                        var c = ($('#f_country_of_manufacture').val() || '').trim();
                        var m = SBL_COUNTRY_OPTS[inp.attr('name')][c];
                        if (m && m.length) pool = m;
                    }
                    resp($.grep(pool, function(v){ return !t || v.toLowerCase().indexOf(t) !== -1; }));
                },
                select: function(){
                    // Value is applied right after this handler - recompute then.
                    var el = $(this); setTimeout(function(){ el.trigger('change'); }, 0);
                }
            }).autocomplete('widget').addClass('sbl-combo');
            // Clicking shows the whole list when the box already holds a valid
            // pick (so it's easy to change); otherwise it filters by the text.
            inp.on('mousedown focus', function(){
                if (inp.prop('disabled') || inp.autocomplete('widget').is(':visible')) return;
                var v = inp.val();
                inp.autocomplete('search', (v && opts.indexOf(v) !== -1) ? '' : v);
            });
        });
    }
    function sblResetBelowSeries(){
        sblCurYear = ''; sblPendingGsId = 0; sblYearList = [];
        $('#gs-year').val('').data('sblPicked', 0).prop('disabled', true);
        $('#gs-coin').val('').data('sblPicked', 0).prop('disabled', true);
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
                   type:'info', showCancelButton:true,
                   confirmButtonText:'Generate with AI', cancelButtonText:'Cancel', closeOnConfirm:true },
            function(go){ if (go) sblGsGenerate(hint); });
            return;
        }
        if (res.returnClass === 'error'){ swal('Import failed', res.message || 'GreySheet returned nothing.', 'error'); return; }
        sblPreviewImg = res.preview_image || '';   // GreySheet image, display only
        sblFillFromRow(res.row);
        swal({ title:'Imported', text:'Review the highlighted fields, then Save.',
               type: res.returnClass === 'success' ? 'success' : 'warning', timer:1800, showConfirmButton:false });
    }
    function sblGsGenerate(hint){
        $.post('SellbriteBulkLoader_ajax.php', { action:'gsGenerate', hint:hint }, function(res){
            if (res.returnClass === 'error'){ swal('Generation failed', res.message || 'The AI returned nothing.', 'error'); return; }
            sblFillFromRow(res.row);
            swal({ title:'AI draft ready', text:'Double-check the facts, then Save.',
                   type: res.returnClass === 'success' ? 'success' : 'warning', timer:1800, showConfirmButton:false });
        }, 'json');
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
        // Preview always shows the GreySheet reference image (display only); the
        // SKU-based product_image URLs aren't reachable here so we never use them.
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

        // Tree -> Series -> Year -> Coin drill-down
        sblLoadRoots();
        if ($.fn.autocomplete){ sblSeriesAutocomplete(); sblYearAutocomplete(); sblCoinAutocomplete(); sblFieldCombos(); }
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
