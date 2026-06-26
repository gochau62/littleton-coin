<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteBulkLoader_ai.php        *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */
?>

<?php
// AI Listing-Copy Generator. Drafts title / description / features / search terms for
// the hard 20% (foreign, one-off, rare items) by calling Claude. The human reviews and
// edits every draft before saving -- nothing is auto-saved, and facts are never assumed.

if (!defined('SBL_AI_MODEL'))    { define('SBL_AI_MODEL', 'claude-opus-4-8'); }
if (!defined('SBL_AI_ENDPOINT')) { define('SBL_AI_ENDPOINT', 'https://api.anthropic.com/v1/messages'); }
if (!defined('SBL_AI_VERSION'))  { define('SBL_AI_VERSION', '2023-06-01'); }

/*
 * API key -- NEVER hard-coded or committed. Looked up in this order:
 *   1. environment variable  ANTHROPIC_API_KEY
 *   2. a file outside the web root (default /home/lcc/.anthropic_key, chmod 600);
 *      override the path with  define('SBL_AI_KEYFILE', '/your/path');
 */
function sbl_ai_key()
{
    $k = getenv('ANTHROPIC_API_KEY');
    if ($k) { return trim($k); }
    $file = defined('SBL_AI_KEYFILE') ? SBL_AI_KEYFILE : '/home/lcc/.anthropic_key';
    return is_readable($file) ? trim((string) file_get_contents($file)) : '';
}

/** Build [system, user] prompts from the coin's attributes. */
function sbl_ai_prompt(array $row)
{
    $g = static fn($k) => trim((string) ($row[$k] ?? ''));
    $labels = [
        'sku' => 'SKU', 'year' => 'Year', 'mint_mark' => 'Mint Mark', 'mint_location' => 'Mint',
        'category_name' => 'Category', 'coin_type' => 'Coin Type', 'denomination' => 'Denomination',
        'coin_variety_1' => 'Variety', 'coin_variety_2' => 'Variety 2', 'grade' => 'Grade',
        'certification' => 'Certification', 'certification_number' => 'Cert #',
        'circulated_or_uncirculated' => 'Circulated/Uncirculated', 'composition' => 'Composition',
        'fineness' => 'Fineness', 'precious_metal_content' => 'Precious Metal Content',
        'country_of_manufacture' => 'Country', 'single_coin_or_set' => 'Single/Set',
    ];
    $attrs = [];
    foreach ($labels as $k => $label) { if ($g($k) !== '') { $attrs[] = $label . ': ' . $g($k); } }

    $system =
        "You are a numismatic copywriter for Profile Coins & Collectibles, writing product "
      . "listings sold on Sellbrite, eBay, and Amazon. Write accurate, professional copy from "
      . "the supplied attributes.\n\n"
      . "TITLE (name): \"[Year] [Mint Mark] [Category] [Denomination] [Grade] [Certification] "
      . "[Title Suffix]\" -- omit any blank part; do not repeat words; no marketing fluff.\n"
      . "DESCRIPTION: begin \"A genuine [title].\" then 2-4 sentences of accurate historical or "
      . "collector context, then \"Composition: X.\" if composition is known.\n"
      . "FEATURES (exactly 4, in this order):\n"
      . "  1. \"DETAILS: ...\" one line of key specs (year, mint, denomination, metal, grade).\n"
      . "  2. \"COLLECTOR'S NOTE: ...\" 1-3 sentences on the series' history or significance.\n"
      . "  3. \"ABOUT PROFILE COINS & COLLECTIBLES: ...\" a brief about-us blurb (seller of "
      . "collectible coins and currency; satisfaction guaranteed).\n"
      . "  4. one more relevant, factual selling point (authenticity, packaging, or shipping).\n"
      . "SEARCH_TERMS: lowercase, space-separated keywords (denomination, type, country, plus "
      . "\"coin coinage numismatics money collectible\").\n"
      . "RED_BOOK_DESCRIPTION: a short, factual reference-style line (Red Book voice), or \"\".\n\n"
      . "ACCURACY: never invent mintages, populations, grades, or varieties. If unsure of a fact, "
      . "stay general. Never state a grade or certification that was not provided.\n\n"
      . "Return ONLY a JSON object with keys: name, description, features (array of 4 strings), "
      . "search_terms, red_book_description. No markdown, no code fences, no text outside the JSON.";

    $user = "Write the listing for this item:\n" . ($attrs ? implode("\n", $attrs) : '(no attributes provided)')
          . "\n\nReturn ONLY the JSON object.";
    return [$system, $user];
}

/** Strip accidental ```json fences, then JSON-decode. */
function sbl_ai_parse_json($text)
{
    $text = trim((string) $text);
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text);
    $data = json_decode($text, true);
    return is_array($data) ? $data : null;
}

/**
 * Call Claude and return ['ok'=>true,'fields'=>[...machine-name => value]] or
 * ['ok'=>false,'error'=>'...']. Maps the 4 features to feature_1/3/4/5 (feature_2 is the
 * EXACT IMAGE / STOCK PHOTO selector, left for the human).
 */
function sbl_ai_draft(array $row)
{
    $key = sbl_ai_key();
    if ($key === '')               { return ['ok' => false, 'error' => 'No Anthropic API key configured (set ANTHROPIC_API_KEY or the key file).']; }
    if (!function_exists('curl_init')) { return ['ok' => false, 'error' => 'PHP cURL extension not available.']; }

    [$system, $user] = sbl_ai_prompt($row);
    $body = [
        'model'      => SBL_AI_MODEL,
        'max_tokens' => 3000,
        'system'     => $system,
        'messages'   => [['role' => 'user', 'content' => $user]],
    ];

    $ch = curl_init(SBL_AI_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'content-type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: ' . SBL_AI_VERSION,
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_TIMEOUT        => 60,
    ]);
    if (defined('SBL_AI_PROXY') && SBL_AI_PROXY) { curl_setopt($ch, CURLOPT_PROXY, SBL_AI_PROXY); }

    $resp = curl_exec($ch);
    if ($resp === false) { $e = curl_error($ch); curl_close($ch); return ['ok' => false, 'error' => 'Network error: ' . $e]; }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) { return ['ok' => false, 'error' => 'API ' . $code . ': ' . substr((string) $resp, 0, 300)]; }

    $data = json_decode($resp, true);
    $text = '';
    foreach (($data['content'] ?? []) as $b) {
        if (($b['type'] ?? '') === 'text') { $text = $b['text']; break; }
    }
    $out = sbl_ai_parse_json($text);
    if ($out === null) { return ['ok' => false, 'error' => 'Could not parse the AI response as JSON.']; }

    $feats  = array_values($out['features'] ?? []);
    $fields = [
        'name'                 => (string) ($out['name'] ?? ''),
        'description'          => (string) ($out['description'] ?? ''),
        'search_terms'         => (string) ($out['search_terms'] ?? ''),
        'red_book_description' => (string) ($out['red_book_description'] ?? ''),
        'feature_1'            => (string) ($feats[0] ?? ''),
        'feature_3'            => (string) ($feats[1] ?? ''),
        'feature_4'            => (string) ($feats[2] ?? ''),
        'feature_5'            => (string) ($feats[3] ?? ''),
    ];
    return ['ok' => true, 'fields' => $fields];
}
