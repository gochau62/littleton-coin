<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_logic.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  * Date Written 07/01/2026                         *  -->
<!--  ***************************************************  -->
<!--  * Maintenance History                             *  -->
<!--  *                                                 *  -->
<!--  * Author    -                                     *  -->
<!--  * Date      -                                     *  -->
<!--  * Purpose   -                                     *  -->
<!--  *                                                 *  -->
<!--  * Project   - 260064                              *  -->
<!--  ***************************************************   */


if (!defined('SBL_CDN_PREFIX')) {
    define('SBL_CDN_PREFIX', 'https://cdn.shopify.com/s/files/1/0198/0799/3956/files/');
}
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

/* =========================================================================
 * SCHEMA - reference data reader (fields, valid values, lookups, pools)
 * Source of truth: the array in SellbriteBulkLoader_data.php.
 * ========================================================================= */
final class Schema
{
    private static $data = null;
    private static $schema = null;
    private static $values = null;
    private static $lookups = null;

    // Opens the reference binder (_data.php) once and keeps it handy.
    /** Load the consolidated reference data (schema/values/lookups) once. */
    private static function data(): array
    {
        if (self::$data === null) { self::$data = require __DIR__ . '/SellbriteBulkLoader_data.php'; }
        return is_array(self::$data) ? self::$data : [];
    }

    // Hands out the list of every form box / spreadsheet column.
    public static function columns(): array
    {
        if (self::$schema === null) { self::$schema = self::data()['schema'] ?? []; }
        return self::$schema;
    }
    // Same list, but looked up by a box's machine name.
    public static function byName(): array
    {
        $out = [];
        foreach (self::columns() as $c) { $out[$c['name']] = $c; }
        return $out;
    }
    // Hands out the dropdowns' allowed-options lists.
    public static function values(): array
    {
        if (self::$values === null) { self::$values = self::data()['values'] ?? []; }
        return self::$values;
    }
    // "what should THIS box's dropdown menu show?"
    // Des's per-category listing copy: an admin override wins, his generated
    // file is the base; [] when the category has neither
    public static function categoryCopy(string $cat): array
    {
        $cat = trim($cat);
        if ($cat === '') { return []; }
        if (function_exists('sblCfgAll')) {
            static $ov = null;
            if ($ov === null) { $ov = sblCfgAll('COPY'); }
            if (isset($ov[$cat])) {
                $d = json_decode($ov[$cat], true);
                if (is_array($d)) { return $d; }
            }
        }
        $base = self::data()['category_copy'] ?? [];
        return $base[$cat] ?? [];
    }

