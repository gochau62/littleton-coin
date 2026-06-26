# Sellbrite Bulk Loader — Project Roadmap & Plan

> **Living document.** This is the source of truth for the build. It is written to be read
> top-to-bottom by anyone on the team. Companion docs: [`MEETING-NOTES.md`](./MEETING-NOTES.md)
> (what was discussed) and the SQL in [`../sql/SBLPRODT.sql`](../sql/SBLPRODT.sql).
>
> _Last updated: 2026-06-26._

---

## 1. What we're building (in one paragraph)

We are turning ProfileCoin's **bulk-upload spreadsheet** (the `.ods` workbook with 85 product
columns, dropdown "Valid Values," and a "VLOOKUP" tab) into a proper **web screen on the IBM i /
M-Power stack**. Instead of typing into a fragile spreadsheet, a user fills in a form, the screen
**auto-computes** the same fields the spreadsheet formulas produce (title, description, image
URLs, package dims, defaults), **validates** the row live, **saves** it to a DB2 table, and can
**export a Sellbrite-ready CSV**. Later we add an **agentic automation** layer to attack the slow,
manual 20% of listings (foreign / one-off coins) and the strict marketplace formatting rules.

**Why:** the meeting made the pain clear — three systems that don't sync, ~$7K/yr Sellbrite with
weak reporting, and 30–60 min of manual work per rare coin. Consolidating data entry + validation
in one IBM i screen is the first concrete step toward a custom solution.

---

## 2. How the pieces fit together (architecture)

The Sellbrite screen follows the same **MVC pattern** as the other LCC tools in this repo
(ImportOrderFile, UploadGiftCard, PrintInvoices):

```
                ┌─────────────────────────────────────────────────────────┐
  Browser  ──►  │  _ctl.php   controller: auth check, jQuery/SweetAlert,   │
                │             loads list view, wires live-recompute        │
                └───────────────┬──────────────────────────┬──────────────┘
                                │ include                    │ AJAX (POST)
                                ▼                            ▼
                ┌──────────────────────────┐   ┌────────────────────────────┐
                │  _dsp.php  presentation   │   │  _ajax.php  JSON endpoints  │
                │  list grid + add/edit     │   │  compute / save / find /    │
                │  form, green LCC styling  │   │  delete / export            │
                └─────────────┬─────────────┘   └──────────────┬─────────────┘
                              │ uses                           │ uses
                              ▼                                ▼
                ┌───────────────────────────────────────────────────────────┐
                │  _logic.php   Schema · Computer · Validator · Exporter      │
                │  (the spreadsheet's formulas, re-expressed in PHP)          │
                └───────────────┬───────────────────────────┬───────────────┘
                                │ reads                       │ calls
                                ▼                             ▼
                ┌──────────────────────────┐   ┌────────────────────────────┐
                │  _data.php (one PHP file) │   │  _model.php  DB2 for i      │
                │  schema / values /        │   │  inline parameterized SQL:  │
                │  lookups  (from the .ods) │   │  getAll/find/save/delete    │
                └──────────────────────────┘   └──────────────┬─────────────┘
                                                               ▼
                                                  ┌────────────────────────┐
                                                  │  DB2 table  SBLPRODT    │
                                                  │  (85 cols + id + audit) │
                                                  └────────────────────────┘
```

**Plain-English data flow:** you type → `compute` redraws the auto fields and validation live →
`save` writes the row to **SBLPRODT** via parameterized SQL → the list view reads rows back →
`export` streams the 3-header Sellbrite CSV.

**File naming:** every file uses the **`SellbriteBulkLoader_*`** base name —
`_ctl` / `_dsp` / `_ajax` / `_logic` / `_model` / `_data`.

---

## 3. What already exists vs. what's missing (Phase 0 findings)

I read every file in `Sellbrite/` plus the reference tools and the `.ods`. Status:

