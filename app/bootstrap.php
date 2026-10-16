<?php
/**
 * Cielo Cafe and Restro - Modular Application Bootstrap
 * PSR-4 Autoloader, Environment Setup, Error Handling, and Service Access
 */

declare(strict_types=1);

define('CIELO_ROOT', dirname(__DIR__));
define('CIELO_APP', CIELO_ROOT . '/app');
define('CIELO_CONFIG', CIELO_ROOT . '/config');
define('CIELO_DATABASE', CIELO_ROOT . '/database');
define('CIELO_STORAGE', CIELO_ROOT . '/storage');
define('CIELO_INCLUDES', CIELO_ROOT . '/includes');
define('CIELO_PAGES', CIELO_ROOT . '/pages');

// 1. PSR-4 Autoloader for "App\" namespace
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = CIELO_APP . '/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Lightweight .env Parser
function loadDotEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Strip surrounding single or double quotes
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

loadDotEnv(CIELO_ROOT . '/.env');

// 3. Environment & Production Error Handling Configuration
$appEnv = getenv('APP_ENV') ?: 'development';
$appDebug = filter_var(getenv('APP_DEBUG') ?? ($appEnv === 'development' ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);

if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // In production: hide errors from browser, log to protected storage/logs
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

ini_set('log_errors', '1');
$logDir = CIELO_STORAGE . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/error.log');

// 4. Timezone Setting (Indian Standard Time)
date_default_timezone_set('Asia/Kolkata');

// 5. Storage Path Helper
function storage_path(string $path = ''): string {
    return CIELO_STORAGE . ($path ? '/' . ltrim($path, '/') : '');
}

// 6. Application Logger Helper
function cielo_log(string $message, string $level = 'info'): void {
    $dir = CIELO_STORAGE . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $level = strtoupper($level);
    @file_put_contents($file, "[{$timestamp}] [{$level}] {$message}\n", FILE_APPEND | LOCK_EX);
}

// 7. Secure Session Startup
function startCieloSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            session_set_cookie_params([
                'lifetime' => 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        } elseif (session_id() === '') {
            @session_start();
        }
    }
}

// 5. Global Config Helper
function config(string $key, mixed $default = null): mixed {
    return \App\Core\Config::get($key, $default);
}

// 6. Global Database Helper
function db(): \App\Core\Database {
    return \App\Core\Database::getInstance();
}

// 7. Money Formatting Helper (Paise to Rupee)
function formatMoney(int $paise): string {
    return \App\Core\Money::format($paise);
}

// 8. Safe HTML Escaper
if (!function_exists('e')) {
    function e(?string $text): string {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}
