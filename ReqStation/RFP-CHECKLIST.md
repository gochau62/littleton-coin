# Requisitions RFP — object checklist and promotion plan

How to package this migration as an RFP, modeled directly on Sellbrite
RFP 3185 (appl `LCC`, project/task `PROJECTS-500`, tables as `*SQLTAB`
from `LSCDEVLIBP`, PHP as `*IFS` / `PHPSRC` from the dev web root).

## 1. Objects to assign to the RFP

### Db2 for i — `*SQLTAB` (MD attribute `SQLTAB_PRD`), dev library `LSCDEVLIBP`

| Object | Description | Source (this repo) |
|---|---|---|
| RQSREQHT | Requisition Header File | `Db2/RQSREQHT.TABLE` |
| RQSREQDT | Requisition Detail File | `Db2/RQSREQDT.TABLE` |
| RQSAREAT | Requisition Area Code File | `Db2/RQSAREAT.TABLE` |
| RQSARTYT | Requisition Area Type File | `Db2/RQSARTYT.TABLE` |
| RQSRQSNT | Requisitioner Names File | `Db2/RQSRQSNT.TABLE` |
| RQSAUTHT | Requisition Authorizers File | `Db2/RQSAUTHT.TABLE` |
| RQSREQHV | Requisition Header View w/Dates (attribute per shop view standard) | `Db2/RQSREQHV.VIEW` |

### Db2 for i — SQL procedures (attribute per shop standard, e.g. `SQLPROC`)

| Object | Description | Source |
|---|---|---|
| REQSTN001S | Insert requisition header (OUT new req#) | `Db2/REQSTN001S.PROC` |
| REQSTN002S | Insert requisition detail line | `Db2/REQSTN002S.PROC` |
| REQSTN003S | List open requisitions (main grid) | `Db2/REQSTN003S.PROC` |
| REQSTN004S | Get one requisition (header + lines) | `Db2/REQSTN004S.PROC` |
| REQSTN005S | Authorize requisition | `Db2/REQSTN005S.PROC` |
| REQSTN006S | Mark/unmark line returned | `Db2/REQSTN006S.PROC` |
| REQSTN007S | List requisitioner names | `Db2/REQSTN007S.PROC` |
| REQSTN008S | List area codes | `Db2/REQSTN008S.PROC` |
| REQSTN009S | List area types | `Db2/REQSTN009S.PROC` |
| REQSTN010S | List authorizers | `Db2/REQSTN010S.PROC` |

### IFS — `*IFS` (MD attribute `PHPSRC`), dev path `/www/seidendev/htdocs/requisitions/`

| Object | Description | Source |
|---|---|---|
| Requisitions_ctl.php | Controller (session sign-on, LCCONLINE check) | `Web/Requisitions_ctl.php` |
| Requisitions_dsp.php | Display function dspRequisitions(): grid + modals, styling and page JS inline | `Web/Requisitions_dsp.php` |
| Requisitions_model.php | Model (rqs* functions, CALL REQSTNnnnS only) | `Web/Requisitions_model.php` |
| Requisitions_ajax.php | Ajax dispatcher (JSON) | `Web/Requisitions_ajax.php` |

### Existing IFS files superseded (retire on cutover, do not delete during parallel run)

`request.php`, `getEntry.php`, `getIdInfo.php`, `getInsert.php`,
`getUpdate.php`, `RQUtils/*` — reference copies preserved in this
branch's git history (along with the full Access extraction: VBA,
queries, schemas, and data CSVs).

### Data (not RFP objects — one-time load inputs)

`Data/*.csv` — the full MySQL history, already transformed to Db2 format
(see `Data/README.md` for load commands and validation totals).

## 2. Build sequence in dev

1. `RUNSQLSTM` each `.TABLE` member into `LSCDEVLIBP` (order: RQSREQHT,
   RQSREQDT, then the lookups, then RQSREQHV).
2. `RUNSQLSTM` the ten `.PROC` members.
3. Load all six tables from `Data/*.csv` with CPYFRMIMPF — the CSVs are
   already in Db2 format (transforms done), commands in `Data/README.md`.
4. Restart the identity:
   `ALTER TABLE RQSREQHT ALTER COLUMN RHREQ# RESTART WITH 17179`.
5. Validate against the expected totals in `Data/README.md` (14,073 headers,
   50,063 lines, 741 open, SUM(qty) 33,464,119); record results with the RFP.
6. Copy the `Web/` files to `/www/seidendev/htdocs/requisitions/`, wire the
   standard includes (`StartBlockHead.php`, `getDB2PConn`, `chkAutUsr` —
   confirm the LCCONLINE authority level number), and test end-to-end.

## 3. RFP mechanics (mirror Sellbrite 3185)

1. Create the RFP under appl `LCC`, tie it to the Requisitions project/task.
2. Assign the 17 Db2 objects and 4 IFS files above (level 10, same as
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

1. The `activity`/`station`/`applications` logging: dies with the .mdb, or
   gets a Db2 equivalent (decide before cutover).
2. Monthly report page (Access "Requested Material Summary") — next RFP
   increment; the RQSREQHV view is already in place for it or for PHP Query.
3. Exact MD attributes for views/procs per shop standard, and the LCCONLINE
   authority level number for `chkAutUsr`.
