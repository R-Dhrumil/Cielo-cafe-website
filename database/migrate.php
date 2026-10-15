<?php
/**
 * Cielo Cafe and Restro - Database Migration Runner
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

echo "========================================================\n";
echo " Cielo Cafe & Restro - Database Migration\n";
echo "========================================================\n";

try {
    $db = Database::getInstance();
    $driver = $db->getDriver();

    echo "Connected via driver: [{$driver}]\n";

    if ($driver === 'sqlite') {
        $schemaFile = __DIR__ . '/sqlite_schema.sql';
        $sql = file_get_contents($schemaFile);
        $db->getPdo()->exec($sql);
    } else {
        $schemaFile = __DIR__ . '/schema.sql';
        $sql = file_get_contents($schemaFile);
        $db->getPdo()->exec($sql);
    }

    echo "✔ Schema executed successfully from: " . basename($schemaFile) . "\n";

    // Verify all 20 tables exist
    $tables = [
        'settings', 'staff_users', 'users', 'tables', 'menu_categories',
        'menu_items', 'item_variants', 'item_addons', 'orders', 'order_items',
        'payments', 'coupons', 'coupon_usage', 'offers', 'reward_rules',
        'points_ledger', 'visits', 'user_rewards', 'reviews', 'audit_log'
    ];

    echo "\nVerifying table structure:\n";
    foreach ($tables as $table) {
        if ($driver === 'sqlite') {
            $exists = (int)$db->fetchColumn("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name = :t", ['t' => $table]);
        } else {
            $exists = (int)$db->fetchColumn("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t", ['t' => $table]);
        }

        if ($exists) {
            echo "  ✔ Table [{$table}] verified\n";
        } else {
            echo "  ✖ Table [{$table}] MISSING\n";
        }
    }

    echo "\n✔ Migration completed successfully!\n";
} catch (Throwable $e) {
    echo "\n✖ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
