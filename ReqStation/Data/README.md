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
| RQSREQHT.csv | 14,073 | RQSREQHT |
| RQSREQDT.csv | 50,063 | RQSREQDT |
| RQSCODET.csv | 90 | RQSCODET (all four dropdown lists: 3 AREACODE, 16 AREATYPE, 65 NAMES, 6 AUTHBY) |

`ReqMaterial_Returns` was empty in production — no Db2 table, nothing to load.

## Load steps

1. Copy the CSVs to the IFS (e.g. `/home/REQSTN/import/`) and tag them UTF-8:

   ```
   CHGATR OBJ('/home/REQSTN/import/RQSREQHT.csv') ATR(*CCSID) VALUE(1208)
   ```
   (repeat per file)

2. Load each file (tables must exist and be empty; parents before children):

   ```
   CPYFRMIMPF FROMSTMF('/home/REQSTN/import/RQSREQHT.csv')
              TOFILE(LSCDEVLIBP/RQSREQHT) MBROPT(*REPLACE)
              RCDDLM(*ALL) FLDDLM(',') STRDLM('"')
   ```
   (repeat per file, RQSREQHT before RQSREQDT)

3. Restart the identity so new requisitions continue the sequence
   (17178 is the loaded max; MySQL's AUTO_INCREMENT agreed at 17179):

   ```
   ALTER TABLE LSCDEVLIBP/RQSREQHT ALTER COLUMN RHREQ# RESTART WITH 17179
   ```

## Validation — the loaded tables must reproduce these exactly

| Check | Expected |
|---|---|
| `SELECT COUNT(*) FROM RQSREQHT` | 14,073 |
| `SELECT COUNT(*) FROM RQSREQDT` | 50,063 |
| `SELECT COUNT(*) FROM RQSREQDT WHERE RDRTNF='N'` (open lines) | 741 |
| `SELECT SUM(RDQTY) FROM RQSREQDT` | 33,464,119 |
| `SELECT COUNT(*) FROM RQSREQHT WHERE RHAUTF='Y'` | 46 |
| `SELECT COUNT(*) FROM RQSREQHT WHERE RHRUSH='Y'` | 2,349 |
| `SELECT MAX(RHREQ#) FROM RQSREQHT` | 17,178 |
| next identity value (insert a test row, then delete it) | 17,179 |
