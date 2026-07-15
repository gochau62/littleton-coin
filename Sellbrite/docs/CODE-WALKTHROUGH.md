# Sellbrite Bulk Loader — Plain-English Code Walkthrough

> This guide explains **every piece of the code in everyday language**, so someone who
> has never programmed can build a mental picture of the tool, find the right spot, and
> eventually make small changes with confidence. Read it next to the file it describes.
>
> The more technical companions: [`HOW-IT-WORKS.md`](./HOW-IT-WORKS.md) (the big picture)
> and [`MAINTENANCE.md`](./MAINTENANCE.md) (recipes for common changes).
>
> _Last updated: 2026-07-13._

---

## The cast of characters (the 8 files)

Think of the tool as a small office:

| File | Think of it as… |
|---|---|
| `SellbriteBulkLoader_data.php` | **The reference binder.** Pure lists: valid dropdown values, the list of form boxes, the packaging weight tables. No behavior at all — just facts. |
| `SellbriteBulkLoader_logic.php` | **The brains.** Knows the rules: what every box is, how titles/descriptions/weights are calculated, what counts as a mistake, and how to build the export spreadsheet. |
| `SellbriteBulkLoader_model.php` | **The filing clerk.** The only file that talks to the database where SKUs are stored. Saves, finds, lists, deletes. |
| `SellbriteBulkLoader_agent.php` | **The researcher.** Phones GreySheet for coin facts, remembers what it saw, and asks Gemini (the AI) to write the sales copy. |
| `SellbriteBulkLoader_ajax.php` | **The switchboard.** The web page sends it short requests ("save this", "compute that", "export"); it routes each one to the right person above and returns the answer. |
| `SellbriteBulkLoader_ctl.php` | **The stage manager.** All the JavaScript that makes the page feel alive — button clicks, dropdowns, the live recalculation as you type. |
| `SellbriteBulkLoader_dsp.php` | **The set designer.** Draws the page: the green screen, the toolbar, every form box, the colors. |
| `SellbriteBulkLoader_secrets.php` | **The key drawer.** Holds the Gemini API key on each machine. Never in git. |

Two extras: `_seed.php` (a one-time crawler that filled the coin "memory" table) and
`_test.php` (a standalone diagnostics page with test buttons).

**The two database tables:**
- `SBLPRODUCT` — one row per listing you create (the SKUs on the home screen).
- `SBLMEMORYT` — the "coin phone book": every GreySheet folder and coin from the seed
  crawl, so the dropdowns work instantly without calling GreySheet.

---

## 1. `SellbriteBulkLoader_data.php` — the reference binder

This file is one big list, split in three parts. **Editing it is the safest change you
can make** — you're editing facts, not behavior.

- **`values`** — every dropdown's allowed options, copied from Des's workbook's "Valid
  Values" tab. Want a new brand in the Brand menu? Add one quoted line to the `brand`
  list. Two lists are special because they contain `--- SECTION ---` divider lines:
  - `grade` — the dividers split it into coin vs paper money, raw vs certified pools.
    Add a new grade *inside the right section* and it appears in the right menus.
  - `coin_type` — the dividers group types by tree (US Coins, US Currency, World…).
    That's how the Coin Type menu knows which options belong to which `1. Tree` pick.
- **`schema`** — one line per form box / spreadsheet column: its machine name (must
  match the Sellbrite export header exactly), the label the user sees, and which values
  list feeds its dropdown.
- **`lookups`** — only packaging numbers live here: how much weight each grading
  service's holder adds (PCGS slab = +0.08 lb, plain 2×2 wrap = +0.015 lb…), and the
  fixed weights of GSA government holders. These are physical facts about *packaging*,
  which GreySheet can never tell us — everything about the *coin* comes from GreySheet.

---

## 2. `SellbriteBulkLoader_logic.php` — the brains

Four "departments" (classes), each with small, named jobs (functions).

### `Schema` — the librarian of the reference binder

