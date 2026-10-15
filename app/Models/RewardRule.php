<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class RewardRule extends Model
{
    protected static string $table = 'reward_rules';

    public static function getActive(string $triggerType = ''): array
    {
        if ($triggerType !== '') {
            return self::where('`is_active` = 1 AND `trigger_type` = :t', ['t' => $triggerType], 'id ASC');
        }
        return self::where('`is_active` = 1', [], 'id ASC');
    }
}
