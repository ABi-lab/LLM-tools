<?php
/**
 * desktop_control — Move mouse, click, type, send keys, scroll via PowerShell Win32 APIs.
 *
 * Params: action, x, y, text, amount
 */

$action = trim($_REQUEST['action'] ?? '');
$x      = isset($_REQUEST['x']) && $_REQUEST['x'] !== '' ? (int)$_REQUEST['x'] : null;
$y      = isset($_REQUEST['y']) && $_REQUEST['y'] !== '' ? (int)$_REQUEST['y'] : null;
$text   = $_REQUEST['text']   ?? '';
$amount = isset($_REQUEST['amount']) && $_REQUEST['amount'] !== '' ? (int)$_REQUEST['amount'] : 3;

$validActions = ['move', 'click', 'double_click', 'right_click', 'type', 'key', 'scroll'];
if (!in_array($action, $validActions, true)) {
    echo 'Error: Unknown action "' . htmlspecialchars($action) . '". Valid: ' . implode(', ', $validActions);
    return;
}

if (in_array($action, ['move', 'scroll'], true) && ($x === null || $y === null)) {
    echo 'Error: x and y are required for action=' . $action;
    return;
}

if (in_array($action, ['type', 'key'], true) && $text === '') {
    echo 'Error: text is required for action=' . $action;
    return;
}

/* For action=type, escape SendKeys special chars so text is typed literally.
   For action=key, pass through raw (caller uses SendKeys notation). */
function escapeSendKeys(string $s): string {
    return preg_replace('/([+^%~()[\]{}])/', '{$1}', $s);
}
$sendKeysStr = ($action === 'type') ? escapeSendKeys($text) : $text;
$sendKeysB64 = base64_encode($sendKeysStr);  // avoid PowerShell escaping hell

$hasXY  = ($x !== null && $y !== null) ? 'true' : 'false';
$xVal   = (int)($x ?? 0);
$yVal   = (int)($y ?? 0);

/* Build PS script using nowdoc (no PHP interpolation), then substitute placeholders. */
$ps = <<<'PSEOF'
Add-Type -Assembly System.Windows.Forms,System.Drawing
Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
public class PdCtrl {
    [DllImport("user32.dll")] public static extern bool SetCursorPos(int x, int y);
    [DllImport("user32.dll")] public static extern void mouse_event(uint f, int dx, int dy, int data, IntPtr extra);
}
'@

function Move-Cursor($x, $y) { [void][PdCtrl]::SetCursorPos($x, $y); Start-Sleep -Milliseconds 40 }
function LDown() { [PdCtrl]::mouse_event(0x0002, 0, 0, 0, [IntPtr]::Zero) }
function LUp()   { [PdCtrl]::mouse_event(0x0004, 0, 0, 0, [IntPtr]::Zero) }
function RDown() { [PdCtrl]::mouse_event(0x0008, 0, 0, 0, [IntPtr]::Zero) }
function RUp()   { [PdCtrl]::mouse_event(0x0010, 0, 0, 0, [IntPtr]::Zero) }
function LClick() { LDown; Start-Sleep -Milliseconds 60; LUp }

$action = '__ACTION__'
$hasXY  = $__HASXY__
$x      = __X__
$y      = __Y__
$amount = __AMOUNT__
$skText = [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String('__TEXTB64__'))

switch ($action) {
    'move' {
        Move-Cursor $x $y
        "Moved mouse to ($x, $y)"
    }
    'click' {
        if ($hasXY) { Move-Cursor $x $y }
        LClick
        if ($hasXY) { "Clicked at ($x, $y)" } else { "Clicked at current position" }
    }
    'double_click' {
        if ($hasXY) { Move-Cursor $x $y }
        LClick; Start-Sleep -Milliseconds 100; LClick
        if ($hasXY) { "Double-clicked at ($x, $y)" } else { "Double-clicked at current position" }
    }
    'right_click' {
        if ($hasXY) { Move-Cursor $x $y }
        RDown; Start-Sleep -Milliseconds 60; RUp
        if ($hasXY) { "Right-clicked at ($x, $y)" } else { "Right-clicked at current position" }
    }
    'type' {
        [System.Windows.Forms.SendKeys]::SendWait($skText)
        "Typed $($skText.Length) characters"
    }
    'key' {
        [System.Windows.Forms.SendKeys]::SendWait($skText)
        "Sent key: $skText"
    }
    'scroll' {
        Move-Cursor $x $y
        $delta = $amount * 120
        [PdCtrl]::mouse_event(0x0800, 0, 0, $delta, [IntPtr]::Zero)
        $dir = if ($amount -gt 0) { 'up' } else { 'down' }
        "Scrolled $([Math]::Abs($amount)) notch(es) $dir at ($x, $y)"
    }
    default { "Error: Unknown action '$action'" }
}
PSEOF;

$ps = str_replace(
    ['__ACTION__', '__HASXY__', '__X__', '__Y__', '__AMOUNT__', '__TEXTB64__'],
    [$action,      $hasXY,      $xVal,   $yVal,   $amount,      $sendKeysB64],
    $ps
);

$scriptFile = tempnam(sys_get_temp_dir(), 'pd_ctrl_') . '.ps1';
file_put_contents($scriptFile, $ps);

$out = trim(shell_exec(
    'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' .
    escapeshellarg($scriptFile) . ' 2>&1'
));
@unlink($scriptFile);

echo $out ?: 'Done.';
