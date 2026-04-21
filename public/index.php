<?php
/**
 * OpsOne — Front Controller
 * All requests are routed through this file.
 */

// Built-in dev server: let real files in /public (CSS, JS, images) be served
// directly instead of hitting the router. Apache/Nginx in prod already do this.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

// Error reporting — always log, only display when APP_DEBUG=true (set below after .env loads)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('VIEWS_PATH', BASE_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

// Load configuration
require CONFIG_PATH . '/app.php';

// Load .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    loadEnv($envFile);
} else {
    if (file_exists(BASE_PATH . '/.env.example')) {
        copy(BASE_PATH . '/.env.example', $envFile);
        loadEnv($envFile);
    }
}

$__appDebug = config('app.debug');
if ($__appDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

// Global exception handler — details only in debug; generic page otherwise.
// Always logs to PHP error log, never leaks absolute paths to the browser in prod.
set_exception_handler(function($e) use ($__appDebug) {
    error_log('[OpsOne fatal] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
    if ($__appDebug) {
        echo "<div style='font-family:monospace; padding: 20px; background: #fff1f0; color: #a80000; border: 1px solid #ffa39e;'>";
        echo "<h2 style='margin-top:0;'>Fatal Application Error (debug)</h2>";
        echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
        echo "<strong>File:</strong> " . htmlspecialchars(basename($e->getFile())) . ":" . (int)$e->getLine() . "<br><br>";
        echo "<strong>Trace (top frames, relative paths only):</strong><br><pre>";
        foreach (array_slice($e->getTrace(), 0, 6) as $i => $f) {
            $file = isset($f['file']) ? str_replace(BASE_PATH . '/', '', $f['file']) : '(internal)';
            $line = $f['line'] ?? '?';
            $fn   = ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '');
            echo "#$i $file:$line  $fn()\n";
        }
        echo "</pre></div>";
    } else {
        if (is_file(VIEWS_PATH . '/errors/500.php')) {
            require VIEWS_PATH . '/errors/500.php';
        } else {
            echo "<h1>500</h1><p>Something went wrong. Please try again or contact support.</p>";
        }
    }
    exit;
});

// Load helpers
require APP_PATH . '/Helpers/functions.php';

// Load database config
require CONFIG_PATH . '/database.php';

// Security response headers — applied to every response (web + API).
// HSTS is only emitted on HTTPS to avoid poisoning HTTP-dev setups.
$__isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
          || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
if ($__isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=(), interest-cohort=()');
// X-Frame-Options, X-Content-Type-Options, X-XSS-Protection remain set by LiteSpeed/htaccess,
// but we also emit them here so local/dev gets the same posture.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Start session for web requests — with hardened cookie flags.
if (!str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
    $sessionSecure = env('SESSION_SECURE', 'false') === 'true';
    $sessionLifetime = max(0, (int) env('SESSION_LIFETIME', 120)) * 60;
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $sessionSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('OPSONE_SESSID');
    session_start();
}

// Load all app classes
$autoloadDirs = [
    APP_PATH . '/Controllers',
    APP_PATH . '/ApiControllers',
    APP_PATH . '/Models',
    APP_PATH . '/Middleware',
    APP_PATH . '/Services',
];

foreach ($autoloadDirs as $dir) {
    if (is_dir($dir)) {
        foreach (glob("$dir/*.php") as $file) {
            require_once $file;
        }
    }
}

// Load routes
$routes = require CONFIG_PATH . '/routes.php';

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Match route
$matchedRoute = null;
$params = [];

foreach ($routes as $pattern => $handler) {
    [$routeMethod, $routePath] = explode(' ', $pattern, 2);
    $routeMethod = trim($routeMethod);
    $routePath = trim($routePath);

    if ($routeMethod !== $method) continue;

    // Convert {id} placeholders to regex
    $regex = preg_replace('/\{(\w+)\}/', '(\d+)', $routePath);
    $regex = '#^' . $regex . '$#';

    if (preg_match($regex, $uri, $matches)) {
        $matchedRoute = $handler;
        array_shift($matches);
        $params = $matches;
        break;
    }
}

if (!$matchedRoute) {
    http_response_code(404);
    if (str_starts_with($uri, '/api/')) {
        jsonResponse(['error' => 'Endpoint not found'], 404);
    }
    require VIEWS_PATH . '/errors/404.php';
    exit;
}

[$controllerName, $action] = $matchedRoute;

// Determine route type
$isApi = str_starts_with($uri, '/api/');
$isPublic = $controllerName === 'PublicController';

// Apply middleware
if ($isApi) {
    // API routes: token auth (except login)
    if ($controllerName !== 'AuthApiController' || $action !== 'login') {
        $apiAuth = new ApiAuthMiddleware();
        $apiAuth->handle();
    }
} elseif ($isPublic) {
    // Public marketing routes: NO auth required
} elseif ($controllerName === 'PasswordResetController') {
    // Forgot/reset password flow — public by design, but still protected
    // by per-IP rate limit + single-use, time-limited tokens.
} else {
    // Web routes: session auth (except login page)
    if ($controllerName !== 'AuthController' && $controllerName !== 'TwoFactorController') {
        $webAuth = new WebAuthMiddleware();
        $webAuth->handle();

        // ── Platform separation guard ─────────────────────────────
        // Platform-only users must NOT access airline-operational controllers.
        // They should use /platform/* routes instead.
        $airlineOnlyControllers = [
            'RosterController',
            'FdmController',
            'NoticeController',
            'FileController',
            'ComplianceController',
            'UserController',    // airline /users — platform uses /platform/users
            // Phase 6 — Personnel Records (airline scope)
            'CrewDocumentController',
            'ChangeRequestController',
            'EligibilityController',
        ];
        if (in_array($controllerName, $airlineOnlyControllers, true) && isPlatformOnly()) {
            flash('error', 'That section is scoped to a specific airline. Use controlled access to enter an airline workspace.');
            redirect('/tenants');
        }
    }
}

// Resolve controller class name
$fullClassName = str_contains($controllerName, '\\')
    ? $controllerName
    : $controllerName;

// Instantiate and call
if (!class_exists($fullClassName)) {
    http_response_code(500);
    if ($isApi) {
        jsonResponse(['error' => 'Internal server error'], 500);
    }
    require VIEWS_PATH . '/errors/500.php';
    exit;
}

$controller = new $fullClassName();
if (!method_exists($controller, $action)) {
    http_response_code(500);
    if ($isApi) {
        jsonResponse(['error' => 'Internal server error'], 500);
    }
    require VIEWS_PATH . '/errors/500.php';
    exit;
}

// Call the action with route params
call_user_func_array([$controller, $action], $params);
