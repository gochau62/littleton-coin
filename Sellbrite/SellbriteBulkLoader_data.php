<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_data.php      *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Column SCHEMA for the Sellbrite Bulk Loader, generated from the source
 * workbook (Sellbrite-Bulk-New.ods).  Loaded once by Schema in
 * SellbriteBulkLoader_logic.php.
 *
 *   'schema' - 85 column definitions (label / required / auto / dropdown)
 *
 * The screen is DB-driven: dropdown options, category lookups and the
 * validation rules live in DB2 (SBLVALUEST / SBLLOOKUPT / SBLRULEST) and are
 * loaded via SellbriteBulkLoader_admin_model.php -> Schema::setOverrides().
 * Initial contents are loaded once from the companion seed scripts
 * (SBLVALUEST.seed.sql / SBLLOOKUPT.seed.sql / SBLRULEST.seed.sql).  Only the
 * fixed 85-column layout stays here, since the form structure needs it before
 * any DB call.  The 'dropdown' key on a column just names which SBLVALUEST
 * list fills it.
 */
return [
    'schema' => [
        [
            'name' => 'sku',
            'label' => 'SKU',
            'required' => true,
            'auto' => false,
        ],
        [
            'name' => 'category_name',
            'label' => 'Sellbrite Category Name',
            'required' => false,
            'auto' => false,
            'dropdown' => 'category_name',
        ],
        [
            'name' => 'year',
            'label' => 'Year',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'mint_mark',
            'label' => 'Mint Mark',
            'required' => false,
            'auto' => false,
            'dropdown' => 'mint_mark',
        ],
        [
            'name' => 'mint_location',
            'label' => 'Mint Location',
            'required' => false,
            'auto' => false,
            'dropdown' => 'mint_location',
        ],
        [
            'name' => 'coin_type',
            'label' => 'Coin Type',
            'required' => false,
            'auto' => true,
            'dropdown' => 'coin_type',
        ],
        [
            'name' => 'denomination',
            'label' => 'Denomination',
            'required' => false,
            'auto' => true,
            'dropdown' => 'denomination',
        ],
        [
            'name' => 'coin_variety_1',
            'label' => 'Coin Variety 1',
            'required' => false,
            'auto' => false,
            'dropdown' => 'coin_variety',
        ],
        [
            'name' => 'coin_variety_2',
            'label' => 'Coin Variety 2',
            'required' => false,
            'auto' => false,
            'dropdown' => 'coin_variety',
        ],
        [
            'name' => 'grade',
            'label' => 'Grade',
            'required' => false,
            'auto' => false,
            'dropdown' => 'grade',
        ],
        [
            'name' => 'title_suffix',
            'label' => 'Title Suffix',
            'required' => false,
            'auto' => false,
            'dropdown' => 'title_suffix',
        ],
        [
            'name' => 'designation_abbrivation',
            'label' => 'Designation Abbrivation',
            'required' => false,
            'auto' => false,
            'dropdown' => 'designation_abbrivation',
        ],
        [
            'name' => 'paper_money_grade_designation',
            'label' => 'Paper Money Grade Designation',
            'required' => false,
            'auto' => false,
            'dropdown' => 'paper_money_grade_designation',
        ],
        [
            'name' => 'paper_money_type',
            'label' => 'Paper Money Type',
            'required' => false,
            'auto' => false,
            'dropdown' => 'paper_money_type',
        ],
        [
            'name' => 'certification',
            'label' => 'Certification',
            'required' => false,
            'auto' => false,
            'dropdown' => 'certification',
        ],
        [
            'name' => 'certification_number',
            'label' => 'Certification Number',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'circulated_or_uncirculated',
            'label' => 'Circulated or Uncirculated',
            'required' => false,
            'auto' => true,
            'dropdown' => 'circulated_or_uncirculated',
        ],
        [
            'name' => 'strike_type',
            'label' => 'Strike Type',
            'required' => false,
            'auto' => false,
            'dropdown' => 'strike_type',
        ],
        [
            'name' => 'style',
            'label' => 'Style',
            'required' => false,
            'auto' => false,
            'dropdown' => 'style',
        ],
        [
            'name' => 'composition',
            'label' => 'Composition',
            'required' => false,
            'auto' => true,
            'dropdown' => 'composition',
        ],
        [
            'name' => 'fineness',
            'label' => 'Fineness',
            'required' => false,
            'auto' => true,
            'dropdown' => 'fineness',
        ],
        [
            'name' => 'precious_metal_content',
            'label' => 'Precious Metal Content',
            'required' => false,
            'auto' => false,
            'dropdown' => 'precious_metal_content',
        ],
        [
            'name' => 'single_coin_or_set',
            'label' => 'Single Coin or Set',
            'required' => false,
            'auto' => false,
            'dropdown' => 'single_coin_or_set',
        ],
        [
            'name' => 'set_count',
            'label' => 'Set Count',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'country_of_manufacture',
            'label' => 'Country of Manufacture',
            'required' => false,
            'auto' => true,
            'dropdown' => 'country_of_manufacture',
        ],
        [
            'name' => 'brand',
            'label' => 'Brand Name',
            'required' => false,
            'auto' => true,
            'dropdown' => 'brand',
        ],
        [
            'name' => 'modified_item',
            'label' => 'Modified Item',
            'required' => false,
            'auto' => false,
            'dropdown' => 'modified_item',
        ],
        [
            'name' => 'modification_description',
            'label' => 'Modification Description',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'bullion_shape',
            'label' => 'Bullion Shape',
            'required' => false,
            'auto' => false,
            'dropdown' => 'bullion_shape',
        ],
        [
            'name' => 'price',
            'label' => 'Price',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'cost',
            'label' => 'Cost',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'quantity',
            'label' => 'Quantity',
            'required' => true,
            'auto' => false,
        ],
        [
            'name' => 'exact_image',
            'label' => 'Exact Image',
            'required' => false,
            'auto' => false,
            'dropdown' => 'exact_image',
        ],
        [
            'name' => 'name',
            'label' => 'Product Name',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'description',
            'label' => 'Product Description',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'red_book_description',
            'label' => 'Red Book Description',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'feature_1',
            'label' => 'Feature 1',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'feature_2',
            'label' => 'Feature 2',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'feature_3',
            'label' => 'Feature 3',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'feature_4',
            'label' => 'Feature 4',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'feature_5',
            'label' => 'Feature 5',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'search_terms',
            'label' => 'Search Terms',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'product_image_1',
            'label' => 'Product Image URL 1',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'product_image_2',
            'label' => 'Product Image URL 2',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'product_image_3',
            'label' => 'Product Image URL 3',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'product_image_4',
            'label' => 'Product Image URL 4',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'product_image_5',
            'label' => 'Product Image URL 5',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'product_image_6',
            'label' => 'Product Image URL 6',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'product_image_7',
            'label' => 'Product Image URL 7',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'product_image_8',
            'label' => 'Product Image URL 8',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'creation_date',
            'label' => 'Creation Date',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'package_length',
            'label' => 'Package Length (inches)',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'package_width',
            'label' => 'Package Width (inches)',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'package_height',
            'label' => 'Package Height (inches)',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'package_weight',
            'label' => 'Package Weight (pounds)',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'condition_note',
            'label' => 'Condition Note',
            'required' => false,
            'auto' => false,
            'dropdown' => 'condition_note',
        ],
        [
            'name' => 'upc',
            'label' => 'UPC',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'original_retail',
            'label' => 'Original Retail',
            'required' => false,
            'auto' => true,
        ],
        [
            'name' => 'total_precious_metal_content',
            'label' => 'Total Precious Metal Content',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'paper_money_series_designation',
            'label' => 'Paper Money Series Designation',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'coin_design',
            'label' => 'Coin Design',
            'required' => false,
            'auto' => false,
            'dropdown' => 'coin_design',
        ],
        [
            'name' => 'nativity_item_type',
            'label' => 'Nativity Item Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_band_material',
            'label' => 'Watch Band Material',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_band_type',
            'label' => 'Watch Band Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_band_width',
            'label' => 'Watch Band Width',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_case_material',
            'label' => 'Watch Case Material',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_case_size',
            'label' => 'Watch Case Size',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_department',
            'label' => 'Watch Department',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_display_type',
            'label' => 'Watch Display Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_manufacturer_warranty',
            'label' => 'Watch Manufacturer Warranty',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_movement_type',
            'label' => 'Watch Movement Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'watch_water_resistance',
            'label' => 'Watch Water Resistance',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'stamp_color',
            'label' => 'Stamp Color',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'stamp_quality',
            'label' => 'Stamp Quality',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'stamp_type',
            'label' => 'Stamp Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_type',
            'label' => 'Advent Calendar Type',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_occasion',
            'label' => 'Advent Calendar Occasion',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_material',
            'label' => 'Advent Calendar Material',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_number_of_items',
            'label' => 'Advent Calendar Number of Items',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_shape',
            'label' => 'Advent Calendar Shape',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_theme',
            'label' => 'Advent Calendar Theme',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_item_height',
            'label' => 'Advent Calendar Item Height',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_item_length',
            'label' => 'Advent Calendar Item Length',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_item_width',
            'label' => 'Advent Calendar Item Width',
            'required' => false,
            'auto' => false,
        ],
        [
            'name' => 'advent_calendar_item_weight',
            'label' => 'Advent Calendar Item Weight',
            'required' => false,
            'auto' => false,
        ],
    ],
];
