<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_model.php     *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
/*
 * DB2 for i data-access layer for the Sellbrite Bulk Loader.
 *
 * Backs the AJAX contract in SellbriteBulkLoader_ajax.php:
 *     sblGetAll($q)  list / search    (grid)
 *     sblFind($id)   one row          (edit form)
 *     sblSave($row)  insert or update (returns id)
 *     sblDelete($id) remove
 *
 * Design notes:
 *  - Inline parameterized SQL (chosen for an 85-column table). The column list
 *    is read from Schema::columns() so it always matches the data definition.
 *  - DB2 for i returns column names UPPER-cased; every fetched row is run through
 *    array_change_key_case(... CASE_LOWER) so the PHP layer keeps its lowercase
 *    machine names (sku, category_name, ...).
 *  - The screen allows saving in-progress rows, so blanks and "*** HINT ***"
 *    placeholder text are coerced to NULL (and bad numbers/dates to NULL) before
 *    they reach typed columns.
 *  - No die() on DB errors: failures are logged and the function returns
 *    false / [] so the AJAX endpoint can still answer with JSON.
 */

require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema (column list)

if (!defined('SBL_TABLE')) {
    // SQL naming (schema.table). Matches how the other LCC tools reference DB2 objects.
    define('SBL_TABLE', 'LSCDEVLIBP.SBLPRODUCT');
}
// Reference tables that back the dropdowns and the spreadsheet VLOOKUPs, so
// they can be maintained from a web page instead of being hard-coded in
// SellbriteBulkLoader_data.php.
//   SBLVALUET - dropdown option lists
//   SBLAUTOFT - generic auto-fill rules (when_field=value -> set_field=value),
//               one table that covers all category defaults + grade lookups and
//               any new rule you care to add.
if (!defined('SBL_VALUE_TABLE')) { define('SBL_VALUE_TABLE', 'LSCDEVLIBP.SBLVALUET'); }
if (!defined('SBL_AUTO_TABLE'))  { define('SBL_AUTO_TABLE',  'LSCDEVLIBP.SBLAUTOFT'); }

/** Open (once) and reuse a DB2 connection from the session credentials. */
function sbl_conn()
{
    static $conn = null;
    if ($conn !== null) { return $conn; }
    if (!function_exists('db2_prepare')) { return $conn = false; }   // ibm_db not loaded (dev)
    $user     = $_SESSION['username'] ?? '';
    $password = $_SESSION['password'] ?? '';
    $conn = function_exists('getDB2PConn') ? getDB2PConn($user, $password) : false;
    return $conn;
}

/** Log a DB error through the LCCOnline logger when available; always returns false. */
function sbl_db_err($where)
{
    $msg = 'SellbriteBulkLoader_model ' . $where . ': '
         . (function_exists('db2_stmt_errormsg') ? db2_stmt_errormsg() : 'unknown');
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($msg); }
    return false;
}

/** Normalize a user date (MM/DD/YY, MM/DD/YYYY, YYYY-MM-DD) to ISO, or '' if unusable. */
function sbl_norm_date($v)
{
    $v = trim((string) $v);
    if ($v === '') { return ''; }
    $ts = strtotime($v);
    return $ts === false ? '' : date('Y-m-d', $ts);
}

/** Coerce one value to what its DB2 column expects (NULL for blanks/placeholders/bad input). */
function sbl_coerce($name, $val)
{
    static $ints = ['quantity', 'set_count', 'advent_calendar_number_of_items'];
    static $decs = ['price', 'cost', 'original_retail',
                    'package_length', 'package_width', 'package_height', 'package_weight',
                    'advent_calendar_item_height', 'advent_calendar_item_length',
                    'advent_calendar_item_width', 'advent_calendar_item_weight'];
    static $dates = ['creation_date'];

    $v = is_string($val) ? trim($val) : $val;
    if ($v === null) { $v = ''; }
    // "*** SELECT GRADE ***" style hints are guidance, not data.
    if (is_string($v) && strncmp($v, '***', 3) === 0) { $v = ''; }

    if (in_array($name, $ints, true)) {
        if ($name === 'quantity') { return ($v === '' || !is_numeric($v)) ? 0 : (int) $v; }
        return ($v === '' || !is_numeric($v)) ? null : (int) $v;
    }
    if (in_array($name, $decs, true)) {
        return ($v === '' || !is_numeric($v)) ? null : (string) $v;
    }
    if (in_array($name, $dates, true)) {
        $d = sbl_norm_date($v);
        return $d === '' ? null : $d;
    }
    if ($name === 'sku') { return (string) $v; }     // NOT NULL — let the DB enforce
    return $v === '' ? null : $v;                     // other text: '' -> NULL
}

