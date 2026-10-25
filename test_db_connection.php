<?php

declare(strict_types=1);

/**
 * Cielo Cafe - Live Database & Service Connection Diagnostic Tool
 * Run via CLI: php test_db_connection.php
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;

echo "\n=======================================================\n";
echo "  CIELO CAFE - DATABASE & LIVE CONNECTIVITY DIAGNOSTIC\n";
echo "=======================================================\n\n";

$connection = Config::get('database.default', 'mysql');
$config = Config::get("database.connections.{$connection}", []);

echo "Active Driver:     " . strtoupper($connection) . "\n";
if ($connection === 'mysql') {
    echo "Host:              " . ($config['host'] ?? '127.0.0.1') . "\n";
    echo "Port:              " . ($config['port'] ?? 3306) . "\n";
    echo "Database:          " . ($config['database'] ?? '') . "\n";
    echo "Username:          " . ($config['username'] ?? '') . "\n";
    echo "Password Set:      " . (!empty($config['password']) ? 'YES (' . strlen($config['password']) . ' chars)' : 'NO (EMPTY!)') . "\n";
} else {
    echo "SQLite Path:       " . ($config['database'] ?? '') . "\n";
}
echo "OTP Provider:      " . getenv('OTP_PROVIDER') . "\n";
echo "-------------------------------------------------------\n";

echo "Attempting connection to database...\n";

try {
    Database::resetInstance();
    $db = Database::getInstance();
    $pdo = $db->getPdo();

    echo "✅ [SUCCESS] Connected successfully to database!\n";
    echo "   Server Version:  " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "   Driver:          " . $db->getDriver() . "\n";

    // Test tables existence
    if ($db->isMysql()) {
        $tables = $db->fetchAll("SHOW TABLES");
        $tableCount = count($tables);
        echo "   Tables in DB:    {$tableCount} tables found\n";
        
        if ($tableCount > 0) {
            $userCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM `users`");
            $menuCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM `menu_items`");
            echo "   Registered Users: {$userCount}\n";
            echo "   Menu Items:       {$menuCount}\n";
        } else {
            echo "   ⚠️ WARNING: Database is connected but contains 0 tables!\n";
            echo "   Import 'database/ritivers_cafe_full_import.sql' via phpMyAdmin.\n";
        }
    }

} catch (Throwable $e) {
    echo "❌ [FAILED] Database connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    
    if ($connection === 'mysql') {
        echo "💡 DIAGNOSTIC & FIX STEPS:\n";
        if (empty($config['password'])) {
            echo "   1. DB_PASSWORD is currently blank in .env! Open .env and enter your database password.\n";
        }
        if (in_array($config['host'], ['127.0.0.1', 'localhost']) && php_sapi_name() === 'cli') {
            echo "   2. You are running on Windows localhost. If you want to connect to cPanel MySQL remotely:\n";
            echo "      - In .env set: DB_HOST=linux898.defaultserverdns.com\n";
            echo "      - In cPanel > Databases > Remote MySQL: Add '%' to allow remote connection.\n";
            echo "   3. When deploying files directly to cPanel (cafe.ritiverse.in):\n";
            echo "      - DB_HOST=localhost is correct, just provide the DB_PASSWORD in .env.\n";
        }
    }
}

echo "\n=======================================================\n\n";
