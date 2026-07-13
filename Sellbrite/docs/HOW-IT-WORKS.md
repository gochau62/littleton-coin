# Sellbrite Bulk Loader — How the Program Works

> Full functional documentation: what the screen does, how a request runs, and how every
> subsystem behaves. Companion docs: [`MAINTENANCE.md`](./MAINTENANCE.md) (how to change
> things safely), [`ROADMAP.md`](./ROADMAP.md) (project history / plan).
>
> _Last updated: 2026-07-13._

---

## 1. What it is

A web screen on the IBM i (LCCOnline / M-Power stack) where staff author coin listings.
Instead of filling Des's `.ods` spreadsheet by hand, the operator drills down to a coin in
the **GreySheet catalog**, presses **Autofill**, and the screen fills the ~85 Sellbrite
columns itself: catalog facts from the GreySheet CDN Public API, listing copy from
**Gemini**, and everything derivable (title, features, packaging, eBay condition fields)
from the same formulas the spreadsheet used. Rows are saved to DB2 and exported as a
Sellbrite-ready **.xlsx / .csv**, trimmed per marketplace.

## 2. How it runs

**Entry point:** `SellbriteBulkLoader_ctl.php`, served inside the LCCOnline shell like every
other LCC tool.

1. `StartBlockScriptA.php` (framework) establishes the session → `$user` / `$password`.
2. The controller emits its `<script>` block — *all* of the screen's JavaScript.
3. At the bottom of the file, `StartBlockScriptB.php` runs, then the **authority check**:
   `chkAutUsr($conn, $user, "LCCONLINE", 50)`. Unauthorized users get the error box only.
4. Authorized: loads `_logic.php` + `_model.php`, reads the SKU grid (`sblGetAll`),
   includes `_dsp.php` and calls `dspBulkLoader($screenData)` which renders both views
   (home grid + SKU form; JS switches between them, no page reloads).
5. Everything after page load is AJAX against `SellbriteBulkLoader_ajax.php`.

**File map** (MVC + agent, same pattern as ImportOrderFile / UploadGiftCard):

| File | Role |
|---|---|
| `_ctl.php` | Controller. All JS; auth check; renders the page. |
| `_dsp.php` | Display. CSS, field renderer, home + form views, emits JS data (pools). |
| `_ajax.php` | AJAX router. JSON for everything; streams the export file. |
| `_logic.php` | Rules. `Schema` (fields/values), `Computer` (formulas), `Validator`, `Exporter`. |
| `_model.php` | DB2 data access for `SBLPRODUCT` (list/find/save/delete). |
| `_agent.php` | GreySheet API + path memory + Gemini. Mapping and AI writing. |
| `_data.php` | Reference data (returns an array): valid values, schema rows, packaging lookups. |
| `_secrets.php` | Git-ignored. Defines `GEMINI_API_KEY` (and optionally GreySheet keys) per machine. |
| `_seed.php` | One-off crawler that filled the `SBLMEMORYT` path memory. |
| `_test.php` | Standalone diagnostics page (env / ping / crawl / import checks). |

**Databases** (library `LSCDEVLIBP`):

- `SBLPRODUCT` — one row per listing (~90 columns, machine names = Sellbrite headers).
- `SBLMEMORYT` — the **path memory**: every GreySheet node (`kind='N'`) and coin
  (`kind='C'`) the screen has seen: ref_id (node/gs id), name, path
  (`U.S. Coins > Morgan Dollar…`), coin_date, mint_mark, coin_count. Powers all
  drill-down dropdowns with **zero API calls**.

## 3. The two views

**Home** — toolbar with the Search pill (filters the grid), the Export pill (market
picker + Download), **+ New SKU**, **Delete All**; below it the saved-SKU grid
(edit/delete per row, AJAX in-place updates).

**SKU form** — the GreySheet bar (`1. Tree → 2. Series → 3. Year → 4. Coin → Autofill`,
plus the API-call log and raw-response panels), then collapsible sections in Des's
workbook order:

1. **Coin details** — sku, category (SKU of Parent Product), brand, country, price,
   original retail, creation date, condition, coin type … diameter, cost, quantity.
2. **Market specific fields** — search terms (Amazon), the five eBay condition fields.
3. **Other product types** — only appears for advent calendars / watches / stamps / nativity.
4. **Packaging** — weight/length/width/height (auto).
5. **Listing content** — title suffix, name, description, extended description,
   features 1–5, condition note + the **Generate Product details with AI** button.
6. **Product images** — exact-image line + image URL slots with live preview.