/** The 85 product column names, in schema order. */
function sbl_columns()
{
    static $cols = null;
    if ($cols === null) { $cols = array_column(Schema::columns(), 'name'); }
    return $cols;
}

/** Run a prepared SELECT with params and return all rows as lowercase-keyed assoc arrays. */
function sbl_select($sql, array $params = [])
{
    $conn = sbl_conn();
    if (!$conn) { return []; }
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { sbl_db_err('prepare'); return []; }
    if (!db2_execute($stmt, $params)) { sbl_db_err('execute'); return []; }
    $rows = [];
    while ($r = db2_fetch_assoc($stmt)) {
        $rows[] = array_change_key_case($r, CASE_LOWER);
    }
    return $rows;
}

/* ------------------------------------------------------------------ */
/*  Public API (called by SellbriteBulkLoader_ajax.php / _ctl.php)     */
/* ------------------------------------------------------------------ */

/** List rows for the grid, optionally filtered by a search string. */
function sblGetAll($q = '')
{
    $q = trim((string) $q);
    $sql = 'SELECT id, sku, category_name, name, grade, price, quantity, '
         . "VARCHAR_FORMAT(updated_at, 'YYYY-MM-DD HH24:MI') AS updated_at "
         . 'FROM ' . SBL_TABLE;
    $params = [];
    if ($q !== '') {
        $like = '%' . strtoupper($q) . '%';
        $sql .= ' WHERE UPPER(sku) LIKE ? OR UPPER(category_name) LIKE ? OR UPPER(name) LIKE ?';
        $params = [$like, $like, $like];
    }
    $sql .= ' ORDER BY updated_at DESC';
    return sbl_select($sql, $params);
}

/** Fetch a single row (full record) for the edit form, or false if not found. */
function sblFind($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    $rows = sbl_select('SELECT * FROM ' . SBL_TABLE . ' WHERE id = ?', [$id]);
    return $rows[0] ?? false;
}

/** Insert a new product row; returns the new id, or false on error. */
function sblInsert(array $row)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $cols = sbl_columns();
    $sql  = 'INSERT INTO ' . SBL_TABLE . ' (' . implode(', ', $cols) . ') VALUES ('
          . implode(', ', array_fill(0, count($cols), '?')) . ')';
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return sbl_db_err('insert prepare'); }
    $vals = [];
    foreach ($cols as $c) { $vals[] = sbl_coerce($c, $row[$c] ?? null); }
    if (!db2_execute($stmt, $vals)) { return sbl_db_err('insert execute'); }
    return (int) db2_last_insert_id($conn);
}

/** Update an existing product row by id; returns true on success. */
function sblUpdate($id, array $row)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $cols = sbl_columns();
    $set  = implode(', ', array_map(static fn($c) => $c . ' = ?', $cols));
    $sql  = 'UPDATE ' . SBL_TABLE . ' SET ' . $set . ' WHERE id = ?';
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return (bool) sbl_db_err('update prepare'); }
    $vals = [];
    foreach ($cols as $c) { $vals[] = sbl_coerce($c, $row[$c] ?? null); }
    $vals[] = (int) $id;
    if (!db2_execute($stmt, $vals)) { return (bool) sbl_db_err('update execute'); }
    return true;
}

/** Insert or update depending on whether the row carries an id. Returns the id (or false). */
function sblSave(array $row)
{
    $id = (int) ($row['id'] ?? 0);
    if ($id > 0) { return sblUpdate($id, $row) ? $id : false; }
    return sblInsert($row);
}

