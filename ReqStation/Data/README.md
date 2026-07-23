# Data — load-ready CSVs from the MySQL dump

Generated from Brian's `RequistionsTablesCreates.sql` (mysqldump of `lcc`,
taken 07/20/2026). Every transform is already applied — these load straight
into the Db2 tables with `CPYFRMIMPF`, no staging tables needed:

- datetimes → `DECIMAL(8,0)` yyyymmdd + `DECIMAL(6,0)` hhmmss (NULL/zero → 0)
- MySQL `-1`/`1`/`0` booleans (rush, authorized, returned) → `Y`/`N`
- NULL strings → `''`, NULL numerics → `0`
- detail line numbers (`RDLIN#`) minted 1..n per requisition in dump order
- column order matches each Db2 table exactly

| File | Rows | Loads into |
|---|---|---|
| RQSREQHDRT.csv | 14,073 | RQSREQHDRT |
| RQSREQDTLT.csv | 50,063 | RQSREQDTLT |
| RQSCODEFLT.csv | 90 | RQSCODEFLT (all four dropdown lists: 3 AREACODE, 16 AREATYPE, 65 NAMES, 6 AUTHBY) |

`ReqMaterial_Returns` was empty in production — no Db2 table, nothing to load.

## Easiest load: the seeder page

Copy `Web/Requisitions_seed.php` next to the other Requisitions files,
open it in the browser (signed in to LCC Online), and pick each CSV
straight from your PC — no IFS copy, no CHGATR, no commands. It detects
the target table from the filename, clears and loads it with batched
commits, restarts the identity after the header load, and prints the
validation counts. Load order: RQSREQHDRT → RQSREQDTLT → RQSCODEFLT.
Dev tool only — do not assign it to the RFP. The CPYFRMIMPF route below
does the same job from the command line.

## Load steps (CPYFRMIMPF route)

1. Copy the CSVs to the IFS (e.g. `/home/REQSTN/import/`) and tag them UTF-8:

   ```
   CHGATR OBJ('/home/REQSTN/import/RQSREQHDRT.csv') ATR(*CCSID) VALUE(1208)
   ```
   (repeat per file)

2. Load each file (tables must exist and be empty; parents before children):

   ```
   CPYFRMIMPF FROMSTMF('/home/REQSTN/import/RQSREQHDRT.csv')
              TOFILE(LSCDEVLIBP/RQSREQHDRT) MBROPT(*REPLACE)
              RCDDLM(*ALL) FLDDLM(',') STRDLM('"')
   ```
   (repeat per file, RQSREQHDRT before RQSREQDTLT)

3. Restart the identity so new requisitions continue the sequence
   (17178 is the loaded max; MySQL's AUTO_INCREMENT agreed at 17179):

   ```
   ALTER TABLE LSCDEVLIBP/RQSREQHDRT ALTER COLUMN RHREQ# RESTART WITH 17179
   ```

## Validation — the loaded tables must reproduce these exactly

| Check | Expected |
|---|---|
| `SELECT COUNT(*) FROM RQSREQHDRT` | 14,073 |
| `SELECT COUNT(*) FROM RQSREQDTLT` | 50,063 |
| `SELECT COUNT(*) FROM RQSREQDTLT WHERE RDRTNF='N'` (open lines) | 741 |
| `SELECT SUM(RDQTY) FROM RQSREQDTLT` | 33,464,119 |
| `SELECT COUNT(*) FROM RQSREQHDRT WHERE RHAUTF='Y'` | 46 |
| `SELECT COUNT(*) FROM RQSREQHDRT WHERE RHRUSH='Y'` | 2,349 |
| `SELECT MAX(RHREQ#) FROM RQSREQHDRT` | 17,178 |
| next identity value (insert a test row, then delete it) | 17,179 |