| Function | Plain English |
|---|---|
| `data()` | Opens the reference binder (`_data.php`) once and keeps it handy. |
| `columns()` / `byName()` | Hands out the list of form boxes — as a list, or looked up by name. |
| `values()` | Hands out the valid-values lists. |
| `optionsFor(box)` | "What should this box's dropdown show?" Combines the binder lists with a few small built-in lists (condition = new/used, compositions, mint marks…). |
| `lookups()` | Hands out the packaging weight tables. |
| `gradePools()` | Reads the grade list and splits it at the `---` dividers into the four pools (coin/paper × raw/certified). |
| `coinTypePools()` | Same idea for Coin Type: splits the list into US/World × Coins/Currency pools; bullion entries starting with "America…" count as US, the rest as World. |
| `requiredNames()` | THE list of required boxes (the red stars). Add a name here to make a field required everywhere at once. |
| `marketFields()` | Which extra boxes each marketplace (Amazon/eBay…) needs. |
| `groups()` | How boxes are grouped into the form's collapsible sections. |

### `Computer` — the calculator (the old spreadsheet formulas)

`Computer::apply(row)` receives everything currently typed in the form and fills in
whatever can be derived. It runs after every keystroke, so the page always shows
up-to-date results. Inside it, in order:

- **Circulated/Uncirculated** — derived from the grade (MS/PR grades = Uncirculated).
- **Package weight** — coin's own weight from GreySheet (converted troy-ounces → pounds)
  **plus** the certification holder add-on from the binder. GSA coins use the holder's
  fixed weight instead. It fills immediately at autofill (using the plain-wrap default)
  and **updates itself when Certification changes** — but it only ever replaces numbers
  it calculated itself, so a weight a person typed by hand is never overwritten.
- **Package length/width/height** — three size brackets based on the weight.
- **original_retail** — copies the price for `.WS` SKUs.
- **`buildTitle`** — glues the title together from the boxes: year, mint mark, series,
  varieties, denomination, grade, certification + "Coin Collectible".
- **`buildDescription`** — writes the one house sentence: "A genuine {year} {mint mark}
  {variety} {series} {denomination}, graded and certified {grade} by {service}." (or
  ", in {grade} Condition" for raw coins). It only rewrites the description while it
  still starts with "A genuine" — if a person rewrote it, the machine leaves it alone —
  and any extra sentences after the first are kept.
- **Features 1–5** — 1 = "DETAILS:" (front half of the description), 2 = "CONDITION:",
  3 = "IMAGES:" (the exact-image line), 4 = "COLLECTOR'S NOTE:" (the AI writes the note
  itself), 5 = the fixed About-Profile-Coins paragraph.
- **eBay condition boxes** — certified coins become "Graded" with the grader and grade
  numbers; raw coins become "Ungraded" with a circulated/uncirculated word.

### `Validator` — the proofreader

`Validator::check(row)` looks at every box and returns a color + message for each:
- **red (error)** — required box is empty, or a number box has a non-number.
- **yellow (action)** — something to look at: a 4-digit year problem, "Enter number of
  coins in the set", "Enter the certification number" (only when a grading service is
  picked but the number box is empty).
- **green (ok)** — filled and fine.
The page paints boxes from these results, and the "Ready / Needs attention" pill is just
"is anything red?".

### `Exporter` — the spreadsheet writer