A live preview + validation panel sits beside the form: "Ready" / "Needs attention"
with a clickable issue list.

## 4. The drill-down (memory table)

- `gsRoots` → the 4 trees seeded at `parent_id = 0`: U.S. Coins, U.S. Currency,
  World Coins, World Currency.
- `gsSeries` → every leaf node with `coin_count > 0` under the chosen tree
  (path-prefix match, so intermediate folders flatten away). Searchable; fetch cap
  10,000 (never reached).
- `gsNodeYears` → distinct years of the coins under the series (fills `3. Year`).
- `gsCoins` → every coin under the series, optionally narrowed by year/typed text;
  cap 50,000. The front end strips the common name prefix so menus show only the
  distinguishing part.

Picking a **series** immediately fills *SKU of Parent Product* (the series name with
date ranges stripped: `American Women Quarters (2022–2025)` → `American Women Quarters`)
and — for World trees — the country from the path's second node. The country is set
**once** here; autofill never overwrites a non-empty country.

## 5. Autofill (the main pipeline)

Pressing **Autofill** (`sblGsAutofill`):

1. **Wipe** — every field clears EXCEPT the operator-owned keep list:
   `sku, marketplace, quantity, category_name, country_of_manufacture, condition,
   certification, certification_number`.
2. `gsImport` (server): `GetCollectibleRequest` + `GetPricingRequest` for the picked
   GsId. Pricing's `GradeLabel` normalizes into the Grade box (`MS65` → `MS 60`-style
   spacing). Note: pricing is often empty for world coins (GreySheet's pricing data is
   US-centric — not a key/tier problem).
3. `gsMapToProduct` — deterministic mapping, no AI. Highlights:
   - `CoinDate` → year + mint mark; mint location derived from the mark; empty mark ⇒ empty location.
   - `DenominationShort` for U.S. (`25c`), `DenominationLong` for world (`5 Euros`) —
     the short form carries a metal prefix (`S€5`) that would leak into copy.
   - Composition/fineness/diameter/weight; `WeightOunces` (troy oz) is the packaging driver.
   - `CoinShape` → Bullion Shape; `FeaturedImageAttribution` → Brand (left alone when empty).
   - Country from `CatalogPath` country name → world 2nd node → United States only when
     the root is literally a U.S. tree.
   - **Coin Type best-match**: pool picked by tree (see §6), then eagle-family specials
     (`Silver Eagle` → `American Eagle`, `Gold Buffalo` → `American Buffalo`), then the
     longest option whose words all appear in the category/path.
   - Precious metal content per coin and total (`WeightOunces × Fineness`).
4. `gsAiMap` — Gemini writes the listing copy on top (see §8). Deterministic values win;
   the AI only fills gaps.
5. `gs_finalize` — `Computer::apply` (formulas, §7) then `Validator::check`.
6. Back in the browser, `sblFillFromRow` writes the row into the form (never overwriting
   a non-empty country), rebuilds the Year dropdown from the series' real years, applies
   field visibility + market columns, and triggers a recompute.

**Live recompute:** every input/change POSTs the whole form to `action=compute`;
the response re-fills every `[data-auto]` box (never the focused one), repaints
statuses (red `is-error`, yellow `is-action`), and refreshes the preview. This is why
edits to year/grade/certification instantly rebuild the title, description and packaging.

## 6. Field behavior model

- **AUTO badge** (blue): formula- or autofill-owned; operator can still override.
- **No badge but autofilled** (`$noBadge`): coin_type, grade, brand, original_retail —
  operator-owned picks that autofill may suggest.
- **Fully manual** (`$manualAlways`): title_suffix, certification, certification_number,
  condition — never written by autofill or formulas.
- **Required** (red star; from `Schema::requiredNames()` + per-market additions):
  sku, category_name, price, condition, certification, name, description,
  extended description, features 1–5, packaging ×4, exact image, product image 1,
  quantity, cost (+ search_terms when the row can go to Amazon).
- **Gated:** Certification Number exists only when Certification is a real grading
  service (not blank/Uncertified/U.S. Mint) — the box hides otherwise, shows a yellow
  "Enter the certification number" nudge when open and empty, and clears itself when
  the operator switches a coin back to raw.
- **Pooled dropdowns** (`sblFieldCombos` jQuery-UI combos):
  - *Grade* — paper SKUs see the paper pools, everything else the coin pools
    (certified + raw merged; Ungraded/Various Grades first).
  - *Coin Type* — pool by tree: US Coins / US Currency / World Coins / World Currency
    sections of the valid values (`--- BULLION ---` splits: `America*` → US, rest → world).
  - *Country* — U.S. trees lock to United States; World trees list everything else.