| Piece | State | Notes |
| --- | --- | --- |
| `_ctl.php` | ✅ Solid | Auth via `chkAutUsr(... "LCCONLINE", 50)`, list/form view switching, live recompute. |
| `_dsp.php` | ✅ Solid | List grid + grouped add/edit form + live preview + validation checklist. Green `#CCFFCC` LCC styling, matches house look. |
| `_ajax.php` | ✅ Solid | `compute / save / find / delete / export` endpoints. |
| `_logic.php` | ✅ Strong | `Schema`, `Computer` (title/description/image-URL/default formulas), `Validator`, `Exporter` (3-row CSV). This is the spreadsheet's brain, already in PHP. |
| `_model.php` | ✅ **Built** | Inline parameterized SQL: `sblGetAll/sblFind/sblSave/sblDelete`. Columns come from `Schema::columns()`; lower-cases DB2's keys; coerces blanks/`***`/bad input to NULL. Needs the table to run (smoke-tested without DB). |
| `_data.php` | ✅ **Built** | One PHP file holding `schema` (85 cols), `values` (26 dropdown lists), `lookups` (category_copy 242 / category_meta 242 / grade_circ 274). Generated from the `.ods`; **replaces the old JSON files**. |
| DB2 table | ❌ **Not created** | SQL is ready in `sql/SBLPRODT.sql` (Phase 1) — waiting on you to run it. See §5.1. |

### Issues found early — all resolved
1. ✅ **Filename mismatch — FIXED.** Everything now uses the **`SellbriteBulkLoader_*`** base name;
   all includes and AJAX URLs point to those. `php -l` passes on every file.
2. ✅ **Model — BUILT.** DB2 access written as inline parameterized SQL against `SBLPRODT`.
   It runs the moment the table exists (verified the non-DB pipeline already works).
3. ✅ **Data — CONSOLIDATED into one PHP file.** No external JSON: the three sheets were folded
   into `SellbriteBulkLoader_data.php`, loaded once by `Schema`. The `sbl_data/` folder is gone.

### Spreadsheet structure (the source spec)
The `.ods` has **3 sheets**, which map onto the three sections of `SellbriteBulkLoader_data.php`:

| Sheet | Rows | Becomes (`_data.php` key) | Purpose |
| --- | --- | --- | --- |
| **Bulk Import Sheet** | 3 header rows + data | `schema` | Row 2 = human labels, **row 3 = machine column names** (the 85 fields), banner row 1. |
| **Valid Values** | 46 columns | `values` | Dropdown lists (Mint Mark, Grade, Composition, …). Some cells are live formulas (filtered out). |
| **VLOOKUP** | lookup tables | `lookups` | Category → default copy/weight/metadata, grade → circulated/uncirculated, etc. |

---

## 4. The roadmap (phases & status)

| Phase | What | Status |
| --- | --- | --- |
| **0** | Orient: read repo, `.ods`, reference screens; summarize | ✅ **Done** (this doc + meeting notes) |
| **1** | Data model + SQL `CREATE TABLE` (DB2 for i) | ✅ **Script delivered** → ⏸ **waiting on you to create the table** |
| **2** | M-Power files: data from `.ods`, build `_model.php` (DB2), wire up, fix naming | ✅ **Code complete** — data ✅, naming ✅, model ✅; just needs the table created to run live |
| **3** | Website assembly: standalone screen vs. menu integration | ⛔ Blocked on scope decision |
| **4** | Meeting notes → markdown + readable plan | ✅ **Done** (this doc + `MEETING-NOTES.md`) |
| **5** | Agentic automation: pick 1 of 3 options, then scaffold | ⛔ Blocked on your pick |

> **Working rule (unchanged):** _you_ run all SQL and M-Power deploys; I generate code and stop
> at each phase boundary for your confirmation. I never assume anything was executed.

---

## 5. Phase 1 — the table (delivered; your move)

**File:** [`../sql/SBLPRODT.sql`](../sql/SBLPRODT.sql) — DB2 for i `CREATE TABLE LSCDEVLIBP/SBLPRODT`,
created **CCSID 1208 (UTF-8)**.

**Design decisions, explained simply:**
- **85 product columns + `id` + `created_at` + `updated_at`.** One row = one SKU being authored.
- **Long SQL names = the spreadsheet's machine names** (`sku`, `category_name`, …). That way
  `db2_fetch_assoc()` hands PHP the exact keys the existing `_logic.php`/`_dsp.php` already use.
  Each also gets a clean ≤10-char **system name via `FOR COLUMN`** (e.g. `category_name` →
  `CATNAME`) so DBAs keep tidy short names — matches the `CLRCUSSEGT.TABLE` house style.
- **`id`** is an identity primary key (the UI edits/deletes by `id`).
- **`sku`** is `NOT NULL` + `UNIQUE` (no duplicate listings); **`quantity`** is `NOT NULL DEFAULT 0`.
- **Everything else is nullable** — the screen lets you **save in-progress rows with warnings**, so
  the model will convert blanks / `*** PLACEHOLDER ***` hints / non-numeric junk to `NULL` before
  insert.
