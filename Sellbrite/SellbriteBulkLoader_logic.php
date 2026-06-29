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
    private static $lookups = null;

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
    public static function lookups(): array
    {
        if (self::$lookups === null) {
            $db = function_exists('sblLoadLookups') ? sblLoadLookups() : [];
            self::$lookups = $db ?: (self::data()['lookups'] ?? []);
        }
        return self::$lookups;
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
        $sku = $g('sku'); $category = $g('category_name');
        $lookups = Schema::lookups();
        $meta = $lookups['category_meta'][$category] ?? [];
        $copy = $lookups['category_copy'][$category] ?? [];

        // Image URLs (deterministic — exact workbook formulas)
        if ($sku !== '') {
            $row['product_image_1'] = SBL_CDN_PREFIX . $sku . '-obv.jpg';
            $row['product_image_2'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_3'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_4'] = SBL_CDN_PREFIX . $sku . '-det2.jpg';
        }
        if ($g('creation_date') === '') { $row['creation_date'] = date('Y-m-d'); }

        $copyVal = static fn(string $k): string => self::lookupValue($copy[$k] ?? '', '');
        $row['search_terms'] = self::lookupValue($meta['search_terms'] ?? '', $g('search_terms'));
        if ($g('composition') === '' && $copyVal('composition') !== '') { $row['composition'] = $copyVal('composition'); }
        if ($g('fineness') === '' && $copyVal('fineness') !== '') { $row['fineness'] = $copyVal('fineness'); }
        if ($g('country_of_manufacture') === '') { $row['country_of_manufacture'] = $copyVal('country') ?: 'United States'; }
        if ($g('brand') === '' && $copyVal('brand') !== '') { $row['brand'] = $copyVal('brand'); }
        if ($g('coin_type') === '' && $copyVal('coin_type') !== '') { $row['coin_type'] = $copyVal('coin_type'); }
        if ($g('denomination') === '' && $copyVal('denomination') !== '') { $row['denomination'] = $copyVal('denomination'); }

        $grade = $g('grade');
        if ($g('circulated_or_uncirculated') === '' && $grade !== '') {
            $row['circulated_or_uncirculated'] = self::lookupValue($lookups['grade_circ'][$grade] ?? '', '');
        }

        $weight = $g('package_weight');
        if ($weight === '') {
            $weight = self::lookupValue($meta['weight_lb'] ?? '', '');
            if ($weight !== '') { $row['package_weight'] = $weight; }
        }
        if (is_numeric($weight)) {
            $w = (float) $weight;
            $row['package_length'] = $w < 0.5 ? '9' : '11';
            $row['package_width']  = $w < 0.5 ? '8' : ($w < 1 ? '9' : '10');
            $row['package_height'] = $w < 0.17 ? '1' : ($w < 1 ? '2' : '4');
        }
        if (stripos($sku, '.WS') !== false && $g('price') !== '') { $row['original_retail'] = $g('price'); }

        $row['name'] = self::buildTitle($row);
        $row['description'] = self::buildDescription($row, $copy);
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
    private static function buildDescription(array $row, array $copy): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('single_coin_or_set') === 'Set') { return ''; }
        $bits = [];
        $headline = self::buildTitle($row);
        if ($headline !== '') { $bits[] = 'A genuine ' . $headline . '.'; }
        if (!empty($copy['collector_note'])) { $bits[] = $copy['collector_note']; }
        elseif (!empty($copy['copy_description'])) { $bits[] = $copy['copy_description']; }
        if ($g('composition') !== '') { $bits[] = 'Composition: ' . $g('composition') . '.'; }
        return trim(implode(' ', $bits));
    }
}

final class Validator
{
    public static function check(array $row): array
    {
        $statuses = []; $messages = [];
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        foreach (Schema::columns() as $col) {
            $name = $col['name']; $val = $g($name);
            if ($val !== '' && str_starts_with($val, '***')) {
                $statuses[$name] = 'action'; $messages[$name] = trim($val, '* '); continue;
            }
            if (!empty($col['required']) && $val === '') {
                $statuses[$name] = 'error'; $messages[$name] = 'Required field'; continue;
            }
            $statuses[$name] = $val === '' ? '' : 'ok';
        }
        $year = $g('year');
        if ($year !== '' && (!ctype_digit($year) || strlen($year) !== 4)) {
            $statuses['year'] = 'action'; $messages['year'] = 'Year should be 4 digits';
        }
        foreach (['price','cost','original_retail'] as $m) {
            $v = $g($m);
            if ($v !== '' && !is_numeric($v)) { $statuses[$m] = 'error'; $messages[$m] = 'Must be a number'; }
        }
        $qty = $g('quantity');
        if ($qty !== '' && !ctype_digit($qty)) { $statuses['quantity'] = 'error'; $messages['quantity'] = 'Whole number only'; }
        if ($g('single_coin_or_set') === 'Set' && $g('set_count') === '') {
            $statuses['set_count'] = 'action'; $messages['set_count'] = 'Enter number of coins in the set';
        }
        return ['statuses' => $statuses, 'messages' => $messages, 'valid' => !in_array('error', $statuses, true)];
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
