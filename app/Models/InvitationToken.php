<?php
/**
 * InvitationToken Model — admin invitation workflow (no plain-text passwords)
 */
class InvitationToken {

    public static function create(int $tenantId, string $email, string $name, string $roleSlug, ?int $createdBy = null): string {
        $token = bin2hex(random_bytes(32));
        Database::insert(
            "INSERT INTO invitation_tokens (tenant_id, email, name, role_slug, token, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 72 HOUR), ?)",
            [$tenantId, $email, $name, $roleSlug, $token, $createdBy]
        );
        return $token;
    }

    public static function findByToken(string $token): ?array {
        return Database::fetch(
            "SELECT it.*, t.name as tenant_name
             FROM invitation_tokens it
             JOIN tenants t ON t.id = it.tenant_id
             WHERE it.token = ? AND it.accepted_at IS NULL AND it.expires_at > NOW()",
            [$token]
        );
    }

    public static function accept(string $token): void {
        Database::execute(
            "UPDATE invitation_tokens SET accepted_at = NOW() WHERE token = ?",
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
        return Database::fetchAll(
            "SELECT * FROM invitation_tokens
             WHERE tenant_id = ? AND accepted_at IS NULL AND expires_at > NOW()
             ORDER BY created_at DESC",
            [$tenantId]
        );
    }

    public static function isExpired(array $token): bool {
        return strtotime($token['expires_at']) < time();
    }
}
