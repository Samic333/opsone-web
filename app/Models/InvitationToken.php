<?php
/**
 * InvitationToken Model — admin invitation workflow (no plain-text passwords)
 */
class InvitationToken {

    private static function isSqlite(): bool {
        return env('DB_DRIVER', 'mysql') === 'sqlite';
    }

    private static function nowSql(): string {
        return self::isSqlite() ? "datetime('now')" : 'NOW()';
    }

    private static function expiresAtSql(): string {
        return self::isSqlite()
            ? "datetime('now', '+72 hours')"
            : 'DATE_ADD(NOW(), INTERVAL 72 HOUR)';
    }

    public static function create(int $tenantId, string $email, string $name, string $roleSlug, ?int $createdBy = null): string {
        $token     = bin2hex(random_bytes(32));
        $expiresAt = self::expiresAtSql();
        Database::insert(
            "INSERT INTO invitation_tokens (tenant_id, email, name, role_slug, token, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?, $expiresAt, ?)",
            [$tenantId, $email, $name, $roleSlug, $token, $createdBy]
        );
        return $token;
    }

    public static function findByToken(string $token): ?array {
        $now = self::nowSql();
        return Database::fetch(
            "SELECT it.*, t.name as tenant_name
             FROM invitation_tokens it
             JOIN tenants t ON t.id = it.tenant_id
             WHERE it.token = ? AND it.accepted_at IS NULL AND it.expires_at > $now",
            [$token]
        );
    }

    public static function accept(string $token): void {
        $now = self::nowSql();
        Database::execute(
            "UPDATE invitation_tokens SET accepted_at = $now WHERE token = ?",
            [$token]
        );
    }

    public static function forTenant(int $tenantId): array {
        return Database::fetchAll(
            "SELECT * FROM invitation_tokens WHERE tenant_id = ? ORDER BY created_at DESC",
            [$tenantId]
        );
    }

    public static function pendingForTenant(int $tenantId): array {
        $now = self::nowSql();
        return Database::fetchAll(
            "SELECT * FROM invitation_tokens
             WHERE tenant_id = ? AND accepted_at IS NULL AND expires_at > $now
             ORDER BY created_at DESC",
            [$tenantId]
        );
    }

    public static function isExpired(array $token): bool {
        return strtotime($token['expires_at']) < time();
    }
}
