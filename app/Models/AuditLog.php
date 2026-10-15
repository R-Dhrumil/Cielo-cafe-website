<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AuditLog extends Model
{
    protected static string $table = 'audit_log';
    protected static bool $hasUpdatedAt = false;

    public static function log(
        ?int $staffId,
        string $action,
        string $entityType = '',
        int|string|null $entityId = null,
        array $details = []
    ): int|string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return self::create([
            'staff_id' => $staffId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (int)$entityId : null,
            'details_json' => json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
