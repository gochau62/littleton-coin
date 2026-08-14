<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoaderAdmin_dsp.php *  -->
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
function dspBulkLoaderAdmin()
{
?>
<style>
#stdPage { background:#f8f8f8; padding:20px 28px 32px; font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif; color:#344054; position:relative; }
.sba-topbar { display:flex; align-items:center; background:#1C4532; color:#fff;
              padding:.6rem 1.25rem; margin:-20px -28px 18px; position:relative; }
.sba-topbar h1 { font-size:1.15rem; font-weight:600; color:#fff; margin:0; flex:1; text-align:center; }
.sba-back { position:absolute; top:5px; left:12px; }
.sba-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border:none; background:#1e6e43;
           color:#fff; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; }
.sba-btn:hover { background:#16563a; }
.sba-btn.ghost { background:#fff; color:#475467; border:1px solid #d0d5dd; }
.sba-btn.ghost:hover { background:#f8f8f8; color:#101828; }
.sba-tabs { display:flex; gap:8px; margin-bottom:16px; }
.sba-tab { padding:8px 18px; border:1px solid #d0d5dd; border-radius:8px; background:#fff;
           font-size:13px; font-weight:600; color:#475467; cursor:pointer; }
.sba-tab.on { background:#1C4532; border-color:#1C4532; color:#fff; }
.sba-card { background:#fff; border:1px solid #e4e7ec; border-radius:10px; padding:16px;
            box-shadow:0 1px 3px rgba(16,24,40,.06); max-width:1000px; }
.sba-note { font-size:12px; color:#667085; margin:0 0 12px; }
.sba-row { display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap; }
.sba-pick { padding:8px 10px; border:1px solid #d0d5dd; border-radius:8px; font-size:13px;
            background:#fff; min-width:280px; }
.sba-ta { width:100%; min-height:220px; border:1px solid #d0d5dd; border-radius:8px; padding:8px 10px;
          font-size:12.5px; font-family:Consolas,Menlo,monospace; }
.sba-ta.copy { min-height:90px; font-family:inherit; }
.sba-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
           color:#667085; display:block; margin:10px 0 4px; }
.sba-ovtag { font-size:9.5px; font-weight:700; text-transform:uppercase; padding:2px 7px;
             border-radius:50px; background:#e8f2ec; color:#1e6e43; margin-left:6px; }
table.sba-grid { width:100%; border-collapse:collapse; font-size:12.5px; }
.sba-grid th { text-align:left; padding:8px 10px; font-size:11px; font-weight:600; text-transform:uppercase;
               letter-spacing:.4px; color:#475467; background:#f2f7f3; border-bottom:1px solid #e4e7ec; }
.sba-grid td { padding:6px 10px; border-bottom:1px solid #eef1f4; }
.sba-grid select { padding:5px 8px; border:1px solid #d0d5dd; border-radius:6px; font-size:12.5px; background:#fff; }
.sba-msg { font-size:12px; color:#1e6e43; margin-left:10px; }
</style>

<div id='stdPage'>
    <header class="sba-topbar">
        <h1>Sellbrite Data</h1>
    </header>
    <button type="button" class="sba-btn ghost sba-back" onclick="window.location='SellbriteBulkLoader_ctl.php'">&larr; Bulk Loader</button>

    <div class="sba-tabs">
        <button type="button" class="sba-tab on" data-tab="values">Dropdown Values</button>
        <button type="button" class="sba-tab" data-tab="copy">Category Copy</button>
        <button type="button" class="sba-tab" data-tab="markets">Market Columns</button>
    </div>

    <!-- one value per line; saving replaces the whole list for that field -->
    <div class="sba-card" id="tab-values">
        <p class="sba-note">The choices each dropdown on the loader offers. One value per line - saving
           replaces the whole list for that field, Reset returns the standard list. Operators can always
           type values that are not listed; these lists are the suggestions.</p>
        <div class="sba-row">
            <select id="v-field" class="sba-pick"></select>
            <span id="v-ov"></span>
        </div>
        <label class="sba-lbl">Values (one per line)</label>
        <textarea id="v-ta" class="sba-ta" spellcheck="false"></textarea>
        <div style="margin-top:10px">
            <button type="button" class="sba-btn" onclick="saveValues()">Save List</button>
            <button type="button" class="sba-btn ghost" onclick="resetValues()">Reset to Standard</button>
            <span class="sba-msg" id="v-msg"></span>
        </div>
    </div>

    <!-- Des's per-category descriptions; the Extended Description fills from these -->
    <div class="sba-card" id="tab-copy" style="display:none">
        <p class="sba-note">The listing copy per store category, from Des's sheet. The Extended
           Description box fills with the Description below when it is empty; the alternates stand in
           when the main one is blank. Saving stores your version; Reset returns Des's original.</p>
        <div class="sba-row">
            <select id="c-cat" class="sba-pick"></select>
        </div>
        <label class="sba-lbl">Description</label>
        <textarea id="c-copy" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 1</label>
        <textarea id="c-alt1" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 2</label>
        <textarea id="c-alt2" class="sba-ta copy"></textarea>
        <div style="margin-top:10px">
            <button type="button" class="sba-btn" onclick="saveCopy()">Save Copy</button>
            <button type="button" class="sba-btn ghost" onclick="resetCopy()">Reset to Original</button>
            <span class="sba-msg" id="c-msg"></span>
        </div>
    </div>

    <!-- which upload columns each market's spreadsheet carries -->
    <div class="sba-card" id="tab-markets" style="display:none">
        <p class="sba-note">Which markets each upload column exports to. Picking a market sends that column
           only to that market's spreadsheet; All sends it to every one. Changes apply to the next export
           immediately.</p>
        <table class="sba-grid"><thead><tr><th>Column</th><th>Header</th><th>Exports To</th></tr></thead>
            <tbody id="m-body"></tbody></table>
        <span class="sba-msg" id="m-msg"></span>
    </div>
</div>
<?php
}
