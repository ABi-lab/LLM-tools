<?php
/**
 * Description
 */

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$imageB64 = $_REQUEST['image']    ?? null;
$mime     = strtolower(trim($_REQUEST['mime'] ?? 'image/jpeg'));
/* filename param is accepted but only used for the form UI — stored name is always the safe id */

if ($imageB64 === null) {
    showForm();
    exit;
}

// Validate MIME type and derive extension
$allowed = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    echo 'Error: Unsupported MIME type "' . htmlspecialchars($mime, ENT_QUOTES) . '". Allowed: image/jpeg, image/png, image/gif, image/webp.';
    return;
}
$ext = $allowed[$mime];

// Decode base64 — strip data-URL prefix if present
if (str_starts_with($imageB64, 'data:')) {
    $commaPos = strpos($imageB64, ',');
    if ($commaPos !== false) {
        $imageB64 = substr($imageB64, $commaPos + 1);
    }
}
$binary = base64_decode($imageB64, true);
if ($binary === false || strlen($binary) === 0) {
    echo 'Error: Invalid base64 image data.';
    return;
}

// Generate a non-guessable id (32 hex chars = 128 bits of entropy)
$id = bin2hex(random_bytes(16));

// Resolve images directory (BASE_DIR is defined by the dispatcher; use __FILE__ for direct CLI use)
$baseDir   = defined('BASE_DIR') ? BASE_DIR : dirname(__FILE__, 3);
$imagesDir = $baseDir . DIRECTORY_SEPARATOR . 'images';

if (!is_dir($imagesDir) && !mkdir($imagesDir, 0755, true)) {
    echo 'Error: Failed to create images directory.';
    return;
}

$storedName = $id . '.' . $ext;
$filepath   = $imagesDir . DIRECTORY_SEPARATOR . $storedName;

if (file_put_contents($filepath, $binary) === false) {
    echo 'Error: Failed to write image file.';
    return;
}

echo 'http://localhost:7001/images/' . $storedName;

// ---------------------------------------------------------------------------

function showForm(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>image_store</title></head><body>
    <h2>Store a base64 image</h2>
    <form method="post" action="">
      <label>Base64 image data: <textarea name="image" rows="4" cols="60" required></textarea></label><br><br>
      <label>MIME type: <select name="mime">
        <option value="image/jpeg">image/jpeg</option>
        <option value="image/png">image/png</option>
        <option value="image/gif">image/gif</option>
        <option value="image/webp">image/webp</option>
      </select></label><br><br>
      <label>Filename (optional hint): <input name="filename" size="40"></label><br><br>
      <button type="submit">Store</button>
    </form>
    </body></html>
    HTML;
}