    public static function optionsFor(array $col): array
    {
        if (empty($col['dropdown'])) { return []; }
        // a staff-managed list from the admin screen wins over the built-in one
        if (function_exists('sblCfgAll')) {
            static $ovValues = null;
            if ($ovValues === null) { $ovValues = sblCfgAll('VALUES'); }
            if (isset($ovValues[$col['name']])) {
                $d = json_decode($ovValues[$col['name']], true);
                if (is_array($d) && $d) { return array_map('strval', $d); }
            }
        }
        if ($col['dropdown'] === 'store_category') {
            // Des's full Sellbrite store category list; the --- rows are section
            // markers and never render as options
            return [
                    '--- US COINS ---', 'America the Beautiful Quarter', 'American Innovation Dollar',
                    'American Women Quarter', 'Barber Dime', 'Barber Half Dollar',
                    'Barber Quarter', 'Braided Hair Half Cent', 'Braided Hair Large Cent',
                    'Buffalo Nickel', 'Capped Bust Dime', 'Capped Bust Half Dime',
                    'Capped Bust Half Dollar', 'Capped Bust Quarter', 'Classic Head Half Cent',
                    'Classic Head Large Cent', 'Coronet Head Large Cent', 'DC & US Territories Quarter',
                    'Draped Bust Dime', 'Draped Bust Dollar', 'Draped Bust Half Cent',
                    'Draped Bust Half Dime', 'Draped Bust Half Dollar', 'Draped Bust Large Cent',
                    'Draped Bust Quarter', 'Eisenhower Dollar', 'Flowing Hair Half Dime',
                    'Flowing Hair Large Cent', 'Flying Eagle Small Cent', 'Franklin Half Dollar',
                    'Indian Head Small Cent', 'Jefferson Nickel', 'Kennedy Half Dollar',
                    'Liberty Cap Half Cent', 'Liberty Cap Large Cent', 'Liberty Nickel',
                    'Liberty Walking Half Dollar', 'Lincoln Bicentennial Small Cent', 'Lincoln Memorial Small Cent',
                    'Lincoln Shield Small Cent', 'Lincoln Wheat Small Cent', 'Mercury Dime',
                    'Morgan Dollar', 'Morgan Dollar Toned', 'Native American Dollar',
                    'Peace Dollar', 'Presidential Dollar', 'Roosevelt Dime',
                    'Seated Half Dime', 'Seated Liberty Dime', 'Seated Liberty Dollar',
                    'Seated Liberty Half Dollar', 'Seated Liberty Quarter', 'Semiquincentennial Dime',
                    'Semiquincentennial Half Dollar', 'Semiquincentennial Quarter', 'Shield Nickel',
                    'Standing Liberty Quarter', 'State Quarter', 'Susan B Anthony Dollar',
                    'Three Cent Nickel', 'Three Cent Silver', 'Trade Dollar',
                    'Twenty Cent', 'Two Cent', 'Washington Quarter',
                    'Gold $1', 'Gold $2.50 Quarter Eagle', 'Gold $3',
                    'Gold $5 Half Eagle', 'Gold $10 Eagle', 'Gold $20 Double Eagle',
                    'Fractional Pioneer Gold', 'Colonial', 'Post Colonial',
                    'U.S. Philippine Coin', 'Other US Coin', '--- COMMEMORATIVES ---',
                    'Classic Silver Commemorative', 'Classic Gold Commemorative', 'Modern Silver/Clad Commemorative',
                    'Modern Gold Commemorative', '--- BULLION ---', 'Silver Bullion Coin',
                    'Silver Bar or Round', 'Other Silver Bullion', 'Gold Bullion Coin',
                    'Gold Bar or Round', 'Gold Leaf or Flake', 'Gold Nugget',
                    'Platinum Bullion Coin', 'Platinum Bar or Round', 'Palladium Bullion Coin',
                    'Titanium Bar or Round', 'Copper Bar or Round', 'Other Bullion',
                    '--- SETS ---', 'Proof Set', 'Mint Set',
                    '--- MIXED LOTS / ROLLS ---', 'US Small Cent Mixed Lot', 'US Nickel Mixed Lot',
                    'US Half Dollar Mixed Lot', 'US Dollar Mixed Lot', 'US Gold Mixed Lot',
                    'US Coin Mixed Lot', 'US Rolls', '--- PAPER CURRENCY ---',
                    'Large Size Federal Reserve Bank Note', 'Large Size Federal Reserve Note', 'Large Size Gold Certificate',
                    'Large Size Legal Tender Note', 'Large Size National Banknote', 'Large Size Silver Certificate',
                    'Small Size Federal Reserve Bank Note', 'Small Size Federal Reserve Note', 'Small Size Gold Certificate',
                    'Small Size Legal Tender Note', 'Small Size National Banknote', 'Small Size Silver Certificate',
                    'Small Size WWII Emergency Note', 'Colonial Currency', 'Confederate Currency',
                    'Fractional Currency', 'Military Payment Certificate', 'Obsolete Currency',
                    'Obsolete Bank Check', 'Treasury Note', 'Other US Paper Money',
                    '--- PAPER MONEY MIXED LOTS ---', 'Large Size Paper Money Mixed Lot', 'Small Size Paper Money Mixed Lot',
                    'Paper Money Mixed Lot', '--- FOREIGN COINS ---', 'Australia Collection',
                    'Australia Commemorative', 'Australia Proof Set', 'Austria Coin',
                    'British India Coin', 'Cameroon Coin', 'Canada Large Cent',
                    'Canada Small Cent', 'Canada Five Cent Silver', 'Canada Five Cent',
                    'Canada Ten Cent', 'Canada Twenty Cent', 'Canada Twenty Five Cent',
                    'Canada Fifty Cent', 'Canada Dollar', 'Canada Two Dollar',
                    'Canada Commemorative', 'Canada Mint Set', 'Canada Proof Set',
                    'Canada Specimen Set', 'Canada Pre-Confederation Coin', 'Canada Token',
                    'Other Canada Coin', 'Canada Mixed Lot', 'Cayman Islands Coin',
                    'Cook Islands Coin', 'East Germany Coin', 'Egypt Coin',
                    'Fiji Coin', 'German Empire Coin', 'German States Coin',
                    'Germany Third Reich Coin', 'Germany Weimar Republic Coin', 'Germany West & Unified Coin',
                    'Ghana Coin', 'Hawaiian Coin', 'Indonesia Coin',
                    'Isle of Man Coin', 'Italy Coin', 'Liberia Coin',
                    'Maldives Coin', 'Malta Coin', 'Mexico Colonial Coin',
                    'Mexico War of Independence Coin', 'Mexico Empire of Iturbide Coin', 'Mexico First Republic Coin',
                    'Mexico Empire of Maximilian Coin', 'Mexico Second Republic Coin', 'Mexico Modern Coin',
                    'Mexico Mixed Lot', 'Nepal Coin', 'Netherlands Coin',
                    'Russia Empire Coin', 'Russia Federation Coin', 'Russia USSR Coin',
                    'Solomon Islands Coin', 'Spanish Coin', 'Sweden Coin',
                    'Swiss Coin', 'Tuvalu Coin', 'UK / Great Britain Commemorative',
                    'UK / Great Britain Penny', 'UK / Great Britain Sixpence', 'UK / Great Britain Threepence',
                    'Other UK / Great Britain Coin', 'Vanuatu Coin', 'Other Asia Coin',
                    'World Coin Mixed Lot', '--- FOREIGN PAPER MONEY ---', 'China Paper Money',
                    'France Allied Military Currency', 'Italy Allied Military Currency', 'Japan Allied Military Currency',
                    '--- EXONUMIA ---', 'Car Wash Token', 'Civil War Token',
                    'Good Luck Token', 'Hard Times Token', 'Love Token',
                    'Parking Token', 'Recovery Program Token', 'Tax Token',
                    'Transit Token', 'US Trade Token', 'Other Token',
                    'Hobo Nickel', 'Medal', 'Native American Coin',
                    'Snuff Opium Coin', 'So-Called Dollar', 'Other Exonumia',
                    '--- ANCIENT COINS ---', 'Ancient Byzantine Coin', 'Ancient Gaulish Coin',
                    'Ancient Greek Coin', 'Ancient Roman Imperial Coin', 'Ancient Roman Provincial Coin',
                    'Ancient Roman Republic Coin', 'Other Ancient Coin', '--- SUPPLIES ---',
                    'Coin Album', 'Other Coin & Money Supplies', '--- NOVELTIES / OTHER ---',
                    'Advent Calendar', 'Challenge Coin', 'Christmas Nativity Items',
                    'Christmas Tree Ornaments', 'Disney Dollars', 'Earring',
                    'Gift Box', 'Keychain', 'Necklace',
                    'Novelty US Paper Money', 'Other Historical Memorabilia', 'Other Star Trek Collectible',
                    'Palm Stone', 'Postcards', 'Role Playing Game Accessories',
                    'United States Postage Stamp', 'Wristwatches',
            ];
        }

        static $small = [
            // Sellbrite condition; collectible coins list as "used" (default).
            'condition' => ['new', 'used'],
            // Des's catch-all: details grades, errors, slab labels, packaging, hoards
            'title_suffix' => ['Details', 'Cleaned Details', 'Harshly Cleaned', 'Damaged Details',
                'Holed Details', 'Scratched', 'Corroded', 'Bent', 'Environmental Damage',
                'Mint Error', 'Off-Center', 'Clipped Planchet', 'Doubled Die',
                'First Strike', 'Early Releases', 'First Releases',
                'GSA Hoard', 'Redfield Collection', 'Binion Collection', 'Hoard Coin',
                'w/ Box & COA', 'Original Government Packaging', 'Sealed Mint Packaging'],
            'composition' => ['Silver', 'Gold', 'Platinum', 'Palladium', 'Copper', 'Copper-Nickel',
                              'Copper-Nickel Clad', 'Copper-Plated Zinc', 'Silver Clad', 'Sterling Silver',
                              'Bronze', 'Brass', 'Manganese-Brass', 'Aluminum-Bronze', 'Zinc-Coated Steel',
                              'Nickel-Plated Steel', 'Bi-Metallic', 'Titanium', 'Pewter', 'Paper'],
            'fineness' => ['0.35', '0.4', '0.5', '0.75', '0.8', '0.8292', '0.835', '0.8924', '0.9',
                           '0.9167', '0.925', '0.999', '0.9995', '0.9999',
                           '9K', '10K', '12K', '14K', '18K', '22K', '24K'],
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
            // Country autofills from the drill-down / GreySheet path; this
            // list is just the combo menu for manual entries.
            'country_of_manufacture' => ['United States', 'Australia', 'Austria', 'Canada', 'China',
                                'France', 'Germany', 'India', 'Indonesia', 'Isle of Man', 'Italy',
                                'Japan', 'Mexico', 'Russia', 'South Africa', 'Sweden', 'United Kingdom'],
        ];
        if (isset($small[$col['dropdown']])) { return $small[$col['dropdown']]; }
        return self::values()[$col['dropdown']] ?? [];
    }
    // Hands out the packaging weight tables (slab add-ons, GSA holders).
    public static function lookups(): array
    {
        if (self::$lookups === null) { self::$lookups = self::data()['lookups'] ?? []; }
        return self::$lookups;
    }

