<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findOrCreateByPhone(string $phone, ?string $name = null): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) > 10) {
            $cleanPhone = substr($cleanPhone, -10);
        }

        $existing = self::findBy('phone', $cleanPhone);
        if ($existing) {
            if ($name !== null && empty($existing['name'])) {
                self::updateById($existing['id'], ['name' => $name]);
                $existing['name'] = $name;
            }
            return $existing;
        }

        $id = self::create([
            'phone' => $cleanPhone,
            'name' => $name ?: 'Guest Patron',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return self::find($id);
    }
}
