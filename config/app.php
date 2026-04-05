<?php
/**
 * Application Configuration Loader
 * Loads .env and provides config access
 */

// Load .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function config(string $key, mixed $default = null): mixed {
    static $config = null;
    if ($config === null) {
        $config = [
            'app' => [
                'name' => env('APP_NAME', 'OpsOne'),
                'env' => env('APP_ENV', 'production'),
                'debug' => env('APP_DEBUG', 'false') === 'true',
                'url' => env('APP_URL', 'http://localhost:8080'),
                'key' => env('APP_KEY', ''),
                'mode' => env('APP_MODE', 'multi_tenant'),
                'fixed_tenant_id' => env('FIXED_TENANT_ID', ''),
            ],
            'session' => [
                'lifetime' => (int) env('SESSION_LIFETIME', 120),
                'secure' => env('SESSION_SECURE', 'false') === 'true',
            ],
            'api' => [
                'token_expiry_hours' => (int) env('API_TOKEN_EXPIRY_HOURS', 168),
                'rate_limit' => (int) env('API_RATE_LIMIT', 60),
            ],
            'upload' => [
                'max_size' => (int) env('UPLOAD_MAX_SIZE', 52428800),
                'allowed_types' => explode(',', env('UPLOAD_ALLOWED_TYPES', 'pdf,doc,docx')),
            ],
        ];
    }

    $keys = explode('.', $key);
    $value = $config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

function isMultiTenant(): bool {
    return config('app.mode') === 'multi_tenant';
}

function isSingleTenant(): bool {
    return config('app.mode') === 'single_tenant';
}

function getFixedTenantId(): ?int {
    $id = config('app.fixed_tenant_id');
    return $id ? (int) $id : null;
}