    // Splits the grade list at its dividers
    public static function gradePools(): array
    {
        $pools = ['coin_uncertified' => [], 'coin_certified' => [],
                  'paper_uncertified' => [], 'paper_certified' => []];
        $map = ['--- UNCERTIED US COINS ---' => 'coin_uncertified',
                '--- CERTIFIED COINS ---' => 'coin_certified',
                '--- UNCERTIFIED PAPER MONEY ---' => 'paper_uncertified',
                '--- CERTIFIED PAPER MONEY ---' => 'paper_certified'];
        $cur = null; $lead = [];
        foreach (self::values()['grade'] ?? [] as $v) {
            if (isset($map[$v])) { $cur = $map[$v]; continue; }
            if (strpos($v, '---') === 0) { $cur = null; continue; } 
            if ($cur === null) { $lead[] = $v; continue; }
            $pools[$cur][] = $v;
        }
        $pools['coin_uncertified']  = array_merge($lead, $pools['coin_uncertified']);
        $pools['paper_uncertified'] = array_merge($lead, $pools['paper_uncertified']);
        return $pools;
    }

    // Splits the Coin Type list into the per-tree menus (US/World x Coins/Currency).
    public static function coinTypePools(): array
    {
        $map = [
            '--- US COINS ---' => 'us_coins', '--- US GOLD ---' => 'us_coins',
            '--- COMMEMORATIVE ---' => 'us_coins', '--- HAWAIIAN ---' => 'us_coins',
            '--- U.S. PHILIPPINES ---' => 'us_coins',
            '--- BULLION ---' => 'bullion_split',   // American issues -> US, the rest -> world
            '--- U.S. MINT SETS (STANDARD RELEASES) ---' => 'us_coins',
            '--- U.S. MINT SETS (NON-STANDARD RELEASES) ---' => 'us_coins',
            '--- COLONIAL ---' => 'us_coins', '--- FRACTIONAL PIONEER GOLD ---' => 'us_coins',
            '--- EXONUMIA ---' => 'us_coins',
            '--- PAPER MONEY ---' => 'us_currency', '--- OBSOLETE CURRENCY ---' => 'us_currency',
            '--- FOREIGN PAPER MONEY ---' => 'world_currency',
            '--- ANCIENTS: ROMAN RULERS ---' => 'world_coins',
            '--- ANCIENTS: ROMAN REPUBLIC ---' => 'world_coins',
            '--- ANCIENTS: BYZANTINE ---' => 'world_coins',
            '--- ANCIENTS: GREEK ---' => 'world_coins', '--- ANCIENTS: GAULISH ---' => 'world_coins',
            '--- BULLION (OTHER) ---' => 'world_coins',
        ];
        $pools = ['us_coins' => [], 'us_currency' => [], 'world_coins' => [], 'world_currency' => []];
        $cur = [];
        foreach (self::values()['coin_type'] ?? [] as $v) {
            if (strpos($v, '---') === 0) { $cur = explode(' ', $map[$v] ?? ''); continue; }
            foreach ($cur as $p) {
                if ($p === 'bullion_split') {
                    // "America The Beautiful/American Eagle/Buffalo" are U.S.
                    // Mint bullion; Maple Leafs, Libertads etc. are world coins.
                    $pools[strpos($v, 'America') === 0 ? 'us_coins' : 'world_coins'][] = $v;
                } elseif ($p !== '') {
                    $pools[$p][] = $v;
                }
            }
        }
        return $pools;
    }
    // required boxes (the red stars). Add a name here to require a field everywhere.
    public static function requiredNames(): array
    {
        return ['sku', 'category_name', 'price', 'condition', 'certification', 'name', 'description', 'extended_description',
                'feature_1', 'feature_2', 'feature_3', 'feature_4', 'feature_5',
                'package_weight', 'package_length', 'package_width', 'package_height',
                'exact_image', 'product_image_1', 'quantity', 'cost'];
    }

