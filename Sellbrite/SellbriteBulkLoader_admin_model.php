<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_admin_model.php *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
/*
 * DB2 for i data-access for the Bulk Loader REFERENCE data, so dropdown
 * options, category lookups and the per-coin validation ("red") rules can be
 * maintained from the website instead of editing SellbriteBulkLoader_data.php.
 *
 *   SBLVALUEST -> dropdown lists      -> Schema::values()
 *   SBLLOOKUPT -> category/grade copy -> Schema::lookups()
 *   SBLRULEST   -> validation rules    -> Schema::rules()
 *
 * Reuses the connection / error / select helpers from the product model.
 * These tables are the ONLY source for dropdowns / lookups / rules
 * (SellbriteBulkLoader_data.php now holds just the 85-column schema).  Each
 * table is one .TABLE source member (SBLVALUEST / SBLLOOKUPT / SBLRULEST) and
 * is loaded once from its companion seed script (*.seed.sql).  Every read
 * degrades to [] when a table is missing (checked once via the catalog), so
 * nothing fatals before the tables exist -- the screen simply shows empty
 * dropdowns until they are created and seeded.
 */

require_once __DIR__ . '/SellbriteBulkLoader_model.php';   // sbl_conn / sbl_db_err / sbl_select
require_once __DIR__ . '/SellbriteBulkLoader_logic.php';   // Schema (setOverrides)

if (!defined('SBL_LIB'))          { define('SBL_LIB', 'LSCDEVLIBP'); }
if (!defined('SBL_VALUES_TABLE')) { define('SBL_VALUES_TABLE', SBL_LIB . '.SBLVALUEST'); }
if (!defined('SBL_LOOKUP_TABLE')) { define('SBL_LOOKUP_TABLE', SBL_LIB . '.SBLLOOKUPT'); }
if (!defined('SBL_RULE_TABLE'))   { define('SBL_RULE_TABLE',   SBL_LIB . '.SBLRULEST'); }

/** True if a reference table exists (cached). Lets reads/writes no-op pre-create. */
function sbl_obj_exists($obj)
{
    static $cache = [];
    if (array_key_exists($obj, $cache)) { return $cache[$obj]; }
    if (!sbl_conn()) { return $cache[$obj] = false; }
    $rows = sbl_select(
        'SELECT 1 AS x FROM QSYS2.SYSTABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? '
        . 'FETCH FIRST 1 ROW ONLY',
        [SBL_LIB, $obj]
    );
    return $cache[$obj] = !empty($rows);
}

/** Run a prepared INSERT/UPDATE/DELETE; true on success, false (logged) otherwise. */
function sbl_exec($sql, array $params = [])
{
    $conn = sbl_conn();
    if (!$conn) { return false; }
    $stmt = db2_prepare($conn, $sql);
    if (!$stmt) { return (bool) sbl_db_err('admin prepare'); }
    if (!db2_execute($stmt, $params)) { return (bool) sbl_db_err('admin execute'); }
    return true;
}

/* ------------------------------------------------------------------ */
/*  Reads -> arrays shaped exactly like the _data.php sections          */
/* ------------------------------------------------------------------ */

/** ['list_name' => [option, option, ...]] from SBLVALUEST. */
function sblRefValues()
{
    if (!sbl_obj_exists('SBLVALUEST')) { return []; }
    $rows = sbl_select(
        'SELECT list_name, option_value FROM ' . SBL_VALUES_TABLE
        . " WHERE active = 'Y' ORDER BY list_name, sort_order, option_value"
    );
    $out = [];
    foreach ($rows as $r) { $out[$r['list_name']][] = $r['option_value']; }
    return $out;
}

/** category_copy/category_meta (nested) + grade_circ (scalar) from SBLLOOKUPT. */
function sblRefLookups()
{
    if (!sbl_obj_exists('SBLLOOKUPT')) { return []; }
    $rows = sbl_select(
        'SELECT lookup_name, lookup_key, attr_name, attr_value FROM ' . SBL_LOOKUP_TABLE
    );
    $out = [];
    foreach ($rows as $r) {
        $name = $r['lookup_name']; $key = $r['lookup_key'];
        $attr = (string) ($r['attr_name'] ?? ''); $val = $r['attr_value'];
        if ($attr === '') { $out[$name][$key] = $val; }          // grade_circ-style scalar
        else { $out[$name][$key][$attr] = $val; }                // category_copy/meta nested
    }
    return $out;
}

