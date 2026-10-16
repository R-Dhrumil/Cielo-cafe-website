<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Admin Router / Entrypoint
 *
 * Automatically routes /admin and /admin/ to:
 * - /admin/login.php if not authenticated
 * - /kitchen/index.php if kitchen staff
 * - /admin/orders.php if owner / admin staff
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Services\AuthService;

if (!AuthService::check()) {
    header('Location: /admin/login.php');
    exit;
}

$user = AuthService::user();
if ($user && $user['role'] === 'kitchen') {
    header('Location: /kitchen/index.php');
    exit;
}

header('Location: /admin/orders.php');
exit;