/** Delete a product row by id; returns true on success. */
function sblDelete($id)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $id = (int) $id;
    if ($id <= 0) { return false; }
    $stmt = db2_prepare($conn, 'DELETE FROM ' . SBL_TABLE . ' WHERE id = ?');
    if (!$stmt) { return (bool) sbl_db_err('delete prepare'); }
    if (!db2_execute($stmt, [$id])) { return (bool) sbl_db_err('delete execute'); }
    return true;
}

/* ------------------------------------------------------------------ */
/*  Reference data (dropdown lists + VLOOKUP tables)                   */
/*                                                                     */
/*  These functions back the maintenance screen and feed Schema. Two   */
/*  kinds of call:                                                     */
/*    - sblLoad*()  : whole-table loaders shaped exactly like the old  */
/*                    SellbriteBulkLoader_data.php arrays, so Schema    */
/*                    can read from DB2 with the PHP file as fallback.  */
/*    - CRUD        : add / update / delete used by the web page.       */
/* ------------------------------------------------------------------ */

/** Run a prepared write (INSERT/UPDATE/DELETE); returns true on success. */
function sbl_exec($sql, array $params, $where)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return (bool) sbl_db_err($where . ' prepare'); }
    if (!db2_execute($stmt, $params)) { return (bool) sbl_db_err($where . ' execute'); }
    return true;
}

/* ---- Dropdown value lists (SBLVALUET) ---------------------------- */

/**
 * Load every active dropdown option, grouped by list, in sort order.
 * Returns [ list_name => [ value_text, ... ], ... ] — the shape Schema
 * expects for 'values'.  Returns [] when there is no DB (dev/offline).
 */
function sblLoadValues()
{
    $rows = sbl_select(
        'SELECT list_name, value_text FROM ' . SBL_VALUE_TABLE
      . " WHERE active = 'Y' ORDER BY list_name, sort_seq, value_text"
    );
    $out = [];
    foreach ($rows as $r) { $out[$r['list_name']][] = $r['value_text']; }
    return $out;
}

/** Distinct list names (for the maintenance page's list picker). */
function sblValueLists()
{
    $rows = sbl_select('SELECT DISTINCT list_name FROM ' . SBL_VALUE_TABLE . ' ORDER BY list_name');
    return array_column($rows, 'list_name');
}

/** All option rows for one list (full detail for the editing grid). */
function sblValueRows($list)
{
    return sbl_select(
        'SELECT id, list_name, value_text, sort_seq, is_header, active FROM ' . SBL_VALUE_TABLE
      . ' WHERE list_name = ? ORDER BY sort_seq, value_text',
        [(string) $list]
    );
}

/** Add one option to a list; returns the new id (or false). */
function sblValueAdd($list, $value, $isHeader = 'N')
{
    $list = trim((string) $list); $value = trim((string) $value);
    if ($list === '' || $value === '') { return false; }
    $conn = sbl_conn();
    if (!$conn) { return false; }
    // Append to the end of the list (max sort_seq + 10).
    $seqRows = sbl_select('SELECT COALESCE(MAX(sort_seq), 0) + 10 AS nextseq FROM '
                        . SBL_VALUE_TABLE . ' WHERE list_name = ?', [$list]);
    $seq = (int) ($seqRows[0]['nextseq'] ?? 10);
    $ok = sbl_exec(
        'INSERT INTO ' . SBL_VALUE_TABLE
      . ' (list_name, value_text, sort_seq, is_header) VALUES (?, ?, ?, ?)',
        [$list, $value, $seq, ($isHeader === 'Y' ? 'Y' : 'N')], 'value add'
    );
    return $ok ? (int) db2_last_insert_id($conn) : false;
}

/** Update one option row (text / sort / header / active). */
function sblValueUpdate($id, array $fields)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec(
        'UPDATE ' . SBL_VALUE_TABLE
      . ' SET value_text = ?, sort_seq = ?, is_header = ?, active = ? WHERE id = ?',
        [
            trim((string) ($fields['value_text'] ?? '')),
            (int) ($fields['sort_seq'] ?? 0),
            (($fields['is_header'] ?? 'N') === 'Y' ? 'Y' : 'N'),
            (($fields['active'] ?? 'Y') === 'N' ? 'N' : 'Y'),
            $id,
        ], 'value update'
    );
}