/** Validation rules (decoded) from SBLRULEST, in the shape Validator expects. */
function sblRefRules()
{
    if (!sbl_obj_exists('SBLRULEST')) { return []; }
    $rows = sbl_select(
        'SELECT field_name, rule_type, message, condition_json FROM ' . SBL_RULE_TABLE
        . " WHERE active = 'Y' ORDER BY sort_order, id"
    );
    $out = [];
    foreach ($rows as $r) {
        $all = json_decode((string) $r['condition_json'], true);
        if (!is_array($all)) { continue; }
        $out[] = [
            'field'   => $r['field_name'],
            'type'    => $r['rule_type'] ?: 'error',
            'message' => (string) ($r['message'] ?? ''),
            'all'     => $all,
        ];
    }
    return $out;
}

/**
 * Push DB reference data into Schema so the form dropdowns, compute and
 * validation all use the live tables.  No-op (keeps static defaults) when
 * there is no DB connection or the tables are empty/missing.
 */
function sblLoadReferenceOverrides()
{
    if (!sbl_conn()) { return; }
    Schema::setOverrides([
        'values'  => sblRefValues(),
        'lookups' => sblRefLookups(),
        'rules'   => sblRefRules(),
    ]);
}

/* ------------------------------------------------------------------ */
/*  Grids (carry ids for delete) + writes for the admin screen          */
/* ------------------------------------------------------------------ */

function sblValuesGrid($list = '')
{
    if (!sbl_obj_exists('SBLVALUEST')) { return []; }
    $sql = 'SELECT id, list_name, option_value, sort_order, active FROM ' . SBL_VALUES_TABLE;
    $params = [];
    if (trim((string) $list) !== '') { $sql .= ' WHERE list_name = ?'; $params = [trim((string) $list)]; }
    $sql .= ' ORDER BY list_name, sort_order, option_value';
    return sbl_select($sql, $params);
}
function sblValueAdd($list, $opt, $sort = 0)
{
    $list = trim((string) $list); $opt = trim((string) $opt);
    if ($list === '' || $opt === '') { return false; }
    return sbl_exec(
        'INSERT INTO ' . SBL_VALUES_TABLE . ' (list_name, option_value, sort_order) VALUES (?, ?, ?)',
        [$list, $opt, (int) $sort]
    );
}
function sblValueDelete($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec('DELETE FROM ' . SBL_VALUES_TABLE . ' WHERE id = ?', [$id]);
}

function sblLookupsGrid($name = '')
{
    if (!sbl_obj_exists('SBLLOOKUPT')) { return []; }
    $sql = 'SELECT id, lookup_name, lookup_key, attr_name, attr_value FROM ' . SBL_LOOKUP_TABLE;
    $params = [];
    if (trim((string) $name) !== '') { $sql .= ' WHERE lookup_name = ?'; $params = [trim((string) $name)]; }
    $sql .= ' ORDER BY lookup_name, lookup_key, attr_name';
    return sbl_select($sql, $params);
}
function sblLookupAdd($name, $key, $attr, $val)
{
    $name = trim((string) $name); $key = trim((string) $key); $attr = trim((string) $attr);
    if ($name === '' || $key === '') { return false; }
    return sbl_exec(
        'INSERT INTO ' . SBL_LOOKUP_TABLE . ' (lookup_name, lookup_key, attr_name, attr_value) VALUES (?, ?, ?, ?)',
        [$name, $key, $attr, (string) $val]
    );
}
function sblLookupDelete($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec('DELETE FROM ' . SBL_LOOKUP_TABLE . ' WHERE id = ?', [$id]);
}

function sblRulesGrid()
{
    if (!sbl_obj_exists('SBLRULEST')) { return []; }
    return sbl_select(
        'SELECT id, field_name, rule_type, message, condition_json, active, sort_order '
        . 'FROM ' . SBL_RULE_TABLE . ' ORDER BY sort_order, id'
    );
}
function sblRuleAdd($field, $type, $msg, $conditionJson, $sort = 0)
{
    $field = trim((string) $field);
    $type  = trim((string) $type) ?: 'error';
    if ($field === '') { return false; }
    json_decode((string) $conditionJson, true);            // validate JSON before insert
    if (json_last_error() !== JSON_ERROR_NONE) { return false; }
    return sbl_exec(
        'INSERT INTO ' . SBL_RULE_TABLE
        . ' (field_name, rule_type, message, condition_json, sort_order) VALUES (?, ?, ?, ?, ?)',
        [$field, $type, (string) $msg, (string) $conditionJson, (int) $sort]
    );
}
function sblRuleDelete($id)
{
    $id = (int) $id;
    if ($id <= 0) { return false; }
    return sbl_exec('DELETE FROM ' . SBL_RULE_TABLE . ' WHERE id = ?', [$id]);
}
