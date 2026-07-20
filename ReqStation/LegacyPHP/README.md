# LegacyPHP — the existing requisitions web app (reference copy)

The five files served from `lcc1.littletoncoin.com:10088/requisitions/`,
committed as-is for reference during the migration. `RQUtils/dbStr.php`
(which holds the MySQL connection credentials) was deliberately **not**
committed.

| File | Role | Replaced by |
|---|---|---|
| `request.php` | Router: Basic auth + Firefox sniff, `?id=` shows a record, otherwise entry form | `../Web/ReqStn_ctl.php` |
| `getEntry.php` | Entry form: 30 fixed line rows, lookups from `ReqMaterial_*` | Add-Requisition modal in `ReqStn_dsp.php`/`ReqStn.js` |
| `getInsert.php` | Insert: `max(req_num)+1` then header + 30 line inserts | `REQSTN001S`/`REQSTN002S` via `ReqStn_ajax.php` |
| `getIdInfo.php` | View one requisition | View modal via `REQSTN004S` |
| `getUpdate.php` | Authorize (`authorized='1'`, `authorized_by`) | `REQSTN005S` |

Problems in this code the replacement fixes (don't carry these forward):

1. **SQL injection everywhere** — every query interpolates `$_REQUEST`
   values directly into SQL strings. The new code only calls stored
   procedures with bound parameters.
2. **`Select max(req_num)+1` race** — two stations submitting at the same
   moment get the same req_num. Replaced by a Db2 identity column.
3. **Basic auth + Firefox-only user-agent sniff** — replaced by the shop's
   standard session sign-on and `chkAutUsr` authority check.
4. **`getUpdate.php` is broken as written** — `mysqli_query($query)` is
   called without the connection handle and `getCon()`'s return value is
   discarded, so authorize likely only works by accident of globals.
5. **`badge` stores `substr(req_name, 0, 4)`** — a workaround noted in the
   07/01/26 comments after a MySQL v8 upgrade truncated the column. The new
   schema keeps a real 10-char badge column on header and detail.
