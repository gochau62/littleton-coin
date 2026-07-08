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

/**
 * Commit the current unit of work. Required if the connection is under manual
 * commitment control (common on IBM i) - without it, a "successful" INSERT /
 * UPDATE / DELETE can execute cleanly yet never persist, and looks identical
 * to a no-op from the AJAX layer. Harmless no-op when autocommit is already on.
 */
function sbl_commit($conn)
{
    if (!function_exists('db2_commit')) { return true; }
    return (bool) db2_commit($conn);
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

/**
 * The most recently saved listing in a category - the tool's "learned" house
 * copy for that category. Autofill reuses its category-level fields (Expanded
 * Description / category feature) and mirrors its style, so every coin in a
 * category comes out consistent and each saved edit becomes the new template.
 */
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
    $id = (int) db2_last_insert_id($conn);
    if (!sbl_commit($conn)) { return sbl_db_err('insert commit'); }
    return $id;
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
    if (!sbl_commit($conn)) { return (bool) sbl_db_err('update commit'); }
    return true;
}

/** Insert or update depending on whether the row carries an id. Returns the id (or false). */
function sblSave(array $row)
{
    $id = (int) ($row['id'] ?? 0);
    if ($id > 0) { return sblUpdate($id, $row) ? $id : false; }
    return sblInsert($row);
}

/** Delete EVERY product row (home-menu "Delete All"); returns true on success. */
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
    if (!sbl_commit($conn)) { return (bool) sbl_db_err('delete commit'); }
    return true;
}