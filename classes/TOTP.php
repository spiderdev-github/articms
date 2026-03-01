<?php
/**
 * Pure-PHP TOTP / RFC 6238 implementation (no external dependencies).
 * Compatible: Google Authenticator, Authy, Microsoft Authenticator, etc.
 */
class TOTP
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const STEP     = 30;  // seconds per window
    private const DIGITS   = 6;

    // ── Secret generation ─────────────────────────────────────────────────

    /**
     * Generate a random Base32 secret (80 bits = 16 chars, which is the
     * most widely compatible length).
     */
    public static function generateSecret(int $chars = 16): string
    {
        $bytes = random_bytes((int) ceil($chars * 5 / 8));
        $bits  = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= sprintf('%08b', ord($byte));
        }
        $secret = '';
        for ($i = 0; $i + 5 <= strlen($bits) && strlen($secret) < $chars; $i += 5) {
            $secret .= self::ALPHABET[bindec(substr($bits, $i, 5))];
        }
        return $secret;
    }

    // ── Base32 decode ─────────────────────────────────────────────────────

    public static function base32Decode(string $input): string
    {
        $input = strtoupper(str_replace(' ', '', $input));
        $map   = array_flip(str_split(self::ALPHABET));
        $bits  = '';
        foreach (str_split($input) as $char) {
            if ($char === '=') break;
            if (!isset($map[$char])) continue;
            $bits .= sprintf('%05b', $map[$char]);
        }
        $bytes = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $bytes .= chr(bindec(substr($bits, $i, 8)));
        }
        return $bytes;
    }

    // ── HOTP (counter-based) ──────────────────────────────────────────────

    public static function hotp(string $secret, int $counter): string
    {
        $key  = self::base32Decode($secret);
        $msg  = pack('J', $counter);                          // 64-bit big-endian
        $hash = hash_hmac('sha1', $msg, $key, true);
        $off  = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$off])     & 0x7F) << 24) |
            ((ord($hash[$off + 1]) & 0xFF) << 16) |
            ((ord($hash[$off + 2]) & 0xFF) << 8)  |
            ( ord($hash[$off + 3]) & 0xFF)
        ) % 10 ** self::DIGITS;
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    // ── TOTP (time-based) ─────────────────────────────────────────────────

    public static function totp(string $secret, ?int $timestamp = null): string
    {
        return self::hotp($secret, intdiv($timestamp ?? time(), self::STEP));
    }

    /**
     * Verify a user-supplied code.
     * $window=1 means ±1 step (±30 s) tolerance for clock drift.
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);
        if (!preg_match('/^\d{6}$/', $code)) return false;
        $t = intdiv(time(), self::STEP);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($secret, $t + $i), $code)) return true;
        }
        return false;
    }

    // ── QR Code URL ───────────────────────────────────────────────────────

    /**
     * Returns a Google Charts API URL for an otpauth:// QR code.
     * Use with <img src="..."> in profile page.
     */
    public static function getQRUrl(string $issuer, string $account, string $secret, int $size = 220): string
    {
        $label   = rawurlencode($issuer . ':' . $account);
        $params  = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::STEP,
        ]);
        $otpauth = 'otpauth://totp/' . $label . '?' . $params;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
             . '&ecc=M&data=' . rawurlencode($otpauth);
    }

    // ── Recovery codes ────────────────────────────────────────────────────

    /**
     * Generate $n one-time recovery codes in format XXXX-XXXX-XXXX.
     * Normalize before hashing: strtoupper(str_replace('-', '', $code))
     */
    public static function generateRecoveryCodes(int $n = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $n; $i++) {
            $hex     = strtoupper(bin2hex(random_bytes(6))); // 12 hex chars
            $codes[] = substr($hex, 0, 4) . '-' . substr($hex, 4, 4) . '-' . substr($hex, 8, 4);
        }
        return $codes;
    }
}