- **Types:** money (`price/cost/original_retail`) = `DECIMAL(11,2)`; counts = `INTEGER`/`SMALLINT`;
  package dims = `DECIMAL`; `creation_date` = `DATE`; long copy fields sized so the **row stays
  ~21.7 KB, well under DB2's 32,766-byte limit**.
- **`updated_at`** uses DB2's `ROW CHANGE TIMESTAMP` so it auto-updates on every change (the list
  grid shows "Updated").

**Decisions locked (06/26):** target library **`LSCDEVLIBP`**; text columns created
**`CCSID 1208` (UTF-8)** so foreign-coin names and special characters store cleanly. The script is
finalized — no further edits needed before you run it. (If `CREATE` ever reports *row too long*,
switch `description` to `CLOB(1M)`; everything else fits comfortably.)

### 5.1 How to actually create the table (your question, answered)

The table does **not** exist yet — `sql/SBLPRODT.sql` is the script that *creates* it. **Yes, you
build it straight from that file.** You don't name anything by hand; the script already names it:
- **Object name:** `SBLPRODT`  •  **Library:** `LSCDEVLIBP`  •  **Record format:** `SBLPRODTR`

Two ways to run it (either works — pick what you normally use):

**Option A — RUNSQLSTM (the house way, matches `CLRCUSSEGT.TABLE` / `GFTCRD001.PROC`):**
1. Copy the contents of `SBLPRODT.sql` into a source member named **`SBLPRODT`** in
   **`LSCDEVLIBP/QSQLSRC`** (same place your other table/proc sources live).
2. Run:
   ```
   RUNSQLSTM SRCFILE(LSCDEVLIBP/QSQLSRC) SRCMBR(SBLPRODT) COMMIT(*NONE)
   ```
3. Verify: `WRKOBJ LSCDEVLIBP/SBLPRODT` (or `SELECT * FROM LSCDEVLIBP.SBLPRODT FETCH FIRST 1 ROW`).

**Option B — ACS "Run SQL Scripts" (GUI, fastest):** open IBM i Access Client Solutions →
**Run SQL Scripts**, paste the whole `SBLPRODT.sql`, and **Run All**. The script uses system-naming
(`LSCDEVLIBP/SBLPRODT`, `FOR COLUMN`, `LABEL ON`, `RCDFMT`), so set the connection to
**system naming** (or just run as-is — these statements are system-naming syntax).

➡️ **Action for you:** create the table with A or B, then tell me it exists. The app code is already
finished and waiting — the model points at `LSCDEVLIBP.SBLPRODT`.

---

## 6. Phase 2 — M-Power application files (the plan, file by file)

Once `SBLPRODT` exists, in this order:

1. ✅ **DONE — Data from the `.ods`** → consolidated into **`SellbriteBulkLoader_data.php`**
   (one PHP file, no JSON). `Schema` loads it once via `require`.
   - `schema`: 85 entries `{name, label, required, auto, dropdown?}` from rows 2–3.
   - `values`: 26 dropdown option lists (formula cells skipped, `---` separators kept).
   - `lookups`: `category_copy` (242), `category_meta` (242), `grade_circ` (274) from VLOOKUP.
2. ✅ **DONE — Built `SellbriteBulkLoader_model.php` (DB2 for i)** — **inline parameterized SQL**
   (chosen over stored procs because the table is 85 columns wide; no extra IBM i objects to create):
   - `sblGetAll($q)` — list/search (`SELECT` with optional `WHERE` on sku/category/name).
   - `sblFind($id)` — one row for the edit form.
   - `sblSave($row)` — insert or update (by `id`), coercing blanks/`***`/bad input to `NULL`.
   - `sblDelete($id)`.
   - Column list comes from `Schema::columns()`; **DB2's UPPERCASE keys are lower-cased** via
     `array_change_key_case(... CASE_LOWER)`. Errors are logged (no `die()`), so AJAX still returns JSON.
   - Smoke-tested end-to-end without DB: Schema→Computer→Validator→Exporter all green; it runs live
     as soon as `SBLPRODT` exists.
3. ✅ **DONE — Filenames aligned** — all files use the **`SellbriteBulkLoader_*`** base name;
   includes, AJAX URLs, and header comments all match. `php -l` clean across the board.
4. **SQL Composer runtime-parameter pattern** — for any list filtering (date range, category,
   "needs attention" status), use the seed-column + `${R00X}` placeholder pattern (the Cory McBeth
   report structure) so filters are swapped into the `WHERE` clause at runtime.
