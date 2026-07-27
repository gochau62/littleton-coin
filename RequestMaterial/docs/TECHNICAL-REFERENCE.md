# Requisition Material - Technical Reference

Updated 7/24/2026

## 1. Architecture

Requisition Material is a web screen on the IBM i, built on the LCCOnline
framework. The entry point is `Requisitions_ctl.php`: it runs the
framework session scripts (StartBlockScriptA and StartBlockScriptB),
checks authority with `chkAutUsr` at LCCONLINE level 50, preloads the
dropdown lists, and renders the page. Every action after that goes
through `Requisitions_ajax.php` as an AJAX request returning JSON, so
there are no page reloads.

`?mode=entry` renders the same page in entry only mode: the add form is
the page, the grid and reports never render, and a fresh blank form
follows every submit. That link is the workfloor shortcut and the plain
URL is the full station.

### The PHP files

| File | Role |
|---|---|
| `Requisitions_ctl.php` | Controller. Session sign on, authority check, dropdown preload, renders the display. |
| `Requisitions_dsp.php` | Display. The CSS, all HTML (grid and modals) and all JavaScript, inline in the one file. |
| `Requisitions_ajax.php` | AJAX router. JSON for everything: validates, backs out a failed insert, writes the activity log. |
| `Requisitions_model.php` | Db2 access. The rqs functions call `REQSTNnnnS` procedures only, with no inline SQL anywhere. |
| `request.php` | The old requisition address, now a redirect onto the new screen. |

### Keeping the old address alive

`request.php` keeps its original address so every bookmark, desktop
shortcut and station link still works. With no parameters it sends the
user to the entry form; with a requisition number it opens that
requisition. The redirect is the first thing the file does, so the old
browser check and the old password prompt never run.

It sends a temporary redirect rather than a permanent one on purpose: a
browser caches a permanent redirect indefinitely, which would keep
sending users to the new screen even after a rollback put the old file
back. `RQS_NEW_APP` at the top of the file holds the target address and
is the only line to change if the screen moves. Adding `shimtest` to the
old address prints the target instead of redirecting, which is how the
address is checked before anyone is pointed at it.

The rest of the old folder retires at cutover. `getEntry.php` and
`getIdInfo.php` hold only function definitions and print nothing on their
own, and the old save pages go with them, so once the folder is gone
nothing can write to the old database.

## 2. Tables (library currently LSCDEVLIBP)

| Table | Contents |
|---|---|
| RQSREQHDRT | One row per requisition. RHREQ# is a GENERATED identity, restarted at 17179 after the history load. Name, date and time, area code and type, rush flag, authorized flag and name, badge, comments. |
| RQSREQDTLT | One row per line, keyed by RDREQ# and RDLIN#. Item, location, coin date, description, qty, cost and retail as DEC(13,4), add cost, badge, SKU to, plus RDRTNF returned flag and RDRTDT date returned. |
| RQSCODEFLT | The code file: four dropdown lists in one table keyed by CDTYPE and CDCODE, holding AREACODE (3), AREATYPE (16), NAMES (65) and AUTHBY (6). The badge list is not stored here, it reads live from LSCPRDLIB/XEMPLOYP. "Authorization = None" is a real AUTHBY row that over 13,000 historical headers store, it sorts first, and it is the natural default. |

All three are journaled to LSCSAVLIB/LSCJRN. Dates follow the shop
convention of DEC(8,0) yyyymmdd plus DEC(6,0) hhmmss rather than date
types.

Data notes from the load: the old boolean values became Y and N, the
authorized flag is derived from the authorized by text, and historical
badges are a mix of real numbers and a truncated name from the old
screen.

## 3. Stored procedures

All nine are `CREATE OR REPLACE` with `SET OPTION DBGVIEW = *SOURCE,
DYNUSRPRF = *OWNER`, built with RUNSQLSTM from QSQLSRC members.