    // Which extra boxes each marketplace (Amazon/eBay...) needs.
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
    // How the boxes are grouped into the form's collapsible sections.
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

/* =========================================================================
 * COMPUTER - the spreadsheet formulas (title/copy/packaging/eBay fields)
 * Fills only boxes it owns: empties, or values it computed itself.
 * ========================================================================= */
final class Computer
{
    /** Return a copy of $row with all auto/derived columns (re)computed. */
    public static function apply(array $row): array
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        $sku = $g('sku'); $category = $g('category_name');
        $lookups = Schema::lookups();
        $meta = $lookups['category_meta'][$category] ?? [];
        $copy = [];

        // Product image URLs are NOT auto-generated; the operator pastes the real uploaded photo URLs.
        if ($g('creation_date') === '') { $row['creation_date'] = date('Y-m-d'); }

        // money boxes: strip thousands commas ("6,250.00" -> "6250.00")
        foreach (['price', 'cost', 'original_retail'] as $pf) {
            if (strpos($g($pf), ',') !== false) { $row[$pf] = str_replace(',', '', $g($pf)); }
        }

        // Des's per-category copy fills the Extended Description when empty
        if ($g('extended_description') === '') {
            $dc = Schema::categoryCopy($category);
            $txt = trim((string) ($dc['copy'] ?? ''));
            if ($txt === '') { $txt = trim((string) ($dc['alt1'] ?? '')); }
            if ($txt === '') { $txt = trim((string) ($dc['alt2'] ?? '')); }
            if ($txt !== '') { $row['extended_description'] = $txt; }
        }

        // Search Terms are Amazon-specific: only auto-build when amazon
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

