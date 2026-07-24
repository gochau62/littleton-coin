# Requisition Material - Technical Reference

Updated 7/23/2026

## 1. Architecture

Requisition Material is a web screen on the IBM i, built on the
LCCOnline framework like the other LCC tools (same pattern as the
Sellbrite Bulk Loader). The entry point is `Requisitions_ctl.php`. It
runs the framework session scripts (StartBlockScriptA/B), checks
authority (`chkAutUsr`, LCCONLINE level 50), preloads the four dropdown
lists, and renders the page. After the page loads, every action goes
through `Requisitions_ajax.php` as an AJAX request that returns JSON.
There are no page reloads.

`?mode=entry` renders the same page in entry-only mode: the add form IS
the page, the grid and reports never render, and a fresh blank form
follows every submit. That link is the workfloor shortcut; the plain URL
is the full station.

### File map

| File | Role |
|---|---|
| `Requisitions_ctl.php` | Controller. Session sign-on, authority check, dropdown preload, renders the display. |
| `Requisitions_dsp.php` | Display. CSS, all HTML (grid + modals) and all JavaScript, inline in the one file. |
| `Requisitions_ajax.php` | AJAX router. JSON for everything; validates, back-outs, writes the activity log. |
| `Requisitions_model.php` | Db2 access. rqs* functions, `CALL REQSTNnnnS` only - no inline SQL anywhere. |
| `Requisitions_seed.php` | Dev-only browser loader for the CSV history files. Not an RFP object; remove from the server after the load. |

## 2. Databases (library currently LSCDEVLIBP)

