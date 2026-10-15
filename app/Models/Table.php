<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class Table extends Model
{
    protected static string $table = 'tables';

    public static function findByToken(string $token): ?array
    {
        return self::findBy('qr_token', trim($token));
    }

    public static function createTable(int|string $number, int $capacity = 4): int|string
    {
        return self::create([
            'table_number' => (string)$number,
            'qr_token' => Security::generateTableToken(16),
            'capacity' => $capacity,
            'is_active' => 1,
        ]);
    }
}
