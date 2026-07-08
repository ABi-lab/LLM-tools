<?php
/**
 * LLM Tool Service launcher.
 * Usage: php index.php [auto-on]
 * Starts PHP built-in server at http://localhost:7001/
 */

if (php_sapi_name() !== 'cli') {
    echo "Run from CLI: php index.php [auto-on]";
    exit(1);
}

define('BASE_DIR', __DIR__);
define('CONF_FILE', BASE_DIR . '/tools.conf.json');
define('PERM_REQ_FILE',  BASE_DIR . '/_perm_request.json');
define('PERM_RESP_FILE', BASE_DIR . '/_perm_response.json');

$args   = array_slice($argv, 1);
$autoOn = in_array('auto-on', $args);

// Write initial state for the router to read
file_put_contents(BASE_DIR . '/_state.json', json_encode(['auto_on' => $autoOn]));

// Clean up any stale permission signal files
@unlink(PERM_REQ_FILE);
@unlink(PERM_RESP_FILE);

echo "LLM Tool Service\n";
echo "Listening on http://0.0.0.0:7001/ (accessible from the network)\n";
echo ($autoOn
    ? "Mode: auto-approve ON  (type 'auto off' to require permissions)\n"
    : "Mode: permission prompts ON  (type 'auto on' to auto-approve)\n");
echo "Press Ctrl+C to stop.\n\n";

// Spawn the built-in PHP server
$php    = PHP_BINARY;
$router = __DIR__ . DIRECTORY_SEPARATOR . '_router.php';
$proc   = proc_open(
    "\"$php\" -S 0.0.0.0:7001 \"$router\"",
    [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR],
    $pipes
);
if (!is_resource($proc)) {
    fwrite(STDERR, "Failed to start server.\n");
    exit(1);
}
fclose($pipes[0]);

while (true) {
    // Check if server is still running
    $status = proc_get_status($proc);
    if (!$status['running']) break;

    // Handle permission prompts from router (file-based IPC)
    if (file_exists(PERM_REQ_FILE) && !file_exists(PERM_RESP_FILE)) {
        $req = json_decode(file_get_contents(PERM_REQ_FILE), true);
        if ($req && ($req['status'] ?? '') === 'pending') {
            handlePermissionPrompt($req, $autoOn);
        }
    }

    usleep(50000); // 50ms poll
}

proc_close($proc);

// ---------------------------------------------------------------------------

function handlePermissionPrompt(array $req, bool &$autoOn): void {
    $tool          = $req['tool']          ?? '?';
    $shortDesc     = $req['short_description'] ?? '';
    $justification = $req['justification'] ?? '';
    $params        = $req['params']        ?? [];

    $permissions = loadPermissions();

    // Already decided
    if (in_array($tool, $permissions['never'] ?? [], true)) {
        writePermResponse('denied', $autoOn, $permissions);
        return;
    }
    if ($autoOn || in_array($tool, $permissions['always'] ?? [], true)) {
        $label = $autoOn ? 'AUTO' : 'ALWAYS';
        echo "\n[$label] $tool" . ($shortDesc ? " — $shortDesc" : '') . "\n";
        writePermResponse('granted', $autoOn, $permissions);
        return;
    }

    // Prompt user
    $skip = ['short_description', 'justification', 'tool'];
    $paramLines = '';
    foreach ($params as $k => $v) {
        if (in_array($k, $skip, true)) continue;
        $display = strlen((string)$v) > 100 ? substr($v, 0, 97) . '...' : $v;
        $paramLines .= "  $k: $display\n";
    }

    echo "\n";
    echo "Incoming tool call request for: $tool\n";
    if ($shortDesc) echo "  $shortDesc\n";
    echo $paramLines;
    echo "Justification: $justification\n";
    echo "Allow? [y]es  [a]lways (session)  always for this [t]ool  [n]o  n[e]ver (session)  never for this t[o]ol: ";
    fflush(STDOUT);

    $answer = strtolower(trim(fgets(STDIN)));

    $decision = 'denied';
    switch ($answer) {
        case 'y':
            $decision = 'granted';
            break;
        case 'a':
            $autoOn   = true;
            $decision = 'granted';
            file_put_contents(BASE_DIR . '/_state.json', json_encode(['auto_on' => true]));
            echo "Auto-approve ON for this session.\n";
            break;
        case 't':
            $permissions['always'][] = $tool;
            savePermissions($permissions);
            $decision = 'granted';
            echo "Tool '$tool' added to always-allow list.\n";
            break;
        case 'n':
            $decision = 'denied';
            break;
        case 'e':
            $autoOn   = false;
            $decision = 'denied';
            file_put_contents(BASE_DIR . '/_state.json', json_encode(['auto_on' => false]));
            echo "Auto-approve OFF.\n";
            break;
        case 'o':
            $permissions['never'][] = $tool;
            savePermissions($permissions);
            $decision = 'denied';
            echo "Tool '$tool' added to never-allow list.\n";
            break;
    }

    writePermResponse($decision, $autoOn, $permissions);
}

function writePermResponse(string $decision, bool $autoOn, array $permissions): void {
    file_put_contents(PERM_RESP_FILE, json_encode([
        'decision'    => $decision,
        'auto_on'     => $autoOn,
        'permissions' => $permissions,
    ]));
    @unlink(PERM_REQ_FILE);
}

function loadPermissions(): array {
    if (file_exists(CONF_FILE)) {
        return json_decode(file_get_contents(CONF_FILE), true) ?? ['always' => [], 'never' => []];
    }
    return ['always' => [], 'never' => []];
}

function savePermissions(array $permissions): void {
    file_put_contents(CONF_FILE, json_encode($permissions, JSON_PRETTY_PRINT));
}
