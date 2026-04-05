<?php
/**
 * Helper Functions
 */

/**
 * Redirect to URL
 */
function redirect(string $url): never {
    header("Location: $url");
    exit;
}

/**
 * Return JSON response
 */
function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get base URL
 */
function baseUrl(string $path = ''): string {
    $base = rtrim(config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/**
 * Escape HTML output
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get session flash message
 */
function flash(string $key, string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/**
 * Check if current user has role
 */
function hasRole(string $role): bool {
    $user = $_SESSION['user'] ?? null;
    if (!$user) return false;
    $roles = $_SESSION['user_roles'] ?? [];
    return in_array($role, $roles);
}

/**
 * Check if any of the given roles match
 */
function hasAnyRole(array $roles): bool {
    foreach ($roles as $role) {
        if (hasRole($role)) return true;
    }
    return false;
}

/**
 * Get current authenticated user
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Get current tenant ID
 */
function currentTenantId(): ?int {
    if (isSingleTenant()) {
        return getFixedTenantId();
    }
    return $_SESSION['tenant_id'] ?? null;
}

/**
 * Generate CSRF token
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field
 */
function csrfField(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrf(): bool {
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrfToken(), $token);
}

/**
 * Generate a random API token
 */
function generateApiToken(): string {
    return bin2hex(random_bytes(32));
}

/**
 * Format datetime for display
 */
function formatDate(?string $date, string $format = 'M j, Y'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

/**
 * Format datetime with time
 */
function formatDateTime(?string $date): string {
    if (!$date) return '—';
    return date('M j, Y H:i', strtotime($date));
}

/**
 * Render status badge HTML
 */
function statusBadge(string $status): string {
    $colors = [
        'active' => '#10b981',
        'approved' => '#10b981',
        'pending' => '#f59e0b',
        'suspended' => '#ef4444',
        'inactive' => '#6b7280',
        'rejected' => '#ef4444',
        'revoked' => '#dc2626',
        'published' => '#10b981',
        'draft' => '#6b7280',
    ];
    $color = $colors[strtolower($status)] ?? '#6b7280';
    $label = ucfirst($status);
    return "<span class=\"status-badge\" style=\"--badge-color: $color\">$label</span>";
}

/**
 * Log application event
 */
function appLog(string $message, string $level = 'info'): void {
    $logFile = __DIR__ . '/../../storage/logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] [$level] $message" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Get portal storage path
 */
function storagePath(string $path = ''): string {
    return dirname(__DIR__, 2) . '/storage/' . ltrim($path, '/');
}

/**
 * Sanitize filename
 */
function sanitizeFilename(string $filename): string {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return substr($filename, 0, 255);
}

/**
 * Database-agnostic current timestamp
 */
function dbNow(): string {
    $driver = env('DB_DRIVER', 'mysql');
    if ($driver === 'sqlite') {
        return "datetime('now')";
    }
    return 'NOW()';
}

/**
 * Database-agnostic date add
 */
function dbDateAdd(string $interval, int $amount): string {
    $driver = env('DB_DRIVER', 'mysql');
    if ($driver === 'sqlite') {
        return "datetime('now', '+$amount $interval')";
    }
    return "DATE_ADD(NOW(), INTERVAL $amount " . strtoupper($interval) . ")";
}