| Proc | Role | Notes |
|---|---|---|
| REQSTN001S | Insert header | Returns the new req number through IDENTITY_VAL_LOCAL(), which removes the old max plus one race. |
| REQSTN002S | Insert one line | Twelve IN parameters. |
| REQSTN003S | Grid lines | INSHOW CHAR(1) picks O for open (the default), R for returned, A for all. INSRCH VARCHAR(50) filters req number, name, item or badge, and blank returns everything. ROW_NUMBER caps returned lines to the 500 most recent so the full history never renders at once. |
| REQSTN004S | One requisition | Header LEFT JOIN all of its lines, ordered by line number. |
| REQSTN005S | Update header | Authorized by, comments and badge, where a NULL argument leaves that column unchanged, so the view window and the grid badge box share one procedure. RHAUTF is derived from the authorized by value. |
| REQSTN006S | Mark or unmark returned | Idempotent through a flag guard. INRTDT carries the entered return date and 0 stamps today. |
| REQSTN007S | The one lookup proc | INTYPE picks the cursor: any code list from RQSCODEFLT, BADGE for live active employees from LSCPRDLIB/XEMPLOYP, ITEM for autofill from the LSCPRDLIB/ITMMSTP item master with last used cost and retail from history, and ITEMSRCH for the searchable item list, first 200 per prefix. |
| REQSTN008S | Monthly report rows | Takes yyyymm and returns header and lines with extended cost and retail computed, plus RDRTNF and RDRTDT for the Returned column, ordered by name, date, req and line. |
| REQSTN009S | Delete a requisition | Detail then header. This is the insert back out, so a failed submit never leaves half a requisition. |

**The OR REPLACE gotcha:** `CREATE OR REPLACE PROCEDURE` does NOT replace
across different parameter counts. Changing a signature creates a second
overload and the old one lingers until it is dropped with
`DROP PROCEDURE name(type, type)`. Three procedures changed signature
during development, so a library that was built along the way needs these
dropped once: `DROP PROCEDURE REQSTN003S()`,
`DROP PROCEDURE REQSTN003S(CHAR)`,
`DROP PROCEDURE REQSTN005S(DECIMAL, VARCHAR, VARCHAR)` and
`DROP PROCEDURE REQSTN006S(DECIMAL, DECIMAL, CHAR)`.

## 4. Screens

**Station grid.** Two line records: the fields on line one (Req number,
Date, Requestor, Item number, Loc, Qty, Badge number, Authorized, Rush)
and the description plus the Return Item box on line two. Columns are
fixed pixels with the leftover width on Requestor, and below 780px the
wrapper scrolls sideways instead of crushing. Stripe, hover and selection
act on the whole record because the two rows are paired by a record id.
The Badge box saves on change. The Show dropdown requeries REQSTN003S
with the INSHOW code, and returned lines show their return date read only
in place of the Return Item box. Returned and All are capped to the 500
most recent on the server, and for those modes the Filter box drives an
INSRCH search over the whole history while Open filters its loaded rows
in the browser. Every column header sorts in the browser. The sticky
header draws its box and column lines with box shadow so they stay put
while scrolling. Auto refresh runs every 60 seconds and on tab
visibility, and a JSON compare skips the redraw when nothing changed.

**Entry form** (a modal on the station, the whole page under
`?mode=entry`). Header fields, then the spreadsheet style line sheet
where the cell is the box and the focused cell gets a slim blue inner
outline. Item number has autofill and a typeahead dropdown that opens on
focus, filters as you type, and takes Tab or Enter to pick. Arrow keys
move around the sheet, Enter hops fields, and Enter on the last box grows
the sheet.

**View window.** Update posts the authorized by name and comments, per
line Returned boxes post immediately and un return here as well, a Date
Ret. column shows when each returned line came back, and Print gives the
paper copy.

**Reports.** Monthly Update uses month and year dropdowns rather than
`input type=month`, which Firefox renders as a dead text box, and it
opens on the current month with no Run button. Both reports carry a
Returned column showing a green return date or a dash while the item is
still out, the preview lists every line, and the monthly totals are
compact single ruled lines so a busy month stays to few pages. The report
renders into a hidden zero size iframe on the current page and prints
that, so no separate window opens, and `print-color-adjust: exact` keeps
the band and totals backgrounds on paper. The on screen modal is id
scoped to `#rptBody` so the framework page global table gridlines do not
leak in and the preview matches the printout. Printing always brings up
the browser print dialog, which briefly holds the browser until the user
prints or cancels and cannot be avoided from a web page.

## 5. Workflows

**Insert.** The browser serializes the form into one JSON payload. The
insert action calls REQSTN001S for the header then REQSTN002S per line,
and any line failure calls REQSTN009S to back the whole requisition out
and returns a message naming the line that failed.

**Grid refresh.** `loadGrid()` first submits every pending Return Item
through REQSTN006S with the entered date, then pulls REQSTN003S with the
current Show code and, for Returned and All, the Filter text as the
search. Ticking Return Item only queues the return in the browser in a
map keyed by req and line that survives redraws, and the refresh is what
commits it. A pending return with an invalid date holds the refresh
rather than silently losing it.

**Update.** The view window sends the authorized by name and comments,
the badge box sends only the badge, and the missing fields ride as NULL
so REQSTN005S leaves those columns alone.

**Lookups.** The controller preloads the lists with the page to save a
round trip, and the ajax lookups action is the fallback if that preload
failed.

