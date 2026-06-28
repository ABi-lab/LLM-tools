<?php
/**
 * web_fetch — Fetch a URL and return its text content (HTML tags stripped, links preserved).
 * Can be run directly: php tools/web_fetch/index.php url=https://example.com
 */

require_once __DIR__ . '/../_lib/html_utils.php';

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$url = $_REQUEST['url'] ?? $_REQUEST['URL'] ?? null;

if ($url === null) {
    showForm_web_fetch_url();
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid URL: $url";
    return;
}

// PHP built-in server is single-threaded — a self-request to localhost:7001
// would deadlock. Serve these directly from disk instead.
$parsed    = parse_url($url);
$selfHosts = ['localhost', '127.0.0.1', '::1'];
$selfPort  = 7001;
if (in_array($parsed['host'] ?? '', $selfHosts, true) && ($parsed['port'] ?? $selfPort) === $selfPort) {
    $localPath = $parsed['path'] ?? '/';
    $base      = defined('BASE_DIR') ? BASE_DIR : dirname(__DIR__, 2);

    if ($localPath === '/' || $localPath === '' || $localPath === '/index.php') {
        $parts = [];
        foreach (glob($base . '/tools/*/INFO.md') ?: [] as $f) {
            $parts[] = file_get_contents($f);
        }
        echo $parts ? implode("\n\n---\n\n", $parts) : "No tools available.";
        return;
    }

    $filePath = $base . str_replace('/', DIRECTORY_SEPARATOR, $localPath);
    if (!file_exists($filePath)) {
        echo "Error: File not found on local server: $localPath";
        return;
    }
    $content = file_get_contents($filePath);
    echo (str_contains($content, '<html') || str_contains($content, '<!DOCTYPE'))
        ? processHtml($content, $url)
        : $content;
    return;
}

$context = stream_context_create(createHttpContext());

$html = @file_get_contents($url, false, $context);
if ($html === false) {
    $error = error_get_last();
    echo "Error: Failed to fetch URL. " . ($error['message'] ?? 'Unknown error');
    return;
}

echo processHtml($html, $url);

// ---------------------------------------------------------------------------

function showForm_web_fetch_url(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>web_fetch</title></head><body>
    <h2>Fetch Web Page</h2>
    <form method="post" action="">
      <label>URL: <input name="url" size="80" type="url" required></label><br><br>
      <button type="submit">Fetch</button>
    </form>
    </body></html>
    HTML;
}