        // GreySheet provides denomination/composition/fineness by the time the coin is picked; 
        $grade = $g('grade');
        if ($g('circulated_or_uncirculated') === '' && $grade !== '') {
            $row['circulated_or_uncirculated'] = self::lookupValue($lookups['grade_circ'][$grade] ?? '', '');
        }
        // Condition follows certification: a certified coin lists as new, an
        // uncertified one as used - off the screen but still in the spreadsheet
        $condCert = $g('certification');
        $row['condition'] = ($condCert !== '' && strcasecmp($condCert, 'Uncertified') !== 0) ? 'new' : 'used';
        // "1 Dollar" reads as just "Dollar"; multiples ("10 Kreuzer") keep their number
        if (preg_match('/^1\s+(\S.*)$/', $g('denomination'), $dm)) { $row['denomination'] = $dm[1]; }

        // Package weight = the coin's own weight FROM GREYSHEET
        // auto adjusted for certification wrap and slabs from GSA
        $weight = $g('package_weight');
        $pw = $lookups['package_weights'] ?? [];
        if ($g('single_coin_or_set') !== 'Set') {
            $isGsa = stripos($g('coin_variety_1'), 'GSA') !== false
                  || stripos($g('coin_variety_2'), 'GSA') !== false;
            $base = $isGsa ? ($pw['gsa'][$g('title_suffix')] ?? null) : null;
            if ($base === null && !$isGsa && is_numeric($g('weight'))) {
                $base = (float) $g('weight') * 0.0685714;   // troy oz -> lb
            }
            // paper money has no metal weight - a sleeved note gets the flat base, holders add on like slabs
            if ($base === null && !$isGsa
                && (strcasecmp($g('composition'), 'Paper') === 0 || $g('paper_money_type') !== ''
                    || preg_match('/currency|paper money|banknote|\bnote\b/i', $g('category_name')))) {
                $base = (float) ($pw['paper_base'] ?? 0.035);
            }
            if ($base !== null) {
                $certAdds = $pw['certification'] ?? [];
                $add = !$isGsa ? ($certAdds[$g('certification')] ?? $certAdds['Uncertified'] ?? 0) : 0;
                $new = (string) round($base + $add, 2);
                $ours = $weight === '';
                if (!$ours && !$isGsa) {
                    foreach ($certAdds as $a) {
                        if ((string) round($base + $a, 2) === $weight) { $ours = true; break; }
                    }
                }
                if (!$ours && in_array($weight, array_map('strval', array_values($pw['gsa'] ?? [])), true)) {
                    $ours = true;
                }
                if ($ours) { $weight = $new; $row['package_weight'] = $new; }
            }
        }
        if (is_numeric($weight)) {
            $w = (float) $weight;
            $row['package_length'] = $w < 0.5 ? '9' : '11';
            $row['package_width']  = $w < 0.5 ? '8' : ($w < 1 ? '9' : '10');
            $row['package_height'] = $w < 0.17 ? '1' : ($w < 1 ? '2' : '4');
        }
        if (stripos($sku, '.WS') !== false && $g('price') !== '' && $g('original_retail') === '') { $row['original_retail'] = $g('price'); }

        // keep whatever is in the box when there is not enough yet to compose a title,
        // so the LCC inventory description stands in until the parts arrive
        $builtTitle = self::buildTitle($row);
        if ($builtTitle !== '') { $row['name'] = $builtTitle; }
        // The description REBUILDS while it still has the standard house shape

        $curDesc = trim((string) ($row['description'] ?? ''));
        if ($curDesc === '' || preg_match('/^A genuine\b/i', $curDesc)) {
            $built = self::buildDescription($row, $copy);
            if ($built !== '' && $curDesc !== '') {
                // Only the first sentence is formulaic
                $keep = [];
                foreach (array_slice(preg_split('/(?<=\.)\s+/', $curDesc), 1) as $s) {
                    if (trim($s) !== '') { $keep[] = trim($s); }
                }
                if ($keep) { $built .= ' ' . implode(' ', $keep); }
            }
            $row['description'] = $built;
        }

        // 1 DETAILS  2 CONDITION  3 IMAGES  4 COLLECTOR'S NOTE  5 ABOUT PCC.
        $desc = trim((string) ($row['description'] ?? ''));
        if ($desc !== '') {
            // DETAILS = the identity part of sentence 1
            
            $first = preg_split('/(?<=\.)\s/', $desc, 2)[0];
            $core  = preg_replace('/^A genuine\s+/i', '', $first);
            $bits  = preg_split('/,\s*(?:in|graded and certified|from|with)\s+/i', $core, 2);
            $row['feature_1'] = 'DETAILS: ' . rtrim(trim($bits[0]), ' .,');
        }
        // CONDITION bullet derives from grade/circulated directly
        $condBits = $g('grade') !== '' && strcasecmp($g('grade'), 'Ungraded') !== 0
                  ? $g('grade') : $g('circulated_or_uncirculated');
        if ($condBits !== '') {
            if (!preg_match('/condition$/i', $condBits)) { $condBits .= ' Condition'; }
            $row['feature_2'] = 'CONDITION: ' . $condBits;
        }
        // Sellbrite Condition (new/used/reconditioned): collectible coins list
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
            // eBay refuses numerical grades on raw coins - MS/PR numbers list as Uncirculated
            $zc = $grade !== '' && strcasecmp($grade, 'Ungraded') !== 0 ? $grade : $g('circulated_or_uncirculated');
            if (preg_match('/^(MS|PR|PF|SP)\s*-?\s*\d/i', $zc)) { $zc = 'Uncirculated'; }
            $row['z_ebay_ungraded_coin_condition'] = $zc;
        }

