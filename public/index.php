<?php
/**
 * OpsOne — Front Controller
 * All requests are routed through this file.
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Extreme debug catch-all for Namecheap suppressed errors
set_exception_handler(function($e) {
    http_response_code(500);
    echo "<div style='font-family:monospace; padding: 20px; background: #fff1f0; color: #a80000; border: 1px solid #ffa39e;'>";
    echo "<h2 style='margin-top:0;'>Fatal Application Error</h2>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br><br>";
    echo "<strong>Trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    exit;
});

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

// Set error display based on config
if (config('app.debug')) {
    ini_set('display_errors', '1');
}

// Load helpers
require APP_PATH . '/Helpers/functions.php';

// Load database config
require CONFIG_PATH . '/database.php';

// Start session for web requests
if (!str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
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
$isInstall = $controllerName === 'InstallController';

// Apply middleware
if ($isApi) {
    // API routes: token auth (except login)
    if ($controllerName !== 'AuthApiController' || $action !== 'login') {
        $apiAuth = new ApiAuthMiddleware();
        $apiAuth->handle();
    }
} elseif ($isPublic || $isInstall) {
    // Public routes: NO auth required
} else {
    // Web routes: session auth (except login page)
    if ($controllerName !== 'AuthController') {
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
