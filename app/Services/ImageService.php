<?php

declare(strict_types=1);

namespace App\Services;

class ImageService
{
    /**
     * Process an uploaded image (from multipart $_FILES or client-side Canvas base64)
     * and save it strictly as an optimized .webp file.
     *
     * @param array|null $file $_FILES['input_name'] array
     * @param string|null $base64Data Base64 Data URL or string
     * @param string $relativeDir Destination directory relative to project root
     * @param string $prefix Filename prefix (e.g. dish slug)
     * @param int $maxWidth Max width to downscale large camera uploads
     * @param int $quality WebP compression quality (0-100)
     * @return string|null Relative file path to project root on success, null on failure
     */
    public static function saveAsWebp(
        ?array $file = null,
        ?string $base64Data = null,
        string $relativeDir = 'assets/images/dishes',
        string $prefix = 'dish',
        int $maxWidth = 1000,
        int $quality = 85
    ): ?string {
        $root = dirname(__DIR__, 2);
        $targetDir = rtrim($root . '/' . ltrim($relativeDir, '/\\'), '/\\');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $imageResource = null;

        // 1. Check for Base64 Data (e.g. from Canvas .toDataURL('image/webp'))
        if (!empty($base64Data) && is_string($base64Data)) {
            $cleanBase64 = $base64Data;
            if (str_contains($cleanBase64, ';base64,')) {
                $parts = explode(';base64,', $cleanBase64, 2);
                $cleanBase64 = $parts[1] ?? '';
            }

            $binary = base64_decode($cleanBase64, true);
            if ($binary !== false && strlen($binary) > 0) {
                $imageResource = @imagecreatefromstring($binary);
            }
        }

        // 2. Fallback to standard multipart $_FILES
        if ($imageResource === null && !empty($file) && !empty($file['tmp_name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmpPath = (string)$file['tmp_name'];
            if (is_uploaded_file($tmpPath) || file_exists($tmpPath)) {
                $binary = @file_get_contents($tmpPath);
                if ($binary !== false && strlen($binary) > 0) {
                    $imageResource = @imagecreatefromstring($binary);
                }
            }
        }

        if (!$imageResource instanceof \GdImage) {
            return null;
        }

        // 3. Proportional downscaling if image exceeds maxWidth
        $origWidth = imagesx($imageResource);
        $origHeight = imagesy($imageResource);

        if ($origWidth > $maxWidth && $maxWidth > 0) {
            $newWidth = $maxWidth;
            $newHeight = (int)round(($origHeight / $origWidth) * $newWidth);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            // Retain alpha transparency for png/webp
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);

            imagecopyresampled(
                $resized,
                $imageResource,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $origWidth, $origHeight
            );

            $imageResource = $resized;
        }

        // 4. Generate unique safe WebP filename
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($prefix));
        $safePrefix = trim($safePrefix, '-');
        if ($safePrefix === '') {
            $safePrefix = 'dish';
        }

        $filename = $safePrefix . '-' . time() . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.webp';
        $fullPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

        // 5. Save as WebP
        $saved = imagewebp($imageResource, $fullPath, $quality);

        if (!$saved || !file_exists($fullPath)) {
            return null;
        }

        // Return forward-slash relative path
        return rtrim($relativeDir, '/') . '/' . $filename;
    }

    /**
     * Retrieve list of preset dish images from the dishes directory
     */
    public static function getDishPresets(): array
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . '/assets/images/dishes';
        if (!is_dir($dir)) {
            return [];
        }

        $files = scandir($dir);
        $presets = [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            if (str_ends_with(strtolower($f), '.webp') || str_ends_with(strtolower($f), '.jpg') || str_ends_with(strtolower($f), '.png')) {
                $presets[] = 'assets/images/dishes/' . $f;
            }
        }
        return $presets;
    }
}
