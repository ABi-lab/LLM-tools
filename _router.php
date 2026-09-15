<?php
/**
 * HTTP router for PHP built-in server.
 * Started by index.php via: php -S localhost:7001 _router.php
 * Do not run this file directly.
 */

define('BASE_DIR',       dirname(__FILE__));
define('CONF_FILE',      BASE_DIR . '/tools.conf.json');
define('PERM_REQ_FILE',  BASE_DIR . '/_perm_request.json');
define('PERM_RESP_FILE', BASE_DIR . '/_perm_response.json');
define('STATE_FILE',     BASE_DIR . '/_state.json');
define('DISPATCHER_MODE', true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path   = rtrim($path, '/') ?: '/';

// CORS — send on every response so cross-origin callers (e.g. Prompt Designer
// at file://, :5174, :23080) can read the plain-text body.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Tool-Token');
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight — return immediately, no body.
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Explicit image-serving route — validates id format, prevents path traversal.
// Files are stored by image_store and persist until the user deletes them manually.
if (preg_match('#^/images/([a-f0-9]{32}\.[a-z]{2,5})$#', $path, $m)) {
    $imgFile = BASE_DIR . '/images/' . $m[1];
    if (!is_file($imgFile)) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Image not found';
    } else {
        $ext     = strtolower(pathinfo($m[1], PATHINFO_EXTENSION));
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',  'gif'  => 'image/gif', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($mimeMap[$ext] ?? 'application/octet-stream'));
        readfile($imgFile);
    }
    exit;
}

// Serve static files ourselves (not via return false) so CORS headers are included.
if (php_sapi_name() === 'cli-server') {
    $staticFile = BASE_DIR . $path;
    if (is_file($staticFile)) {
        $mime = mime_content_type($staticFile) ?: 'application/octet-stream';
        // Treat .md and .txt as plain text so the browser (and fetch) can read them.
        if (preg_match('/\.(md|txt|json|js|css|html?)$/i', $staticFile)) {
            $mime = 'text/plain; charset=utf-8';
        }
        header('Content-Type: ' . $mime);
        readfile($staticFile);
        exit;
    }
}

// Collect all params (POST overrides GET), then unescape JSON-style double
// backslashes so paths like K:\\Code\\Foo arrive as K:\Code\Foo.
$params = array_merge($_GET, $_POST);
$params = array_map(fn($v) => is_string($v) ? str_replace('\\\\', '\\', $v) : $v, $params);

// Route
if ($path === '' || $path === '/' || $path === '/index.php') {
    if (isset($params['tool'])) {
        respond(dispatchTool($params['tool'], $params));
    } else {
        respond(getToolsIndex());
    }
} elseif ($path === '/catalog.json') {
    getToolsCatalogJson();
} elseif (preg_match('#^/tools/([^/]+)(?:/index\.php)?$#', $path, $m)) {
    respond(dispatchTool($m[1], $params));
} else {
    http_response_code(404);
    respond("Not found: $path");
}

// ---------------------------------------------------------------------------

function respond(string $body): void {
    header('Content-Type: text/plain; charset=utf-8');
    echo $body;
}