        $exact = trim((string) ($row['exact_image'] ?? ''));
        if ($exact !== '') { $row['feature_3'] = 'IMAGES: ' . $exact; }
        // feature_4 = the agent's category COLLECTOR'S NOTE; make sure it carries the label.
        $note = trim((string) ($row['feature_4'] ?? ''));
        if ($note !== '' && stripos($note, "COLLECTOR'S NOTE") !== 0) { $row['feature_4'] = "COLLECTOR'S NOTE: " . $note; }
        // already begins "ABOUT PROFILE COINS & COLLECTIBLES:"
        $row['feature_5'] = SBL_ABOUT_SELLER; 
        return $row;
    }

    // tiny helper - uses the tables answers
    private static function lookupValue(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '***')) { return $fallback; }
        return $value;
    }

    // builts product title; year, mint mark, series, varieties, denomination, grade, certification + "Coin Collectible".
    private static function buildTitle(array $row): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('category_name') === '') { return ''; }
        // the coin is named by its Coin Type; the store category stands in without one
        $catName = $g('coin_type') !== '' ? $g('coin_type') : $g('category_name');
        $parts = [
            $g('year'),
            $g('mint_mark') !== '' && $g('mint_mark') !== 'No Mint Mark' ? $g('mint_mark') : '',
            $catName,
            $g('coin_variety_1'),   // the distinguishing issue ("Anna May Wong")
            $g('coin_variety_2'),
            $g('denomination'),
            $g('grade') !== '' && $g('grade') !== 'Ungraded' ? $g('grade') : '',
            $g('certification') !== '' && $g('certification') !== 'Uncertified' ? $g('certification') : '',
            $g('title_suffix'),   // operator catch-all: grade/error/packaging/slab details
            'Coin Collectible',   // constant title tail (ODS hardcodes this, not title_suffix)
        ];
        $parts = array_filter($parts, static fn($p) => $p !== '');
        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
    }
    // Writes the one house sentence ("A genuine ..., in X Condition." / "..., graded and certified X by Y.").
    private static function buildDescription(array $row, array $copy): string
    {
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        if ($g('category_name') === '') { return ''; }
        $specs = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $g('year'),
            $g('mint_mark') !== '' && $g('mint_mark') !== 'No Mint Mark' ? $g('mint_mark') : '',
            $g('coin_variety_1'),
            $g('coin_variety_2'),
            $g('coin_type') !== '' ? $g('coin_type') : $g('category_name'),
            $g('denomination'),
        ]))));
        $d = 'A genuine ' . $specs . ' Coin';
        $grade = $g('grade'); $cert = $g('certification');
        $certified = $cert !== '' && strcasecmp($cert, 'Uncertified') !== 0 && strcasecmp($cert, 'U.S. Mint') !== 0;
        if ($certified && $grade !== '' && strcasecmp($grade, 'Ungraded') !== 0) {
            $d .= ', graded and certified ' . trim($grade . ' ' . $g('designation_abbrivation')) . ' by ' . $cert;
        } else {
            $cond = $grade !== '' && strcasecmp($grade, 'Ungraded') !== 0 ? $grade : $g('circulated_or_uncirculated');
            if ($cond !== '') { $d .= ', in ' . $cond . ' Condition'; }
        }
        return $d . '.';
    }
}

/* =========================================================================
 * VALIDATOR - per-field statuses + messages (required/format/nudges)
 * ========================================================================= */
final class Validator
{
    // The proofreader: every box gets a color - red must fix, yellow look at this, green fine.
    public static function check(array $row): array
    {
        $statuses = []; $messages = [];
        $g = static fn(string $k): string => trim((string) ($row[$k] ?? ''));
        // Required = schema flag OR the Sellbrite "mandatory for all" set OR the chosen marketplace's required fields.
        $required = array_flip(Schema::requiredNames());
        $market   = strtolower(trim((string) ($row['marketplace'] ?? '')));
        // Market-required fields are coin-specific; paper money is exempt.
        $catText = ($row['category_name'] ?? '') . ' ' . ($row['paper_money_type'] ?? '');
        $isPaper = (bool) preg_match('/currency|paper money|banknote|\bnote\b/i', $catText);
        if (!$isPaper) {
            foreach (Schema::marketFields()[$market]['required'] ?? [] as $mf) { $required[$mf] = true; }
        }
        // Search Terms are Amazon-only
        if ($market === '' || $market === 'all' || $market === 'amazon') {
            $required['search_terms'] = true;
        }
        // Coin details requires only SKU / SKU of Parent / Cost / Quantity - coin block itself is optional
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
        // Cost and Quantity are operator-required; price must be numeric
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
        // Certification Number opens once a grading service is picked; yellow warning to fill
        $vCert = $g('certification');
        if ($vCert !== '' && strcasecmp($vCert, 'Uncertified') !== 0 && strcasecmp($vCert, 'U.S. Mint') !== 0
            && $g('certification_number') === '') {
            $statuses['certification_number'] = 'action'; $messages['certification_number'] = 'Enter the certification number';
        }
        if ($g('single_coin_or_set') === 'Set' && $g('set_count') === '') {
            $statuses['set_count'] = 'action'; $messages['set_count'] = 'Enter number of coins in the set';
        }
        return ['statuses' => $statuses, 'messages' => $messages, 'valid' => !in_array('error', $statuses, true)];
    }
}

