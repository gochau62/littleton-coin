<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_logic.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
if (!defined('SBL_CDN_PREFIX')) {
    define('SBL_CDN_PREFIX', 'https://cdn.shopify.com/s/files/1/0198/0799/3956/files/');
}

/** HTML-escape helper (guarded so it never clashes with framework helpers). */
if (!function_exists('sbl_e')) {
    function sbl_e($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

final class Schema
{
    private static $data = null;
    private static $schema = null;
    private static $values = null;
    private static $autofills = null;

    /** Load the consolidated reference data (schema/values/lookups) once. */
    private static function data(): array
    {
        if (self::$data === null) { self::$data = require __DIR__ . '/SellbriteBulkLoader_data.php'; }
        return is_array(self::$data) ? self::$data : [];
    }

    public static function columns(): array
    {
        if (self::$schema === null) { self::$schema = self::data()['schema'] ?? []; }
        return self::$schema;
    }
    public static function byName(): array
    {
        $out = [];
        foreach (self::columns() as $c) { $out[$c['name']] = $c; }
        return $out;
    }
    public static function values(): array
    {
        if (self::$values === null) {
            // Prefer the live DB2 tables (maintainable from the web page); fall
            // back to the bundled data file when there is no DB (dev/offline).
            $db = function_exists('sblLoadValues') ? sblLoadValues() : [];
            self::$values = $db ?: (self::data()['values'] ?? []);
        }
        return self::$values;
    }
    public static function optionsFor(array $col): array
    {
        if (empty($col['dropdown'])) { return []; }
        return self::values()[$col['dropdown']] ?? [];
    }
    /**
     * Generic auto-fill rules as [ when_field => [ when_value => [ set_field => set_value ] ] ].
     * Prefers the live SBLAUTOFT table; falls back to the bundled data file
     * (its category/grade lookups, reshaped into the same generic map) when
     * there is no DB.  Adding a new auto-fill is just a new row in the table.
     */
    public static function autofills(): array
    {
        if (self::$autofills === null) {
            $db = function_exists('sblLoadAutofills') ? sblLoadAutofills() : [];
            self::$autofills = $db ?: self::autofillsFromFile();
        }
        return self::$autofills;
    }
    /** Reshape the data file's category_copy/category_meta/grade_circ into the generic map. */
    private static function autofillsFromFile(): array
    {
        $lk = self::data()['lookups'] ?? [];
        $map = [];
        $copyMap = ['coin_type' => 'coin_type', 'denomination' => 'denomination',
            'composition' => 'composition', 'fineness' => 'fineness',
            'country' => 'country_of_manufacture', 'brand' => 'brand',
            'copy_description' => 'copy_description', 'collector_note' => 'collector_note'];
        foreach ($lk['category_copy'] ?? [] as $cat => $vals) {
            foreach ($copyMap as $k => $f) {
                if (isset($vals[$k]) && $vals[$k] !== '') { $map['category_name'][$cat][$f] = $vals[$k]; }
            }
        }
        $metaMap = ['search_terms' => 'search_terms', 'weight_lb' => 'package_weight'];
        foreach ($lk['category_meta'] ?? [] as $cat => $vals) {
            foreach ($metaMap as $k => $f) {
                if (isset($vals[$k]) && $vals[$k] !== '') { $map['category_name'][$cat][$f] = $vals[$k]; }
            }
        }
        foreach ($lk['grade_circ'] ?? [] as $grade => $circ) {
            if ($circ !== '') { $map['grade'][$grade]['circulated_or_uncirculated'] = $circ; }
        }
        return $map;
    }
    public static function groups(): array
    {
        return [
            'Identity'            => ['sku', 'category_name'],
            'Coin Attributes'     => [
                'year','mint_mark','mint_location','coin_type','denomination',
                'coin_variety_1','coin_variety_2','grade','title_suffix',
                'designation_abbrivation','paper_money_grade_designation','paper_money_type',
                'certification','certification_number','circulated_or_uncirculated',
                'strike_type','style','composition','fineness','precious_metal_content',
                'single_coin_or_set','set_count','country_of_manufacture','brand',
                'modified_item','modification_description','bullion_shape','coin_design',
            ],
            'Pricing & Inventory' => ['price','cost','quantity','upc','original_retail'],
            'Listing Content'     => [
                'exact_image','name','description','red_book_description',
                'feature_1','feature_2','feature_3','feature_4','feature_5','search_terms',
            ],
            'Images'              => [
                'product_image_1','product_image_2','product_image_3','product_image_4',
                'product_image_5','product_image_6','product_image_7','product_image_8',
            ],
            'Shipping & Package'  => [
                'creation_date','package_length','package_width','package_height',
                'package_weight','condition_note','total_precious_metal_content',
            ],
        ];
    }
}

final class Computer
{
    /** Return a copy of $row with all auto/derived columns (re)computed. */
    public static function apply(array $row): array
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        $sku = $g('sku');

        // Resolve every auto-fill rule whose "when" field matches this row into
        // a flat [set_field => value] map.  This is the generic version of the
        // old category_copy / category_meta / grade_circ VLOOKUPs — any rule in
        // SBLAUTOFT participates, so new auto-fills work with no code change.
        $resolved = [];
        foreach (Schema::autofills() as $whenField => $byValue) {
            $cur = $g($whenField);
            if ($cur === '' || !isset($byValue[$cur])) { continue; }
            foreach ($byValue[$cur] as $setField => $setValue) {
                $sv = self::lookupValue((string) $setValue, '');
                if ($sv !== '' && !isset($resolved[$setField])) { $resolved[$setField] = $sv; }
            }
        }
        $auto = static fn(string $f): string => $resolved[$f] ?? '';

        // Image URLs (deterministic — exact workbook formulas)
        if ($sku !== '') {
            $row['product_image_1'] = SBL_CDN_PREFIX . $sku . '-obv.jpg';
            $row['product_image_2'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_3'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_4'] = SBL_CDN_PREFIX . $sku . '-det2.jpg';
        }
        if ($g('creation_date') === '') { $row['creation_date'] = date('Y-m-d'); }

        if ($auto('search_terms') !== '') { $row['search_terms'] = $auto('search_terms'); }
        if ($g('composition') === '' && $auto('composition') !== '') { $row['composition'] = $auto('composition'); }
        if ($g('fineness') === '' && $auto('fineness') !== '') { $row['fineness'] = $auto('fineness'); }
        if ($g('country_of_manufacture') === '') { $row['country_of_manufacture'] = $auto('country_of_manufacture') ?: 'United States'; }
        if ($g('brand') === '' && $auto('brand') !== '') { $row['brand'] = $auto('brand'); }
        if ($g('coin_type') === '' && $auto('coin_type') !== '') { $row['coin_type'] = $auto('coin_type'); }
        if ($g('denomination') === '' && $auto('denomination') !== '') { $row['denomination'] = $auto('denomination'); }

        if ($g('circulated_or_uncirculated') === '' && $g('grade') !== '' && $auto('circulated_or_uncirculated') !== '') {
            $row['circulated_or_uncirculated'] = $auto('circulated_or_uncirculated');
        }

        $weight = $g('package_weight');
        if ($weight === '' && $auto('package_weight') !== '') { $weight = $row['package_weight'] = $auto('package_weight'); }
        if (is_numeric($weight)) {
            $w = (float) $weight;
            $row['package_length'] = $w < 0.5 ? '9' : '11';
            $row['package_width']  = $w < 0.5 ? '8' : ($w < 1 ? '9' : '10');
            $row['package_height'] = $w < 0.17 ? '1' : ($w < 1 ? '2' : '4');
        }
        if (stripos($sku, '.WS') !== false && $g('price') !== '') { $row['original_retail'] = $g('price'); }

        $row['name'] = self::buildTitle($row);
        $row['description'] = self::buildDescription($row, $resolved);
        return $row;
    }
    private static function lookupValue(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '***')) { return $fallback; }
        return $value;
    }
    private static function buildTitle(array $row): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('category_name') === '') { return ''; }
        $parts = [
            $g('year'),
            $g('mint_mark') !== '' && $g('mint_mark') !== 'No Mint Mark' ? $g('mint_mark') : '',
            $g('category_name'),
            $g('denomination'),
            $g('grade') !== '' && $g('grade') !== 'Ungraded' ? $g('grade') : '',
            $g('certification') !== '' && $g('certification') !== 'Uncertified' ? $g('certification') : '',
            $g('title_suffix'),
        ];
        $parts = array_filter($parts, static fn($p) => $p !== '');
        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
    }
    private static function buildDescription(array $row, array $resolved): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('single_coin_or_set') === 'Set') { return ''; }
        $bits = [];
        $headline = self::buildTitle($row);
        if ($headline !== '') { $bits[] = 'A genuine ' . $headline . '.'; }
        if (!empty($resolved['collector_note'])) { $bits[] = $resolved['collector_note']; }
        elseif (!empty($resolved['copy_description'])) { $bits[] = $resolved['copy_description']; }
        if ($g('composition') !== '') { $bits[] = 'Composition: ' . $g('composition') . '.'; }
        return trim(implode(' ', $bits));
    }
}

