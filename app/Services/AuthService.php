<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StaffUser;
use App\Models\AuditLog;
use App\Core\Security;

class AuthService
{
    private const SESSION_KEY = 'cielo_staff';

    /**
     * Authenticate staff/admin user
     */
    public static function attempt(string $email, string $password): ?array
    {
        $cleanEmail = strtolower(trim($email));
        $user = StaffUser::findBy('email', $cleanEmail);

        if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
            return null;
        }

        if (!Security::verifyPassword($password, (string)$user['password_hash'])) {
            return null;
        }

        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        // Store staff session
        $staffData = [
            'id' => (int)$user['id'],
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
            'logged_in_at' => time(),
        ];

        $_SESSION[self::SESSION_KEY] = $staffData;

        // Log audit event
        AuditLog::log(
            (int)$user['id'],
            'staff_login',
            'staff_users',
            (int)$user['id'],
            ['ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
        );

        return $staffData;
    }

    /**
     * Get currently authenticated staff member
     */
    public static function user(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Check if staff user is logged in
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Check if logged in user has a specific role
     */
    public static function hasRole(string|array $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = (array)$roles;
        // Owner has access to everything
        if ($user['role'] === 'owner') {
            return true;
        }

        return in_array($user['role'], $roles, true);
    }

    /**
     * Require authentication and optional role; redirect to login if unauthorized
     */
    public static function requireRole(string|array $roles, string $redirect = '/admin/login.php'): array
    {
        $user = self::user();

        if (!$user) {
            header("Location: {$redirect}?redirect=" . urlencode($_SERVER['REQUEST_URI'] ?? '/admin/orders.php'));
            exit;
        }

        if (!self::hasRole($roles)) {
            http_response_code(403);
            die("Access Denied: Your staff role ({$user['role']}) does not have permission for this section.");
        }

        return $user;
    }

    /**
     * Staff logout
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            startCieloSession();
        }

        $user = self::user();
        if ($user) {
            AuditLog::log((int)$user['id'], 'staff_logout', 'staff_users', (int)$user['id']);
        }

        unset($_SESSION[self::SESSION_KEY]);
    }
}
