<?php
/**
 * TotpService — RFC 6238 Time-based One-Time Password (TOTP), no external deps.
 *
 * - Shared secret is 20 bytes (160 bits), stored as base32 text.
 * - 30-second time step. SHA-1 HMAC. 6-digit codes.
 * - Verify accepts a ±1 window to absorb small clock skew.
 *
 * Usage:
 *   $secret = TotpService::generateSecret();
 *   TotpService::uri('OpsOne', $user['email'], $secret) → otpauth:// URL for QR
 *   TotpService::verify($secret, $code) → bool
 */
class TotpService {
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const ALGO   = 'sha1';
    private const WINDOW = 1; // codes valid ±1 period

    public static function generateSecret(): string {
        $raw = random_bytes(20);
        return self::base32Encode($raw);
    }

    public static function uri(string $issuer, string $accountName, string $secret): string {
        $label = rawurlencode($issuer) . ':' . rawurlencode($accountName);
        return 'otpauth://totp/' . $label
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    public static function currentCode(string $secret, ?int $timestamp = null): string {
        return self::codeAt($secret, (int) floor(($timestamp ?? time()) / self::PERIOD));
    }

    public static function verify(string $secret, string $code, ?int $timestamp = null): bool {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $t = (int) floor(($timestamp ?? time()) / self::PERIOD);
        for ($off = -self::WINDOW; $off <= self::WINDOW; $off++) {
            if (hash_equals(self::codeAt($secret, $t + $off), $code)) return true;
        }
        return false;
    }

    // ─── Internals ─────────────────────────────────────────────

    private static function codeAt(string $secret, int $counter): string {
        $key = self::base32Decode($secret);
        // Counter must be 8-byte big-endian.
        $binCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac(self::ALGO, $binCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $slice  = substr($hash, $offset, 4);
        $value  = unpack('N', $slice)[1] & 0x7FFFFFFF;
        return str_pad((string)($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bytes): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $padLen = (5 - strlen($bits) % 5) % 5;
        $bits  .= str_repeat('0', $padLen);
        $out = '';
        for ($i = 0; $i < strlen($bits); $i += 5) {
            $out .= $alphabet[bindec(substr($bits, $i, 5))];
        }
        return $out;
    }

    private static function base32Decode(string $b32): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(trim($b32));
        $b32 = rtrim($b32, '=');
        $bits = '';
        for ($i = 0; $i < strlen($b32); $i++) {
            $idx = strpos($alphabet, $b32[$i]);
            if ($idx === false) continue;
            $bits .= str_pad(decbin($idx), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $out .= chr(bindec(substr($bits, $i, 8)));
        }
        return $out;
    }
}
