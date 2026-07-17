# Req Station Migration Plan

> **Update 2026-07-17:** `Requisitions.mdb` has been received and extracted —
> see `FINDINGS.md` and `AccessExport/`. Two findings supersede assumptions
> below: the live requisition data lives in **MySQL** (`lcc` @ 192.168.1.126,
> `ReqMaterial`/`ReqMaterialDetails` header-detail pair), and a **PHP web app
> already exists** at `lcc1.littletoncoin.com:10088/requisitions/`. Phase 1 is
> largely complete; read FINDINGS.md for how Phases 2, 5, and 7 shift.

Migrate the "Req Station" Microsoft Access application (v1.0.3, deployed per-PC as Req Station #1..#n)
to the shop-standard stack: **Db2 for i tables + SQL stored procedures + PHP web front end**, following
the conventions already in this repo (Clario/ for DDL and .PROC style, PrintInvoices/ for the PHP
ctl/dsp/ajax/js split, WavePickSearch/ for the db2_prepare model style).

---

## Phase 1 - Handover and Inventory

1. Collect the AccessExport kit output for the Req Station front end: object list, table schemas,
   relationships, query SQL, form/report definitions, VBA modules, and per-table CSV data dumps.
   Require a declared, consistent export encoding for every CSV (Windows-1252 or UTF-8 **without**
   a BOM) and record it in the export manifest - Phase 3 tags the IFS files with that CCSID.
2. Confirm the local-vs-linked split. The navigation pane shows globe (linked-table) icons on
   `activity`, `department tbl`, and `employee tbl` - these are almost certainly ODBC-linked **MySQL**
   tables. Everything else (`Area Table`, `Area Type`, `Inventory Data Entry`, `Requested Material Table`,
   `Requisitioner Table`, `Switchboard Items`) is local Jet/ACE.
3. Determine the deployment topology: is the local data a single shared split back-end, or does each
   Req Station PC carry its own local `.mdb`/`.accdb`? Check every station's linked-table paths. If
   per-station, the AutoNumber `req_num` values collide across stations on consolidation - plan a
   consolidation pass that either renumbers into the new identity or carries a composite
   (station, req_num) legacy key column - and capture every Phase 3 baseline count/checksum
   **per station**.
4. Inspect the VBA and queries for writes to the linked MySQL tables - `activity` (badge + date +
   code) looks like a transaction/clock log, and anything that writes through the link cannot be
   replaced by a static copy. The per-table read/write direction decided here gates Phase 3 step 4
   and the badge-validation procs.
5. Get a `mysqldump --no-data` and a full dump of the linked MySQL schema so we own both halves of the data.
6. Record baseline counts from Access before anything moves: ~728 rows in `Requested Material Table`
   (per station, if step 3 finds per-station data - the 728 may be a single station's count), plus
   counts for every other table. These are the Phase 3 validation targets.
7. Confirm what `Inventory Data Entry` actually holds versus `Requested Material Table` - the Phase 2
   item-source table and the item-lookup proc (REQSTN015S) both depend on the answer, so this cannot
   wait for Phase 8.
8. Check the actual badge values in `employee tbl` (min/max length, leading zeros, alphanumerics).
   If any leading zeros or non-digits exist, the badge columns in Phase 2 must be CHAR(n), not
   DECIMAL(9,0).
9. Note the naming landmines for the export scripts: the object named `Inventory Data Entry/Report`
   contains a **slash**, and the form field `Badge#` contains a **#**. Neither survives as-is in file
   names or column names - map them explicitly in the export manifest.

## Phase 2 - Target Schema on Db2 for i

One `.TABLE` source member per table in `LSCARCLIB/QSQLSRC`, using the CLRCUSSEGT style: header comment
block, `RUNSQLSTM SRCFILE(LSCARCLIB/QSQLSRC) SRCMBR(...) COMMIT(*NONE)`, short uppercase names ending
in `T`, 2-char column prefixes, `WITH DEFAULT NOT NULL`, `CCSID 37` on character columns (the
CLRCUSSEGT style: `CSSEGC CHAR(10) CCSID 37 WITH DEFAULT NOT NULL` - without an explicit CCSID the
table inherits the job/system CCSID), `RCDFMT`, and `LABEL ON` blocks.

Proposed tables (Access source -> Db2 member):

| Db2 table  | Prefix | Replaces                          | Key columns |
|------------|--------|-----------------------------------|-------------|
| RQSREQHT   | RH     | Requested Material Table (header) | RHREQ# (identity), RHRQDT DECIMAL(8,0), RHRQSN, RHSTN# |
| RQSREQDT   | RD     | Requested Material Table (lines)  | RDREQ#, RDLIN#, RDITEM CHAR(15), RDLOC, RDQTY DECIMAL(7,0), RDBDG#, RDAUTH, RDRTNF CHAR(1), RDSTAT CHAR(1) |
| RQSAREAT   | AR     | Area Table                        | ARAREA, ARDESC, ARTYPC |
| RQSARTYT   | AT     | Area Type                         | ATTYPC, ATDESC |
| RQSRQSNT   | RQ     | Requisitioner Table               | RQRQSN, RQNAME, RQDEPT, RQACTV CHAR(1) |
| RQSITEMT   | IT     | Inventory Data Entry (item master) | ITITEM CHAR(15), ITDESC |
| RQSDEPTT   | DP     | department tbl (MySQL)            | DPDEPT, DPDESC |
| RQSEMPT    | EM     | employee tbl (MySQL)              | EMBDG# DECIMAL(9,0), EMNAME, EMDEPT, EMACTV CHAR(1) |
| RQSACTVT   | AC     | activity (MySQL)                  | ACBDG#, ACDATE DECIMAL(8,0), ACCODE |

Notes:
1. If Req Station is truly line-per-request (frmMain shows one req_num per row), RQSREQHT/RQSREQDT can
   collapse to a single RQSREQT - decide after seeing the real Access schema; plan for the pair.
2. `RDSTAT` carries outstanding/filled state so "Total Outstanding Requisitions" is a simple predicate.
3. Add a date-conversion view per reporting table in the CLRCUSSEGV style (e.g. `RQSREQHV`) casting
   DECIMAL(8,0) dates to real DATE for the BI/PHP Query tool, with the zero null-date convention
   (see Phase 3) CASEd to NULL.
4. `RQSITEMT` assumes `Inventory Data Entry` is the item master behind the item-lookup combo. If
   item data actually comes from an existing corporate item master file on the i, drop RQSITEMT and
   point REQSTN015S at that file instead - Phase 1 step 7 settles it.
5. `EMBDG#`/`RDBDG#`/`ACBDG#` as DECIMAL(9,0) holds only if Phase 1 step 8 confirms badges are pure
   integers with no leading zeros; otherwise make every badge column CHAR(n) - a numeric column
   silently drops leading zeros and breaks badge-scan matching and the join back to `activity`.

Type mappings (apply uniformly):

| Access type        | Db2 for i target |
|--------------------|------------------|
| AutoNumber         | `GENERATED BY DEFAULT AS IDENTITY` on DECIMAL/INTEGER - BY DEFAULT (not ALWAYS) so the Phase 3 historical load can keep existing req_num values; restart the identity after load (Phase 3 step 3) |
| Yes/No (Return Item, Active) | `CHAR(1)` 'Y'/'N' (shop-visible); SMALLINT acceptable but CHAR(1) matches existing files |
| Date/Time          | Two options: (a) shop `DECIMAL(8, 0)` yyyymmdd + separate DECIMAL(6,0) hhmmss time, or (b) TIMESTAMP. **Recommend (a)** for consistency with CLRCUSSEGT and every existing query/proc in the shop; expose DATE via the companion view. |
| Text(n)            | `CHAR(n)` for fixed codes, `VARCHAR(n)` for descriptions |
| Currency           | `DECIMAL(11, 2)` |
| Memo/Long Text     | `VARCHAR(2000)` (confirm max used length from export) |

Access-isms to eliminate during schema design:
1. **Multi-value fields** - split into a proper child table.
2. **Attachment fields** - export files to the IFS, store the path.
3. **Lookup fields** (column-level combo definitions) - replace with plain FK columns + the combos
   live in the PHP UI, backed by procs.
4. `Switchboard Items` - do not migrate; the switchboard is replaced by the web page itself.
5. The `#` in `Badge#` maps cleanly (Db2 for i allows `#` in names - see `CSCUST#`), but the `/` in
   `Inventory Data Entry/Report` must not leak into any member or column name.

## Phase 3 - Data Load

1. Push the export-kit CSVs to the IFS (`/home/REQSTN/import/`). Before any load, strip UTF-8 BOMs
   (a BOM corrupts the first field of the first row) and tag each stream file with the encoding
   recorded in the Phase 1 manifest (`CHGATR OBJ('...') ATR(*CCSID) VALUE(1252)` for Windows-1252,
   `1208` for UTF-8) - CPYFRMIMPF converts based on the stream file's CCSID tag, and an untagged or
   mis-tagged file silently corrupts every non-ASCII character (accented employee names, curly
   quotes typed into memo fields).
2. Plain code tables (areas, area types, departments) load with
   `CPYFRMIMPF FROMSTMF(...) TOFILE(LSCARCLIB/RQSxxxxT) RCDDLM(*CRLF) FLDDLM(',') STRDLM('"')`.
   Any table containing dates or Yes/No fields must go through a transform load instead - CPYFRMIMPF
   cannot turn Access date strings like `1/5/2024 3:42:11 PM` into DECIMAL(8,0) yyyymmdd, and MySQL
   zero dates (`0000-00-00`) need the same treatment. Options: ACS Data Transfer for one-time hand
   loads, a staging table + SQL transform, or a one-off PHP loader using phpSpreadsheet in the
   ImportOrderFile/UploadGiftCard pattern (date reformatting to yyyymmdd, Y/N conversion, trimming) -
   the loader script is probably the path of least resistance since the transforms are nontrivial.
   Null/empty dates load as **0** by convention; the RQSREQHV view CASEs 0 to NULL DATE.
3. Identity seeding: historical req_num values insert straight into the `GENERATED BY DEFAULT`
   identity columns (Phase 2). After the load, restart every identity that replaced an Access
   AutoNumber: `ALTER TABLE RQSREQHT ALTER COLUMN RHREQ# RESTART WITH n` where n = MAX(RHREQ#)+1 -
   otherwise the first new web requisition gets req_num 1 and collides with history.
4. Load MySQL-side tables (`employee tbl`, `department tbl`, `activity`) from the mysqldump the same
   way - but only as the Phase 1 step 4 write-direction findings allow. A one-time snapshot is valid
   only for tables nothing writes through the link, and badge authorization against a static RQSEMPT
   copy goes stale immediately (new hires rejected, terminated employees still able to authorize).
   If employee/department data changes over time, pick the sync mechanism (scheduled pull job,
   federation, or point the procs at the authoritative source directly) **before go-live**, not after.
5. Validation, per table: (a) row count Db2 vs Access against the Phase 1 baselines (per station if
   the back end is per-station; ~728 on requisitions only if it proved to be one shared store),
   (b) checksums - SUM of quantity, SUM/MOD of req_num, count by month of req_date (zero-dates
   counted separately, excluded from the by-month buckets), count of Return Item = Yes - compared
   against the same aggregates run in Access before cutover, (c) identity restarted and verified:
   next generated value > MAX(loaded req_num), (d) encoding spot-check: query both sides for rows
   containing non-ASCII bytes and compare them character-for-character. Record results in the
   migration ticket.

## Phase 4 - Stored Procedures

One `.PROC` member per operation, CLARIO pattern: header comment block, `CREATE OR REPLACE PROCEDURE`,
`LANGUAGE SQL`, `MODIFIES/READS SQL DATA`, `SET OPTION DBGVIEW = *SOURCE, DYNUSRPRF = *OWNER`,
deployed via RUNSQLSTM. Naming: `REQSTNnnnS`.

| Proc       | Purpose |
|------------|---------|
| REQSTN001S | Insert requisition line (header+detail; returns new RHREQ# via OUT parm) |
| REQSTN002S | List outstanding requisitions (result set for the main grid; optional station/date filters) |
| REQSTN003S | Authorize requisition line by badge (validates badge against RQSEMPT, stamps RDAUTH) |
| REQSTN004S | Mark/unmark Return Item on a line |
| REQSTN005S | Monthly report aggregate (yyyymm in, result set of totals by item/area/requisitioner) |
| REQSTN006S | Insert requisitioner (Requisitioner Update Form's add path) |
| REQSTN007S | Update requisitioner |
| REQSTN008S | Deactivate requisitioner (sets RQACTV = 'N') |
| REQSTN009S | Insert area |
| REQSTN010S | Update area |
| REQSTN011S | Deactivate area |
| REQSTN012S | Insert area type |
| REQSTN013S | Update area type |
| REQSTN014S | Delete area type |
| REQSTN015S | Item lookup - item_num validation + description for the combo (source of the SB77A "Bdwsr Winter Clydesdale..." line), reading the item source settled in Phase 1 step 7 |
| REQSTN016S | Badge lookup - validate Badge# and return employee name/department for the combo |

No action-code parameters: every proc is single-operation, matching the CLARIO members and the three
separate check/update/insert procs (PLYGRND10S/11S/12S) called by AD_importOrderFile_model.php.

Add a `REQSTN001C.CLLE` wrapper only if any batch/scheduled job needs to drive PHP from the job
scheduler (CLARIO001C pattern); interactive use goes straight from PHP via `CALL`.

## Phase 5 - PHP Front End

Combine the PrintInvoices ctl/dsp/ajax/js layout with the separate `_model.php` file used by
WavePickSearch/UploadGiftCard (no demonstrated app carries all five files; PrintInvoices keeps its
model logic inside its ajax file plus an included `Utils/InvPrt_Utils.php` helper) - one app folder
`ReqStation/` on the web server:

1. `ReqStn_ctl.php` - controller; includes StartBlockHead/StartBlockBody, jQuery, swal, `ReqStn.js`,
   plus the mandatory auth boilerplate from both demonstrated ctl files: credentials from
   `$_SESSION['username']`/`$_SESSION['password']`, connection via `getDB2PConn()`, the whole page
   gated on `chkAutUsr($conn, $user, "LCCONLINE", 50)` with a `showNotAuthorized()` branch, and
   closed with `include("EndBlock.php")` (InvPrt_Print_Invoices_ctl.php, AIS_WavePickSearch_ctl.php).
2. `ReqStn_dsp.php` - display; renders the main grid and modals.
3. `ReqStn_model.php` - db2_prepare / db2_bind_param / `CALL REQSTNnnnS(?)` functions only,
   in the AD_importOrderFile_model.php style (one function per proc, die-with-db2_stmt_errormsg);
   consolidates the shared-helper role PrintInvoices gives to `Utils/InvPrt_Utils.php`.
4. `ReqStn_ajax.php` - ajax dispatcher mapping action names to model calls, returning JSON;
   re-establishes the session via `session_name(SESSION_NAME); session_start()` and reloads the
   session credentials before connecting (InvPrt_Print_Invoices_ajax.php pattern).
5. `ReqStn.js` - button wiring, grid refresh, swal alerts.

Access UI -> web mapping:

| Access piece | Web equivalent |
|--------------|----------------|
| frmMain continuous form | Main grid, populated by REQSTN002S via ajax; auto/manual **Refresh** re-fetches |
| Add Requests button | Modal (or inline blank row) posting to REQSTN001S; item_num combo hits REQSTN015S as-you-type and shows the description line under the field |
| Badge# combo | Input validated against REQSTN016S; invalid badge = swal error |
| "authorized by" / Authorization = None | Dropdown/action calling REQSTN003S; unauthorized rows display "None" |
| Return Item checkbox | Per-row checkbox posting via ajax to REQSTN004S |
| Monthly Report / Preview Report | Printable PHP page fed by REQSTN005S (print CSS), and/or expose the RQSREQHV view to the in-house PHP Query BI tool (see Documentation/Business Intelligence Tools (PHP Query).pdf) |
| STOP button / Switchboard | Not needed - browser navigation replaces both |

Multi-station: the web app replaces per-PC Access installs. "Station #12" becomes a session/login
attribute (captured at sign-on or derived from user profile), stored in `RHSTN#` - no more
per-machine deployments or version-1.0.3-on-some-PCs drift.

## Phase 6 - VBA Translation

1. After the AccessExport kit commits the VBA source to this repo, inventory every form/module event.
2. Expect mostly bound-form behavior (combo AfterUpdate filling the description line, requery on
   Refresh, default req_date = Date()) - this becomes proc logic + thin ajax handlers, not
   line-by-line translation.
3. Translate genuinely procedural code (report prep, validation rules, any DoCmd chains) into the
   matching proc or `ReqStn.js` function, and check each one off against the VBA inventory.

## Phase 7 - Parallel Run and Cutover

1. Decide the write topology first - exactly one store takes writes during the parallel period.
   Recommended: make the step-4 relink mandatory, so Db2 is the single store and both UIs read/write
   it (the zero-diff gate below is then meaningful). Otherwise declare Access/Jet the sole writer and
   run the web app read-only until cutover day - two live writers on diverging stores (Access on
   Jet, web on Db2) can never pass the report diff.
2. Keep Access alive; dual-run for an agreed period (suggest one full monthly-report cycle).
3. Each week and at month-end, diff Monthly Report totals: Access report vs REQSTN005S output.
   Zero-diff for a full cycle is the cutover gate.
4. **Interim step (recommended, but a mini-project with its own test pass - not invisible):** relink
   the Access front end's tables to Db2 for i using the IBM i Access ODBC driver, so data moves to
   Db2 *first* while the familiar Access UI keeps working. A raw relink against the Phase 2 types
   breaks the forms: DECIMAL(8,0) yyyymmdd dates defeat the continuous form's date display/sorting
   and VBA `Date()` arithmetic, the Return Item checkbox cannot bind to CHAR(1) 'Y'/'N', and Access
   may not retrieve the identity-assigned req_num after insert (`@@IDENTITY` over IBM i ODBC is
   unreliable). So link Access to **updatable compatibility views with INSTEAD OF triggers** that
   present real DATE and boolean-shaped columns, select a unique key at link time (required for the
   linked tables to be updateable at all), and budget front-end rework and regression testing. Then
   Phase 5 swaps the UI at leisure, and cutover day is just "use this URL instead."
5. Cutover: freeze Access edits, final delta load + Phase 3 validation, set Access front ends
   read-only (or replace with a "moved to <URL>" splash), archive the .accdb/.mdb files. In the
   no-relink variant, define the delta-load key mapping up front: Jet AutoNumber req_num values in
   the delta can overlap identities already consumed in Db2, so map delta rows through a legacy-key
   column instead of assuming req_num survives.
6. Rollback: keep the relinked Access front end (pointed at Db2) installed and working for ~4 weeks
   post-cutover as the fallback UI, with written criteria for invoking it (e.g. web-app outage or a
   blocking defect not fixable same-day) and the steps to do so. Because both UIs share the Db2
   store, nothing is lost switching back. The archived .mdb is a snapshot only - rows created in Db2
   after cutover cannot flow back to Jet, so it is never the fallback.

## Phase 8 - Open Questions for the Team

1. Where does the linked MySQL database live (host, owner, is anything else using `employee tbl` /
   `department tbl` / `activity`)? Copy-once vs ongoing sync and the per-table write direction are
   settled in Phase 1 step 4 / Phase 3 step 4, not left open here.
2. Is the full `.accdb`/`.mdb` source available, or only a compiled `.accde` (no VBA source)? Phase 6
   depends on the answer.
3. Web-app auth details beyond the house convention (Phase 5 already commits to the LCCONLINE
   session sign-on gated by `chkAutUsr`): what authority level number to check, whether stations
   also need badge-scan capture at the terminal, and who may authorize vs merely request.
4. Print requirements: does the Monthly Report need pixel-faithful output, or are printable HTML /
   PHP Query extracts acceptable?
5. Retention: migrate all 728+ historical requisitions into RQSREQHT/RQSREQDT (recommended - it's
   tiny), or archive-and-start-fresh?
6. (Answered in Phase 1 step 7, since the Phase 2 item source and REQSTN015S depend on it.) Confirm
   what `Inventory Data Entry` vs `Requested Material Table` each actually hold - the form/report
   named `Inventory Data Entry/Report` suggests overlap that may collapse into one table.
