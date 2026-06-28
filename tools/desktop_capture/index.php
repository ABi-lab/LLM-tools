<?php
/**
 * desktop_capture — Capture the full virtual screen via PowerShell and return
 * base64 PNG + a stable local URL for serving it.
 *
 * Output: JSON {"_pd_image":true,"url":"...","mime":"image/png","base64":"..."}
 */

$ps = <<<'PS'
Add-Type -Assembly System.Windows.Forms,System.Drawing
$vs  = [System.Windows.Forms.SystemInformation]::VirtualScreen
$bmp = New-Object System.Drawing.Bitmap($vs.Width, $vs.Height)
$g   = [System.Drawing.Graphics]::FromImage($bmp)
$g.CopyFromScreen($vs.Location, [System.Drawing.Point]::Empty, $bmp.Size)
$g.Dispose()
$tmp = [System.IO.Path]::GetTempFileName() + '.png'
$bmp.Save($tmp, [System.Drawing.Imaging.ImageFormat]::Png)
$bmp.Dispose()
[Convert]::ToBase64String([System.IO.File]::ReadAllBytes($tmp))
Remove-Item $tmp -Force
PS;

$scriptFile = tempnam(sys_get_temp_dir(), 'pd_cap_') . '.ps1';
file_put_contents($scriptFile, $ps);

$b64 = trim(shell_exec(
    'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
    escapeshellarg($scriptFile) . ' 2>&1'
));
@unlink($scriptFile);

if (!$b64 || !preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $b64)) {
    echo 'Error: Screenshot failed. ' . htmlspecialchars(substr($b64, 0, 200));
    return;
}

// Strip any whitespace PowerShell may have added
$b64 = preg_replace('/\s+/', '', $b64);

$imageData = base64_decode($b64, true);
if ($imageData === false) {
    echo 'Error: base64 decode failed.';
    return;
}

// Store in images/ (same directory used by image_store)
$imagesDir = defined('BASE_DIR') ? BASE_DIR . '/images' : dirname(dirname(__DIR__)) . '/images';
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

$id       = bin2hex(random_bytes(16));
$filename = $id . '.png';
$path     = $imagesDir . '/' . $filename;

if (file_put_contents($path, $imageData) === false) {
    echo 'Error: Could not store screenshot.';
    return;
}

$host     = $_SERVER['HTTP_HOST'] ?? 'localhost:7001';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$url      = $protocol . '://' . $host . '/images/' . $filename;

echo json_encode([
    '_pd_image' => true,
    'url'       => $url,
    'mime'      => 'image/png',
    'base64'    => $b64,
], JSON_UNESCAPED_SLASHES);