/**
 * Live row validation — the PHP version of the workbook's red "Bad" cells.
 *
 * The .ods drives ~113 conditional-format rules that turn a cell red to tell
 * the user something is wrong or still owed.  We reproduce those rule families
 * here.  Each rule sets a per-field status that the form paints live:
 *     'error'  -> red    (blocks save; truly invalid input)
 *     'action' -> amber  (needs attention; matches the spreadsheet's advisory
 *                         red — the row can still be saved "with warnings")
 *
 * Families covered (see docs): required-once-a-SKU-exists, cross-field
 * conditional requirements, length/format, and key consistency checks.  The
 * exhaustive per-category year ranges and variety tables are intentionally
 * left as future data-driven rules (they belong in a lookup table, not code).
 */
final class Validator
{
    /** Categories that require a Title Suffix even when uncertified (workbook K-column rule). */
    private static array $titleSuffixCategories = [
        'Modern Silver/Clad Commemorative','Modern Gold Commemorative','Proof Set','Mint Set',
        'Gold Bullion Coin','Platinum Bullion Coin','Palladium Bullion Coin','Morgan Dollar Toned',
    ];

    public static function check(array $row): array
    {
        $statuses = []; $messages = [];
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));

        // Mark a field, but never downgrade an existing 'error' to 'action'.
        $mark = static function (string $f, string $st, string $msg) use (&$statuses, &$messages): void {
            $cur = $statuses[$f] ?? '';
            if ($cur === 'error' && $st === 'action') { return; }
            $statuses[$f] = $st; $messages[$f] = $msg;
        };

        // --- base pass: placeholders, hard-required, valid-value -----------
        $optionCache = [];
        foreach (Schema::columns() as $col) {
            $name = $col['name']; $val = $g($name);
            if ($val !== '' && str_starts_with($val, '***')) {
                $statuses[$name] = 'action'; $messages[$name] = trim($val, '* '); continue;
            }
            if (!empty($col['required']) && $val === '') {
                $statuses[$name] = 'error'; $messages[$name] = 'Required field'; continue;
            }
            // Typed a value that is not one of this field's valid options.
            if ($val !== '' && !empty($col['dropdown'])) {
                $opts = $optionCache[$name] ??= Schema::optionsFor($col);
                if ($opts && !in_array($val, $opts, true)) {
                    $statuses[$name] = 'action'; $messages[$name] = 'Not a valid option for this field'; continue;
                }
            }
            $statuses[$name] = $val === '' ? '' : 'ok';
        }

        $hasSku = $g('sku') !== '';

        // --- 1) required once a SKU exists (advisory red) ------------------
        // Which fields redden when blank is data: the 'reddens' flag on each
        // field definition (set from the .ods, editable as data — no hardcoded
        // list here).  Auto-filled content/image fields are NOT flagged, since
        // the spreadsheet fills them by formula so they never go blank.
        if ($hasSku) {
            foreach (Schema::columns() as $col) {
                if (empty($col['reddens'])) { continue; }
                $f = $col['name'];
                if ($g($f) !== '' || ($statuses[$f] ?? '') !== '') { continue; }
                if ($f === 'coin_type' && $g('category_name') === 'Two Cent') { continue; } // no coin type
                $mark($f, 'action', 'Required once a SKU is entered');
            }
        }

        // --- 2) cross-field conditional requirements ----------------------
        // Mint mark is owed when this is a U.S. Mint product.
        $brand = $g('brand');
        if ($hasSku && $g('mint_mark') === '' && ($brand === '' || $brand === 'U.S. Mint')) {
            $mark('mint_mark', 'action', 'Mint mark required for U.S. Mint coins');
        }
        // Title suffix is owed for the special categories (when uncertified).
        $cert = $g('certification');
        if ($g('title_suffix') === '' && ($cert === '' || $cert === 'Uncertified')
            && in_array($g('category_name'), self::$titleSuffixCategories, true)) {
            $mark('title_suffix', 'action', 'Title suffix required for this category');
        }
        // Set vs. set_count.
        $isSet = $g('single_coin_or_set') === 'Set';
        $setCount = $g('set_count');
        if ($isSet && ($setCount === '' || !ctype_digit($setCount) || (int) $setCount <= 1)) {
            $mark('set_count', 'action', 'Enter the number of coins in the set (2 or more)');
        } elseif (!$isSet && $setCount !== '') {
            $mark('set_count', 'action', 'Set count only applies when this is a Set');
        }
        // Certification number is owed for a real certification (single coins).
        $certNum = $g('certification_number');
        $isStock = stripos($g('exact_image'), 'stock') !== false;
        if ($cert !== '' && $cert !== 'Uncertified' && !$isSet && !$isStock) {
            if ($certNum === '') {
                $mark('certification_number', 'action', 'Certification number required for a certified coin');
            } elseif (($cert === 'PCGS' || $cert === 'PCGS & CAC')
                      && (strpos($certNum, '-') !== false || strlen($certNum) < 7 || strlen($certNum) > 8)) {
                $mark('certification_number', 'action', 'PCGS number should be 7-8 digits, no dash');
            } elseif (($cert === 'NGC' || $cert === 'NGC & CAC')
                      && (strpos($certNum, '-') === false || strlen($certNum) < 10 || strlen($certNum) > 11)) {
                $mark('certification_number', 'action', 'NGC number should be 10-11 chars incl. the dash');
            }
        } elseif ($certNum !== '' && ($cert === '' || $cert === 'Uncertified')) {
            $mark('certification_number', 'action', 'Remove the number or set a certification');
        }

        // --- 3) length / format -------------------------------------------
        $name = $g('name');
        if (mb_strlen($name) > 70) { $mark('name', 'error', 'Title must be 70 characters or fewer (' . mb_strlen($name) . ')'); }
        if ($name !== '' && (strpos($name, '  ') !== false)) { $mark('name', 'action', 'Remove the double space in the title'); }
        $sku = $g('sku');
        if ($sku !== '') {
            if (strlen($sku) > 10) { $mark('sku', 'error', 'SKU must be 10 characters or fewer'); }
            if (strpos($sku, ' ') !== false) { $mark('sku', 'error', 'SKU cannot contain spaces'); }
        }
        $year = $g('year');
        if ($year !== '') {
            $ok = (ctype_digit($year) && strlen($year) === 4)
               || (strpos($year, '-') !== false && strlen($year) === 9);   // e.g. 1878-1879
            if (!$ok) { $mark('year', 'action', 'Year should be 4 digits (or a 9-char range)'); }
        }
        foreach (['price','cost','original_retail'] as $m) {
            $v = $g($m);
            if ($v !== '' && !is_numeric($v)) { $mark($m, 'error', 'Must be a number'); }
        }
        $qty = $g('quantity');
        if ($qty !== '' && !ctype_digit($qty)) { $mark('quantity', 'error', 'Whole number only'); }
        elseif ($hasSku && ($qty === '' || (int) $qty < 1)) { $mark('quantity', 'action', 'Quantity must be at least 1'); }
        if (ctype_digit($qty) && (int) $qty > 1 && stripos($g('exact_image'), 'exact') === false) {
            $mark('quantity', 'action', 'Quantity over 1 needs a stock (non-exact) image');
        }

        // --- 4) consistency & pricing -------------------------------------
        // Strike type says Proof, so the grade must reflect it.
        $strike = $g('strike_type'); $grade = $g('grade');
        if (stripos($strike, 'Proof') !== false && $grade !== '' && stripos($grade, 'Ungraded') === false) {
            $hasProof = preg_match('/proof|PR |PF |RP /i', $grade);
            if (!$hasProof) { $mark('grade', 'action', 'Proof strike should have a Proof (PR/PF) grade'); }
        }
        // Price vs cost sanity (numbers only).
        $price = $g('price'); $cost = $g('cost');
        if (is_numeric($price) && is_numeric($cost)) {
            $p = (float) $price; $c = (float) $cost;
            if ($p <= $c) { $mark('price', 'action', 'Price should be above cost'); }
            else {
                // Margin band by cost tier (workbook AD-column rule).
                [$lo, $hi] = self::marginBand($c);
                if ($p < $c * $lo || $p > $c * $hi) {
                    $mark('price', 'action', 'Price margin looks off for this cost tier');
                }
            }
        }
        if ($cert === 'Uncertified' && stripos($g('category_name'), 'Bullion') === false
            && is_numeric($price) && (float) $price >= 2500) {
            $mark('price', 'action', 'High-value uncertified coin — review before listing');
        }

        return ['statuses' => $statuses, 'messages' => $messages, 'valid' => !in_array('error', $statuses, true)];
    }

    /** Acceptable [min, max] price multiple of cost, by cost tier (from the workbook). */
    private static function marginBand(float $c): array
    {
        if ($c < 2)    { return [3.7, 26]; }
        if ($c < 4)    { return [2.3, 4.4]; }
        if ($c < 10)   { return [1.6, 3]; }
        if ($c < 41)   { return [1.3, 2]; }
        if ($c < 1000) { return [1.2, 1.5]; }
        return [1.2, 1.3];
    }
}

final class Exporter
{
    public static function csv(array $rows): string
    {
        $columns = Schema::columns();
        $banner = 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
                . 'You MAY delete or change the order of columns, but do NOT alter the '
                . 'header names in row 3. *Required Fields.';
        $human   = array_map(static fn($c) => $c['label'] . (!empty($c['required']) ? '*' : ''), $columns);
        $machine = array_map(static fn($c) => $c['name'], $columns);
        $fh = fopen('php://temp', 'r+');
        $bannerRow = array_fill(0, count($columns), ''); $bannerRow[0] = $banner;
        fputcsv($fh, $bannerRow); fputcsv($fh, $human); fputcsv($fh, $machine);
        foreach ($rows as $row) {
            $line = [];
            foreach ($machine as $name) { $line[] = (string) ($row[$name] ?? ''); }
            fputcsv($fh, $line);
        }
        rewind($fh); $out = stream_get_contents($fh); fclose($fh);
        return $out;
    }
}
