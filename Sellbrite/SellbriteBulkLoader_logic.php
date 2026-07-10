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
        if ($col['dropdown'] === 'store_category') {
            // SKU of Parent Product: the dropdown only offers the product lines
            // that are NOT on GreySheet (watches, calendars, stamps, nativity,
            // albums...). Coin AND currency categories are set automatically by
            // the GreySheet pick, which appends its option on the fly.
            return ['Advent Calendar', 'Challenge Coin', 'United States Postage Stamp',
                    'Wristwatches', 'Coin Album', 'Other Exonumia', 'Nativity'];
        }
        // Derived dropdowns: distinct values straight out of the embedded ODS
        // VLOOKUP table - nothing to hand-manage. Autofill can still land any
        // other value (the select gains its option on the fly).
        static $vlookupKey = ['coin_type' => 'coin_type', 'denomination' => 'denomination',
                              'composition' => 'composition', 'fineness' => 'fineness',
                              'country_of_manufacture' => 'country'];
        if (isset($vlookupKey[$col['dropdown']])) {
            $k = $vlookupKey[$col['dropdown']];
            $out = [];
            foreach (self::lookups()['category_copy'] ?? [] as $c) {
                $v = trim((string) ($c[$k] ?? ''));
                if ($v !== '' && !in_array($v, $out, true)) { $out[] = $v; }
            }
            sort($out, SORT_NATURAL | SORT_FLAG_CASE);
            return $out;
        }
        // Small fixed vocabularies for the other GreySheet-autofilled fields
        // (same lists the AI is constrained to). Unknown autofill values still
        // land - the select gains the option on the fly.
        static $small = [
            'single_coin_or_set' => ['Single Coin', 'Set'],
            'circulated_or_uncirculated' => ['Circulated', 'Uncirculated'],
            'strike_type' => ['Business', 'Burnished', 'Enhanced Uncirculated', 'Matte', 'Proof-Like',
                              'Satin', 'Specimen', 'Proof', 'Brilliant Proof', 'Reverse Proof', 'Satin Proof'],
            'certification' => ['Uncertified', 'ANACS', 'CAC', 'ICG', 'NGC', 'NGC & CAC', 'PCGS', 'PCGS & CAC',
                                'U.S. Mint', 'PCGS Banknote Grading', 'PCGS Currency', 'PMG', 'Legacy Currency Grading'],
            'mint_mark' => ['No Mint Mark', 'CC', 'D', 'D/D', 'D/S', 'Mo', 'O', 'O/CC', 'O/O', 'O/S',
                            'P', 'P, D', 'P, D, S', 'P, D, S, W', 'P, D, W', 'P, S', 'P, S, W', 'P, W',
                            'S', 'S, W', 'S/S', 'W', 'Various Mint Marks'],
            'mint_location' => ['Philadelphia', 'Denver', 'San Francisco', 'West Point', 'Carson City',
                                'New Orleans', 'Charlotte', 'Dahlonega', 'Manila', 'Mexico City'],
        ];
        if (isset($small[$col['dropdown']])) { return $small[$col['dropdown']]; }
        return self::values()[$col['dropdown']] ?? [];
    }
    public static function lookups(): array
    {
        if (self::$lookups === null) { self::$lookups = self::data()['lookups'] ?? []; }
        return self::$lookups;
    }
    /* Coin Type valid values grouped by Country of Manufacture (from the
     * embedded VLOOKUP table). The form's combo menu narrows to the chosen
     * country's list; no country (or an unknown one) shows everything.
     * (Denomination is a plain GreySheet-filled text box - no menu.) */
    public static function countryFieldOptions(): array
    {
        $out = ['coin_type' => []];
        foreach (self::lookups()['category_copy'] ?? [] as $c) {
            $country = trim((string) ($c['country'] ?? ''));
            if ($country === '') { continue; }   // stays in the full-list fallback only
            foreach (array_keys($out) as $f) {
                $v = trim((string) ($c[$f] ?? ''));
                if ($v === '') { continue; }
                if (!in_array($v, $out[$f][$country] ?? [], true)) { $out[$f][$country][] = $v; }
            }
        }
        foreach ($out as $f => $byCountry) {
            foreach ($byCountry as $k => $list) {
                sort($list, SORT_NATURAL | SORT_FLAG_CASE);
                $out[$f][$k] = $list;
            }
        }
        return $out;
    }
    /* Fields required for EVERY listing - the Sellbrite export's peach
     * "Mandatory for all listings" group (plus Quantity/Cost from the inventory
     * file). An empty required field flags the listing "needs attention" but
     * still saves, so this can faithfully mirror the sheet. Original Retail and
     * extra image slots are left off (the workbook itself makes them optional). */
    public static function requiredNames(): array
    {
        // Coin details keeps ONLY SKU / SKU of Parent Product / Price / Cost /
        // Quantity required; every other coin-details box is optional. The
        // other sections (listing copy, packaging, images) keep theirs.
        return ['sku', 'category_name', 'price', 'name', 'description', 'extended_description',
                'feature_1', 'feature_2', 'feature_3', 'feature_4', 'feature_5',
                'package_weight', 'package_length', 'package_width', 'package_height',
                'exact_image', 'product_image_1', 'quantity', 'cost'];
    }
    /* Extra fields a marketplace needs, shown only when that market is chosen
     * for the SKU (from the Sellbrite export's market groups). Only fields that
     * already exist as columns are listed; the eBay-specific condition columns
     * (ebay_coin_condition_type, ebay_graded_coin_*) need adding to SBLPRODUCT
     * first, so they are intentionally not here yet. */
    public static function marketFields(): array
    {
        return [
            // Search Terms are Amazon-specific (workbook row-1 note on col 30).
            'amazon'  => ['fields' => ['search_terms'], 'required' => ['search_terms']],
            'ebay'    => ['fields' => ['ebay_coin_condition_type', 'ebay_graded_coin_letter_grade',
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
                'strike_type','composition','fineness','precious_metal_content',
                'single_coin_or_set','set_count','country_of_manufacture','brand',
                'bullion_shape','coin_design',
            ],
            'Pricing & Inventory' => ['price','cost','quantity','upc','original_retail'],
            'Listing Content'     => [
                'exact_image','name','description','extended_description',
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

        // Product image URLs are NOT auto-generated (the SKU-based guesses were
        // wrong) - the operator pastes the real uploaded photo URLs.
        if ($g('creation_date') === '') { $row['creation_date'] = date('Y-m-d'); }

        $copyVal = static fn(string $k): string => self::lookupValue($copy[$k] ?? '', '');
        // Search Terms are Amazon-specific: only auto-build them when the SKU
        // can go to Amazon (market blank / all / amazon).
        $mkt = strtolower($g('marketplace'));
        if ($mkt === '' || $mkt === 'all' || $mkt === 'amazon') {
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
        }
        // CONDITION bullet derives from grade/circulated directly, so it fills
        // even when the description carries no condition clause yet.
        $condBits = $g('grade') !== '' && strcasecmp($g('grade'), 'Ungraded') !== 0
                  ? $g('grade') : $g('circulated_or_uncirculated');
        if ($condBits !== '') {
            if (!preg_match('/condition$/i', $condBits)) { $condBits .= ' Condition'; }
            $row['feature_2'] = 'CONDITION: ' . $condBits;
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
        // Market-required fields are coin-specific; paper money is exempt.
        $catText = ($row['category_name'] ?? '') . ' ' . ($row['paper_money_type'] ?? '');
        $isPaper = (bool) preg_match('/currency|paper money|banknote|\bnote\b/i', $catText);
        if (!$isPaper) {
            foreach (Schema::marketFields()[$market]['required'] ?? [] as $mf) { $required[$mf] = true; }
        }
        // Search Terms are Amazon-only, but not coin-only: required whenever the
        // SKU can go to Amazon ('all' / blank / amazon), even for paper money.
        if ($market === '' || $market === 'all' || $market === 'amazon') {
            $required['search_terms'] = true;
        }
        // Coin details requires only SKU / SKU of Parent / Cost / Quantity -
        // the coin block itself is optional (autofill supplies it anyway).
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
        // Cost and Quantity are operator-required; Price only has to be a
        // number when entered (autofill suggests it).
        $cost = $g('cost');
        if ($cost === '')           { $statuses['cost'] = 'error'; $messages['cost'] = 'Required field'; }
        elseif (!is_numeric($cost)) { $statuses['cost'] = 'error'; $messages['cost'] = 'Must be a number'; }
        if ($g('price') !== '' && !is_numeric($g('price'))) {
            $statuses['price'] = 'error'; $messages['price'] = 'Must be a number';
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
    // Per the sheet's row-1 annotations: Search Terms and Style are Amazon-
    // specific; the eBay set is the modified pair plus the condition fields.
    // A market-filtered export drops the OTHER markets' columns entirely.
    private const AMAZON_ONLY = ['search_terms', 'style'];
    private const EBAY_ONLY   = ['modified_item', 'modification_description',
                                 'ebay_coin_condition_type','ebay_graded_coin_letter_grade',
                                 'ebay_graded_coin_numerical_grade','ebay_graded_coin_professional_grader',
                                 'z_ebay_ungraded_coin_condition'];

    public static function markets(): array { return ['all', 'amazon', 'ebay', 'walmart']; }

    /* Which LAYOUT column positions a market's file keeps ('all' keeps every
     * column, header-for-header with Des's workbook). */
    private static function keepIndexes(string $market): array
    {
        $drop = [];
        if ($market === 'amazon')  { $drop = self::EBAY_ONLY; }
        if ($market === 'ebay')    { $drop = self::AMAZON_ONLY; }
        if ($market === 'walmart') { $drop = array_merge(self::AMAZON_ONLY, self::EBAY_ONLY); }
        $keep = [];
        foreach (self::LAYOUT as $i => $name) {
            if (!in_array($name, $drop, true)) { $keep[] = $i; }
        }
        return $keep;
    }

    /* Internal working fields with no Sellbrite header (diameter/weight are
     * ours until Des adds them in Sellbrite) - kept out of the upload file. */
    private const INTERNAL_ONLY = ['diameter', 'weight'];

    /* The EXACT Sellbrite product_data layout (Des's file): 88 machine names in
     * order. Deprecated columns (style, modified_item, ...) still export as
     * empty columns so the file matches header-for-header. */
    private const LAYOUT = [
        'sku','parent_sku','name','description','red_book_description',
        'feature_1','feature_2','feature_3','feature_4','feature_5',
        'brand','country_of_manufacture','price','original_retail','creation_date',
        'condition','condition_note','package_weight','package_height','package_length','package_width',
        'exact_image','product_image_1','product_image_2','product_image_3','product_image_4',
        'product_image_5','product_image_6','product_image_7','product_image_8','search_terms',
        'coin_type','denomination','year','mint_mark','mint_location','coin_variety_1','coin_variety_2',
        'coin_design','grade','designation_abbrivation','title_suffix','circulated_or_uncirculated',
        'strike_type','certification','certification_number','composition','fineness',
        'precious_metal_content','single_coin_or_set','set_count','total_precious_metal_content',
        'style','modified_item','modification_description',
        'ebay_coin_condition_type','ebay_graded_coin_letter_grade','ebay_graded_coin_numerical_grade',
        'ebay_graded_coin_professional_grader','z_ebay_ungraded_coin_condition',
        'bullion_shape','paper_money_grade_designation','paper_money_series_designation','paper_money_type',
        'advent_calendar_item_height','advent_calendar_item_length','advent_calendar_item_weight',
        'advent_calendar_item_width','advent_calendar_material','advent_calendar_number_of_items',
        'advent_calendar_occasion','advent_calendar_shape','advent_calendar_theme','advent_calendar_type',
        'watch_band_material','watch_band_type','watch_band_width','watch_case_material','watch_case_size',
        'watch_department','watch_display_type','watch_manufacturer_warranty','watch_movement_type',
        'watch_water_resistance','stamp_color','stamp_quality','stamp_type','nativity_item_type',
    ];
    private const LAYOUT_HUMAN = [
        'SKU*','SKU of Parent Product','Product Name','Product Description','Red Book Description',
        'Feature 1','Feature 2','Feature 3','Feature 4','Feature 5',
        'Brand Name','Country of Manufacture','Price','Original Retail','Creation Date',
        'Condition (new, used, reconditioned)','Condition Note','Package Weight (pounds)',
        'Package Height (inches)','Package Length (inches)','Package Width (inches)',
        'Exact Image','Product Image URL 1','Product Image URL 2','Product Image URL 3','Product Image URL 4',
        'Product Image URL 5','Product Image URL 6','Product Image URL 7','Product Image URL 8','Search Terms',
        'Coin Type','Denomination','Year','Mint Mark','Mint Location','Coin Variety 1','Coin Variety 2',
        'Coin Design','Grade','Designation Abbrivation','Title Suffix','Circulated or Uncirculated',
        'Strike Type','Certification','Certification Number','Composition','Fineness',
        'Precious Metal Content','Single Coin or Set','Set Count','Total Precious Metal Content',
        'Style','Modified Item','Modification Description',
        'eBay Coin Condition Type','eBay Graded Coin Letter Grade','eBay Graded Coin Numerical Grade',
        'eBay Graded Coin Professional Grader','z eBay Ungraded Coin Condition',
        'Bullion Shape','Paper Money Grade Designation','Paper Money Series Designation','Paper Money Type',
        'Advent Calendar Item Height','Advent Calendar Item Length','Advent Calendar Item Weight',
        'Advent Calendar Item Width','Advent Calendar Material','Advent Calendar Number of Items',
        'Advent Calendar Occasion','Advent Calendar Shape','Advent Calendar Theme','Advent Calendar Type',
        'Watch Band Material','Watch Band Type','Watch Band Width','Watch Case Material','Watch Case Size',
        'Watch Department','Watch Display Type','Watch Manufacturer Warranty','Watch Movement Type',
        'Watch Water Resistance','Stamp Color','Stamp Quality','Stamp Type','Nativity Item Type',
    ];
    // Row-1 group annotations at their exact column positions (0-based).
    private const LAYOUT_NOTES = [
        2  => 'Mandatory for all listings, independent of product category',
        30 => 'Amazon specific',
        31 => 'US Coin and World Coin used by all stores',
        52 => 'US Coin and World Coin, Amazon Specific, could depreciate',
        53 => 'US Coin and World Coin, eBay Specific, could depreciate',
        55 => 'US Coin and World Coin, eBay Specific, mandatory and specific',
        60 => 'Bullion Category Only',
        61 => 'Paper Money Category Only',
        64 => 'Advent Calendar Category Only',
        74 => 'Watch Category Only',
        84 => 'Stamp Category Only',
        87 => 'Nativity Product Category Only',
    ];

    /* Column fills exactly as in Des's workbook (0-based column => ARGB). */
    public static function headerFills(): array
    {
        $f = [];
        $paint = static function ($a, $b, $c) use (&$f) { for ($i = $a; $i <= $b; $i++) { $f[$i] = $c; } };
        $paint(0, 30, 'FFFFDBB6');   // mandatory for all (peach)
        $paint(31, 51, 'FFFFF5CE');  // coin block (yellow)
        $paint(52, 52, 'FFDEDCE6');  // style (purple)
        $paint(53, 54, 'FFDDE8CB');  // modified pair (green)
        $paint(55, 59, 'FFFFD8CE');  // eBay mandatory (pink)
        $paint(60, 60, 'FFFFDBB6');  // bullion
        $paint(61, 63, 'FFFFF5CE');  // paper money
        $paint(64, 73, 'FFDEDCE6');  // advent
        $paint(74, 83, 'FFDDE8CB');  // watch
        $paint(84, 86, 'FFFFDBB6');  // stamp
        $paint(87, 87, 'FFFFF5CE');  // nativity
        return $f;
    }

    /* Colour-coded XLSX matching Des's product_data workbook; a specific
     * market keeps only its own columns (Amazon drops the eBay set and vice
     * versa). Returns null when PhpSpreadsheet isn't available (caller falls
     * back to CSV). */
    public static function xlsx(array $rows, string $market = 'all')
    {
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) { return null; }
        // "A5"-style addresses: works on every PhpSpreadsheet version (the
        // [col,row] array form only exists from 1.23 up).
        $cell = static fn($i, $r) =>
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $r;
        $keep  = self::keepIndexes($market);
        $fills = self::headerFills();
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('product_data');
        $ws->setCellValue('A2', 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
            . 'You MAY delete or change the order of columns, but do NOT alter the header names in row 3. *Required Fields.');
        foreach ($keep as $i => $orig) {
            if (isset(self::LAYOUT_NOTES[$orig])) { $ws->setCellValue($cell($i, 1), self::LAYOUT_NOTES[$orig]); }
            $ws->setCellValue($cell($i, 3), self::LAYOUT_HUMAN[$orig]);
            $ws->setCellValue($cell($i, 4), self::LAYOUT[$orig]);
            if (isset($fills[$orig])) {
                foreach ([1, 3, 4] as $rowNo) {
                    $ws->getStyle($cell($i, $rowNo))->getFill()
                       ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                       ->getStartColor()->setARGB($fills[$orig]);
                }
            }
        }
        // Column widths follow the content (header + widest cell), capped so
        // the copy-heavy columns (description, features) stay readable.
        $widths = [];
        foreach ($keep as $i => $orig) { $widths[$i] = strlen(self::LAYOUT_HUMAN[$orig]); }
        $r = 5;
        foreach ($rows as $row) {
            $mkt = strtolower(trim((string) ($row['marketplace'] ?? '')));
            foreach ($keep as $i => $orig) {
                $name = self::LAYOUT[$orig];
                $src  = $name;
                if ($name === 'parent_sku')           { $src = 'category_name'; }
                if ($name === 'red_book_description') { $src = 'extended_description'; }
                $v = (string) ($row[$src] ?? '');
                // Search Terms are Amazon-specific - blank for eBay/Walmart-only SKUs.
                if ($name === 'search_terms' && $mkt !== '' && $mkt !== 'all' && $mkt !== 'amazon') { $v = ''; }
                if ($v !== '') {
                    $ws->setCellValueExplicit($cell($i, $r), $v,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    // Multi-line text: the longest line drives the width.
                    foreach (explode("\n", $v) as $ln) { $widths[$i] = max($widths[$i], strlen($ln)); }
                }
            }
            $r++;
        }
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1))
               ->setWidth(min(max($w + 2, 10), 60));
        }
        return $ss;
    }

    public static function csv(array $rows, string $market = 'all'): string
    {
        $keep = self::keepIndexes($market);
        $n = count($keep);
        $banner = 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
                . 'You MAY delete or change the order of columns, but do NOT alter the '
                . 'header names in row 3. *Required Fields.';
        $fh = fopen('php://temp', 'r+');
        $notes = $human = $machine = [];
        foreach ($keep as $orig) {
            $notes[]   = self::LAYOUT_NOTES[$orig] ?? '';
            $human[]   = self::LAYOUT_HUMAN[$orig];
            $machine[] = self::LAYOUT[$orig];
        }
        $bannerRow = array_fill(0, $n, ''); $bannerRow[0] = $banner;
        fputcsv($fh, $notes); fputcsv($fh, $bannerRow);
        fputcsv($fh, $human); fputcsv($fh, $machine);
        foreach ($rows as $row) {
            $mkt  = strtolower(trim((string) ($row['marketplace'] ?? '')));
            $line = [];
            foreach ($keep as $orig) {
                $name = self::LAYOUT[$orig];
                // Internal names differ for two Sellbrite headers.
                $src = $name;
                if ($name === 'parent_sku')           { $src = 'category_name'; }         // store category
                if ($name === 'red_book_description') { $src = 'extended_description'; }  // renamed internally
                $v = (string) ($row[$src] ?? '');
                // Search Terms are Amazon-specific - blank for eBay/Walmart-only SKUs.
                if ($name === 'search_terms' && $mkt !== '' && $mkt !== 'all' && $mkt !== 'amazon') { $v = ''; }
                $line[] = $v;
            }
            fputcsv($fh, $line);
        }
        rewind($fh); $out = stream_get_contents($fh); fclose($fh);
        return $out;
    }
}