/* =========================================================================
 * EXPORTER - Sellbrite spreadsheet layout, per-market columns, xlsx/csv
 * ========================================================================= */
final class Exporter
{

    // A market-filtered export drops the OTHER markets' columns entirely.
    private const AMAZON_ONLY = ['search_terms', 'style'];
    private const EBAY_ONLY   = ['modified_item', 'modification_description',
                                 'ebay_coin_condition_type','ebay_graded_coin_letter_grade',
                                 'ebay_graded_coin_numerical_grade','ebay_graded_coin_professional_grader',
                                 'z_ebay_ungraded_coin_condition'];

    // The marketplaces the export dropdown offers.
    public static function markets(): array { return ['all', 'amazon', 'ebay', 'walmart']; }

    // Which columns survive a market's export (eBay drops the Amazon-only ones, and vice versa).
    private static function keepIndexes(string $market): array
    {
        // staff overrides from the admin screen move a column between markets
        $ov = function_exists('sblCfgAll') ? sblCfgAll('MARKET') : [];
        $keep = [];
        foreach (self::LAYOUT as $i => $name) {
            $home = in_array($name, self::AMAZON_ONLY, true) ? 'amazon'
                  : (in_array($name, self::EBAY_ONLY, true) ? 'ebay' : 'all');
            $set = strtolower(trim((string) ($ov[$name] ?? '')));
            if ($set === 'none') { continue; }
            if (in_array($set, ['all', 'amazon', 'ebay', 'walmart'], true)) { $home = $set; }
            if ($market !== 'all' && $home !== 'all' && $home !== $market) { continue; }
            $keep[] = $i;
        }
        return $keep;
    }

    // Staff-added columns (data screen): appended after the standard layout on every export.
    private static function customCols(string $market): array
    {
        $out = [];
        $all = function_exists('sblCfgAll') ? sblCfgAll('COL') : [];
        foreach ($all as $name => $json) {
            $c = json_decode((string) $json, true) ?: [];
            $home = strtolower(trim((string) ($c['market'] ?? 'all')));
            if ($market !== 'all' && $home !== 'all' && $home !== $market) { continue; }
            $out[] = ['name' => (string) $name, 'label' => (string) ($c['label'] ?? $name),
                      'value' => (string) ($c['value'] ?? '')];
        }
        return $out;
    }

    // the admin screen lists every upload column with its home market
    public static function layout(): array
    {
        $out = [];
        foreach (self::LAYOUT as $i => $n) {
            $out[] = ['name' => $n, 'label' => self::LAYOUT_HUMAN[$i],
                      'home' => in_array($n, self::AMAZON_ONLY, true) ? 'amazon'
                              : (in_array($n, self::EBAY_ONLY, true) ? 'ebay' : 'all')];
        }
        return $out;
    }

    // Internal working fields with no Sellbrite header
    private const INTERNAL_ONLY = ['diameter', 'weight'];

