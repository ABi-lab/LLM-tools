<?php
/**
 * file_text_read — Read lines from a text file.
 * Can be run directly: php tools/file_text_read/index.php path=C:\file.txt offset=0 length=100
 * Or called through the dispatcher.
 */

// When run directly from CLI, parse query-string style args from argv
if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$path   = $_REQUEST['path']   ?? null;
$offset = max(0, (int)($_REQUEST['offset'] ?? 0));
$length = min(1000, max(1, (int)($_REQUEST['length'] ?? 100)));

if ($path === null) {
    showForm();
    exit;
}

if (!file_exists($path)) {
    echo "Error: File not found: $path";
    return;
}

if (!is_readable($path)) {
    echo "Error: File is not readable: $path";
    return;
}

$lines = file($path, FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    echo "Error: Failed to read file.";
    return;
}

$total   = count($lines);
$slice   = array_slice($lines, $offset, $length);
$end     = $offset + count($slice);

$msg_start = "LINES ".$offset."–".($end - 1)." OF $total TOTAL";
$line_s = "-----===== $msg_start BELOW THIS LINE =====-----\n";
echo $line_s;
echo implode("\n", $slice);
if ($end < $total) {
    $remaining = $total - $end;
    $msg_end = "$remaining more lines. Call with offset=$end for more.";
    $line_e = "\n-----===== $msg_end =====-----";
    echo $line_e;
}

// ---------------------------------------------------------------------------

function showForm(): void {
    $self = basename($_SERVER['PHP_SELF'] ?? 'index.php');
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>file_text_read</title></head><body>
    <h2>Read text from file</h2>
    <form method="post" action="">
      <label>Path: <input name="path" size="60" required></label><br><br>
      <label>Offset (line): <input name="offset" type="number" value="0" min="0"></label><br><br>
      <label>Length (lines, max 1000): <input name="length" type="number" value="100" min="1" max="1000"></label><br><br>
      <button type="submit">Read</button>
    </form>
    </body></html>
    HTML;
}
