<?php
/**
 * audit_route_guards — surface routes whose controller method does not
 * call any explicit auth/RBAC guard.
 *
 * Heuristic, not a substitute for code review. Prints two columns:
 *   GAP  GET /tenants/{id}        TenantController@show
 *   OK   POST /platform/onboarding/store  OnboardingController@store
 *
 * False-positive cases (acceptable):
 *   - Public routes (whitelisted below).
 *   - Routes whose guard is in a parent constructor / trait / before-filter
 *     (this codebase doesn't currently use those, but if added later, extend
 *     the heuristic).
 *
 * Usage:
 *   php bin/audit_route_guards.php
 *   php bin/audit_route_guards.php --gaps-only
 */

define('BASE_PATH', dirname(__DIR__));

$routes = require BASE_PATH . '/config/routes.php';
if (!is_array($routes)) {
    fwrite(STDERR, "ERROR: config/routes.php did not return an array.\n");
    exit(1);
}

// Routes that legitimately do NOT need a guard.
$publicPathPatterns = [
    '#^GET /$#',
    '#^GET /home$#',
    '#^GET /(features|how-it-works|install-info|support|contact|faq|about|privacy|terms)$#',
    '#^POST /contact$#',
    '#^GET /login$#',
    '#^POST /login$#',
    '#^GET /logout$#',
    '#^GET /activate$#',
    '#^POST /activate$#',
    '#^GET /forgot-password$#',
    '#^POST /forgot-password$#',
    '#^GET /reset-password$#',
    '#^POST /reset-password$#',
    '#^GET /2fa/challenge$#',
    '#^POST /2fa/challenge$#',
    // API auth + install endpoints (guarded by ApiAuthMiddleware separately)
    '#^POST /api/auth/login$#',
    '#^GET /api/install/manifest#',
    '#^GET /api/install/health$#',
];

$guardPattern = '/'
    . '(\\brequireAuth\\s*\\()'                                  // requireAuth()
    . '|(\\bRbacMiddleware::(requireRole|requirePlatformRole|requirePlatformSuperAdmin|requireAirlineRole|requireModuleCapability|requireTenantScope|apiRequireRole)\\s*\\()' // RbacMiddleware::*
    . "|(empty\\s*\\(\\s*\\\$_SESSION\\s*\\[\\s*['\"]user['\"]\\s*\\]\\s*\\))" // empty($_SESSION['user'])
    . "|(\\\$_SESSION\\s*\\[\\s*['\"](pending_2fa_user_id|user|api_user)['\"]\\s*\\])" // direct session checks
    . '|(\\bapiUser\\s*\\(\\))'                                  // API uses apiUser() helper
    . '|(\\bapiTenantId\\s*\\(\\))'                              // ditto
    . '|(\\$this->require[A-Z]\\w*\\s*\\()'                     // delegated guard helpers e.g. $this->requireHr()
    . '/';

$gapsOnly = in_array('--gaps-only', $argv ?? [], true);
$gaps = 0;
$ok   = 0;
$skip = 0;

foreach ($routes as $routeKey => $handler) {
    if (!is_array($handler) || count($handler) !== 2) continue;
    [$controller, $method] = $handler;

    // Whitelist?
    $isPublic = false;
    foreach ($publicPathPatterns as $rx) {
        if (preg_match($rx, $routeKey)) { $isPublic = true; break; }
    }
    if ($isPublic) {
        $skip++;
        if (!$gapsOnly) printf("PUBLIC %-50s %s@%s\n", $routeKey, $controller, $method);
        continue;
    }

    // Locate the controller file.
    $candidatePaths = [
        BASE_PATH . "/app/Controllers/{$controller}.php",
        BASE_PATH . "/app/ApiControllers/{$controller}.php",
    ];
    $file = null;
    foreach ($candidatePaths as $p) {
        if (is_file($p)) { $file = $p; break; }
    }
    if ($file === null) {
        printf("MISSING %-50s %s@%s (controller file not found)\n", $routeKey, $controller, $method);
        $gaps++;
        continue;
    }

    $src = file_get_contents($file);

    // Helper: extract a single PHP method body by name. Returns '' if not found.
    $extractBody = static function(string $src, string $methodName): string {
        if (!preg_match(
            '/function\\s+' . preg_quote($methodName, '/') . '\\s*\\(.*?\\)\\s*(?::\\s*\\??[\\\\a-zA-Z_]+\\s*)?\\{/s',
            $src,
            $m,
            PREG_OFFSET_CAPTURE
        )) return '';
        $start = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        $i = $start;
        $len = strlen($src);
        while ($i < $len && $depth > 0) {
            $c = $src[$i];
            if ($c === '{') $depth++;
            elseif ($c === '}') $depth--;
            $i++;
        }
        return substr($src, $start, max(0, $i - $start - 1));
    };

    $methodBody      = $extractBody($src, $method);
    $constructorBody = $extractBody($src, '__construct');

    if ($methodBody === '' && !str_contains($src, "function {$method}")) {
        printf("MISSING %-50s %s@%s (method not found in %s)\n", $routeKey, $controller, $method, basename($file));
        $gaps++;
        continue;
    }

    $hasMethodGuard      = preg_match($guardPattern, $methodBody) === 1;
    $hasConstructorGuard = preg_match($guardPattern, $constructorBody) === 1;

    if ($hasMethodGuard || $hasConstructorGuard) {
        $ok++;
        $tag = $hasConstructorGuard && !$hasMethodGuard ? 'OK-CTOR' : 'OK';
        if (!$gapsOnly) printf("%-7s %-50s %s@%s\n", $tag, $routeKey, $controller, $method);
    } else {
        $gaps++;
        printf("GAP    %-50s %s@%s\n", $routeKey, $controller, $method);
    }
}

printf("\nSummary: %d guarded, %d gap, %d public/whitelisted.\n", $ok, $gaps, $skip);
exit($gaps > 0 ? 2 : 0);
