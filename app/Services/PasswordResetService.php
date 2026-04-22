<?php
/**
 * PasswordResetService — issue + consume password-reset tokens.
 *
 * The raw token is sent to the user via email (or, in the current build, stored
 * in the audit log until a mail pipeline is wired). Only a SHA-256 hash of the
 * token is persisted, so a DB leak doesn't expose valid tokens.
 */
class PasswordResetService {
    private const TOKEN_LIFETIME_HOURS = 2;

    /**
     * Issue a reset token for the given email address.
     * Always returns an array with 'ok' => true to avoid leaking which emails exist;
     * callers should show the same success message regardless.
     * When a matching user is found, the raw token is returned in 'token_for_log'
     * so the caller can log it for the admin to retrieve manually.
     */
    public static function issue(string $email): array {
        $email = trim($email);
        if ($email === '') return ['ok' => false, 'reason' => 'missing_email'];
        $user = UserModel::findByEmail($email);
        if (!$user) {
            // Return ok anyway so an attacker can't enumerate emails.
            return ['ok' => true, 'token_for_log' => null, 'user_id' => null];
        }

        // Generate a 40-byte URL-safe token.
        $raw  = rtrim(strtr(base64_encode(random_bytes(40)), '+/', '-_'), '=');
        $hash = hash('sha256', $raw);

        // Expire any outstanding tokens for this user to prevent stacking.
        // Wrapped in try/catch so this gracefully no-ops until migration 035 is imported.
        try {
            Database::execute(
                "UPDATE password_reset_tokens SET used_at = " . dbNow() . "
                  WHERE user_id = ? AND used_at IS NULL",
                [(int)$user['id']]
            );

            $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_LIFETIME_HOURS * 3600);
            Database::insert(
                "INSERT INTO password_reset_tokens (user_id, token_hash, email, requested_ip, expires_at)
                 VALUES (?, ?, ?, ?, ?)",
                [(int)$user['id'], $hash, $email, $_SERVER['REMOTE_ADDR'] ?? '', $expiresAt]
            );
        } catch (\Throwable $e) {
            error_log('[OpsOne password-reset persist skipped] ' . $e->getMessage());
            return ['ok' => true, 'token_for_log' => null, 'user_id' => null];
        }
        $expiresAt = $expiresAt ?? date('Y-m-d H:i:s', time() + self::TOKEN_LIFETIME_HOURS * 3600);

        return [
            'ok'            => true,
            'user_id'       => (int)$user['id'],
            'token_for_log' => $raw,
            'expires_at'    => $expiresAt,
        ];
    }

    /**
     * Consume a token (by raw value). Returns the associated user row if valid,
     * or null if the token is invalid/expired/used.
     */
    public static function consume(string $rawToken): ?array {
        $hash = hash('sha256', trim($rawToken));
        try {
            $row  = Database::fetch(
                "SELECT * FROM password_reset_tokens
                  WHERE token_hash = ? AND used_at IS NULL
                  LIMIT 1",
                [$hash]
            );
        } catch (\Throwable $e) {
            error_log('[OpsOne password-reset consume skipped] ' . $e->getMessage());
            return null;
        }
        if (!$row) return null;
        if (strtotime($row['expires_at']) < time()) return null;

        // Mark used immediately — one-shot tokens.
        Database::execute(
            "UPDATE password_reset_tokens SET used_at = " . dbNow() . " WHERE id = ?",
            [(int)$row['id']]
        );
        $user = Database::fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [(int)$row['user_id']]);
        return $user ?: null;
    }

    /** Overwrite a user's password hash. */
    public static function setPassword(int $userId, string $newPassword): void {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        Database::execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$hash, $userId]
        );
    }

    /**
     * Simple password-strength gate: ≥ 10 chars, ≥ 1 letter, ≥ 1 digit.
     * Returns an error string, or null if acceptable.
     */
    public static function validatePassword(string $pw): ?string {
        if (strlen($pw) < 10)            return 'Password must be at least 10 characters.';
        if (!preg_match('/[A-Za-z]/',$pw)) return 'Password must contain at least one letter.';
        if (!preg_match('/\d/',$pw))      return 'Password must contain at least one digit.';
        if (strlen($pw) > 200)           return 'Password is too long.';
        return null;
    }
}
