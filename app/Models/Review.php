<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Review extends Model
{
    protected static string $table = 'reviews';

    public static function getApproved(int $limit = 10): array
    {
        return self::where('`status` = "approved"', [], 'id DESC', $limit);
    }
}