| Table | Contents |
|---|---|
| RQSREQHDRT | One row per requisition. RHREQ# is a GENERATED identity (restarted at 17179 after the history load). Name, DEC(8,0)/DEC(6,0) date+time, area code/type, rush flag, authorized flag + by, badge, comments. |
| RQSREQDTLT | One row per line. PK (RDREQ#, RDLIN#). Item, loc, coin date, description, qty, cost/retail DEC(13,4), add cost, badge, SKU-to, RDRTNF returned flag + RDRTDT date returned. |
| RQSCODEFLT | The code file: the four seeded dropdown lists in one table, keyed by CDTYPE + CDCODE - AREACODE (3), AREATYPE (16), NAMES (65), AUTHBY (6). The BADGE list is not stored here: it reads live from LSCPRDLIB/XEMPLOYP. "Authorization = None" is a REAL AUTHBY row (13k+ historical headers store that literal), sorts first, and is the natural default - nothing synthetic in the code. |

All three are journaled to LSCSAVLIB/LSCJRN. Dates follow the shop
convention: DEC(8,0) yyyymmdd + DEC(6,0) hhmmss, not date types.

Legacy data notes: Access booleans (-1/0) became Y/N; the `authorized`
flag is derived from the authorized-by text; historical badges are a mix
of real numbers and the old PHP's `substr(name, 0, 4)` habit.

## 3. Stored procedures

All are `CREATE OR REPLACE`, `SET OPTION DBGVIEW = *SOURCE,
DYNUSRPRF = *OWNER`, built with RUNSQLSTM from QSQLSRC members.

| Proc | Role | Notes |
|---|---|---|
| REQSTN001S | Insert header | OUT new req# via IDENTITY_VAL_LOCAL(). Kills the legacy max(req_num)+1 race. |
| REQSTN002S | Insert one line | 12 IN parameters. |
| REQSTN003S | Open lines for the grid | No parameters. Header JOIN detail WHERE RDRTNF='N'. |
| REQSTN004S | One requisition | Header LEFT JOIN all lines, ordered by line. |
| REQSTN005S | Update header | authorized-by, comments, badge - NULL leaves a column unchanged (COALESCE), so the view window and the grid's badge box share one proc. RHAUTF derives from the authorized-by value. |
| REQSTN006S | Mark/unmark returned | Idempotent (flag guard). INRTDT = caller-entered return date; 0 stamps today. |
| REQSTN007S | The one lookup proc | INTYPE picks the cursor: any code list from RQSCODEFLT; BADGE = live active employees (LEESTAT='A') from LSCPRDLIB/XEMPLOYP; ITEM = autofill from the LSCPRDLIB/ITMMSTP item master (description, coin year) + last-used cost/retail from history, with history fallback for legacy items; ITEMSRCH = the item list from LSCPRDLIB/ITMMSTP (first 200 per prefix), history-enriched. |
| REQSTN008S | Monthly report rows | IN yyyymm; returns header + lines with RDEXTC/RDEXTR computed, ordered name/date/req/line. |
| REQSTN009S | Delete a requisition | Detail then header. The web insert's back-out - a failed submit never leaves half a requisition. |

**The OR REPLACE gotcha:** `CREATE OR REPLACE PROCEDURE` does NOT
replace across different parameter counts - changing a proc's signature
creates a second overload and the old one lingers until dropped
(`DROP PROCEDURE name(type, type, ...)`). The RFP checklist tracks the
drops done during development.

## 4. Views

**Station grid** - two-line records like Access frmMain: fields on line
1 (Req#, Date, Requestor, Item#, Loc, Qty, Badge#, Authorized, Rush),
description + the Return Item checkbox/date on line 2. Fixed pixel
columns with the leftover width on Requestor; below 780px the wrap
scrolls sideways instead of crushing. Stripe, hover and the ▶ selection
act on the whole record (the two rows are paired by a record id). The
Badge # box is a live input (change = save). Auto-refresh every 60s,
plus on tab-visibility; a JSON compare skips re-renders when nothing
changed.

**Entry form** (modal on the station, full page in `?mode=entry`) -
legacy getEntry.php layout: header fields, then the spreadsheet-style
line sheet (the cell is the box; the focused cell gets a slim blue
inner outline). Item # has autofill + a type-ahead dropdown. Enter hops
fields; Enter on the last box grows the sheet.

**View window** - legacy request.php layout; Update posts authorized-by
+ comments; per-line Returned checkboxes post immediately; Print.

**Report modals/windows** - Monthly Update (month/year dropdowns - not
`input type=month`, which Firefox renders as a dead text box), Preview
Report. Print windows print THEMSELVES on load and
close on afterprint - if the station called print(), the station's own
thread sat blocked in the dialog and the whole app froze.

## 5. Workflows

**Insert** - the browser serializes the form to one JSON payload;
`action=insert` calls 001S for the header, then 002S per line; any line
failure calls 009S to back the whole requisition out and returns "Line N
failed ... nothing was saved."

**Grid refresh** - `loadGrid()` first submits every pending Return Item
(006S per line, with the entered date), then pulls 003S. Checking
Return Item only queues the return client-side (a map keyed req|line
that survives re-renders); the refresh is what commits it - the Access
requery behavior. A pending return with an invalid date holds the
refresh instead of silently losing it.

**Update** - the view window sends authBy + comments, the badge box
sends badge only; missing fields ride as NULL and 005S leaves those
columns alone.

**Lookups** - preloaded with the page by the ctl (saves a round trip);
the ajax `lookups` action is the fallback if the preload failed.

**Item autofill / search** - `itemlookup` (exact, on change) and
`itemsearch` (opens on focus with the full list, 250ms debounce while typing, 200 rows per view) both ride REQSTN007S.

## 6. Error model

- No `die()` anywhere near JSON. Model functions return false and stash
  the real Db2 message (`db2_stmt_errormsg`) in `$GLOBALS['rqsErr']` and
  the PHP error log; the ajax layer returns `{ok:false, msg}` with that
  message so support can act on what the user reports.
- The ajax endpoint buffers output from byte 0 and clears it before the
  JSON - a stray include warning can't corrupt a response.
- Background work (auto-refresh, autofill) never pops dialogs; failures
  turn the Updated stamp red instead. Foreground actions get the real
  message in a dialog.

## 7. Activity log

`requisition_activity.log`, next to the PHP - same
`@file_put_contents(..., FILE_APPEND)` pattern as ClarioSFTP_pull.log.
One line per event: timestamp, user profile, station IP, action, detail.

Events: OPEN (page load, station vs entry form), INSERT (req + line
count), UPDATE (req, badge when changed from the grid), RETURN/UNRETURN
(req + line + entered date), BACKOUT (failed insert rolled back).

Write failures are @-suppressed - logging can never take the app down.
The file is created on first write (web profile needs write authority to
the folder) and grows forever; trim it yearly. Replaced the RQSACTLOGT
Db2 table, which nothing read.

## 8. Build, load, promote

1. RUNSQLSTM the three `.TABLE` members (RQSREQHDRT, RQSREQDTLT,
   RQSCODEFLT), then the nine `.PROC` members. Members are re-runnable
   (`CREATE OR REPLACE`); never hardcode the library in source - it
   breaks promotion.
2. STRJRNPF the tables to LSCSAVLIB/LSCJRN IMAGES(*BOTH)
   OMTJRNE(*OPNCLO).
3. Load `Data/*.csv` with CPYFRMIMPF (already in Db2 format - all
   transforms were done at export time), or the dev-only seed page.
4. `ALTER TABLE RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH 17179`.
5. Validate: 14,073 headers / 50,063 lines / 741 open / SUM(qty)
   33,464,119 / 90 code rows.
6. Copy the four `Web/` files to the LCCOnline docroot. Web profile
   needs authority to the library (SQL0551 means GRTOBJAUT) and write
   authority for the activity log.
7. RFP mirrors Sellbrite 3185: 12 Db2 objects as `*SQLTAB` from
   LSCDEVLIBP + 4 `*IFS`/PHPSRC files, level 10.

## 9. Deliberate changes vs legacy

1. Identity column replaces max(req_num)+1 (duplicate-req race fixed).
2. Bound-parameter procs replace string-built SQL (injection fixed).
3. Session sign-on + chkAutUsr replace HTTP Basic auth; the
   Firefox-only user-agent check is gone.
4. The add form grows lines dynamically instead of 30 fixed rows.
5. Badge stored properly (10 chars) and editable from the grid.
6. Returns from the grid require a date (autofilled today, editable)
   and commit on refresh; the view window still stamps today directly.
7. A failed submit backs out completely instead of half-saving.

## 10. Good to know

- The raw .mdb files never go in git - they carry plaintext credentials
  (both flagged for rotation at cutover).
- The legacy PHP and the full Access extraction (VBA, queries, schemas,
  data) are preserved in this branch's git history.
- The station grid shows open lines only; nothing is deleted -
  REQSTN009S runs only as the insert back-out.
- Blank page after a copy usually means a stale or corrupted file -
  compare against git and Ctrl+F5.
