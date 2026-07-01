<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGemini_client.php       *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Thin client for the Google Gemini API (generateContent).
 *
 *  - Key + model are inline constants below (edit the top lines).
 *  - Asks Gemini for JSON (responseMimeType application/json) and returns the
 *    decoded object, so the agent can read structured field values.
 *  - Handles errors properly: cURL failure, non-2xx, blocked/empty candidates,
 *    and bad JSON all come back structured instead of fatal.
 *  - Monitors usage: captures token counts (usageMetadata) and timing, logged.
 *
 *  Public surface:
 *     geminiConfigured()                       -> bool (is a key set?)
 *     geminiJson($system, $user, &$meta)       -> decoded JSON (array) or null
 */

/*
 * ----------------------------------------------------------------------------
 *  SETTINGS - edit these lines.  Do NOT commit a real key to git.
 *     Get a key at https://aistudio.google.com/apikey
 * ----------------------------------------------------------------------------
 */
if (!defined('GEMINI_API_KEY')) { define('GEMINI_API_KEY', ''); }                 // paste your Gemini API key
if (!defined('GEMINI_MODEL'))   { define('GEMINI_MODEL',   'gemini-2.5-flash'); } // or gemini-2.5-pro
if (!defined('GEMINI_BASE'))    { define('GEMINI_BASE',    'https://generativelanguage.googleapis.com/v1beta'); }
if (!defined('GEMINI_TIMEOUT')) { define('GEMINI_TIMEOUT', 40); }                  // seconds per request

/** Is a Gemini key configured? */
function geminiConfigured()
{
    return defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '';
}

/** Log a short usage/error line through the LCC logger when present, else error_log. */
if (!function_exists('gsLog')) {
    function gsLog($msg)
    {
        $line = 'Sellbrite-AI ' . $msg;
        if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
        else { error_log($line); }
    }
}

/**
 * Ask Gemini for a JSON object.
 *   $system  system instruction (role / rules)
 *   $user    the user content (data + task)
 *   $meta    receives: status, error, tokens, ms, finish
 * Returns the model's decoded JSON (array) on success, or null on any failure.
 */
function geminiJson($system, $user, array &$meta = [])
{
    $meta = ['status' => 0, 'error' => '', 'tokens' => 0, 'ms' => 0, 'finish' => ''];
    if (!geminiConfigured()) {
        $meta['error'] = 'GEMINI_API_KEY not set in SellbriteGemini_client.php';
        gsLog('config: ' . $meta['error']);
        return null;
    }

    $url  = rtrim(GEMINI_BASE, '/') . '/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
    $body = json_encode([
        'systemInstruction' => ['parts' => [['text' => (string) $system]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => (string) $user]]]],
        'generationConfig'  => [
            'temperature'      => 0.2,
            'responseMimeType' => 'application/json',
            'maxOutputTokens'  => 2048,
        ],
    ], JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => (int) GEMINI_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
    ]);
    $t0  = microtime(true);
    $raw = curl_exec($ch);
    $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);

    if ($raw === false) {
        $meta['error'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
        curl_close($ch);
        gsLog('gemini network ' . $meta['error']);
        return null;
    }
    $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($raw, true);
    if ($meta['status'] < 200 || $meta['status'] >= 300) {
        $meta['error'] = 'Gemini HTTP ' . $meta['status'] . ': '
                       . ($resp['error']['message'] ?? substr((string) $raw, 0, 200));
        gsLog($meta['error']);
        return null;
    }

    $meta['tokens'] = (int) ($resp['usageMetadata']['totalTokenCount'] ?? 0);
    $meta['finish'] = (string) ($resp['candidates'][0]['finishReason'] ?? '');
    $text = $resp['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') {
        $meta['error'] = 'Gemini returned no content (finish=' . $meta['finish'] . ')';
        gsLog($meta['error']);
        return null;
    }

    $data = json_decode($text, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Occasionally the model wraps JSON in prose/fences — salvage the object.
        if (preg_match('/\{.*\}/s', $text, $m) && ($data = json_decode($m[0], true)) !== null) {
            // recovered
        } else {
            $meta['error'] = 'Gemini JSON parse: ' . json_last_error_msg();
            gsLog($meta['error']);
            return null;
        }
    }
    gsLog('gemini ok tokens=' . $meta['tokens'] . ' ms=' . $meta['ms']);
    return is_array($data) ? $data : null;
}
