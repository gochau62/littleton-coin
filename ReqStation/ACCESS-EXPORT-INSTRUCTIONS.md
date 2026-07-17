# Req Station — Access Handover Instructions

How to get the Microsoft Access requisition program ("Req Station") into this
repo so the Db2-for-i / PHP migration can start. The Access `.accdb` file alone
is a binary blob — forms, VBA, and queries are not readable from it directly, so
the key step is exporting everything to **text** from inside Access first.

## Step 1 — Run the source exporter inside Access

1. Open the Req Station database in Access **on a machine where it works**
   (linked tables reachable). Use the real `.accdb` / `.mdb` — an `.accde`/`.mde`
   is compiled and has **no VBA source**; if all you have is an `.accde`, find
   the original `.accdb` it was built from.
2. Press `Alt+F11` to open the VBA editor.
3. `File → Import File…` and pick `ExportAccessSource.bas` (in this folder).
4. Put the cursor inside `ExportAllSource` and press `F5` (or type
   `ExportAllSource` in the Immediate window, `Ctrl+G`).
5. When the summary box appears, check `AccessExport\_export_log.txt` for any
   errors. The `AccessExport` folder is created next to the `.accdb`.

The export contains:

| Folder | Contents |
|---|---|
| `Modules/` | All VBA standard + class modules (`.bas`) |
| `Forms/` | Every form as text, **including its code-behind** |
| `Reports/` | Every report as text, including code-behind |
| `Macros/` | Macros as text |
| `Queries/` | Every saved query as `.sql` |
| `Tables/` | Per-table schema (fields, types, indexes), linked-table connect strings (passwords scrubbed), `_relationships.txt` |
| `Data/` | CSV of every **local** table |

## Step 2 — Dump the MySQL side (if tables are linked)

In the Access navigation pane, tables with a **globe/arrow icon** (in this DB
that looks like `activity`, `department tbl`, `employee tbl`) are *linked*
tables — their data lives in an external database (likely MySQL via ODBC), not
in the `.accdb`. The exporter records their connect strings in `Tables/`, but
the data itself needs a dump from the MySQL server:

```
mysqldump --routines --triggers --no-tablespaces <dbname> > reqstation_mysql.sql
```

If every table turns out to be local Jet/ACE (no globe icons), skip this step —
the CSVs in `Data/` already have everything.

## Step 3 — Commit it to this repo

Put everything under `ReqStation/` on branch
`claude/access-as400-migration-k4q7kx`:

```
ReqStation/
  AccessExport/            <- the whole exported folder from Step 1
  reqstation_mysql.sql     <- Step 2, if applicable
  ReqStation.accdb         <- the database file itself (optional but useful)
  screenshots/             <- one screenshot per form/report (optional but helpful)
```

Easiest ways to upload:

- **GitHub web UI**: open the repo, switch to the branch above, `Add file →
  Upload files`, drag the folders in, commit.
- **git CLI**: clone, `git checkout claude/access-as400-migration-k4q7kx`, copy
  the files in, commit, push.

Notes:

- GitHub rejects single files over 100 MB. Run **Database Tools → Compact and
  Repair** on the `.accdb` first; if it is still huge, the text export +
  CSVs + mysqldump are what actually matter — the `.accdb` itself is optional.
- The exporter scrubs `PWD=`/`PASSWORD=` from linked-table connect strings, but
  scan `Tables/*.txt` and the VBA in `Modules/` for hard-coded credentials
  before pushing — VBA connection strings sometimes embed passwords.
- Screenshots of each form and a sample of each printed report make rebuilding
  the UI much faster. The `frmMain` screenshot already shared is a good model.

## Ongoing source control (optional)

For continued Access development during the parallel-run period, the free
[msaccess-vcs add-in](https://github.com/joyfullservice/msaccess-vcs-addin)
does this same export automatically on every save, so Access changes keep
flowing into git until cutover.

## What happens next

See `MIGRATION-PLAN.md` in this folder — the exported source drives the
phases there: Db2 for i DDL (`.TABLE` members), stored procedures (`.PROC`,
CLARIO-style), data load, and the PHP ctl/dsp/model/ajax front end following
the PrintInvoices pattern.
