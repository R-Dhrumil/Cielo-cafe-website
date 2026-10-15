<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Table;

class TableSessionService
{
    private const SESSION_KEY = 'cielo_table';
    private const COOKIE_KEY = 'cielo_table_token';
    private const COOKIE_EXPIRY = 21600; // 6 hours dining session

    /**
     * Start a table session by QR token
     *
     * @param string $token 16-character cryptographic QR token
     * @return array|null Returns table record if valid, null otherwise
     */
    public static function startSession(string $token): ?array
    {
        $cleanToken = trim($token);
        if ($cleanToken === '') {
            return null;
        }

        // Look up table by QR token
        $table = Table::findByToken($cleanToken);

        if (!$table || (int)($table['is_active'] ?? 0) !== 1) {
            return null;
        }

        // Initialize session if needed
        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        $sessionData = [
            'id' => (int)$table['id'],
            'table_number' => (string)$table['table_number'],
            'floor' => (string)($table['floor'] ?? 'Ground Floor'),
            'capacity' => (int)($table['capacity'] ?? 4),
            'qr_token' => (string)$table['qr_token'],
            'verified_at' => time(),
        ];

        $_SESSION[self::SESSION_KEY] = $sessionData;

        // Set persistent cookie for duration of the dining visit
        if (!headers_sent()) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

            setcookie(self::COOKIE_KEY, $cleanToken, [
                'expires' => time() + self::COOKIE_EXPIRY,
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return $sessionData;
    }

    /**
     * Get active table session
     */
    public static function getActiveTable(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        // 1. Check active session
        if (!empty($_SESSION[self::SESSION_KEY])) {
            return $_SESSION[self::SESSION_KEY];
        }

        // 2. Check cookie if session expired or lost
        $cookieToken = $_COOKIE[self::COOKIE_KEY] ?? '';
        if ($cookieToken !== '') {
            $table = Table::findByToken($cookieToken);
            if ($table && (int)($table['is_active'] ?? 0) === 1) {
                return self::startSession($cookieToken);
            }
        }

        return null;
    }

    /**
     * Check if a table session is active
     */
    public static function hasActiveTable(): bool
    {
        return self::getActiveTable() !== null;
    }

    /**
     * Terminate table session (e.g. after bill payment / departure)
     */
    public static function clearSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        unset($_SESSION[self::SESSION_KEY]);

        if (!headers_sent()) {
            setcookie(self::COOKIE_KEY, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    /**
     * Generate full scan URL for a given table QR token
     */
    public static function generateScanUrl(string $token): string
    {
        $baseUrl = (string)config('app.url', '');

        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'cielocafebistro.in';
            $baseUrl = "{$scheme}://{$host}";
        }

        $baseUrl = rtrim($baseUrl, '/');
        return "{$baseUrl}/t.php?token={$token}";
    }
}
