<?php
/**
 * web_search_brave — Search Brave and return results as plain text.
 */

require_once __DIR__ . '/../_lib/html_utils.php';

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$query = $_REQUEST['query'] ?? null;

if ($query === null) {
    showForm_web_search_brave();
    exit;
}

$url     = 'https://search.brave.com/search?q=' . urlencode($query);
$ctx     = createHttpContext();
$ctx['http']['header'] .= "Referer: https://search.brave.com/\r\n";
$context = stream_context_create($ctx);

$html = @file_get_contents($url, false, $context);
if ($html === false) {
    $error = error_get_last();
    echo "Error: Failed to fetch search results. " . ($error['message'] ?? 'Unknown error');
    return;
}

echo processHtml($html, $url);

// ---------------------------------------------------------------------------

function showForm_web_search_brave(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>web_search_brave</title></head><body>
    <h2>Brave Web Search</h2>
    <form method="post" action="">
      <label>Query: <input name="query" size="80" required></label><br><br>
      <button type="submit">Search</button>
    </form>
    </body></html>
    HTML;
}
