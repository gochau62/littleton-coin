<?php
// dev only probe, not an RFP object, delete it once the redirect is confirmed working
// drop this in the same folder as the old requisition page and open it in a browser
// it answers the only question that matters right now: which file on disk is this web address actually running

header('Content-Type: text/plain');

echo "PROBE\n";
echo str_repeat('=', 60) . "\n\n";

echo "this file on disk    : " . __FILE__ . "\n";
echo "folder it lives in   : " . __DIR__ . "\n";
echo "document root        : " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "web address you used : " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
echo "server time now      : " . date('Y-m-d H:i:s') . "\n\n";

echo "PHP\n";
echo str_repeat('-', 60) . "\n";
echo "version              : " . PHP_VERSION . "\n";
echo "short open tag       : " . (ini_get('short_open_tag') ? 'on' : 'off') . "\n";

// an opcode cache can keep serving a compiled copy of a file after the file on disk was edited
if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(false);
    if (is_array($st) && !empty($st['opcache_enabled'])) {
        echo "opcode cache         : ON, this can serve a stale copy after an edit\n";
        echo "  revalidate freq    : " . ini_get('opcache.revalidate_freq') . " seconds\n";
        echo "  checks timestamps  : " . (ini_get('opcache.validate_timestamps') ? 'yes' : 'NO, edits are ignored until it is reset') . "\n";
    } else {
        echo "opcode cache         : off\n";
    }
} else {
    echo "opcode cache         : not present\n";
}

echo "\nFILES IN THIS FOLDER\n";
echo str_repeat('-', 60) . "\n";
$files = @scandir(__DIR__);
if ($files === false) {
    echo "could not read the folder\n";
} else {
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') { continue; }
        $p = __DIR__ . '/' . $f;
        if (!is_file($p)) { continue; }
        printf("  %-26s %8d bytes   last changed %s\n", $f, filesize($p), date('Y-m-d H:i:s', filemtime($p)));
    }
}

echo "\nWHAT THE OLD PAGE CONTAINS\n";
echo str_repeat('-', 60) . "\n";
$req = __DIR__ . '/request.php';
if (!is_file($req)) {
    echo "request.php is not in this folder, so this is not the folder being served\n";
} else {
    $body = @file_get_contents($req);
    echo "last changed         : " . date('Y-m-d H:i:s', filemtime($req)) . "\n";
    echo "size                 : " . strlen($body) . " bytes\n";
    // the redirect version defines this constant, the old version never did
    echo "is the new version   : " . (strpos($body, 'RQS_NEW_APP') !== false ? 'YES, the redirect is in this file' : 'NO, this is still the old page') . "\n";
    echo "mentions Firefox     : " . (stripos($body, 'Firefox') !== false ? 'yes, old page marker' : 'no') . "\n";
    echo "\nfirst 5 lines of it  :\n";
    $lines = explode("\n", $body);
    for ($i = 0; $i < 5 && $i < count($lines); $i++) {
        echo "  " . rtrim($lines[$i]) . "\n";
    }
}
?>
