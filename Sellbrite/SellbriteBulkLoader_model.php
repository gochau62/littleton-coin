<?php
// DB2 data-access layer for the SBLPRODUCT table (list / find / save / delete)
// blanks and "***" hints coerce to NULL; DB errors log and return false/[] so AJAX still answers

require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema (column list)

if (!defined('SBL_TABLE')) {
    // SQL naming (schema.table). Matches how the other LCC tools reference DB2 objects.
    define('SBL_TABLE', 'LSCDEVLIBP.SBLPRODUCT');
}

// open one DB2 connection from the session credentials and reuse it
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

// log a DB error through the LCCOnline logger; always returns false
function sbl_db_err($where)
{
    $msg = 'SellbriteBulkLoader_model ' . $where . ': '
         . (function_exists('db2_stmt_errormsg') ? db2_stmt_errormsg() : 'unknown');
    if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($msg); }
    return false;
}

// commit the unit of work - without it a clean INSERT/UPDATE can silently never persist
function sbl_commit($conn)
{
    if (!function_exists('db2_commit')) { return true; }
    return (bool) db2_commit($conn);
}

// normalize a user date (MM/DD/YY, YYYY-MM-DD...) to ISO, '' if unusable
function sbl_norm_date($v)
{
    $v = trim((string) $v);
    if ($v === '') { return ''; }
    $ts = strtotime($v);
    return $ts === false ? '' : date('Y-m-d', $ts);
}

// coerce one value to what its DB2 column expects (NULL for blanks/hints/bad input)
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

// product column names in schema order
function sbl_columns()
{
    static $cols = null;
    if ($cols === null) { $cols = array_column(Schema::columns(), 'name'); }
    return $cols;
}

// columns that ACTUALLY exist on the DB2 table (lowercased, cached)
function sbl_table_columns()
{
    static $set = null;
    if ($set !== null) { return $set; }
    $set = [];
    $parts  = explode('.', SBL_TABLE);
    $schema = count($parts) > 1 ? $parts[0] : '';
    $table  = strtoupper(end($parts));
    $sql = 'SELECT COLUMN_NAME FROM QSYS2.SYSCOLUMNS WHERE TABLE_NAME = ?'
         . ($schema !== '' ? ' AND TABLE_SCHEMA = ?' : '');
    $params = $schema !== '' ? [$table, strtoupper($schema)] : [$table];
    foreach (sbl_select($sql, $params) as $r) {
        $name = strtolower((string) ($r['column_name'] ?? ''));
        if ($name !== '') { $set[$name] = true; }
    }
    return $set;
}

// schema columns intersected with the real table - a new field is skipped until its ALTER runs
function sbl_writable_columns()
{
    $cols  = sbl_columns();
    $cols[] = 'marketplace';   // per-SKU market; not a Sellbrite header, so not in the schema
    $tcols = sbl_table_columns();
    if (count($tcols) < 5) { return $cols; }   // lookup failed - don't over-filter
    return array_values(array_filter($cols, static fn($c) => isset($tcols[$c])));
}

// run a prepared SELECT, return rows as lowercase-keyed arrays
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

// list rows for the grid, optional search filter
function sblGetAll($q = '')
{
    $q = trim((string) $q);
    // marketplace only exists after its ALTER; select '' until then.
    $mk  = isset(sbl_table_columns()['marketplace']) ? 'marketplace' : "'' AS marketplace";
    $sql = 'SELECT id, sku, ' . $mk . ', category_name, name, grade, price, quantity, '
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

// most recent saved listing in a category (currently unused)
function sblCategoryExample($category)
{
    $category = trim((string) $category);
    if ($category === '') { return []; }
    $rows = sbl_select(
        'SELECT * FROM ' . SBL_TABLE . ' WHERE UPPER(category_name) = ? '
      . 'ORDER BY updated_at DESC FETCH FIRST 1 ROWS ONLY',
        [strtoupper($category)]
    );
    return $rows[0] ?? [];
}

// every row, all columns, for the export
function sblGetAllFull()
{
    return sbl_select('SELECT * FROM ' . SBL_TABLE . ' ORDER BY updated_at DESC');
}

// one full row for the edit form, false if not found
function sblFind($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    $rows = sbl_select('SELECT * FROM ' . SBL_TABLE . ' WHERE id = ?', [$id]);
    return $rows[0] ?? false;
}

// insert a new row; returns the new id or false
function sblInsert(array $row)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $cols = sbl_writable_columns();
    // Delimited uppercase identifiers: safe for reserved-ish names (condition, year).
    $qcols = array_map(static fn($c) => '"' . strtoupper($c) . '"', $cols);
    $sql  = 'INSERT INTO ' . SBL_TABLE . ' (' . implode(', ', $qcols) . ') VALUES ('
          . implode(', ', array_fill(0, count($cols), '?')) . ')';
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return sbl_db_err('insert prepare'); }
    $vals = [];
    foreach ($cols as $c) { $vals[] = sbl_coerce($c, $row[$c] ?? null); }
    if (!db2_execute($stmt, $vals)) { return sbl_db_err('insert execute'); }
    $id = (int) db2_last_insert_id($conn);
    if (!sbl_commit($conn)) { return sbl_db_err('insert commit'); }
    return $id;
}

// update a row by id; true on success
function sblUpdate($id, array $row)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $cols = sbl_writable_columns();
    $set  = implode(', ', array_map(static fn($c) => '"' . strtoupper($c) . '" = ?', $cols));
    $sql  = 'UPDATE ' . SBL_TABLE . ' SET ' . $set . ' WHERE id = ?';
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return (bool) sbl_db_err('update prepare'); }
    $vals = [];
    foreach ($cols as $c) { $vals[] = sbl_coerce($c, $row[$c] ?? null); }
    $vals[] = (int) $id;
    if (!db2_execute($stmt, $vals)) { return (bool) sbl_db_err('update execute'); }
    if (!sbl_commit($conn)) { return (bool) sbl_db_err('update commit'); }
    return true;
}

// insert or update depending on id; returns the id or false
function sblSave(array $row)
{
    $id = (int) ($row['id'] ?? 0);
    if ($id > 0) { return sblUpdate($id, $row) ? $id : false; }
    return sblInsert($row);
}

// delete EVERY row (home Delete All)
function sblDeleteAll()
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, 'DELETE FROM ' . SBL_TABLE);
    if (!$stmt) { return (bool) sbl_db_err('deleteAll prepare'); }
    if (!db2_execute($stmt)) { return (bool) sbl_db_err('deleteAll execute'); }
    if (!sbl_commit($conn)) { return (bool) sbl_db_err('deleteAll commit'); }
    return true;
}

// delete one row by id
function sblDelete($id)
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $id = (int) $id;
    if ($id <= 0) { return false; }
    $stmt = db2_prepare($conn, 'DELETE FROM ' . SBL_TABLE . ' WHERE id = ?');
    if (!$stmt) { return (bool) sbl_db_err('delete prepare'); }
    if (!db2_execute($stmt, [$id])) { return (bool) sbl_db_err('delete execute'); }
    if (!sbl_commit($conn)) { return (bool) sbl_db_err('delete commit'); }
    return true;
}