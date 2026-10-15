<?php

declare(strict_types=1);

/**
 * Cielo Cafe and Restro - Raw SVG QR Code Endpoint
 *
 * Endpoint: /admin/table_qr.php?token={token} or ?id={id}
 * Serves crisp, scalable vector SVG QR code for table tent cards
 */

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Table;
use App\Services\TableSessionService;
use App\Core\QrCodeGenerator;

$token = trim((string)($_GET['token'] ?? ''));
$id = (int)($_GET['id'] ?? 0);

if ($token === '' && $id > 0) {
    $table = Table::find($id);
    if ($table) {
        $token = (string)$table['qr_token'];
    }
}

if ($token === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing table token or ID.';
    exit;
}

$scanUrl = TableSessionService::generateScanUrl($token);

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

$size = max(100, min(800, (int)($_GET['size'] ?? 300)));
$fg = $_GET['fg'] ?? '#1A1412'; // Cielo espresso default
$bg = $_GET['bg'] ?? '#FFFFFF';

echo QrCodeGenerator::generateSvg($scanUrl, $size, $fg, $bg);
exit;
