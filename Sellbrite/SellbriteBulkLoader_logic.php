<?php
if (!defined('SBL_CDN_PREFIX')) {
    define('SBL_CDN_PREFIX', 'https://cdn.shopify.com/s/files/1/0198/0799/3956/files/');
}
// Constant listing copy (Des): feature 5 is a brief PCC company blurb applied
// to every listing; the exact-image line is the default for feature 3.
if (!defined('SBL_ABOUT_SELLER')) { define('SBL_ABOUT_SELLER',
    'ABOUT PROFILE COINS & COLLECTIBLES: Selling collectible coins and currency online for more than a '
  . 'decade, we are the dealer of choice for new and experienced collectors. Our ever-changing inventory '
  . 'ranges from coins such as Morgan & Peace Dollars, Liberty Walking & Franklin Half Dollars, Standing '
  . 'Liberty & Washington Quarters to modern sets, including proof sets, mint sets, & commemorative sets.'); }
if (!defined('SBL_EXACT_IMAGE_DEFAULT')) { define('SBL_EXACT_IMAGE_DEFAULT',
    'The images you see are for the exact item you will receive.'); }

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
        if (self::$values === null) { self::$values = self::data()['values'] ?? []; }
        return self::$values;
    }
    public static function optionsFor(array $col): array
    {
        if (empty($col['dropdown'])) { return []; }
        return self::values()[$col['dropdown']] ?? [];
    }
    public static function lookups(): array
    {
        if (self::$lookups === null) { self::$lookups = self::data()['lookups'] ?? []; }
        return self::$lookups;
    }
    /* Fields required for EVERY listing - the Sellbrite export's peach
     * "Mandatory for all listings" group (plus Quantity/Cost from the inventory
     * file). An empty required field flags the listing "needs attention" but
     * still saves, so this can faithfully mirror the sheet. Original Retail and
     * extra image slots are left off (the workbook itself makes them optional). */
    public static function requiredNames(): array
    {
        return ['sku', 'name', 'description', 'red_book_description',
                'feature_1', 'feature_2', 'feature_3', 'feature_4', 'feature_5',
                'brand', 'country_of_manufacture', 'price', 'creation_date', 'condition',
                'package_weight', 'package_length', 'package_width', 'package_height',
                'exact_image', 'product_image_1', 'search_terms', 'quantity', 'cost'];
    }
    /* Extra fields a marketplace needs, shown only when that market is chosen
     * for the SKU (from the Sellbrite export's market groups). Only fields that
     * already exist as columns are listed; the eBay-specific condition columns
     * (ebay_coin_condition_type, ebay_graded_coin_*) need adding to SBLPRODUCT
     * first, so they are intentionally not here yet. */
    public static function marketFields(): array
    {
        return [
            'amazon'  => ['fields' => ['style'], 'required' => []],
            'ebay'    => ['fields' => ['modified_item', 'modification_description',
                                       'ebay_coin_condition_type', 'ebay_graded_coin_letter_grade',
                                       'ebay_graded_coin_numerical_grade', 'ebay_graded_coin_professional_grader',
                                       'z_ebay_ungraded_coin_condition'],
                          'required' => ['ebay_coin_condition_type']],
            'walmart' => ['fields' => [], 'required' => []],
        ];
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

        // Image URLs (deterministic — exact workbook formulas from the SKU).
        if ($sku !== '') {
            $row['product_image_1'] = SBL_CDN_PREFIX . $sku . '-obv.jpg';
            $row['product_image_2'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_3'] = SBL_CDN_PREFIX . $sku . '-det1.jpg';
            $row['product_image_4'] = SBL_CDN_PREFIX . $sku . '-det2.jpg';
        }
        if ($g('creation_date') === '') { $row['creation_date'] = date('Y-m-d'); }

        $copyVal = static fn(string $k): string => self::lookupValue($copy[$k] ?? '', '');
        $row['search_terms'] = self::lookupValue($meta['search_terms'] ?? '', $g('search_terms'));
        // Deterministic fallback so search terms always fill (even if the AI didn't).
        if (trim((string) $row['search_terms']) === '') {
            $words = [];
            foreach ([$g('coin_type'), $g('category_name'), $g('composition'),
                      $g('denomination'), 'coin', 'numismatics', 'collectible'] as $src) {
                foreach (preg_split('/[^a-z0-9]+/', strtolower(trim((string) $src))) as $w) {
                    if ($w !== '' && !in_array($w, $words, true)) { $words[] = $w; }
                }
            }
            $row['search_terms'] = implode(' ', $words);
        }
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
        // Keep an author-supplied (e.g. AI) description; only build one if empty.
        if (trim((string) ($row['description'] ?? '')) === '') {
            $row['description'] = self::buildDescription($row, $copy);
        }

        // Amazon bullet points, PCC layout (from the real exports):
        //   1 DETAILS  2 CONDITION  3 IMAGES  4 COLLECTOR'S NOTE  5 ABOUT PCC
        // The description reads "A genuine <specs> Coin, in <condition> Condition",
        // which we split into the DETAILS and CONDITION bullets.
        $desc = trim((string) ($row['description'] ?? ''));
        if ($desc !== '') {
            $core = preg_replace('/^A genuine\s+/i', '', $desc);
            $bits = preg_split('/,\s*in\s+/i', $core, 2);
            $row['feature_1'] = 'DETAILS: ' . rtrim(trim($bits[0]), ' .');
            if (!empty($bits[1])) { $row['feature_2'] = 'CONDITION: ' . rtrim(trim($bits[1]), ' .'); }
        }
        // Sellbrite Condition (new/used/reconditioned): collectible coins list
        // as "used" (Des's export rows do) unless the operator overrides.
        if ($g('condition') === '') { $row['condition'] = 'used'; }

        // eBay condition fields, derived from certification + grade:
        //   certified/slabbed -> Graded (grader + letter/numerical grade)
        //   raw               -> Ungraded (circulated/uncirculated condition)
        $cert   = $g('certification');
        $grade  = $g('grade');
        $graded = $cert !== '' && strcasecmp($cert, 'Uncertified') !== 0 && strcasecmp($cert, 'U.S. Mint') !== 0;
        if ($g('ebay_coin_condition_type') === '') { $row['ebay_coin_condition_type'] = $graded ? 'Graded' : 'Ungraded'; }
        if ($graded) {
            if ($g('ebay_graded_coin_professional_grader') === '') { $row['ebay_graded_coin_professional_grader'] = $cert; }
            if ($g('ebay_graded_coin_letter_grade') === '' && $grade !== '') { $row['ebay_graded_coin_letter_grade'] = $grade; }
            if ($g('ebay_graded_coin_numerical_grade') === '' && preg_match('/\d{1,2}/', $grade, $gm)) {
                $row['ebay_graded_coin_numerical_grade'] = $gm[0];
            }
        } elseif ($g('z_ebay_ungraded_coin_condition') === '') {
            $row['z_ebay_ungraded_coin_condition'] = $grade !== '' && strcasecmp($grade, 'Ungraded') !== 0
                ? $grade : $g('circulated_or_uncirculated');
        }

        $exact = trim((string) ($row['exact_image'] ?? ''));
        if ($exact !== '') { $row['feature_3'] = 'IMAGES: ' . $exact; }
        // feature_4 = the agent's category COLLECTOR'S NOTE; make sure it carries the label.
        $note = trim((string) ($row['feature_4'] ?? ''));
        if ($note !== '' && stripos($note, "COLLECTOR'S NOTE") !== 0) { $row['feature_4'] = "COLLECTOR'S NOTE: " . $note; }
        $row['feature_5'] = SBL_ABOUT_SELLER;   // already begins "ABOUT PROFILE COINS & COLLECTIBLES:"
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
            $g('title_suffix'),   // operator catch-all: grade/error/packaging/slab details
            'Coin Collectible',   // constant title tail (ODS hardcodes this, not title_suffix)
        ];
        $parts = array_filter($parts, static fn($p) => $p !== '');
        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
    }
    /* Fallback description (used only when the agent didn't write one). Same
     * shape as the real listings: "A genuine <specs> Coin, in <condition>
     * Condition." The DETAILS/CONDITION bullets are split back out of it. */
    private static function buildDescription(array $row, array $copy): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('category_name') === '') { return ''; }
        $specs = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $g('year'),
            $g('mint_mark') !== '' && $g('mint_mark') !== 'No Mint Mark' ? $g('mint_mark') : '',
            $g('coin_type') !== '' ? $g('coin_type') : $g('category_name'),
            $g('denomination'),
        ]))));
        $d = 'A genuine ' . $specs . ' Coin';
        $cond = $g('grade') !== '' && $g('grade') !== 'Ungraded'
              ? $g('grade') : $g('circulated_or_uncirculated');
        if ($cond !== '') { $d .= ', in ' . $cond . ' Condition'; }
        return $d . '.';
    }
}

