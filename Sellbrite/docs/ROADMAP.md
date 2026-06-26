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
                │  sbl_data/*.json          │   │  _model.php  DB2 for i      │
                │  schema / valid_values /  │   │  CALL stored procs:         │
                │  lookups  (from the .ods) │   │  getAll/find/save/delete    │
                └──────────────────────────┘   └──────────────┬─────────────┘
                                                               ▼
                                                  ┌────────────────────────┐
                                                  │  DB2 table  SBLPRODT    │
                                                  │  (85 cols + id + audit) │
                                                  └────────────────────────┘
```

**Plain-English data flow:** you type → `compute` redraws the auto fields and validation live →
`save` writes the row to **SBLPRODT** via a stored procedure → the list view reads rows back →
`export` streams the 3-header Sellbrite CSV.

---

## 3. What already exists vs. what's missing (Phase 0 findings)

I read every file in `Sellbrite/` plus the reference tools and the `.ods`. Status:

| Piece | State | Notes |
| --- | --- | --- |
| `_ctl.php` | ✅ Solid | Auth via `chkAutUsr(... "LCCONLINE", 50)`, list/form view switching, live recompute. |
| `_dsp.php` | ✅ Solid | List grid + grouped add/edit form + live preview + validation checklist. Green `#CCFFCC` LCC styling, matches house look. |
| `_ajax.php` | ✅ Solid | `compute / save / find / delete / export` endpoints. |
| `_logic.php` | ✅ Strong | `Schema`, `Computer` (title/description/image-URL/default formulas), `Validator`, `Exporter` (3-row CSV). This is the spreadsheet's brain, already in PHP. |
| `_model.php` | ❌ **Empty stub** | Only a header. **No DB2 code yet** — `sblGetAll/sblFind/sblSave/sblDelete` are referenced but undefined. |
| `sbl_data/schema.json` | ❌ **Missing** | `Schema` loads it; defines the 85 columns (name/label/required/dropdown/auto). |
| `sbl_data/valid_values.json` | ❌ **Missing** | Dropdown options (from the "Valid Values" sheet). |
| `sbl_data/lookups.json` | ❌ **Missing** | `category_meta` / `category_copy` / `grade_circ` maps (from the "VLOOKUP" sheet). |
| DB2 table | ❌ **Not created** | SQL is now ready in `sql/SBLPRODT.sql` (Phase 1). |

### Three issues to fix early
1. **Filename mismatch.** The files are named `SellbriteBulkLoader_*.php`, but the includes
   reference `SBL_BulkLoader_*.php` (e.g. `_ctl.php` requires `SBL_BulkLoader_logic.php`,
   `_dsp.php`, `_model.php`; `_ajax.php` exports to `SBL_BulkLoader_ajax.php`). **We must pick one
   naming scheme** and make includes consistent, or the app won't load. _Recommendation: rename
   files to the `SBL_BulkLoader_*` prefix the code already expects._
2. **Empty model.** All DB2 access has to be built (Phase 2) against the new `SBLPRODT` table.
3. **Missing JSON data files.** They can be **generated directly from the `.ods`** (the three
   sheets map 1:1 to the three JSON files). This is the first Phase 2 task.

### Spreadsheet structure (the source spec)
The `.ods` has **3 sheets**, which map exactly onto the three JSON files:

| Sheet | Rows | Becomes | Purpose |
| --- | --- | --- | --- |
| **Bulk Import Sheet** | 3 header rows + data | `schema.json` | Row 2 = human labels, **row 3 = machine column names** (the 85 fields), banner row 1. |
| **Valid Values** | 46 columns | `valid_values.json` | Dropdown lists (Mint Mark, Grade, Composition, …). Some cells are live formulas. |
| **VLOOKUP** | lookup tables | `lookups.json` | Category → default copy/weight/metadata, grade → circulated/uncirculated, etc. |

---

## 4. The roadmap (phases & status)

| Phase | What | Status |
| --- | --- | --- |
| **0** | Orient: read repo, `.ods`, reference screens; summarize | ✅ **Done** (this doc + meeting notes) |
| **1** | Data model + SQL `CREATE TABLE` (DB2 for i) | ✅ **Script delivered** → ⏸ **waiting on you to create the table** |
| **2** | M-Power files: generate JSON from `.ods`, build `_model.php` (DB2), wire up, fix naming | ⏳ Next, after the table exists |
| **3** | Website assembly: standalone screen vs. menu integration | ⛔ Blocked on scope decision |
| **4** | Meeting notes → markdown + readable plan | ✅ **Done** (this doc + `MEETING-NOTES.md`) |
| **5** | Agentic automation: pick 1 of 3 options, then scaffold | ⛔ Blocked on your pick |

> **Working rule (unchanged):** _you_ run all SQL and M-Power deploys; I generate code and stop
> at each phase boundary for your confirmation. I never assume anything was executed.

---

## 5. Phase 1 — the table (delivered; your move)

**File:** [`../sql/SBLPRODT.sql`](../sql/SBLPRODT.sql) — DB2 for i `CREATE TABLE PLAYGROUND/SBLPRODT`.

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

**Two choices that affect the script** (see §8 Open Decisions): the **target library** (default
shown: `PLAYGROUND`) and the **CCSID** (default job CCSID vs. UTF-8 `1208` for foreign-coin
characters). Tell me your picks and I'll finalize the header before you create it.

➡️ **Action for you:** create the table, then tell me it exists. I won't start Phase 2 until then.

---

## 6. Phase 2 — M-Power application files (the plan, file by file)

Once `SBLPRODT` exists, in this order:

1. **Generate the data files from the `.ods`** → `sbl_data/schema.json`,
   `valid_values.json`, `lookups.json`. This makes the existing `_logic.php` and `_dsp.php` run.
   - `schema.json`: 85 entries `{name, label, required, dropdown?, auto?}` from rows 2–3.
   - `valid_values.json`: each dropdown's option list (skipping the spreadsheet's formula cells).
   - `lookups.json`: `category_meta`, `category_copy`, `grade_circ` from the VLOOKUP sheet.
2. **Build `_model.php` (DB2 for i)** — the only truly missing code. Four functions matching the
   AJAX contract, following the `GFTCRDCVP_model.php` pattern (`db2_prepare` → `db2_bind_param` →
   `db2_execute`):
   - `sblGetAll($q)` — list/search (SELECT with optional `WHERE` on sku/category/name).
   - `sblFind($id)` — one row for the edit form.
   - `sblSave($row)` — insert or update (by `id`), coercing blanks/placeholders to `NULL`.
   - `sblDelete($id)`.
   - **Two house options:** (a) inline parameterized SQL in the model, or (b) **stored procedures**
     `SBLPROD001/002/003…` like `GFTCRD001.PROC`. _Recommendation: stored procs_ — consistent with
     the repo and keeps SQL on the IBM i side. I'll generate the `.PROC` sources for you to create.
   - **Key gotcha:** DB2 returns column keys **uppercase**; the model will
     `array_change_key_case($row, CASE_LOWER)` so PHP keeps using lowercase names.
3. **Fix the filename mismatch** — rename to the `SBL_BulkLoader_*` prefix the includes expect
   (or update every include). One consistent scheme.
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

## 9. Open decisions (I need these from you)

| # | Decision | Default I'll use if unspecified |
| --- | --- | --- |
| 1 | **Target library/schema** for `SBLPRODT` | `PLAYGROUND` (matches GFTCRD dev work) |
| 2 | **CCSID** for text columns | Job default; switch to **UTF-8 1208** if foreign-coin chars matter |
| 3 | **Website scope** (Phase 3) | **B** — integrate into LCCOnline menu |
| 4 | **First agentic automation** (Phase 5) | **A** — AI Listing-Copy Generator |
| 5 | **Model style** (Phase 2) | Stored procedures (`.PROC`), like GFTCRD |
| 6 | Sellbrite vs **Cellbrite** — same system or two? | Capture as-is; confirm when convenient |

---

## 10. Glossary (so everyone reads this the same way)

- **M-Power** — mrc (michaels, ross & cole) low-code generator on the IBM i; produces the
  model/ajax/display PHP files.
- **DB2 for i** — the database on the IBM i (AS/400). Our table lives here.
- **SKU** — the unique product code; drives image URLs and links photo → product.
- **Staging table (`SBLPRODT`)** — where authored products live before the Sellbrite CSV export.
- **Computer / Validator** — PHP classes that reproduce the spreadsheet's formulas and checks.
- **Valid Values / VLOOKUP** — the two helper sheets that become dropdown options and lookups.
