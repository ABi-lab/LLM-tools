<?php
/**
 * execute — Run a shell command and return its output.
 * Can be run directly: php tools/execute/index.php command=dir working_directory=C:\
 */

if (php_sapi_name() === 'cli' && !defined('DISPATCHER_MODE')) {
    parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
}

$command    = $_REQUEST['command']            ?? null;
$workingDir = $_REQUEST['working_directory']  ?? $_REQUEST['working_dir'] ?? null;

if ($command === null) {
    showForm();
    exit;
}

// Validate working directory if provided
if ($workingDir !== null && !is_dir($workingDir)) {
    echo "Error: working_directory does not exist: $workingDir";
    return;
}

$descriptors = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptors, $pipes, $workingDir ?: null);
if (!is_resource($process)) {
    echo "Error: Failed to start process.";
    return;
}

fclose($pipes[0]);
$stdout   = stream_get_contents($pipes[1]);
$stderr   = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

$output = $stdout;
if ($stderr !== '') {
    $output .= ($output !== '' ? "\n" : '') . "STDERR:\n$stderr";
}
if ($exitCode !== 0) {
    $output .= "\n[Exit code: $exitCode]";
}

echo $output !== '' ? $output : "[Command produced no output. Exit code: $exitCode]";

// ---------------------------------------------------------------------------

function showForm(): void {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
    <!DOCTYPE html><html><head><title>execute</title></head><body>
    <h2>Execute shell command</h2>
    <form method="post" action="">
      <label>Command: <input name="command" size="80" required></label><br><br>
      <label>Working directory (optional): <input name="working_directory" size="60"></label><br><br>
      <button type="submit">Execute</button>
    </form>
    </body></html>
    HTML;
}
