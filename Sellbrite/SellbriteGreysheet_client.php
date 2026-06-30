<?php
/*    ***************************************************  -->
<!--  * Program Name - SellbriteGreysheet_client.php    *  -->
<!--  *                                                 *  -->
<!--  * Author    - G CHAU                              *  -->
<!--  *             Littleton Coin Company              *  -->
<!--  *             Littleton NH                        *  -->
<!--  ***************************************************   */

/*
 * Thin client for the GreySheet CDN Public API v2.
 *
 *  - Reads credentials from SellbriteGreysheet_config.php (git-ignored).
 *  - Sends the required x-api-token / x-api-key headers.
 *  - Handles errors properly: cURL failures, non-2xx HTTP, and bad JSON all
 *    come back as a structured result instead of a fatal.
 *  - Monitors usage: captures rate-limit / quota response headers and the
 *    request timing, and logs them so we can watch consumption while testing.
 *
 *  Public surface:
 *     gsConfig()                       -> array (or throws if not configured)
 *     gsApiGet($path, $params, &$meta) -> decoded body array, or null on error
 *     gsResult($path, $params)         -> ['ok'=>bool,'status'=>int,'data'=>..,
 *                                          'error'=>..,'usage'=>[..],'ms'=>int]
 */

if (!function_exists('gsConfig')) {
    function gsConfig()
    {
        static $cfg = null;
        if ($cfg !== null) { return $cfg; }
        $file = __DIR__ . '/SellbriteGreysheet_config.php';
        if (!is_file($file)) {
            throw new RuntimeException('Missing SellbriteGreysheet_config.php — copy the .sample and add your keys.');
        }
        $cfg = require $file;
        $cfg += ['base_url' => '', 'api_token' => '', 'api_key' => '', 'api_level' => 'basic', 'timeout' => 20];
        return $cfg;
    }
}

/** Log a short usage/error line through the LCC logger when present, else error_log. */
if (!function_exists('gsLog')) {
    function gsLog($msg)
    {
        $line = 'GreySheet ' . $msg;
        if (function_exists('putLCCOnlineLogRec')) { putLCCOnlineLogRec($line); }
        else { error_log($line); }
    }
}

/**
 * GET a path under the configured base URL.
 *  $params  query string params (apiLevel is added automatically)
 *  $meta    receives: status, error, usage (rate-limit headers), ms, url
 * Returns the decoded JSON body (array) on success, or null on any failure.
 */
if (!function_exists('gsApiGet')) {
    function gsApiGet($path, array $params = [], array &$meta = [])
    {
        $cfg = gsConfig();
        $meta = ['status' => 0, 'error' => '', 'usage' => [], 'ms' => 0, 'url' => ''];

        if ($cfg['api_token'] === '' || $cfg['api_token'] === 'PUT-TEST-TOKEN-HERE'
            || $cfg['api_key'] === '' || $cfg['api_key'] === 'PUT-TEST-KEY-HERE') {
            $meta['error'] = 'API token/key not set in SellbriteGreysheet_config.php';
            gsLog('config: ' . $meta['error']);
            return null;
        }

        if (!isset($params['apiLevel']) && $cfg['api_level'] !== '') {
            $params['apiLevel'] = $cfg['api_level'];
        }
        $url = rtrim($cfg['base_url'], '/') . '/' . ltrim($path, '/');
        if ($params) { $url .= '?' . http_build_query($params); }
        $meta['url'] = $url;

        // Capture response headers (rate-limit / quota names vary, so grab any).
        $headers = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) $cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'x-api-token: ' . $cfg['api_token'],
                'x-api-key: '   . $cfg['api_key'],
                'Accept: application/json',
            ],
            CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$headers) {
                $p = explode(':', $h, 2);
                if (count($p) === 2) {
                    $name = strtolower(trim($p[0]));
                    if (strpos($name, 'ratelimit') !== false || strpos($name, 'rate-limit') !== false
                        || strpos($name, 'quota') !== false || strpos($name, 'x-api') !== false) {
                        $headers[trim($p[0])] = trim($p[1]);
                    }
                }
                return strlen($h);
            },
        ]);
        $t0   = microtime(true);
        $body = curl_exec($ch);
        $meta['ms'] = (int) round((microtime(true) - $t0) * 1000);

        if ($body === false) {
            $meta['error'] = 'cURL: ' . curl_error($ch) . ' (errno ' . curl_errno($ch) . ')';
            curl_close($ch);
            gsLog('network ' . $meta['error'] . ' url=' . $url);
            return null;
        }
        $meta['status'] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $meta['usage']  = $headers;
        curl_close($ch);

        if ($meta['status'] === 401 || $meta['status'] === 403) {
            $meta['error'] = 'Auth rejected (HTTP ' . $meta['status'] . ') — check token/key and subscription tier';
            gsLog($meta['error']);
            return null;
        }
        if ($meta['status'] === 429) {
            $meta['error'] = 'Rate limited (HTTP 429) — back off; see usage headers';
            gsLog($meta['error'] . ' usage=' . json_encode($headers));
            return null;
        }
        if ($meta['status'] < 200 || $meta['status'] >= 300) {
            $meta['error'] = 'HTTP ' . $meta['status'];
            gsLog($meta['error'] . ' url=' . $url . ' body=' . substr((string) $body, 0, 300));
            return null;
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $meta['error'] = 'Bad JSON: ' . json_last_error_msg();
            gsLog($meta['error'] . ' url=' . $url);
            return null;
        }
        if ($headers) { gsLog('usage ' . json_encode($headers) . ' ms=' . $meta['ms']); }
        return $data;
    }
}

/** Convenience wrapper returning a flat result array (handy for the test page / AJAX). */
if (!function_exists('gsResult')) {
    function gsResult($path, array $params = [])
    {
        $meta = [];
        try {
            $data = gsApiGet($path, $params, $meta);
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => 0, 'data' => null, 'error' => $e->getMessage(),
                    'usage' => [], 'ms' => 0, 'url' => ''];
        }
        return [
            'ok'     => $data !== null,
            'status' => $meta['status'],
            'data'   => $data,
            'error'  => $meta['error'],
            'usage'  => $meta['usage'],
            'ms'     => $meta['ms'],
            'url'    => $meta['url'],
        ];
    }
}