// Returns the structured tool catalog as a bare JSON array.
// Response: [{name, description, parameters:{type,properties,required}}, ...]
// Compatible with Anthropic input_schema and OpenAI function.parameters shapes.
// (Prose instructions live at GET /INSTRUCTIONS.md — a separate request; not bundled here.)
function getToolsCatalogJson(): void {
    $tools = [];
    $files = glob(BASE_DIR . '/tools/*/INFO.md');
    if ($files) {
        sort($files);
        foreach ($files as $file) {
            if (str_contains($file, 'new_tool_template')) continue;
            $tool = parseToolInfo(file_get_contents($file));
            if ($tool !== null) $tools[] = $tool;
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Parse a single INFO.md into a structured tool entry.
function parseToolInfo(string $md): ?array {
    // Split into sections by ## headings
    $sections = [];
    $current  = null;
    $buf      = '';
    foreach (explode("\n", $md) as $line) {
        if (preg_match('/^## (.+)$/', $line, $m)) {
            if ($current !== null) $sections[$current] = trim($buf);
            $current = strtolower(trim($m[1]));
            $buf     = '';
        } elseif ($current !== null) {
            $buf .= $line . "\n";
        }
    }
    if ($current !== null) $sections[$current] = trim($buf);

    $key = trim($sections['key'] ?? '');
    if ($key === '') return null;

    $description = trim($sections['short description'] ?? $sections['long description'] ?? '');

    // Parse input parameters section. Expected line format (see
    // tools/new_tool_template/INFO.md and INSTRUCTIONS.md "Creating new tools"):
    //   - **name** (type, required|optional[, default: X]): description
    // type is one of string/number/boolean/array/object; the second token
    // must be the literal word "required" or "optional" (not both — that's
    // the old unedited template placeholder — and not neither).
    $paramText = $sections['input parameters'] ?? '';
    // Some INFO.md files use literal \n instead of real newlines
    $paramText = str_replace('\n', "\n", $paramText);

    $validTypes = ['string', 'number', 'boolean', 'array', 'object'];
    $lineRe     = '/^\*\*([A-Za-z0-9_]+)\*\*\s*\(\s*(' . implode('|', $validTypes) . ')\s*,\s*(required|optional)\s*(?:,\s*default:\s*([^)]*))?\)\s*:\s*(.*)$/i';

    $properties = [];
    $required   = [];

    foreach (explode("\n", $paramText) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] !== '-') continue;
        $line = trim(ltrim($line, '- '));

        if (!preg_match($lineRe, $line, $m)) {
            error_log("parseToolInfo: malformed 'Input parameters' line in tool '$key', excluding tool from catalog: $line");
            return null;
        }

        [, $paramName, $type, $reqOpt, $default, $desc] = $m;
        $type = strtolower($type);
        $desc = trim($desc);

        $prop = ['type' => $type, 'description' => $desc];
        if ($default !== '') {
            $default = trim($default);
            // Unwrap a quoted literal, e.g. default: '' or default: "x", to its raw value.
            if (preg_match('/^([\'"])(.*)\1$/', $default, $qm)) $default = $qm[2];
            $prop['default'] = match ($type) {
                'number'  => is_numeric($default) ? $default + 0 : $default,
                'boolean' => in_array(strtolower($default), ['true', '1'], true) ? true
                           : (in_array(strtolower($default), ['false', '0'], true) ? false : $default),
                default   => $default,
            };
        }

        $properties[$paramName] = $prop;
        if (strcasecmp($reqOpt, 'required') === 0) $required[] = $paramName;
    }

    return [
        'name'        => $key,
        'description' => $description,
        'parameters'  => [
            'type'       => 'object',
            'properties' => $properties ?: (object)[],
            'required'   => $required,
        ],
    ];
}

function getToolsIndex(): string {
    $baseDir = BASE_DIR;
    $parts   = [];
    $files   = glob($baseDir . '/tools/*/INFO.md');
    if ($files) {
        foreach ($files as $file) {
            if (str_contains($file, "new_tool_template"))
                continue;
            $content = file_get_contents($file);
            // Skip tools whose INFO.md fails parseToolInfo() (malformed
            // Input parameters section) — parseToolInfo() already logs why.
            // A model reading raw prose here would otherwise be misled by
            // the same malformed line that broke the JSON schema.
            if (parseToolInfo($content) === null) continue;
            $parts[] = $content;
        }
    }
    return $parts ? implode("\n\n---\n\n", $parts) : "No tools available.";
}

function dispatchTool(string $tool, array $params): string {
    $toolScript = BASE_DIR . "/tools/$tool/index.php";
    if (!file_exists($toolScript)) {
        return "Error: Tool '$tool' not found.";
    }

    $shortDesc     = $params['short_description'] ?? '';
    $justification = $params['justification']     ?? '';

    if (!checkPermission($tool, $params, $shortDesc, $justification)) {
        logToolCall($tool, $params, $justification, "DENIED by user.");
        return "Tool execution denied by user.";
    }

    $output = runTool($toolScript, $params);
    logToolCall($tool, $params, $justification, $output);
    return $output;
}

function checkPermission(string $tool, array $params, string $shortDesc, string $justification): bool {
    $permissions = loadPermissions();

    if (in_array($tool, $permissions['never'] ?? [], true)) {
        return false;
    }

    $state  = loadState();
    $autoOn = $state['auto_on'] ?? false;

    if ($autoOn || in_array($tool, $permissions['always'] ?? [], true)) {
        return true;
    }

    // Trusted-caller bypass: when tools.conf.json contains a non-empty
    // "browser_token", a caller that supplies a matching token (via
    // X-Tool-Token header or _token query/body param) is treated as
    // pre-approved and skips the console round-trip.  The call is still
    // logged.  The console gate stays the default for all other callers.
    $configuredToken = $permissions['browser_token'] ?? '';
    if ($configuredToken !== '') {
        $suppliedToken = $_SERVER['HTTP_X_TOOL_TOKEN']
            ?? $params['_token']
            ?? '';
        if ($suppliedToken === $configuredToken) {
            return true;
        }
    }

    // Request permission from the CLI parent process via file IPC
    // Write request
    file_put_contents(PERM_REQ_FILE, json_encode([
        'status'            => 'pending',
        'tool'              => $tool,
        'short_description' => $shortDesc,
        'justification'     => $justification,
        'params'            => $params,
    ]));
    @unlink(PERM_RESP_FILE);

    // Wait for response (up to 5 minutes — user may need time to decide)
    $deadline = time() + 300;
    while (time() < $deadline) {
        if (file_exists(PERM_RESP_FILE)) {
            $resp = json_decode(file_get_contents(PERM_RESP_FILE), true);
            @unlink(PERM_RESP_FILE);
            return ($resp['decision'] ?? 'denied') === 'granted';
        }
        usleep(100000); // 100ms
    }

    // Timed out — deny
    @unlink(PERM_REQ_FILE);
    return false;
}

function runTool(string $toolScript, array $params): string {
    $_GET     = $params;
    $_POST    = $params;
    $_REQUEST = $params;

    ob_start();
    try {
        include $toolScript;
    } catch (Throwable $e) {
        ob_end_clean();
        return "Tool error: " . $e->getMessage();
    }
    $out = ob_get_clean();
    return $out === false ? '' : $out;
}

function logToolCall(string $tool, array $params, string $justification, string $output): void {
    $timestamp = date('Ymd-His');
    $logFile   = BASE_DIR . "/Tool-call-$timestamp.md";

    $skip       = ['short_description', 'justification', 'tool'];
    $paramLines = '';
    foreach ($params as $k => $v) {
        if (in_array($k, $skip, true)) continue;
        $paramLines .= "  $k: $v\n";
    }

    $content  = "Incoming tool call request for: $tool\n";
    $content .= $paramLines . "\n";
    $content .= "Justification: $justification\n\n";
    $content .= "Tool call response:\n\n$output\n";

    file_put_contents($logFile, $content);
}

function loadPermissions(): array {
    if (file_exists(CONF_FILE)) {
        return json_decode(file_get_contents(CONF_FILE), true) ?? ['always' => [], 'never' => []];
    }
    return ['always' => [], 'never' => []];
}

function loadState(): array {
    if (file_exists(STATE_FILE)) {
        return json_decode(file_get_contents(STATE_FILE), true) ?? [];
    }
    return [];
}