- **Category visibility:** paper-money boxes only on Currency trees; the coin block
  only on Coin trees; bullion shape only when the path mentions bullion; set count only
  for Sets. Marketplace picker shows only that market's specific fields.

## 7. The formulas (`Computer::apply`)

- **Title**: `{year} {mint mark} {category} {varieties} {denomination} {grade}
  {designation} {certification} Coin Collectible` (raw values, no vocabulary tables).
- **Description**: one house sentence:
  `A genuine {year} {mm} {variety1} {variety2} {category} {denomination}` +
  `", graded and certified {grade} {designation} by {certification}"` when certified,
  else `", in {grade/condition} Condition"`. Rebuilt only while it still starts with
  `A genuine` (operator rewrites are respected); flavor sentences after the first are
  preserved on rebuild.
- **Features**: 1 = `DETAILS:` (first half of the description), 2 = `CONDITION:`,
  3 = `IMAGES:` exact-image line, 4 = `COLLECTOR'S NOTE:` (AI-written, §8),
  5 = the fixed ABOUT PROFILE COINS blurb.
- **Packaging** (from Des's VLOOKUP tables, stored in `_data.php`):
  `package_weight = WeightOunces × 0.0685714 (troy oz→lb) + certification add-on`
  (Uncertified/U.S. Mint +0.015, PCGS/CAC +0.08, ICG +0.07, NGC/ANACS +0.1).
  A GSA coin (Variety contains "GSA") uses the holder weight from Title Suffix instead
  (0.36 boxed / 0.226 holder / 0.081 soft pack). Fills at autofill with the raw-coin
  default and **re-computes when Certification changes**; it only replaces values the
  formula itself produced, so hand-typed weights (Sets, no-weight coins) are never
  touched. Dims from weight: L `<0.5→9 else 11`; W `<0.5→8, <1→9, else 10`;
  H `<0.17→1, <1→2, else 4`.
- **eBay condition fields**: certified ⇒ Graded + grader/letter/numerical grade;
  raw ⇒ Ungraded + circulated/uncirculated condition.
- **original_retail**: mirrors price for `.WS` SKUs.
- The **certified test** everywhere: certification not blank, not `Uncertified`,
  not `U.S. Mint`.

## 8. Gemini (AI writing)

- Configured via `GEMINI_API_KEY` (secrets file or env var). Never in git.
- **At autofill** (`gsAiMap`): writes description (house shape, full-criteria example in
  the prompt), extended description (2–4 category-level sentences from
  GeneralNotes/obverse/reverse — fits every coin in the category), and the collector's
  note (feature 4) — which must take a **different angle** in its own words, never
  copying GeneralNotes or the extended description (the prompt carries Des's Lincoln
  Wheat example of each).
- **On demand** (`gsListingFill`, the AI button): fills only the *empty* Listing Content
  boxes from the form's own facts — also works for non-GreySheet products.
- **Fallbacks when the AI call fails**: extended description ← cleaned GeneralNotes;
  collector's note ← obverse+reverse design text (each reuses the other only as a last
  resort). Identical boxes therefore mean the Gemini call itself failed (check the API log).
- Also used (rarely) to break ties when navigating unknown coins down the live GreySheet
  tree; every node visited is learned into memory.

## 9. Export

Home → Export pill: market picker (All markets / Amazon / eBay / Chrono24 / Walmart) +
Download. Server filters rows (a row exports when its marketplace is blank/`all`/the
picked market), builds the fixed 88-column layout, then **drops other markets' columns**
(Amazon-only: search_terms, style; eBay-only: modified_item, modification_description +
the five eBay condition columns). Output is real `.xlsx` via PhpSpreadsheet — 3 header
rows (title / notes / machine names) with the workbook's fill colors, every cell written
as TEXT (`255R.50` survives), columns autosized — or CSV when the vendor library is
missing. The AJAX endpoint buffers all output from the first line and clears it before
streaming (a single stray byte corrupts an .xlsx for Excel).

## 10. Saving

`sblSave` serializes the form (hidden fields included — the coin's GreySheet weight
rides in hidden `f_weight` so packaging can re-compute later). The model coerces values
per column type, intersects with the columns that **actually exist** on `SBLPRODUCT`
(pending ALTERs never break saves), and inserts or updates by id. The grid updates
in place.
