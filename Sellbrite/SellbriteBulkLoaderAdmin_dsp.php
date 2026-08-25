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
.sba-new { background:#f4f8f5; border:1px dashed #b9cec2; border-radius:8px; padding:12px 14px; margin-bottom:16px; }
.sba-new-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#1e6e43; margin:0 0 8px; }
.sba-new .sba-row { align-items:center; }
.sba-grid tr.staff td { background:#fbfdfb; }
#m-body tr { cursor:grab; }
#m-body tr:active { cursor:grabbing; }
.sba-actions { display:flex; align-items:center; gap:10px; margin-top:12px; }
.sba-actions .spacer { flex:1; }
.sba-btn.danger { background:#fff; color:#b42318; border:1px solid #e4b8b4; }
.sba-btn.danger:hover { background:#fef3f2; color:#912018; }
</style>

<div id='stdPage'>
    <header class="sba-topbar">
        <h1>Sellbrite Data</h1>
    </header>
    <button type="button" class="sba-btn ghost sba-back" onclick="window.location='SellbriteBulkLoader_ctl.php'">&larr; Bulk Loader</button>

    <div class="sba-tabs">
        <button type="button" class="sba-tab on" data-tab="values">Dropdown Values</button>
        <button type="button" class="sba-tab" data-tab="copy">Category Descriptions</button>
        <button type="button" class="sba-tab" data-tab="markets">Market Columns</button>
    </div>

    <!-- each tab: an "add new" box on top, the editor for existing entries below -->
    <div class="sba-card" id="tab-values">
        <p class="sba-note">The choices each dropdown on the loader offers. Pick a dropdown, edit its
           choices (one per line - add or delete lines) and Save. Operators can always type values
           that are not listed; these lists are the suggestions.</p>
        <div class="sba-new">
            <p class="sba-new-title">Add a new header</p>
            <div class="sba-row">
                <input class="sba-pick" id="v-new" placeholder="Header name (e.g. Ruler)">
                <select class="sba-pick" id="v-sec">
                    <option value="Coin details">Coin details</option>
                    <option value="Market specific fields">Market specific fields</option>
                    <option value="Other product types (advent calendar / watch / stamp / nativity)">Other product types</option>
                    <option value="Packaging">Packaging</option>
                    <option value="Listing content">Listing content</option>
                </select>
                <button type="button" class="sba-btn" onclick="addField()">Add Header</button>
            </div>
            <p class="sba-note" style="margin:8px 0 0">Creates a new box on the loader (in the picked section)
               AND a new column in the export spreadsheet. Autofill will fill it whenever the coin data
               clearly provides a value.</p>
        </div>
        <label class="sba-lbl">Edit a dropdown</label>
        <div class="sba-row">
            <select id="v-field" class="sba-pick"></select>
            <span id="v-ov"></span>
        </div>
        <label class="sba-lbl">Choices (one per line)</label>
        <textarea id="v-ta" class="sba-ta" spellcheck="false"></textarea>
        <div class="sba-actions">
            <button type="button" class="sba-btn" onclick="saveValues()">Save List</button>
            <span class="sba-msg" id="v-msg"></span>
            <span class="spacer"></span>
            <button type="button" class="sba-btn danger" id="v-del" onclick="delField()" style="display:none">Delete This Header</button>
        </div>
    </div>

    <!-- Des's per-category descriptions; the Extended Description fills from these -->
    <div class="sba-card" id="tab-copy" style="display:none">
        <p class="sba-note">The reusable listing description per coin/category, from Des's sheet. The Extended
           Description box on the loader fills with the Description when it is empty; the alternates stand
           in when the main one is blank.</p>
        <div class="sba-new">
            <p class="sba-new-title">Add a new category</p>
            <div class="sba-row">
                <input class="sba-pick" id="c-new" placeholder="Category name (e.g. Morgan Dollar)">
                <button type="button" class="sba-btn" onclick="addCat()">Add Category</button>
            </div>
        </div>
        <label class="sba-lbl">Edit a category</label>
        <div class="sba-row">
            <select id="c-cat" class="sba-pick"></select>
        </div>
        <label class="sba-lbl">Description</label>
        <textarea id="c-copy" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 1</label>
        <textarea id="c-alt1" class="sba-ta copy"></textarea>
        <label class="sba-lbl">Alternate 2</label>
        <textarea id="c-alt2" class="sba-ta copy"></textarea>
        <div class="sba-actions">
            <button type="button" class="sba-btn" onclick="saveCopy()">Save Descriptions</button>
            <span class="sba-msg" id="c-msg"></span>
            <span class="spacer"></span>
            <button type="button" class="sba-btn danger" onclick="delCat()">Delete This Category</button>
        </div>
    </div>

    <!-- which upload columns each market's spreadsheet carries -->
    <div class="sba-card" id="tab-markets" style="display:none">
        <p class="sba-note">Where each upload column exports to. Picking a market sends that column only to
           that market's spreadsheet, All sends it to every one, and Not exported / Remove drops the column
           from every spreadsheet. Drag a row up or down to reorder the columns in the spreadsheet.
           Changes save the moment they are made and apply to the next export.</p>
        <div class="sba-new">
            <p class="sba-new-title">Add a new column</p>
            <div class="sba-row">
                <input class="sba-pick" id="m-new-label" placeholder="Column header (e.g. Lot Code)">
                <select class="sba-pick" id="m-new-market">
                    <option value="all">All</option><option value="amazon">Amazon only</option>
                    <option value="ebay">eBay only</option><option value="walmart">Walmart only</option>
                </select>
                <input class="sba-pick" id="m-new-value" placeholder="Fill every row with (optional)">
                <button type="button" class="sba-btn" onclick="addCol()">Add Column</button>
            </div>
            <p class="sba-note" style="margin:8px 0 0">The column lands at the end of the export with the fixed
               text in every row. Staff-added columns list first in the table with a Remove button; standard
               columns cannot be removed - set them to Not exported instead.</p>
        </div>
        <table class="sba-grid"><thead><tr><th>Column</th><th>Header</th><th>Exports To</th></tr></thead>
            <tbody id="m-body"></tbody></table>
        <span class="sba-msg" id="m-msg"></span>
    </div>
</div>
<?php
}