final class Validator
{
    public static function check(array $row): array
    {
        $statuses = []; $messages = [];
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        // Required = schema flag OR the Sellbrite "mandatory for all" set OR the
        // chosen marketplace's required fields.
        $required = array_flip(Schema::requiredNames());
        $market   = strtolower(trim((string) ($row['marketplace'] ?? '')));
        foreach (Schema::marketFields()[$market]['required'] ?? [] as $mf) { $required[$mf] = true; }
        foreach (Schema::columns() as $col) {
            $name = $col['name']; $val = $g($name);
            if ($val !== '' && str_starts_with($val, '***')) {
                $statuses[$name] = 'action'; $messages[$name] = trim($val, '* '); continue;
            }
            if ((!empty($col['required']) || isset($required[$name])) && $val === '') {
                $statuses[$name] = 'error'; $messages[$name] = 'Required field'; continue;
            }
            $statuses[$name] = $val === '' ? '' : 'ok';
        }
        $year = $g('year');
        if ($year !== '' && (!ctype_digit($year) || strlen($year) !== 4)) {
            $statuses['year'] = 'action'; $messages['year'] = 'Year should be 4 digits';
        }
        // Price, Cost and Quantity are operator-required (autofill only suggests price/cost).
        foreach (['price','cost'] as $m) {
            $v = $g($m);
            if ($v === '')           { $statuses[$m] = 'error'; $messages[$m] = 'Required field'; }
            elseif (!is_numeric($v)) { $statuses[$m] = 'error'; $messages[$m] = 'Must be a number'; }
        }
        if ($g('original_retail') !== '' && !is_numeric($g('original_retail'))) {
            $statuses['original_retail'] = 'error'; $messages['original_retail'] = 'Must be a number';
        }
        $qty = $g('quantity');
        if ($qty === '')            { $statuses['quantity'] = 'error'; $messages['quantity'] = 'Required field'; }
        elseif (!ctype_digit($qty)) { $statuses['quantity'] = 'error'; $messages['quantity'] = 'Whole number only'; }
        if ($g('single_coin_or_set') === 'Set' && $g('set_count') === '') {
            $statuses['set_count'] = 'action'; $messages['set_count'] = 'Enter number of coins in the set';
        }
        return ['statuses' => $statuses, 'messages' => $messages, 'valid' => !in_array('error', $statuses, true)];
    }
}

