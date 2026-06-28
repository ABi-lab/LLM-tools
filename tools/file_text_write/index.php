<?php
/**
 * file_text_write — Write (or insert) text into a file, creating it if needed.
 * Can be run directly: php tools/file_text_write/index.php path=C:\file.txt content=hello
 */

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$path    = $_REQUEST['path']    ?? null;
$offset  = isset($_REQUEST['offset']) ? max(0, (int)$_REQUEST['offset']) : null;
$content = $_REQUEST['content'] ?? '';

if ($path === null) {
    showForm();
    exit;
}

// Ensure directory exists
$dir = dirname($path);
if (!is_dir($dir)) {
    if (!mkdir($dir, 0777, true)) {
        echo "Error: Could not create directory: $dir";
        return;
    }
}

// Insert at offset, or append/overwrite
if ($offset === null) {
    // No offset: write from beginning (overwrite / create)
    if (file_put_contents($path, $content) === false) {
        echo "Write failed: could not write to $path";
        return;
    }
    echo "File successfully written: $path";
    return;
}

// Insert at line offset
$existing = [];
if (file_exists($path)) {
    $existing = file($path, FILE_IGNORE_NEW_LINES) ?: [];
}

$before = array_slice($existing, 0, $offset);
$after  = array_slice($existing, $offset);
$newLines = explode("\n", $content);

$merged = array_merge($before, $newLines, $after);

if (file_put_contents($path, implode("\n", $merged)) === false) {
    echo "Write failed: could not write to $path";
    return;
}

echo "File successfully modified: $path (inserted " . count($newLines) . " line(s) at offset $offset)";

// ---------------------------------------------------------------------------

function showForm(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>file_text_write</title></head><body>
    <h2>Write to (or create) text file</h2>
    <form method="post" action="">
      <label>Path: <input name="path" size="60" required></label><br><br>
      <label>Offset (line, leave blank to overwrite from start): <input name="offset" type="number" min="0"></label><br><br>
      <label>Content:<br><textarea name="content" rows="10" cols="80"></textarea></label><br><br>
      <button type="submit">Write</button>
    </form>
    </body></html>
    HTML;
}
