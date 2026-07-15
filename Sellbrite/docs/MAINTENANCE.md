# Sellbrite Bulk Loader — Maintenance Guide

> The technical companion to [`HOW-IT-WORKS.md`](./HOW-IT-WORKS.md): how to change things
> without breaking them, how to test, and how to diagnose the recurring problems.
>
> _Last updated: 2026-07-13._

---

## 1. Deploying changes

- **Branches:** work lands on the feature branch (`claude/sellbrite-data-tables-fqd44u`)
  *and* is mirrored file-by-file onto `master` (`git checkout <branch> -- Sellbrite/<file>`
  then commit + push). Master is the main code.
- **The served copy is a different directory** than the git clone. After pulling, copy the
  changed files into the directory the web server actually serves, then **Ctrl+F5** in the
  browser (the JS/CSS cache is the #1 cause of "my change isn't there").
- **Line endings matter:** `_logic.php`, `_agent.php`, `_model.php` are CRLF; `_ctl.php`,
  `_dsp.php`, `_data.php`, `_ajax.php` are LF. Keep each file's existing endings — mixed
  endings break text-anchored patches and produce noisy diffs.

## 2. Configuration & secrets

- `SellbriteBulkLoader_secrets.php` (git-ignored) defines `GEMINI_API_KEY` per machine;
  an environment variable also works. **Never commit a Google key** — GitHub secret
  scanning blocks the push. GreySheet token/key/level constants live in `_agent.php`
  (they are not scanning-sensitive) and can be overridden the same way.
- `GS_TIMEOUT`, `GEMINI_TIMEOUT`, `GS_ROOT_NODE` have `if (!defined(...))` guards: keep
  them — they are used at the cURL calls and root-picking, and the guards let `_test.php`
  and local overrides predefine them.
- If a key was ever pasted into a chat/ticket, rotate it in Google AI Studio.

## 3. Database objects

- `LSCDEVLIBP.SBLPRODUCT` — listing rows. The model **discovers columns at runtime**
  (`sbl_writable_columns()` = `Schema::columns()` ∩ actual table columns), so schema rows
  added in `_data.php` before the ALTER simply don't persist — the screen keeps working.
  Run the pending ALTERs (condition, the five eBay columns, marketplace,
  extended_description, diameter, weight) to make those fields stick.
- `LSCDEVLIBP.SBLMEMORYT` — the GreySheet path memory. **Do not drop or rebuild it**;
  reseeding costs thousands of API calls. At runtime it is **read-only**: the drill-down
  dropdowns and the Autofill (which always has the picked coin's GsId) never write to
  it. It grows exactly one way: running `_seed.php` against an *unseeded* tree (e.g.
  Ancient Coins) — additive, doesn't touch the rest. (The live tree-walk finder that
  could also teach it was removed 07/13/2026 — the screen never used it.) If a
  coin/series is missing from the dropdowns, that part of the tree was never crawled.

## 4. Cookbook — the changes you'll actually make

All reference data lives in **`_data.php`** (a plain returned array). After any edit:
`php -l Sellbrite/SellbriteBulkLoader_data.php`.

| Change | Where | Notes |
|---|---|---|
| Add/remove a dropdown option (brand, designation, condition note…) | `values` in `_data.php` | One quoted string per option. |
| Grade list | `values.grade` | Pools split on the `--- SECTION ---` separators: UNCERTIFIED US COINS / CERTIFIED COINS / UNCERTIFIED PAPER MONEY / CERTIFIED PAPER MONEY. Keep new grades inside the right section — `Schema::gradePools()` parses by those separators. The UI merges certified+raw per paper/coin. |
| Coin Type list | `values.coin_type` | Same separator idea; `Schema::coinTypePools()` maps sections → trees, and `--- BULLION ---` splits by name (`America*` → US, rest → world). |
| Certification slab add-on / GSA holder weight | `lookups.package_weights` | Keys must match the Certification valid values / the exact Title Suffix wording. |
| Make a field required | `Schema::requiredNames()` in `_logic.php` | The star, the red validation, and the export check all read this one list. |
| Make a field manual / badge-less | `$manualAlways` / `$noBadge` in `_dsp.php` | Manual = no badge AND no formula refresh (no `data-auto`). |
| Field order / sections | the `'Coin details' => [...]` lists in `_dsp.php` | Order mirrors Des's workbook. |
| Category-dependent visibility | `SBL_CAT_FIELDS` in `_ctl.php` | paper/coin/bullion/cob/set groups. |
| Title/description shape | `Computer::buildTitle` / `buildDescription` in `_logic.php` **and** the guide examples in `sbl_field_guide()` (`_agent.php`) | Keep the code and the AI examples in agreement or Gemini will fight the formulas. |
| Export columns / a new marketplace | `Exporter` in `_logic.php`: `LAYOUT`, `AMAZON_ONLY`/`EBAY_ONLY`, `markets()`, `headerFills` | Also add the market's specific fields to `SBL_MARKET_FIELDS` (`_ctl.php`) and `Schema::marketFields()`. |
| New AJAX action | the switch in `_ajax.php` | JSON out; remember it runs inside the output buffer. |

**Adding a whole new field end-to-end:** add the schema row in `_data.php` → ALTER
`SBLPRODUCT` (matching name/type) → put the name into a section list in `_dsp.php` →
if it's category-specific add it to `SBL_CAT_FIELDS`; if computed, write the formula in
`Computer::apply`; if the AI should write it, add a guide entry in `sbl_field_guide()`;
if exported, add it to `Exporter::LAYOUT` (+ human header + note + fill color).

## 5. Testing

- **Diagnostics page:** `SellbriteBulkLoader_test.php` — self-contained (no repo
  includes). Buttons: environment, GreySheet ping, Gemini hello, crawl-a-branch,
  memory search, import-one-coin, then loads the real app files step by step and runs
  the full flow. Use it first when "nothing works".
- **CLI formula tests** (no DB needed — `Schema`/`Computer`/`Validator` are pure):

  ```bash
  php -r 'require "Sellbrite/SellbriteBulkLoader_data.php";
          require "Sellbrite/SellbriteBulkLoader_logic.php";
          var_dump(Computer::apply(["weight"=>"0.1823","certification"=>"PCGS"]));'
  ```

- **Render test:** include `_ctl.php` from a stub with a fake `$_SESSION` — with no
  `getDB2PConn` the auth check passes through and the page renders without DB2. Load the
  saved HTML in a headless browser to exercise the JS.
- **Comments-only refactors:** prove no code changed by comparing PHP token streams
  (`token_get_all` minus `T_COMMENT`/`T_WHITESPACE`) before and after.
- **Export check:** open the downloaded .xlsx in Excel, not just LibreOffice — Excel is
  stricter (see §6 on corruption).

## 6. Troubleshooting

| Symptom | Cause → fix |
|---|---|
| A change "doesn't show up" | Stale served copy or browser cache → copy files to the served directory, Ctrl+F5, verify with DevTools that the new JS/CSS is loaded. |
| Excel: "file format not valid" | Stray bytes before the xlsx stream → `_ajax.php` must keep `ob_start()` at the very top and clear all buffers before streaming. Any `echo`/whitespace added above that breaks it. |
| Extended description = collector's note | The Gemini call failed (429 quota, key) and both fallbacks fired → check the API log panel; the fallbacks now use different sources, so exact copies mean repeated failures. |
| World coin has no price after autofill | Normal: GreySheet pricing is US-centric. Not a tier/key issue. |
| Coin/series missing from dropdowns | That branch of `SBLMEMORYT` was never seeded → crawl it with `_seed.php` (additive). |
| Country flips to United States | Only set for literal U.S. path roots; check the coin's `CatalogPath` in the raw-response panel. Autofill never overwrites a non-empty country box. |
| Packaging empty | The coin is a Set (manual by design), GreySheet gave no `WeightOunces` (weigh it), or the hidden `f_weight` input is missing from the form. |
| Packaging wrong after certification change | It only replaces formula-produced values — a hand-typed weight stays. Clear the box to let the formula take it back. |
| Menus reopen after picking Series/Coin | The `sblPicked` data-flag suppresses late AJAX responses — check it wasn't removed. |
| Dropdown caret missing | A CSS state rule used the `background:` shorthand (resets `background-image`) — state rules must use `background-color:`. |
| `$.trim is not a function` | jQuery ≥ 4 removed it — the code uses `String(...).trim()`; keep it that way. |

## 7. Conventions & gotchas

- **Operator-owned fields** (condition, certification, cert number, coin type, grade,
  brand, title suffix) are never auto-written; autofill preserves them via the keep list
  in `sblGsAutofill`. Adding a new operator-owned field means updating `$manualAlways`
  (or `$noBadge`), the keep list, and — if gated like Cert Number — the gate function.
- **Disabled inputs don't serialize** (jQuery `.serialize()` drops them). To lock a box
  without losing its value, use `readonly` + a CSS class (see `sblCertNumGate`).
- **`Computer` fills only what it owns**: empty boxes, or values it produced itself
  (packaging). Never make a formula overwrite arbitrary operator input.
- **The Gemini prompt and the PHP formulas must tell the same story** — the guide strings
  in `sbl_field_guide()` contain the house examples; update them together with any
  formula change.
- **Certified test** is one rule everywhere (`'' / Uncertified / U.S. Mint` = raw). If a
  new "not really certified" value is added to the Certification list, extend that test in
  all three places: `Validator`, `Computer` (eBay fields), `sblCertNumGate`/grade pools.
- `SBLMEMORYT` is precious (see §3). `_data.php` is the only file that is pure data —
  keep logic out of it.
