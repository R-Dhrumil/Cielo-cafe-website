<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class OtpVerification extends Model
{
    protected static string $table = 'otp_verifications';

    /**
     * Find latest active OTP record for a phone number
     */
    public static function findLatestForPhone(string $phone): ?array
    {
        return self::first('`phone` = ? AND `is_verified` = 0 ORDER BY `id` DESC', [$phone]);
    }
}
