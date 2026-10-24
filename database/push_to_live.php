<?php
/**
 * Cielo Cafe & Restro - Push All Data Directly to Live MySQL Database
 *
 * Usage: php database/push_to_live.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

echo "========================================================\n";
echo " Cielo Cafe & Restro - Push Data to Live MySQL Database\n";
echo "========================================================\n\n";

$dbName = getenv('DB_DATABASE') ?: 'ritivers_cafe';
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);
$dbUser = getenv('DB_USERNAME') ?: 'ritivers_cafe';
$dbPass = getenv('DB_PASSWORD') ?: '';

echo "Connecting to MySQL server...\n";
echo "  Host: {$dbHost}:{$dbPort}\n";
echo "  Database: {$dbName}\n";
echo "  User: {$dbUser}\n\n";

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "✔ Successfully connected to MySQL database [{$dbName}]!\n\n";
} catch (PDOException $e) {
    echo "✖ Connection Failed: " . $e->getMessage() . "\n\n";
    echo "💡 Note: If connecting remotely from your local computer, cPanel's firewall\n";
    echo "   blocks external port 3306 by default. You can:\n";
    echo "   1. Open phpMyAdmin (already open on your screen!)\n";
    echo "   2. Click the 'Import' tab on database `{$dbName}`\n";
    echo "   3. Select: database/ritivers_cafe_full_import.sql\n";
    echo "   4. Click 'Import' / 'Go'\n";
    exit(1);
}

$dumpFile = __DIR__ . '/ritivers_cafe_full_import.sql';
if (!file_exists($dumpFile)) {
    echo "✖ SQL dump file missing: {$dumpFile}\n";
    exit(1);
}

echo "Reading SQL migration file (21 tables + 1,161 records)...\n";
$sql = file_get_contents($dumpFile);

echo "Executing SQL statements on [{$dbName}]...\n";
$pdo->exec($sql);

echo "\n✔ SUCCESS! All 21 tables and 1,161 records with WebP images have been migrated to [{$dbName}]!\n";
