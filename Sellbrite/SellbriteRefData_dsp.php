<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteRefData_dsp.php         *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
function dspRefData(&$screenData)
{
    $lists = $screenData['lists'] ?? [];
    ?>
<style>
/* reuse the LCC green look from the Bulk Loader screen */
#stdPage { background:#CCFFCC; padding:18px 26px 28px; font-family:Arial,Helvetica,sans-serif; color:#222; }
#stdPage h1 { font-size:1.3rem; letter-spacing:1px; font-weight:700; color:#1C4532; text-align:center; margin:0 0 14px; }
.rd-tabs { display:flex; gap:6px; border-bottom:1px solid #a9e2a9; margin-bottom:16px; }
.rd-tab { padding:9px 20px; cursor:pointer; font-weight:700; font-size:13px; color:#1C4532; border:1px solid transparent; border-bottom:none; border-radius:8px 8px 0 0; }
.rd-tab.active { background:#fff; border-color:#b4b4b4; }
.rd-pane { display:none; } .rd-pane.active { display:block; }
.rd-tools { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.rd-tools .spacer { flex:1; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border:none; background:#007bff; color:#fff; font-size:14px; font-weight:700; border-radius:50px; cursor:pointer; }
.btn:hover { background:#0056b3; } .btn-green { background:#2e8b57; } .btn-green:hover { background:#1e6e43; }
.btn-grey { background:#777; } .btn-grey:hover { background:#555; }
select.rd-pick, input.rd-in, textarea.rd-in { background:#fff; border:1px solid #b4b4b4; border-radius:4px; padding:8px 10px; font-size:13px; font-family:inherit; }
.rd-pick { min-width:240px; }
.table-card { background:#fff; border:1px solid #b4b4b4; border-radius:8px; overflow:hidden; }
table.grid { width:100%; border-collapse:collapse; font-size:13.5px; background:#fff; }
.grid thead th { text-align:left; padding:11px 14px; font-size:12px; color:#fff; background:#007bff; }
.grid td { padding:8px 14px; border-bottom:1px solid #e4e4e4; vertical-align:top; }
.grid tbody tr:nth-child(even){ background:#f8f8f8; } .grid tbody tr:hover{ background:#eaf3ff; }
.grid input, .grid textarea { width:100%; box-sizing:border-box; border:1px solid #d4d4d4; border-radius:4px; padding:6px 8px; font:inherit; }
.grid .hdr-row { background:#eef7ff; font-weight:700; }
.mini { font-size:12px; color:#555; padding:4px 12px; border-radius:50px; border:1px solid #b4b4b4; background:#fff; cursor:pointer; font-weight:700; }
.mini:hover{ color:#007bff; border-color:#007bff; } .mini.danger:hover{ color:#fff; background:#cd0a0a; border-color:#cd0a0a; }
.mini.save:hover{ color:#fff; background:#2e8b57; border-color:#2e8b57; }
.empty { text-align:center; padding:30px; color:#5f6b62; }
.rd-hint { font-size:12px; color:#5f6b62; margin:0 0 12px; }
#rd-spinner { position:fixed; inset:0; background:rgba(255,255,255,.6); z-index:9999; display:none; }
#rd-spinner.progress { display:block; }
#rd-spinner .ld { position:absolute; top:42%; left:50%; transform:translateX(-50%); border:6px solid #f3f3f3; border-top:6px solid #007bff; border-radius:50%; width:44px; height:44px; animation:rdspin 1s linear infinite; }
@keyframes rdspin { to { transform:translateX(-50%) rotate(360deg); } }
</style>

<div id="rd-spinner"><div class="ld"></div></div>

<div id="stdPage">
    <h1>Sellbrite Reference Data</h1>
    <div id="errorMsg" class="ui-state-error ui-corner-all" style="display:none"></div>
    <div id="successMsg" class="ui-state-highlight ui-corner-all" style="display:none"></div>

    <div class="rd-tabs">
        <div class="rd-tab active" data-tab="values"  onclick="rdTab('values')">Dropdown Lists</div>
        <div class="rd-tab"        data-tab="cats"    onclick="rdTab('cats')">Category Defaults</div>
        <div class="rd-tab"        data-tab="grades"  onclick="rdTab('grades')">Grades</div>
    </div>

    <!-- ============ DROPDOWN LISTS ============ -->
    <div class="rd-pane active" id="pane-values">
        <p class="rd-hint">Pick a list, then add or delete the options it offers in the Bulk Loader &mdash; just like the spreadsheet's <em>Valid Values</em> sheet.</p>
        <div class="rd-tools">
            <label for="rd-list" style="font-weight:700;color:#1C4532;">List:</label>
            <select id="rd-list" class="rd-pick" onchange="rdLoadValues()">
                <option value="">&mdash; choose a list &mdash;</option>
                <?php foreach ($lists as $l): ?>
                    <option value="<?= sbl_e($l) ?>"><?= sbl_e($l) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="spacer"></span>
            <input type="text" id="rd-new-value" class="rd-in" placeholder="New option text&hellip;" style="width:260px">
            <label style="font-size:12px;color:#5f6b62;"><input type="checkbox" id="rd-new-header"> header/separator</label>
            <button type="button" class="btn btn-green" onclick="rdAddValue()">+ Add option</button>
        </div>
        <div class="table-card">
            <table class="grid">
                <thead><tr><th style="width:90px">Sort</th><th>Option value</th><th style="width:80px">Header</th><th style="width:80px">Active</th><th style="width:150px"></th></tr></thead>
                <tbody id="rd-value-rows"><tr><td colspan="5" class="empty">Choose a list above.</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- ============ CATEGORY DEFAULTS ============ -->
    <div class="rd-pane" id="pane-cats">
        <p class="rd-hint">Per-category defaults that auto-fill the form (coin type, denomination, composition, copy text, search terms, weight).</p>
        <div class="rd-tools">
            <button type="button" class="btn btn-green" onclick="rdCatNew()">+ New category</button>
            <span class="spacer"></span>
            <input type="search" id="rd-cat-filter" class="rd-in" placeholder="Filter categories&hellip;" style="width:260px" onkeyup="rdCatFilter()">
        </div>
        <div class="table-card">
            <table class="grid">
                <thead><tr><th>Category</th><th>Coin Type</th><th>Denom.</th><th>Composition</th><th>Weight (lb)</th><th style="width:150px"></th></tr></thead>
                <tbody id="rd-cat-rows"><tr><td colspan="6" class="empty">Loading&hellip;</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- ============ GRADES ============ -->
    <div class="rd-pane" id="pane-grades">
        <p class="rd-hint">Maps a grade to Circulated or Uncirculated (used to auto-fill that field).</p>
        <div class="rd-tools">
            <input type="text" id="rd-new-grade" class="rd-in" placeholder="Grade (e.g. 64)" style="width:160px">
            <select id="rd-new-circ" class="rd-pick" style="min-width:180px">
                <option value="">&mdash; status &mdash;</option>
                <option>Circulated</option><option>Uncirculated</option>
            </select>
            <button type="button" class="btn btn-green" onclick="rdGradeAdd()">+ Add grade</button>
            <span class="spacer"></span>
            <input type="search" id="rd-grade-filter" class="rd-in" placeholder="Filter grades&hellip;" style="width:220px" onkeyup="rdGradeFilter()">
        </div>
        <div class="table-card">
            <table class="grid">
                <thead><tr><th style="width:240px">Grade</th><th>Circulated / Uncirculated</th><th style="width:150px"></th></tr></thead>
                <tbody id="rd-grade-rows"><tr><td colspan="3" class="empty">Loading&hellip;</td></tr></tbody>
            </table>
        </div>
    </div>
</div>
<?php
}