5. **Match the reference screens** — the `_dsp.php` styling already mirrors the LCC green
   `#stdPage` look; we'll confirm grid columns/filters line up with ImportOrderFile / UploadGiftCard.

I'll walk through each file as a diff and flag anything ambiguous rather than guessing.

---

## 7. Phase 3 — website assembly (needs a scope decision)

"The website" could mean any of:
- **A. Standalone M-Power screen** — just the Bulk Loader page, reached directly (fastest).
- **B. Integrated into LCCOnline** — menu entry + auth wired into the existing portal (matches how
  the other tools ship).
- **C. A small multi-screen app** — Bulk Loader + a dashboard/report + the agentic piece.

_Recommendation: **B** (menu-integrated), since auth (`chkAutUsr … LCCONLINE`) is already wired._
See §8 to choose.

---

## 8. Phase 5 — agentic automation (pick one to start)

The meeting points to three high-value targets. Each is a focused agent we can scaffold; they can
be combined later, but we should **start with one**.

### Option A — AI Listing-Copy Generator  ⭐ _recommended first_
**What:** for the slow **20%** (foreign, ancient, one-off coins), the agent takes the SKU's key
attributes (year, country, denomination, grade, certification) and **drafts the title,
description, 5 features, and search terms** — the exact fields humans hand-write today. It plugs
straight into the screen's `compute` step as a "Draft with AI" button; the human reviews/edits.
**Attacks:** the 30–60 min/item research-and-write bottleneck.
**Tradeoffs:** numismatic facts must be reviewed (hallucination risk) → keep human-in-the-loop;
needs a model + prompt with house copy conventions; cost per generation.

### Option B — Marketplace Pre-Flight + Batch Ingestion
**What:** an agent that **validates a whole batch** against each marketplace's rules before
upload (Amazon required fields + character limits, eBay policy formatting, "don't list on Amazon"
flags, mint-mark/date rules) and can **ingest a CSV/tray batch** into `SBLPRODT`, running
Computer + Validator and surfacing **only the rows that need a human**.
**Attacks:** the "eBay change broke the pipeline," Amazon strictness, and 30–50/day throughput.
**Tradeoffs:** rules need maintenance as marketplaces change; less "wow," more guardrail.

### Option C — Pricing Assistant
**What:** pulls **cost averages from the AS/400** and **market values** (Greysheet-style), applies
margin rules, and **suggests a retail price** per SKU — the automation Des explicitly asked for.
**Attacks:** the manual pricing spreadsheet.
**Tradeoffs:** needs a market-data source/integration and agreed pricing rules; pricing is
sensitive → suggest-only with approval.

**My recommendation:** start with **A** (clearest ROI, lowest integration risk, human-reviewed),
then add **B** as the guardrail layer. Tell me your pick in §8 and I'll write a short design +
scaffold — no agent code runs without your go-ahead.

---

## 9. Decisions (locked 06/26) & still-open items

| # | Decision | Status |
| --- | --- | --- |
| 1 | **Target library/schema** for `SBLPRODT` | ✅ **`LSCDEVLIBP`** |
| 2 | **CCSID** for text columns | ✅ **UTF-8 `1208`** |
| 3 | **Website scope** (Phase 3) | ✅ **Integrate into LCCOnline menu** |
| 4 | **First agentic automation** (Phase 5) | ✅ **A — AI Listing-Copy Generator** |
| 5 | **Model style** (Phase 2) | ✅ **Inline parameterized SQL** (best fit for an 85-column table) |
| 6 | **Data storage** (no external JSON) | ✅ Consolidated into one PHP file, `SellbriteBulkLoader_data.php` |
| 7 | Sellbrite vs **Cellbrite** — same system or two? | ❓ Confirm when convenient (not blocking) |

---

## 10. Glossary (so everyone reads this the same way)

- **M-Power** — mrc (michaels, ross & cole) low-code generator on the IBM i; produces the
  model/ajax/display PHP files.
- **DB2 for i** — the database on the IBM i (AS/400). Our table lives here.
- **SKU** — the unique product code; drives image URLs and links photo → product.
- **Staging table (`SBLPRODT`)** — where authored products live before the Sellbrite CSV export.
- **Computer / Validator** — PHP classes that reproduce the spreadsheet's formulas and checks.
- **Valid Values / VLOOKUP** — the two helper sheets that become dropdown options and lookups.