    // exact Sellbrite product_data excel spreadsheet layout
    private const LAYOUT = [
        'sku','name','description','red_book_description',
        'feature_1','feature_2','feature_3','feature_4','feature_5',
        'brand','country_of_origin','price','original_retail','creation_date',
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
        'SKU*','Product Name','Product Description','Red Book Description',
        'Feature 1','Feature 2','Feature 3','Feature 4','Feature 5',
        'Brand Name','Country of Origin','Price','Original Retail','Creation Date',
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

    // The header background colors
    public static function headerFills(): array
    {
        $f = [];
        $paint = static function ($a, $b, $c) use (&$f) { for ($i = $a; $i <= $b; $i++) { $f[$i] = $c; } };
        $paint(0, 29, 'FFFFDBB6');   // mandatory for all (peach)
        $paint(30, 50, 'FFFFF5CE');  // coin block (yellow)
        $paint(51, 51, 'FFDEDCE6');  // style (purple)
        $paint(52, 53, 'FFDDE8CB');  // modified pair (green)
        $paint(54, 58, 'FFFFD8CE');  // eBay mandatory (pink)
        $paint(59, 59, 'FFFFDBB6');  // bullion
        $paint(60, 62, 'FFFFF5CE');  // paper money
        $paint(63, 72, 'FFDEDCE6');  // advent
        $paint(73, 82, 'FFDDE8CB');  // watch
        $paint(83, 85, 'FFFFDBB6');  // stamp
        $paint(86, 86, 'FFFFF5CE');  // nativity
        return $f;
    }

    // Builds the real Excel download: 3 header rows, every cell as text, columns auto-sized.
    public static function xlsx(array $rows, string $market = 'all')
    {
        if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) { return null; }
        // "A5"-style addresses: works on every PhpSpreadsheet version (the [col,row] array form only exists from 1.23 up).
        $cell = static fn($i, $r) =>
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $r;
        $keep  = self::keepIndexes($market);
        $fills = self::headerFills();
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('product_data');
        $ws->setCellValue('A1', 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
            . 'You MAY delete or change the order of columns, but do NOT alter the header names in row 2. *Required Fields.');
        // staff-added columns follow the standard layout
        $extra = self::customCols($market);
        $base  = count($keep);
        foreach ($extra as $j => $c) {
            $ws->setCellValue($cell($base + $j, 2), $c['label']);
            $ws->setCellValue($cell($base + $j, 3), $c['name']);
        }
        foreach ($keep as $i => $orig) {
            $ws->setCellValue($cell($i, 2), self::LAYOUT_HUMAN[$orig]);
            $ws->setCellValue($cell($i, 3), self::LAYOUT[$orig]);
            if (isset($fills[$orig])) {
                foreach ([2, 3] as $rowNo) {
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
        foreach ($extra as $j => $c) { $widths[$base + $j] = strlen($c['label']); }
        $r = 4;
        foreach ($rows as $row) {
            $mkt = strtolower(trim((string) ($row['marketplace'] ?? '')));
            foreach ($keep as $i => $orig) {
                $name = self::LAYOUT[$orig];
                $src  = $name;
                if ($name === 'country_of_origin')    { $src = 'country_of_manufacture'; }
                if ($name === 'red_book_description') { $src = 'extended_description'; }
                $v = (string) ($row[$src] ?? '');
                // Search Terms are Amazon-specific - blank for eBay/Walmart-only SKUs.
                if ($name === 'search_terms' && $mkt !== '' && $mkt !== 'all' && $mkt !== 'amazon') { $v = ''; }
                if ($v !== '') {
                     // "Explicitly TEXT" so Excel never mangles values like the SKU "255R.50" into numbers or dates.
                    $ws->setCellValueExplicit($cell($i, $r), $v,
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    // Multi-line text: the longest line drives the width.
                    foreach (explode("\n", $v) as $ln) { $widths[$i] = max($widths[$i], strlen($ln)); }
                }
            }
            foreach ($extra as $j => $c) {
                if ($c['value'] === '') { continue; }
                $ws->setCellValueExplicit($cell($base + $j, $r), $c['value'],
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $widths[$base + $j] = max($widths[$base + $j], strlen($c['value']));
            }
            $r++;
        }
        foreach ($widths as $i => $w) {
            $ws->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1))
                // Width = longest content + padding, never narrower than 10 or wider than 60 characters.
               ->setWidth(min(max($w + 2, 10), 60));
        }
        return $ss;
    }

    // The plain-text fallback when the Excel library is not installed.
    public static function csv(array $rows, string $market = 'all'): string
    {
        $keep = self::keepIndexes($market);
        $extra = self::customCols($market);
        $n = count($keep) + count($extra);
        $banner = 'SELLBRITE PRODUCT CSV TEMPLATE (Do NOT remove the first 3 rows). '
                . 'You MAY delete or change the order of columns, but do NOT alter the '
                . 'header names in row 2. *Required Fields.';
        $fh = fopen('php://temp', 'r+');
        $human = $machine = [];
        foreach ($keep as $orig) {
            $human[]   = self::LAYOUT_HUMAN[$orig];
            $machine[] = self::LAYOUT[$orig];
        }
        foreach ($extra as $c) { $human[] = $c['label']; $machine[] = $c['name']; }
        $bannerRow = array_fill(0, $n, ''); $bannerRow[0] = $banner;
        fputcsv($fh, $bannerRow);
        fputcsv($fh, $human); fputcsv($fh, $machine);
        foreach ($rows as $row) {
            $mkt  = strtolower(trim((string) ($row['marketplace'] ?? '')));
            $line = [];
            foreach ($keep as $orig) {
                $name = self::LAYOUT[$orig];
                // Internal names differ for two Sellbrite headers.
                $src = $name;
                if ($name === 'country_of_origin')    { $src = 'country_of_manufacture'; }
                if ($name === 'red_book_description') { $src = 'extended_description'; }  // renamed internally
                $v = (string) ($row[$src] ?? '');
                // Search Terms are Amazon-specific - blank for eBay/Walmart-only SKUs.
                if ($name === 'search_terms' && $mkt !== '' && $mkt !== 'all' && $mkt !== 'amazon') { $v = ''; }
                $line[] = $v;
            }
            foreach ($extra as $c) { $line[] = $c['value']; }
            fputcsv($fh, $line);
        }
        rewind($fh); $out = stream_get_contents($fh); fclose($fh);
        return $out;
    }
}