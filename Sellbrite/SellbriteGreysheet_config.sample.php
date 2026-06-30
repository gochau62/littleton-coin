<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGreysheet_config.sample *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
/*
 * GreySheet (CDN Public API v2) credentials + settings.
 *
 *  HOW TO USE
 *  ----------
 *  1. Copy this file to  SellbriteGreysheet_config.php  (same folder).
 *  2. Paste your keys below.  The real config file is git-ignored, so the
 *     keys are NEVER committed.
 *  3. Start on the DEV / sandbox base URL to test, then switch to production.
 *
 *  Auth: every call sends two headers - x-api-token and x-api-key.
 *  Base URLs (from the CDN Public API v2 usage guide):
 *     DEV  (testing) : https://cpgpublicapiv2dev.greysheet.com/api
 *     PROD (live)    : https://cpgpublicapiv2.greysheet.com/api
 */
return [
    // Test against DEV first.  Flip to the prod URL only once you are happy.
    'base_url'  => 'https://cpgpublicapiv2dev.greysheet.com/api',

    // Paste your testing credentials here (x-api-token / x-api-key).
    'api_token' => 'PUT-TEST-TOKEN-HERE',
    'api_key'   => 'PUT-TEST-KEY-HERE',

    // 'basic' (Coin Dealer Digital/Coin Dealer) or 'advanced' (Dealer+/Pro).
    'api_level' => 'basic',

    // Network safety.
    'timeout'   => 20,   // seconds per request
];
