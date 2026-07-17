# Findings from Requisitions.mdb — what the app really is

Extracting the real database (see `AccessExport/`) changed the picture
substantially from what the screenshot suggested. Read this before the
migration plan — it supersedes several Phase 1 assumptions.

## The real architecture

```
Per-station PC:  Requisitions.mdb (Access front end, auto-updated from v:\copies\fix.bat)
                   |
                   |-- ODBC DSN=ABC ------------------> MySQL "lcc" @ 192.168.1.126
                   |                                      ReqMaterial          (requisition headers)
                   |                                      ReqMaterialDetails   (requisition lines - the live data)
                   |                                      ReqMaterial_*        (AreaCode/AreaType/AuthBy/ReqNames)
                   |                                      employee tbl / department tbl
                   |                                      station / activity / applications
                   |
                   |-- Shell(firefox) ----------------> http://lcc1.littletoncoin.com:10088/requisitions/request.php
                   |                                      (EXISTING PHP web app on the iSeries - adds/views requests)
                   |
                   '-- (defined but unused) AS400 ODBC   DSN=AS400, SERVER=LCC1 - dead code in BAS_MAIN
```

Key discoveries, with evidence:

1. **The live requisition data is NOT in Access and NOT local — it is MySQL.**
   frmMain's grid is bound to `qryMain`, which joins the ODBC-linked
   `ReqMaterial` (req_name, req_date, dataentry_badge, authorized,
   authorized_by) and `ReqMaterialDetails` (req_num, item_num, description,
   loc, quantity, returned, date_returned), filtered to `returned = 0`.
   The local `Requested Material Table` (102 rows, older schema with
   Cost/Retail columns) is a stale pre-MySQL leftover, as are the other
   local tables and most forms/macros ("Inventory Data Entry", course
   offerings, etc. — an older generation of the app).

2. **A PHP requisitions web app already exists.** The "Add Requests" globe
   button (`Command20`) and double-clicking a `req_num` shell out to
   `http://lcc1.littletoncoin.com:10088/requisitions/request.php[?id=N]`.
   So request entry is *already web-based on the iSeries*; the Access app is
   now only: the always-on station grid (with timer auto-refresh), the
   authorize/return-item touchpoints, two reports ("Requested Material
   Summary", `rptRequest`/`rptRequestDetail`), station identification
   (`station` table keyed by Access `CurrentUser()`), activity logging
   (open/close rows in `activity`), and a version check against
   `applications` that self-updates via `v:\copies\fix.bat`.

3. **The migration is therefore smaller than planned**: port the MySQL schema
   and data to Db2 for i, extend the existing PHP requisitions app (source
   needed from lcc1) with the grid/authorize/return/report pages, and retire
   the per-station .mdb + fix.bat updater entirely.

4. **Security — action needed regardless of migration:**
   - `BAS_MAIN.bas` hard-codes an AS400 ODBC login: `UID=PICKAUTO` with a
     plaintext password (redacted in the committed copy). That connection is
     dead code (nothing calls `getConnection(2)`), but the credential ships
     inside every deployed station copy of the .mdb. Rotate it.
   - The `employee tbl` link stores the MySQL `lcc` user's password in its
     connect string (also redacted here, still inside the .mdb). Rotate it too.
   - Neither uploaded .mdb should be committed to git as-is because both
     contain these plaintext credentials.

## What this changes in MIGRATION-PLAN.md

- Phase 1 (inventory) is largely **done** — see `AccessExport/`. The
  local-vs-linked question is answered; the "728 rows" question is answered
  (live data is in MySQL, shared by all stations — no per-station
  consolidation problem).
- Phase 2 (Db2 schema): model on the **MySQL** `ReqMaterial` /
  `ReqMaterialDetails` header-detail pair (the plan's RQSREQHT/RQSREQDT split
  is confirmed correct), not on the stale local tables. Column names come
  from `qryMain` and the form strings dumps. `activity`/`station`/
  `applications` get Db2 equivalents only if their functions survive
  (web session logging replaces `activity`; version-check/auto-update dies
  with the .mdb).
- Phase 5 (PHP): start from the existing `/requisitions/` PHP source on lcc1
  rather than greenfield — it already handles request entry against MySQL;
  the work is repointing it at Db2 procs and adding the grid/authorize/
  return/report pages.
- Phase 7 (parallel run): the "relink Access to Db2" option applies to the
  MySQL-linked tables; alternatively, since the web app already exists in
  users' workflow, a straight cutover of the data layer with the extended
  web app may be simpler than keeping Access alive at all.

## Still needed to complete the picture

1. `mysqldump --routines --triggers lcc` from 192.168.1.126 (schema + data;
   also reveals whether the `ReqMaterial_*` objects are tables or views).
2. The PHP source of the existing requisitions app from lcc1
   (`.../requisitions/request.php` and siblings).
3. Optional: SaveAsText layouts (run `ExportAccessSource.bas` in Access) or
   screenshots of `Requested Material Summary`, `rptRequest`, and
   `rptRequestDetail` for faithful report rebuilds.
