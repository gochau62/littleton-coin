# Requisitions RFP — object checklist and promotion plan

How to package this migration as an RFP, modeled directly on Sellbrite
RFP 3185 (appl `LCC`, project/task `PROJECTS-500`, tables as `*SQLTAB`
from `LSCDEVLIBP`, PHP as `*IFS` / `PHPSRC` from the dev web root).

## 1. Objects to assign to the RFP

### Db2 for i — `*SQLTAB` (MD attribute `SQLTAB_PRD`), dev library `LSCDEVLIBP`

| Object | Description | Source (this repo) |
|---|---|---|
| RQSREQHDRT | Requisition Header File | `Db2/RQSREQHDRT.TABLE` |
| RQSREQDTLT | Requisition Detail File | `Db2/RQSREQDTLT.TABLE` |
| RQSCODEFLT | Requisition Code File (the four seeded dropdown lists by CDTYPE) | `Db2/RQSCODEFLT.TABLE` |

### Db2 for i — SQL procedures (attribute per shop standard, e.g. `SQLPROC`)

| Object | Description | Source |
|---|---|---|
| REQSTN001S | Insert requisition header (OUT new req#) | `Db2/REQSTN001S.PROC` |
| REQSTN002S | Insert requisition detail line | `Db2/REQSTN002S.PROC` |
| REQSTN003S | Grid rows (INSHOW: O open, R returned, A all; INSRCH filter; returned/all capped to the 500 most recent) | `Db2/REQSTN003S.PROC` |
| REQSTN004S | Get one requisition (header + lines) | `Db2/REQSTN004S.PROC` |
| REQSTN005S | Update requisition header (authorized-by, comments, badge; NULL = unchanged) | `Db2/REQSTN005S.PROC` |
| REQSTN006S | Mark/unmark line returned (idempotent; caller-entered return date, 0 = today) | `Db2/REQSTN006S.PROC` |
| REQSTN007S | The one lookup proc: 4 code lists + live BADGE list (LSCPRDLIB/XEMPLOYP) + ITEM autofill/search (LSCPRDLIB/ITMMSTP) | `Db2/REQSTN007S.PROC` |
| REQSTN008S | Monthly Requisitioned Product summary (report) | `Db2/REQSTN008S.PROC` |
| REQSTN009S | Delete requisition (backs out failed inserts) | `Db2/REQSTN009S.PROC` |

Upgrade path once the IBM i version is confirmed 7.3+: a JSON_TABLE
insert proc replaces 001S/002S/009S with one atomic call — 12 objects
become 10.

Dropped 07/23/2026: the RQSACTLOGT activity log and the logging INSERTs
in procs 001/003/005/006/009 — nothing read it. Activity is instead
logged app-side to `requisition_activity.log` next to the PHP (same
`@file_put_contents` append pattern as `ClarioSFTP_pull.log`): one line
per station OPEN, INSERT, UPDATE, RETURN/UNRETURN and BACKOUT, with
timestamp, user and station IP. The file is created on first write and
is not an RFP object; the web profile needs write authority to the
htdocs folder. If the earlier versions were already built in dev, clean
up with:
`DROP TABLE LSCDEVLIBP/RQSACTLOGT`,
`DROP PROCEDURE LSCDEVLIBP/REQSTN003S()` and
`DROP PROCEDURE LSCDEVLIBP/REQSTN003S(CHAR)`,
`DROP PROCEDURE LSCDEVLIBP/REQSTN005S(DECIMAL, VARCHAR, VARCHAR)`, and
`DROP PROCEDURE LSCDEVLIBP/REQSTN006S(DECIMAL, DECIMAL, CHAR)` — the
new 003S now takes INSHOW CHAR(1) + INSRCH VARCHAR(50) (it passed
through a no-parameter and a single CHAR(1) version during development),
005S grew a fourth parameter (badge) and 006S a fourth (return date),
and `OR REPLACE` does not replace across different parameter counts, so
the old versions linger until dropped.

### IFS — `*IFS` (MD attribute `PHPSRC`), dev path `/www/seidendev/htdocs/requisitions/`

| Object | Description | Source |
|---|---|---|
| Requisitions_ctl.php | Controller (session sign-on, LCCONLINE check) | `Web/Requisitions_ctl.php` |
| Requisitions_dsp.php | Display function dspRequisitions(): grid + modals, styling and page JS inline | `Web/Requisitions_dsp.php` |
| Requisitions_model.php | Model (rqs* functions, CALL REQSTNnnnS only) | `Web/Requisitions_model.php` |
| Requisitions_ajax.php | Ajax dispatcher (JSON) | `Web/Requisitions_ajax.php` |
| request.php | Replaces the legacy entry page in place; redirects to the new screen | `Web/request.php` |

**request.php is an update to the existing production file, not a new
object.** It keeps the old address alive so every bookmark, desktop
shortcut and Access station link keeps working:

- `request.php` with no parameters redirects to
  `Requisitions_ctl.php?mode=entry` (the workfloor entry form).
- `request.php?id=N` redirects to `Requisitions_ctl.php?id=N`, so a link
  to one requisition still opens that requisition.
- The redirect is the first thing the file does, so the old Firefox-only
  check and the old Basic auth prompt never run. Workfloor users on any
  browser land on the new screen without a password box.
- It sends a **302, not a 301**, on purpose: browsers cache a 301
  permanently, so a permanent code would keep redirecting users even
  after the file was put back during a rollback.
- `RQS_NEW_APP` at the top is the only line to change when the target
  moves between instances.
- `request.php?shimtest=1` prints where it *would* send you instead of
  redirecting, which is how the redirect gets verified before anyone is
  pointed at it.

The other four legacy filenames (`getEntry.php`, `getIdInfo.php`,
`getInsert.php`, `getUpdate.php`) were only ever called *from*
request.php, never bookmarked directly, so they retire with the old
folder.

### Testing the redirect before cutover

The old page and the new screen sit on different instances, so the
redirect target is an absolute URL and can be checked without touching
production:

1. Put the new `request.php` on the **dev** copy of the old folder.
2. Open `request.php?shimtest=1` in a browser. It prints the address it
   would send you to. Nothing moves, so this is safe to run repeatedly.
3. Open `request.php?id=17204&shimtest=1` and confirm the number is
   carried through.
4. Drop `shimtest` and open `request.php` for real. You should land on
   the entry form, signed in through LCCOnline, with no Firefox warning
   and no password box.
5. Try it in Chrome or Edge as well. The old page refused anything but
   Firefox; the new one must not.
6. Enter one test requisition end to end, then open
   `request.php?id=` that new number and confirm it opens that
   requisition.

If step 4 lands on the LCCOnline sign-on page instead of the form, that
is correct behavior for a browser with no active session; sign in and it
continues to the form.

### Data (not RFP objects — one-time load inputs)

`Data/*.csv` — the full MySQL history, already transformed to Db2 format
(see `Data/README.md` for load commands and validation totals).

## 2. Build sequence in dev

1. `RUNSQLSTM` each `.TABLE` member into `LSCDEVLIBP` (order: RQSREQHDRT,
   RQSREQDTLT, RQSCODEFLT).
2. `RUNSQLSTM` the nine `.PROC` members.
3. Load the three `Data/*.csv` files with CPYFRMIMPF — the CSVs are
   already in Db2 format (transforms done), commands in `Data/README.md`.
4. Restart the identity:
   `ALTER TABLE RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH 17179`.
5. Validate against the expected totals in `Data/README.md` (14,073 headers,
   50,063 lines, 741 open, SUM(qty) 33,464,119); record results with the RFP.
6. Copy the `Web/` files to `/www/seidendev/htdocs/requisitions/`, wire the
   standard includes (`StartBlockHead.php`, `getDB2PConn`, `chkAutUsr` —
   confirm the LCCONLINE authority level number), and test end-to-end.

## 3. RFP mechanics (mirror Sellbrite 3185)

1. Create the RFP under appl `LCC`, tie it to the Requisitions project/task.
2. Assign the 12 Db2 objects and 5 IFS files above (level 10, same as
   Sellbrite). Status flows 01-Assigned → Created → promotion like any RFP.
3. The superseded legacy PHP files ride the same RFP as `*IFS` updates when
   they're stubbed/redirected at cutover.

## 4. Deliberate behavior changes vs legacy (flag these in the RFP notes)

1. Identity column replaces `max(req_num)+1` (fixes duplicate-req race).
2. Bound-parameter procs replace string-built SQL (fixes SQL injection).
3. Session sign-on + `chkAutUsr` replace HTTP Basic auth; the Firefox-only
   user-agent check is gone (any modern browser works).
4. Add form grows lines dynamically instead of 30 fixed rows.
5. Badge is stored properly (10 chars) instead of `substr(name, 0, 4)`;
   decide during data load whether to backfill real badges.
6. `authorized` flag is `Y`/`N` CHAR(1) instead of MySQL `1`/`0`.
7. The DataEntry badge is editable inline from the station grid (the
   grid's Badge # box calls REQSTN005S; it replaced the always-empty
   Returned column, which only ever applied to lines the open-only grid
   filters out anyway).
8. The grid has a Show filter (Open / Returned / All) so returned req
   forms can be brought back up - Access only ever showed open lines.
   REQSTN003S takes INSHOW + INSRCH; returned lines show their return
   date read-only, and the requisition view lists a Date Ret. column per
   line (who requested it, quantity and return date all visible). Since
   ~49k of the 50k lines are returned, Returned/All are capped to the
   500 most recent by return date and the Filter box searches the whole
   history server-side (req#/name/item/badge) to find older ones.
9. Every grid column header sorts (click to sort, click again to flip);
   the sticky header keeps its black box/column borders while scrolling.

## 5. Security actions from the legacy audit (do these regardless)

1. **Rotate the AS400 `PICKAUTO` password** — it is hard-coded in plain text
   in the VBA of every deployed Requisitions.mdb station copy (dead code,
   but it ships everywhere).
2. **Rotate the MySQL `lcc` user's password** — embedded in the `.mdb`
   linked-table connect string.
3. **Never commit the raw `.mdb` files to git** — they contain both
   credentials.

## 6. Resolved by the 07/20/2026 mysqldump

1. Schema confirmed and DDL corrected to match production: area_type
   VARCHAR(25), comments VARCHAR(500), loc VARCHAR(3), cost/retail
   DECIMAL(13,4), identity restart 17179.
2. `ReqMaterial_*` are all real tables (not views); their contents are in
   `Data/*.csv`.
3. `ReqMaterial_Returns` exists but is empty in production — intentionally
   not migrated.

## 7. Still open before promotion to prod

1. ~~The `activity` logging~~ — dropped 07/23/2026 (see the note under the
   procedures table): nothing read the log, so the table and the logging
   INSERTs were removed. The MySQL `station`/`applications` version-check
   machinery dies with the .mdb (the web app has no per-PC installs to
   version).
2. Exact MD attributes for views/procs per shop standard, and the LCCONLINE
   authority level number for `chkAutUsr`.
3. Web-profile authority to LSCDEVLIBP (the dev log already shows
   SQLCODE -551 against it for Sellbrite) — GRTOBJAUT before testing procs.
4. At go-live: replace `request.php` in the old `/requisitions/` folder
   with the redirect version (the rest of that folder retires), and push
   two shortcuts to the desktops:
   - **Workfloor / inventory handlers**: `Requisitions_ctl.php?mode=entry`
     — entry form only, nothing else visible; a fresh blank form after
     each submit (their replacement for the favorited request.php).
   - **IT / supervisors**: the plain URL — full station view (grid,
     authorize, returns, reports).
   Old saved links keep working through the request.php redirect, so
   nobody is stranded on day one if a shortcut was missed.

Reports are done and match the printed Access samples: Monthly Report
button = "Monthly Update: Requisitioned Product" (REQSTN008S; open layout
with serif navy headings, Req. Comments and stacked Req. Totals / Totals
by Name blocks; month picked from month/year dropdowns, not a browser
date field — Firefox has no month input); Preview Report / the Print
button in the requisition view = the old rptRequest (boxed line grid,
unreturned lines only).