**Item autofill and search.** `itemlookup` runs on an exact item number
and `itemsearch` opens on focus with the full list, debounced 250ms while
typing and 200 rows per view. Both ride REQSTN007S.

## 6. Error model

- No `die()` anywhere near JSON. Model functions return false and stash
  the real Db2 message from `db2_stmt_errormsg` in `$GLOBALS['rqsErr']`
  and the PHP error log, then the ajax layer returns `{ok:false, msg}`
  with that message so support can act on what the user reports.
- The ajax endpoint buffers output from byte 0 and clears it before the
  JSON, so a stray include warning cannot corrupt a response.
- Background work such as auto refresh and autofill never pops dialogs,
  it turns the Updated stamp red instead. Foreground actions get the real
  message in a dialog.

## 7. Activity log

`requisition_activity.log` is written to the `LCCOnline_logs` folder
beside the PHP, one appended line per event holding the timestamp, user
profile, station IP, action and detail.

Events are OPEN (page load, station or entry form), INSERT (req and line
count), UPDATE (req, and the badge when changed from the grid), RETURN
and UNRETURN (req, line and entered date), and BACKOUT (a failed insert
rolled back).

It targets `LCCOnline_logs` rather than the docroot because the web
profile is not granted write to the docroot but is granted it there, so
pointing it at the docroot means no file ever appears. The write is
suppressed so a bad write can never take the app down, but on failure the
reason and the line fall to `error_log` (the instance php.log) instead of
vanishing. The monthly purge of that folder keys on modification time, so
a log that is actively appended to is never swept, and it should be
archived for multi year audit history.

## 8. Build, load, promote

1. RUNSQLSTM the three `.TABLE` members (RQSREQHDRT, RQSREQDTLT,
   RQSCODEFLT), then the nine `.PROC` members. Members are re runnable,
   and the library must never be hardcoded in source because that breaks
   promotion.
2. STRJRNPF the tables to LSCSAVLIB/LSCJRN with IMAGES(*BOTH)
   OMTJRNE(*OPNCLO).
3. Load the history with CPYFRMIMPF. The load files are already in Db2
   format because every conversion was done at export time.
4. Run `ALTER TABLE RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH 17179`.
5. Validate the load: 14,073 headers, 50,063 lines, 741 open lines,
   SUM(qty) of 33,464,119, and 90 code rows.
6. Copy the four PHP files to the LCCOnline docroot. The web profile
   needs authority to the library, where SQL0551 means a GRTOBJAUT is
   missing, plus write authority for the activity log folder.
7. Assign the RFP objects: 12 Db2 objects as `*SQLTAB` from LSCDEVLIBP
   plus the 4 PHP files as `*IFS` at level 10.

## 9. Deliberate changes from the old system

1. An identity column replaces the old max plus one numbering, which
   fixes the duplicate requisition race.
2. Bound parameter procedures replace SQL built by string concatenation,
   which closes the injection hole.
3. Session sign on with `chkAutUsr` replaces basic authentication, and
   the Firefox only browser check is gone.
4. The entry form grows lines as needed instead of showing 30 fixed rows.
5. The badge is stored properly at 10 characters and can be edited from
   the grid.
6. Returns from the grid require a date, autofilled to today and
   editable, and commit on refresh, while the view window still stamps
   today directly.
7. A failed submit backs out completely instead of half saving.
8. The Show filter can bring returned req forms back up with their return
   dates, which the old screen could never do.

## 10. Things not to do

- **Never add a parameter to a procedure without dropping the old
  signature.** `CREATE OR REPLACE` leaves the previous overload in place
  and the web profile may keep resolving to it, so the change appears to
  do nothing.
- **Never hardcode the library in source.** The members are promoted
  between libraries, and a hardcoded name breaks that.
- **Never point the activity log at the docroot.** The web profile cannot
  write there, and because the write is suppressed the file simply never
  appears.
- **Never lift the 500 row cap on Returned and All.** Roughly 49,000 of
  the 50,000 detail lines are returned, and drawing them all locks up the
  browser. Use the Filter search to reach older ones instead.
- **Never use REQSTN009S as a user facing delete.** It exists only to back
  out a half written insert. The old system had no delete either, and
  returning a line is what takes it off the grid.

## 11. Good to know

- The two passwords found in the old system are flagged for rotation at
  cutover. They were stored in plain text where anyone with the file
  could read them.
- The station grid opens on open lines only, but the Show filter surfaces
  returned lines too. Nothing is ever deleted.
- A blank page after a copy usually means a stale or corrupted file, so
  recopy it and press Ctrl+F5.
- SQL0551 on a procedure call means the web profile is missing authority
  to the library, which is a GRTOBJAUT, not a code problem.
