# AccessExport — machine extraction of Requisitions.mdb

Extracted 2026-07-17 on Linux directly from the uploaded `Requisitions.mdb`
(JET4 / Access 2000 format) using mdbtools, oletools, and a custom Jet
page-level parser for the VBA project. `Requisitions_Backup.mdb` was compared
and is identical (same schema, same row counts) — nothing separate was kept
from it.

| Folder | Contents | How it was extracted |
|---|---|---|
| `Tables/` | `_schema_all.sql` (local table DDL), `_objects.txt` (every form/report/macro/query/table), `_linked_tables.txt` (linked-table connect strings, **passwords redacted**), `_relationships.csv` | mdb-schema, MSysObjects, MSysRelationships |
| `Data/` | CSV of all 6 local Jet tables | mdb-export |
| `Queries/` | SQL of all 19 saved queries (incl. hidden `~sq_` form recordsources) | mdb-queries |
| `Modules/` | Complete VBA source: `BAS_MAIN.bas`, `Utility Functions.bas`, `Form_frmMain.cls`, `Form_Switchboard.cls`, `Form_Form1.cls`, `Report_rptRequest.cls` | Jet LVAL page reassembly of `MSysAccessObjects` → OLE2 compound doc → olevba |
| `Forms/`, `Reports/` | Readable strings from each form/report design blob (control names, recordsources, captions) — the binary layouts themselves are not convertible to text outside Access | strings dump of the project storage `Forms/N/Blob` streams |

## Known gaps / caveats

1. **Form and report layouts** are binary; the strings dumps identify controls
   and bindings but not positioning/formatting. For pixel-level reference,
   either run `../ExportAccessSource.bas` inside Access (SaveAsText output) or
   collect one screenshot per form/report. The frmMain screenshot already
   shared covers the main UI.
2. **mdb-queries reconstruction is imperfect**: `qryMain`'s export lost its
   join predicate (it reads as a cartesian join; the real join is almost
   certainly `ReqMaterial.req_num = ReqMaterialDetails.req_num`), and
   `Total Outstanding Requisitions` exported empty. Verify both inside Access.
3. **Linked-table data is not here.** The live requisition data (ReqMaterial,
   ReqMaterialDetails), plus `station`, `activity`, `applications`,
   `employee tbl`, `department tbl`, lives in the MySQL database `lcc` on
   `192.168.1.126` — a mysqldump of that database is still needed.
4. **Credentials were found and redacted** in `BAS_MAIN.bas` (AS400 ODBC
   `PICKAUTO` user) and in the `employee tbl` linked-table connect string
   (MySQL `lcc` user). The originals remain inside the `.mdb` files — treat
   those files as secret-bearing and rotate both passwords (see
   `../FINDINGS.md`).