/** Delete one option row by id. */
function sblValueDelete($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec('DELETE FROM ' . SBL_VALUE_TABLE . ' WHERE id = ?', [$id], 'value delete');
}

/* ---- Generic auto-fill rules (SBLAUTOFT) -------------------------- */

/**
 * Load all active auto-fill rules into a nested map the Computer can resolve:
 *   [ when_field => [ when_value => [ set_field => set_value ] ] ]
 * Higher priority wins when two rules target the same set_field.
 * Returns [] when there is no DB (dev/offline -> Schema falls back to the file).
 */
function sblLoadAutofills()
{
    $rows = sbl_select(
        'SELECT when_field, when_value, set_field, set_value FROM ' . SBL_AUTO_TABLE
      . " WHERE active = 'Y' ORDER BY when_field, when_value, set_field, priority"
    );
    $map = [];
    foreach ($rows as $r) {
        // ORDER BY priority ASC means the last write (highest priority) wins.
        $map[$r['when_field']][$r['when_value']][$r['set_field']] = $r['set_value'];
    }
    return $map;
}

/** Distinct "when" field names (for the maintenance page's filter). */
function sblAutofillFields()
{
    $rows = sbl_select('SELECT DISTINCT when_field FROM ' . SBL_AUTO_TABLE . ' ORDER BY when_field');
    return array_column($rows, 'when_field');
}

/** Rule rows for the maintenance grid, optionally filtered by when_field / search. */
function sblAutofillRows($whenField = '', $q = '')
{
    $sql = 'SELECT id, when_field, when_value, set_field, set_value, priority, active FROM ' . SBL_AUTO_TABLE;
    $where = []; $params = [];
    if (trim((string) $whenField) !== '') { $where[] = 'when_field = ?'; $params[] = trim((string) $whenField); }
    if (trim((string) $q) !== '') {
        $like = '%' . strtoupper(trim((string) $q)) . '%';
        $where[] = '(UPPER(when_value) LIKE ? OR UPPER(set_field) LIKE ? OR UPPER(set_value) LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
    $sql .= ' ORDER BY when_field, when_value, set_field FETCH FIRST 500 ROWS ONLY';
    return sbl_select($sql, $params);
}

/** Insert or update one auto-fill rule (by id). Returns id (or false). */
function sblAutofillSave(array $row)
{
    $wf = trim((string) ($row['when_field'] ?? ''));
    $wv = trim((string) ($row['when_value'] ?? ''));
    $sf = trim((string) ($row['set_field'] ?? ''));
    if ($wf === '' || $wv === '' || $sf === '') { return false; }
    $sv  = trim((string) ($row['set_value'] ?? '')); $sv = $sv === '' ? null : $sv;
    $pri = (int) ($row['priority'] ?? 0);
    $act = (($row['active'] ?? 'Y') === 'N') ? 'N' : 'Y';
    $id  = (int) ($row['id'] ?? 0);
    $conn = sbl_conn();
    if (!$conn) { return false; }
    if ($id > 0) {
        return sbl_exec('UPDATE ' . SBL_AUTO_TABLE
            . ' SET when_field = ?, when_value = ?, set_field = ?, set_value = ?, priority = ?, active = ? WHERE id = ?',
            [$wf, $wv, $sf, $sv, $pri, $act, $id], 'autofill update') ? $id : false;
    }
    $ok = sbl_exec('INSERT INTO ' . SBL_AUTO_TABLE
        . ' (when_field, when_value, set_field, set_value, priority, active) VALUES (?, ?, ?, ?, ?, ?)',
        [$wf, $wv, $sf, $sv, $pri, $act], 'autofill insert');
    return $ok ? (int) db2_last_insert_id($conn) : false;
}

/** Delete one auto-fill rule by id. */
function sblAutofillDelete($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec('DELETE FROM ' . SBL_AUTO_TABLE . ' WHERE id = ?', [$id], 'autofill delete');
}