| Function | Plain English |
|---|---|
| `LAYOUT` (a list) | The 88 export columns in exact workbook order, with the human titles, the notes row, and each column's header color. |
| `markets()` | The marketplaces the export dropdown offers. |
| `keepIndexes(market)` | Which columns survive for a given market — e.g. exporting eBay drops the Amazon-only columns (search terms, style) and vice versa. |
| `headerFills()` | The header background colors, copied from the workbook. |
| `xlsx(rows, market)` | Builds the real Excel file: 3 header rows, every cell written as text (so "255R.50" doesn't turn into a number), column widths auto-sized. |
| `csv(rows, market)` | The plain-text fallback when the Excel library isn't installed. |

---

## 3. `SellbriteBulkLoader_model.php` — the filing clerk

Everything that touches the `SBLPRODUCT` table. Nobody else opens that drawer.

| Function | Plain English |
|---|---|
| `sbl_conn()` | Opens the connection to the database (once). |
| `sbl_db_err()` / `sbl_commit()` | Reads the database's error message / finalizes a write. |
| `sbl_norm_date()` / `sbl_coerce()` | Cleans up values before saving (dates into date format, numbers into numbers, blanks into NULLs). |
| `sbl_table_columns()` | Asks the database "which columns do you actually have?" |
| `sbl_writable_columns()` | The overlap of "boxes the form knows" and "columns the table has". This is why adding a new form box **before** running the database change doesn't crash anything — the box just doesn't persist until the column exists. |
| `sbl_select()` | The shared "read rows" helper (also lower-cases the column names, because DB2 shouts in uppercase). |
| `sblGetAll(q)` | The home-screen grid: newest first, optionally filtered by the search text. |
| `sblGetAllFull()` | Every column of every row — used by the export. |
| `sblFind(id)` | One row, for the edit form. |
| `sblInsert` / `sblUpdate` / `sblSave` | Save a row (insert if new, update if it has an id). |
| `sblDelete` / `sblDeleteAll` | Remove one row / everything. |

---

## 4. `SellbriteBulkLoader_agent.php` — the researcher

Eight sections, in file order.

**Section 1 — the two phone lines.**
- `gsApiGet(path, params)` — the ONLY function that calls GreySheet. Adds the API keys,
  enforces the timeout, logs every call (that's what fills the little call log panel).
- `geminiJson(system, user)` — the ONLY function that calls Gemini. Asks for a JSON
  answer, retries on the fallback model if the first is busy.
- `geminiConfigured()` — "do we even have a key?" If not, all AI steps quietly skip.

**Section 2 — writing to the coin phone book** (`SBLMEMORYT`).
`gsMemUpsert` writes one folder or coin; `gsMemLearnNode` / `gsMemLearnCoins` /
`gsMemMarkDone` are the "remember what we just saw" helpers. In the normal daily flow
these never run — the seed crawler is their only caller.

**Section 3 — reading the phone book (what the dropdowns use).**
- `gsMemRoots()` — the four `1. Tree` choices.
- `gsMemSeries(root, q)` — every series under the tree, filtered by what you type.
- `gsMemYears(path)` — the years that series actually exists for.
- `gsMemCoins(path, q, year)` — every coin in the series (up to 50,000 — effectively all).
- `gsMemSearch(q)` — free-text coin search (used by the older search box path).
- `gsLikeEsc` / `gsNorm` — tiny helpers that make typed text safe/consistent to search with.

**Section 4 — GreySheet endpoints.**
`gsCollectible` / `gsPricing` fetch one coin's full facts / price row; `gsPriceNum`
cleans a price string; `gsYearsFor` finds a typed category's years from the phone book.
(A live tree-walk coin finder — `gsChildren`/`gsNavPick`/`gsResolveLeaf`/`gsPickCoin` —
used to live here; it was removed 07/13/2026 because the screen always imports by a
picked GsId. If a coin is missing from the dropdowns, seed that tree with `_seed.php`.)

**Section 5 — little cleaners.**
`sbl_norm_composition` ("90% silver, 10% copper" → "Silver"), `sbl_norm_category`
(strips "(2022–2025)" date ranges off series names — that's how SKU of Parent Product
loses its dates), `sbl_mint_location` (mark letter → city), `sbl_snap` (snaps an almost-
right value onto the exact valid option).

**Section 6 — the AI's writing brief.**
`sbl_field_guide()` is a per-field instruction sheet for Gemini: what each field means,
the allowed values, and the house examples (the 1943 steel-cent description, the Wheat
Cent collector's note). **If you want the AI to write differently, edit the wording
here.** `sbl_field_spec` / `sbl_field_options` turn the guide into the actual prompt;
`sbl_clean_ai_row` / `sbl_snap_row` tidy the AI's answer (drop invented fields, snap
values onto valid options).

**Section 7 — turning GreySheet facts into a form row.**
- `gsMapToProduct(coin)` — the deterministic mapping, no AI: year and mint mark from the
  coin date, denomination (short for US, long form for world), composition, fineness,
  diameter, the coin's weight, bullion shape, brand from the image attribution, country
  from the catalog path, and the best-match **suggestion** for Coin Type. This is the
  place to change "which GreySheet field lands in which box".
- `gs_coin_facts(coin)` — the facts package sent to the AI.
- `gsAiMap(coin)` — runs `gsMapToProduct`, then asks Gemini to write the listing copy on
  top. The mapped facts always win; the AI only fills gaps. If the AI call fails, the
  fallbacks fill the copy from GreySheet's own text (history notes → extended
  description, the coin-face descriptions → collector's note) so the boxes are never
  empty and never identical.
- `gsListingFill(form)` — the "Generate Product details with AI" button: writes ONLY the
  empty Listing Content boxes, from whatever is on the form (works for watches and
  calendars too, not just coins).

**Section 8 — the entry points the switchboard calls.**
`gsSearch` (free-text search), `gsImport` (the Autofill: fetch coin + price, map, write
copy, then `gs_finalize` = run the calculator + proofreader), `gsGenerate` (find + import
in one go, for imports without a picked coin).

---

## 5. `SellbriteBulkLoader_ajax.php` — the switchboard

One `switch` statement. Each `case` is one kind of request from the page:

| Request | What happens |
|---|---|
| `compute` | Run the calculator + proofreader on the form as-is; send back the filled boxes and colors. (This is the live recalculation.) |
| `save` / `find` / `delete` / `deleteAll` | Filing-clerk operations. Save also recomputes first, so what's stored is what you saw. |
| `gsRoots` / `gsSeries` / `gsNodeYears` / `gsCoins` | The four drill-down dropdowns, straight from the phone book. |
| `gsYears` | Years for a typed category (Year dropdown rebuild). |
| `gsSearch` / `gsImport` / `gsGenerate` | The researcher: search / the Autofill button / find-and-import. |
| `gsListingFill` | The AI writing button. |
| `export` | Collects the rows for the chosen market, builds the Excel file, and streams it as a download. The file must start with byte #1 of the Excel data — that's why the whole endpoint buffers and silences any stray output first. |

---

## 6. `SellbriteBulkLoader_ctl.php` — the stage manager (JavaScript)

Top-to-bottom, grouped as in the file's own map comment:

**Message helpers & badges.** `showErrorMessage`/`showSuccessMessage` (the top banners);
`sblSyncAutoBadges`/`sblResetAutoBadges` keep the little blue "auto" badges honest —
after an autofill they only stay on boxes autofill actually wrote.

**View switching.** `sblShow` flips between home and form. `sblBackToList` returns home.
`sblExport` reads the market dropdown and starts the download. `sblSearch` filters the
grid. `sblListingGenerate` is the AI button (disables itself while working, then reports
which boxes are still empty).

**New / edit.** `sblClearForm` empties everything (form, drill-down, preview, badges) —
it's what "+ New SKU" uses via `sblNew`. `sblEdit` fetches one row and fills the form.
`sblMarketApply` shows only the chosen marketplace's extra boxes (lists in
`SBL_MARKET_FIELDS`). `sblDeleteAll` double-confirms, then wipes.

**Save / delete.** `sblFormSerialize` turns the whole form into one request string;
`sblSave` posts it and updates the grid row in place (`sblUpsertListRow`); `sblDelete`
removes one row.

**Coin finder.**
- `sblCertNumGate(clearIt)` — the Certification Number doorman: the box only exists when
  a real grading service is picked; switching back to raw hides it and (only on a real
  user action) empties it. Uses read-only + a CSS class instead of "disabled" so the
  value still reaches saves.
- `sblFillFromRow(row)` — pours an autofill/edit result into the form. Two house rules
  live here: never overwrite a non-empty Country, and rebuild the Year dropdown after.

**Year dropdown.** `sblYearApply`/`sblYearRefresh` swap the Year box between a free-text
input (unknown category) and a select of the series' real years.

**Drill-down + combos.** `sblCleanCategory` strips date ranges from a series name (the
JS twin of the PHP cleaner). `SBL_GS_FIELDS` = which boxes get the "from GreySheet"
badge. `SBL_CAT_FIELDS` + `sblFieldVisibility` = which boxes exist for paper vs coins
vs bullion vs sets. `sblLoadRoots` fills `1. Tree`; picking a tree sets the country
rule. `sblSeriesAutocomplete` (fills SKU of Parent + category on pick),
`sblLoadYears`/`sblYearAutocomplete`, `sblCoinAutocomplete` (arms the Autofill button)
are the three searchable menus — each uses a "picked" flag so a slow server reply can't
pop the menu back open. **`sblFieldCombos`** converts every dropdown box into the same
searchable menu style and holds the pool rules: Grade pools (paper vs coin), Coin Type
pools (by tree), Country pool (US locked / world list). `sblResetBelowSeries` clears
year+coin when the series changes. **`sblGsAutofill`** is the big red button: wipes the
form (keeping the operator-owned boxes: sku, market, quantity, category, country,
condition, certification + number), then asks the switchboard to `gsImport` and pours
the answer in. `sblRenderCalls`/`sblRenderRaw` fill the API log and raw-response panels.

**Live recompute.** `sblRecompute` posts the form to `compute` after every change (with
a quarter-second typing pause), writes returned values into every auto box (never the
one you're typing in), paints the red/yellow colors, updates the preview
(`sblPreview`) and the Ready pill + issue list (`sblValidity`).

**Document ready.** Wires everything: typing → recompute, the Certification change →
`sblCertNumGate`, loads the trees, builds the combos.

**Bottom of the file (PHP).** The authority check (LCCONLINE level 50); authorized users
get the page rendered by `dspBulkLoader`.

---

## 7. `SellbriteBulkLoader_dsp.php` — the set designer

- **`$renderField(col)`** — draws ONE form box: label, red star if required, blue "auto"
  badge if a formula owns it, then the right control (searchable dropdown with the caret
  icon, big text area for copy fields, or plain text box), and the little message line
  under it. Every box on the form goes through this one function — change it and you
  change them all.
- **`<style>` block** — all the looks: the green page, the pill-shaped toolbar, the
  collapsible sections, the box states (red error / yellow action / focus blue), the
  dropdown caret, `cert-locked` (the class that hides Certification Number for raw
  coins). One rule to know: state rules must set `background-color`, never the
  `background` shorthand — the shorthand erases the caret icon.
- **Home view** — the toolbar (Search pill, Export pill with market picker + Download,
  + New SKU, Delete All) and the SKU grid.
- **Form view** — the GreySheet drill-down bar, then the sections in workbook order.
  Three small lists here decide badges: `$autoAlways` (always formula-owned),
  `$noBadge` (autofill suggests, operator owns — no badge), `$manualAlways` (never
  touched by machines: title suffix, certification, cert number, condition). The hidden
  `weight` input also lives here — the coin's GreySheet weight rides invisibly so
  packaging can recalculate when Certification changes.
- **Preview column** — the live listing preview + the issue list.
- **Script tail** — hands the JavaScript its data: the grade pools, the coin-type pools,
  the field groups.

---

## 8. The helpers

- **`SellbriteBulkLoader_seed.php`** — the crawler that filled the coin phone book, one
  GreySheet folder at a time. Additive and resumable; pointing it at a never-crawled
  tree (like Ancient Coins) adds that tree without touching anything else.
- **`SellbriteBulkLoader_test.php`** — a self-contained test page: check the
  environment, ping GreySheet, say hello to Gemini, crawl a small branch, search the
  memory, import one coin, and finally load the real app files one by one (so if
  something is broken, you see exactly which file breaks).
- **`SellbriteBulkLoader_secrets.php`** — three lines defining the Gemini key on this
  machine. Git-ignored on purpose; every machine keeps its own.

---

## If you remember only five things

1. **Dropdown options and packaging numbers live in `_data.php`** — the safest file to edit.
2. **Titles, descriptions, weights, and validation rules live in `_logic.php`** (`Computer` and `Validator`).
3. **"Which GreySheet fact lands in which box" lives in `gsMapToProduct`** (`_agent.php`), and **the AI's writing style lives in `sbl_field_guide`** right above it.
4. **How the page behaves lives in `_ctl.php`; how it looks lives in `_dsp.php`.**
5. After ANY change: `php -l` the file, copy it to the served folder, and **Ctrl+F5**.
