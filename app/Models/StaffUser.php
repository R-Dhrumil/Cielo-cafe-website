<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class StaffUser extends Model
{
    protected static string $table = 'staff_users';

    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findBy('email', strtolower(trim($email)));
        if (!$user || empty($user['is_active'])) {
            return null;
        }

        if (Security::verifyPassword($password, $user['password_hash'])) {
            return $user;
        }

        return null;
    }

    public static function createStaff(string $name, string $email, string $password, string $role = 'staff'): int|string
    {
        return self::create([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password_hash' => Security::hashPassword($password),
            'role' => $role,
            'is_active' => 1,
        ]);
    }
}