final class Exporter
{
    /* One product CSV, tailored per marketplace. Column annotations in Des's
     * Sellbrite export: the five features / search terms / style are Amazon-
     * specific; modified-item fields are eBay-specific. 'all' keeps every
     * column (the house master export). */
    private const AMAZON_ONLY = ['feature_1','feature_2','feature_3','feature_4','feature_5','search_terms','style'];
    private const EBAY_ONLY   = ['modified_item','modification_description',
                                 'ebay_coin_condition_type','ebay_graded_coin_letter_grade',
                                 'ebay_graded_coin_numerical_grade','ebay_graded_coin_professional_grader',
                                 'z_ebay_ungraded_coin_condition'];

    public static function markets(): array { return ['all', 'amazon', 'ebay', 'walmart']; }

    /* Internal working fields with no Sellbrite header (diameter/weight are
     * ours until Des adds them in Sellbrite) - kept out of the upload file. */
    private const INTERNAL_ONLY = ['diameter', 'weight'];

    public static function csv(array $rows, string $market = 'all'): string
    {
        $drop = self::INTERNAL_ONLY;
        if ($market === 'amazon')  { $drop = array_merge($drop, self::EBAY_ONLY); }
        if ($market === 'ebay')    { $drop = array_merge($drop, self::AMAZON_ONLY); }
        if ($market === 'walmart') { $drop = array_merge($drop, self::AMAZON_ONLY, self::EBAY_ONLY); }
        $columns = array_values(array_filter(Schema::columns(),
            static fn($c) => !in_array($c['name'], $drop, true)));
        $banner = 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
                . 'You MAY delete or change the order of columns, but do NOT alter the '
                . 'header names in row 3. *Required Fields.';
        // Sellbrite's real headers differ from two display names: the store
        // category exports as "SKU of Parent Product"/parent_sku, and the
        // "Extended Description" display label is still Sellbrite's
        // "Red Book Description" header.
        $human   = array_map(static function ($c) {
            $label = $c['label'];
            if ($c['name'] === 'category_name')        { $label = 'SKU of Parent Product'; }
            if ($c['name'] === 'red_book_description') { $label = 'Red Book Description'; }
            return $label . (!empty($c['required']) ? '*' : '');
        }, $columns);
        $machine = array_map(static fn($c) => $c['name'] === 'category_name' ? 'parent_sku' : $c['name'], $columns);
        $source  = array_map(static fn($c) => $c['name'], $columns);   // our field names for the data rows
        $fh = fopen('php://temp', 'r+');
        $bannerRow = array_fill(0, count($columns), ''); $bannerRow[0] = $banner;
        fputcsv($fh, $bannerRow); fputcsv($fh, $human); fputcsv($fh, $machine);
        foreach ($rows as $row) {
            $line = [];
            foreach ($source as $name) { $line[] = (string) ($row[$name] ?? ''); }
            fputcsv($fh, $line);
        }
        rewind($fh); $out = stream_get_contents($fh); fclose($fh);
        return $out;
    }
}