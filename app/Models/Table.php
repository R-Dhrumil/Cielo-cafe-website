<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;

class Table extends Model
{
    protected static string $table = 'tables';

    /**
     * Check if table has a valid, active QR token
     */
    public static function hasActiveQr(array $table): bool
    {
        $token = trim((string)($table['qr_token'] ?? ''));
        return $token !== '' && !str_starts_with($token, 'disabled_');
    }

    /**
     * Find table by active cryptographic QR token
     */
    public static function findByToken(string $token): ?array
    {
        $clean = trim($token);
        if ($clean === '' || str_starts_with($clean, 'disabled_')) {
            return null;
        }

        $table = self::findBy('qr_token', $clean);
        if (!$table || (int)($table['is_active'] ?? 0) !== 1) {
            return null;
        }

        return $table;
    }

    /**
     * Create a new table with custom name, optional floor, capacity, and QR token
     */
    public static function createTable(
        string $name,
        int $capacity = 4,
        string $floor = 'Ground Floor',
        bool $generateQr = true
    ): int|string {
        $cleanName = trim($name);
        $token = $generateQr ? Security::generateTableToken(16) : ('disabled_temp_' . time() . '_' . bin2hex(random_bytes(4)));

        $data = [
            'table_number' => $cleanName,
            'qr_token' => $token,
            'capacity' => max(1, $capacity),
            'is_active' => 1,
        ];

        // Attempt to include floor if column exists
        try {
            return self::create(array_merge($data, ['floor' => $floor]));
        } catch (\Throwable $e) {
            // Fallback without floor column if table schema is legacy
            return self::create($data);
        }
    }

    /**
     * Rename or update table properties
     */
    public static function renameTable(
        int $id,
        string $newName,
        ?int $capacity = null,
        ?string $floor = null,
        ?int $isActive = null
    ): bool {
        $data = [
            'table_number' => trim($newName),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($capacity !== null) {
            $data['capacity'] = max(1, $capacity);
        }
        if ($isActive !== null) {
            $data['is_active'] = $isActive ? 1 : 0;
        }

        if ($floor !== null) {
            try {
                self::updateById($id, array_merge($data, ['floor' => trim($floor)]));
                return true;
            } catch (\Throwable $e) {
                // Ignore floor if column missing
            }
        }

        self::updateById($id, $data);
        return true;
    }

    /**
     * Generate or regenerate a fresh cryptographic QR token for table
     */
    public static function regenerateQrToken(int $id): ?string
    {
        $table = self::find($id);
        if (!$table) {
            return null;
        }

        $newToken = Security::generateTableToken(16);
        self::updateById($id, [
            'qr_token' => $newToken,
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $newToken;
    }

    /**
     * Remove / Disable QR code for a table
     * Inactive / disabled QR tokens instantly return 404 on scan
     */
    public static function removeQrToken(int $id): bool
    {
        $table = self::find($id);
        if (!$table) {
            return false;
        }

        $disabledToken = 'disabled_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4));

        try {
            // Attempt null first
            self::updateById($id, [
                'qr_token' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            // Fallback to distinct disabled prefix token
            self::updateById($id, [
                'qr_token' => $disabledToken,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        }
    }

    /**
     * Toggle table active status
     */
    public static function toggleActive(int $id, ?bool $active = null): bool
    {
        $table = self::find($id);
        if (!$table) {
            return false;
        }

        $newState = $active !== null ? ($active ? 1 : 0) : ((int)($table['is_active'] ?? 0) === 1 ? 0 : 1);
        self::updateById($id, [
            'is_active' => $newState,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Get count of past/present orders placed on this table
     */
    public static function getOrderCount(int $id): int
    {
        try {
            return (int)db()->fetchColumn(
                "SELECT COUNT(*) FROM `orders` WHERE `table_id` = :tid",
                ['tid' => $id]
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Safely delete a table or deactivate if orders exist
     */
    public static function deleteTableSafe(int $id): array
    {
        $table = self::find($id);
        if (!$table) {
            return ['success' => false, 'message' => 'Table not found.'];
        }

        $orderCount = self::getOrderCount($id);
        if ($orderCount > 0) {
            // Do not delete rows referenced by foreign key orders; deactivate and revoke QR instead
            self::removeQrToken($id);
            self::toggleActive($id, false);
            return [
                'success' => true,
                'deactivated' => true,
                'message' => "Table '{$table['table_number']}' has {$orderCount} historical orders. It has been deactivated and its QR code removed to preserve financial records.",
            ];
        }

        try {
            self::deleteById($id);
            return [
                'success' => true,
                'deactivated' => false,
                'message' => "Table '{$table['table_number']}' has been permanently deleted.",
            ];
        } catch (\Throwable $e) {
            self::removeQrToken($id);
            self::toggleActive($id, false);
            return [
                'success' => true,
                'deactivated' => true,
                'message' => "Table deactivated and QR code removed.",
            ];
        }
    }
}
