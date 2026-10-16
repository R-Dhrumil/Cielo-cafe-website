<?php

declare(strict_types=1);

namespace App\Core;

class Security
{
    /**
     * Generate a cryptographically secure random hex string
     */
    public static function generateToken(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Generate an alphanumeric high-entropy token (ideal for table QR codes)
     */
    public static function generateTableToken(int $length = 16): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $max = strlen($alphabet) - 1;
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $max)];
        }
        return $token;
    }

    /**
     * Retrieve or generate CSRF token in session
     */
    public static function csrfToken(): string
    {
        startCieloSession();
        $tokenName = Config::get('app.security.csrf_token_name', 'cielo_csrf_token');

        if (empty($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = self::generateToken(32);
        }

        return $_SESSION[$tokenName];
    }

    /**
     * Alias for csrfToken
     */
    public static function generateCsrfToken(): string
    {
        return self::csrfToken();
    }

    /**
     * Validate submitted CSRF token
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        startCieloSession();
        $tokenName = Config::get('app.security.csrf_token_name', 'cielo_csrf_token');
        $sessionToken = $_SESSION[$tokenName] ?? '';

        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Render hidden CSRF input field for forms
     */
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        $tokenName = Config::get('app.security.csrf_token_name', 'cielo_csrf_token');
        return '<input type="hidden" name="' . htmlspecialchars($tokenName, ENT_QUOTES) . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }

    /**
     * Secure password hashing with BCRYPT
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 11]);
    }

    /**
     * Verify password hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input recursively
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        if (is_string($data)) {
            return trim(strip_tags($data));
        }
        return $data;
    }
}
