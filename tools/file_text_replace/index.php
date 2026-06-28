<?php
/**
 * file_text_replace — Replace an exact text block in a file.
 * Can be run directly: php tools/file_text_replace/index.php path=C:\file.txt search="old" replace="new"
 */

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$path    = $_REQUEST['path']    ?? null;
$search  = $_REQUEST['search']  ?? null;
$replace = $_REQUEST['replace'] ?? $_REQUEST['length'] ?? '';  // 'length' is a legacy alias

if ($path === null || $search === null) {
    showForm();
    exit;
}

if (!file_exists($path)) {
    echo "Error: File not found: $path";
    return;
}

if (!is_readable($path) || !is_writable($path)) {
    echo "Error: File is not readable/writable: $path";
    return;
}

$original = file_get_contents($path);
if ($original === false) {
    echo "Error: Failed to read file.";
    return;
}

// Normalize line endings for matching
$normalizedOriginal = str_replace("\r\n", "\n", $original);
$normalizedSearch   = str_replace("\r\n", "\n", $search);

$count = substr_count($normalizedOriginal, $normalizedSearch);

if ($count === 0) {
    echo "Error: Search text not found in file. The file may have changed — re-read it and retry.";
    return;
}

if ($count > 1) {
    echo "Error: Search text found $count times (ambiguous). Widen the search context to make it unique.";
    return;
}

$newContent = str_replace($normalizedSearch, str_replace("\r\n", "\n", $replace), $normalizedOriginal);

// Preserve original line endings if file used CRLF
if (str_contains($original, "\r\n")) {
    $newContent = str_replace("\n", "\r\n", str_replace("\r\n", "\n", $newContent));
}

if (file_put_contents($path, $newContent) === false) {
    echo "Write failed: could not write to $path";
    return;
}

echo "File successfully modified: $path";

// ---------------------------------------------------------------------------

function showForm(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>file_text_replace</title></head><body>
    <h2>Replace text in file</h2>
    <form method="post" action="">
      <label>Path: <input name="path" size="60" required></label><br><br>
      <label>Search (exact text to find, must be unique):<br>
        <textarea name="search" rows="6" cols="80" required></textarea></label><br><br>
      <label>Replace with (leave blank to delete):<br>
        <textarea name="replace" rows="6" cols="80"></textarea></label><br><br>
      <button type="submit">Replace</button>
    </form>
    </body></html>
    HTML;
}
