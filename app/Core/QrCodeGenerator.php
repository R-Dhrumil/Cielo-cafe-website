<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight, zero-dependency QR Code SVG Generator for Cielo Cafe
 * Encodes text/URLs into crisp, scalable vector SVG graphics.
 * Pure PHP 8.2+ without requiring GD, Imagick, or Composer packages.
 */
class QrCodeGenerator
{
    /**
     * Generate an SVG string representation of a QR Code for the given data.
     *
     * @param string $data The text or URL to encode
     * @param int $size Pixel width/height of the generated SVG viewbox (default: 300)
     * @param string $foregroundColor Hex color for the modules (default: Cielo Espresso #1A1412)
     * @param string $backgroundColor Hex color for the background (default: #FFFFFF or #F7F4EE)
     * @param int $margin Number of quiet zone modules around the matrix (default: 2)
     * @return string Valid SVG markup
     */
    public static function generateSvg(
        string $data,
        int $size = 300,
        string $foregroundColor = '#1A1412',
        string $backgroundColor = '#FFFFFF',
        int $margin = 2
    ): string {
        $matrix = self::encodeToMatrix($data);
        $moduleCount = count($matrix);
        $totalModules = $moduleCount + ($margin * 2);
        $moduleSize = $size / $totalModules;

        $pathData = '';

        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = ($c + $margin) * $moduleSize;
                    $y = ($r + $margin) * $moduleSize;
                    $pathData .= sprintf("M%.2f,%.2fh%.2fv%.2fh-%.2fz ", $x, $y, $moduleSize, $moduleSize, $moduleSize);
                }
            }
        }

        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="%d" height="%d" viewBox="0 0 %d %d">' . "\n",
            $size,
            $size,
            $size,
            $size
        );

        if ($backgroundColor !== 'none' && $backgroundColor !== '') {
            $svg .= sprintf(
                '  <rect width="%d" height="%d" fill="%s" rx="8" />' . "\n",
                $size,
                $size,
                htmlspecialchars($backgroundColor, ENT_QUOTES, 'UTF-8')
            );
        }

        $svg .= sprintf(
            '  <path d="%s" fill="%s" />' . "\n",
            trim($pathData),
            htmlspecialchars($foregroundColor, ENT_QUOTES, 'UTF-8')
        );

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Generate Data URI (image/svg+xml;utf8,...) for direct inline HTML/CSS embedding
     */
    public static function generateSvgDataUri(
        string $data,
        int $size = 300,
        string $foregroundColor = '#1A1412',
        string $backgroundColor = '#FFFFFF'
    ): string {
        $svg = self::generateSvg($data, $size, $foregroundColor, $backgroundColor);
        return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
    }

    /**
     * Pure PHP Byte-Mode QR Code Matrix Encoder
     * Implements QR Code Model 2 with automatic version selection (Versions 1-6)
     */
    public static function encodeToMatrix(string $data): array
    {
        $bytes = array_values(unpack('C*', $data));
        $len = count($bytes);

        // Version capacity table for Byte mode (Error Correction Level M: ~15% recovery)
        // Version => [version, module_size, total_codewords, data_codewords, ec_codewords, ec_blocks]
        $versionCaps = [
            1 => [1, 21, 26, 16, 10, 1],
            2 => [2, 25, 44, 28, 16, 1],
            3 => [3, 29, 70, 44, 26, 1],
            4 => [4, 33, 100, 64, 36, 2],
            5 => [5, 37, 134, 86, 48, 2],
            6 => [6, 41, 172, 108, 64, 4],
        ];

        $selectedVersion = null;
        foreach ($versionCaps as $ver => $info) {
            // Byte mode header: 4 bits mode + 8 bits count = 12 bits = 1.5 bytes + data len
            $neededCodewords = $len + 2;
            if ($neededCodewords <= $info[3]) {
                $selectedVersion = $info;
                break;
            }
        }

        if ($selectedVersion === null) {
            // Default to Version 6 if data exceeds Version 5
            $selectedVersion = $versionCaps[6];
        }

        [$verNum, $dim, $totalCodewords, $dataCodewords, $ecCodewords, $ecBlocks] = $selectedVersion;

        // 1. Bitstream creation (Byte mode: 0100)
        $bits = '0100';
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        foreach ($bytes as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        // Terminator bits (up to 4 zeroes)
        $capacityBits = $dataCodewords * 8;
        $terminatorLen = min(4, $capacityBits - strlen($bits));
        $bits .= str_repeat('0', max(0, $terminatorLen));

        // Pad to byte boundary
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        // Pad bytes (0xEC, 0x11)
        $padBytes = ['11101100', '00010001'];
        $padIndex = 0;
        while (strlen($bits) < $capacityBits) {
            $bits .= $padBytes[$padIndex % 2];
            $padIndex++;
        }

        // Convert bits into byte codewords
        $codewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $codewords[] = bindec(substr($bits, $i, 8));
        }

        // 2. Generate Reed-Solomon Error Correction Codewords
        $ecCodewordsPerBlock = (int)($ecCodewords / $ecBlocks);
        $dataCodewordsPerBlock = (int)($dataCodewords / $ecBlocks);

        $blocksData = [];
        $blocksEc = [];

        for ($b = 0; $b < $ecBlocks; $b++) {
            $blockData = array_slice($codewords, $b * $dataCodewordsPerBlock, $dataCodewordsPerBlock);
            $blocksData[] = $blockData;
            $blocksEc[] = self::calculateReedSolomonEc($blockData, $ecCodewordsPerBlock);
        }

        // Interleave codewords
        $finalCodewords = [];
        for ($i = 0; $i < $dataCodewordsPerBlock; $i++) {
            for ($b = 0; $b < $ecBlocks; $b++) {
                if (isset($blocksData[$b][$i])) {
                    $finalCodewords[] = $blocksData[$b][$i];
                }
            }
        }
        for ($i = 0; $i < $ecCodewordsPerBlock; $i++) {
            for ($b = 0; $b < $ecBlocks; $b++) {
                if (isset($blocksEc[$b][$i])) {
                    $finalCodewords[] = $blocksEc[$b][$i];
                }
            }
        }

        // 3. Initialize Grid Matrix ($dim x $dim)
        $matrix = array_fill(0, $dim, array_fill(0, $dim, null));

        // Add Finder Patterns (Top-Left, Top-Right, Bottom-Left)
        self::addFinderPattern($matrix, 0, 0);
        self::addFinderPattern($matrix, $dim - 7, 0);
        self::addFinderPattern($matrix, 0, $dim - 7);

        // Add Separators around finders
        self::addSeparators($matrix, $dim);

        // Add Timing Patterns
        for ($i = 8; $i < $dim - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if ($matrix[6][$i] === null) $matrix[6][$i] = $val;
            if ($matrix[$i][6] === null) $matrix[$i][6] = $val;
        }

        // Add Alignment Pattern for Version >= 2
        if ($verNum >= 2) {
            $alignCenter = $dim - 7;
            self::addAlignmentPattern($matrix, $alignCenter, $alignCenter);
        }

        // Reserve Format Information areas
        self::reserveFormatAreas($matrix, $dim);

        // Place Data Codewords (zigzag traversal)
        self::placeDataBits($matrix, $dim, $finalCodewords);

        // Apply Optimal Mask (Mask 0: (row + col) % 2 == 0)
        self::applyMask($matrix, $dim);

        // Add Format Information (Level M, Mask 0: 101010000010010)
        self::addFormatInfo($matrix, $dim);

        // Replace any remaining null with 0
        for ($r = 0; $r < $dim; $r++) {
            for ($c = 0; $c < $dim; $c++) {
                if ($matrix[$r][$c] === null) {
                    $matrix[$r][$c] = 0;
                }
            }
        }

        return $matrix;
    }

    /**
     * Add 7x7 Finder Pattern
     */
    private static function addFinderPattern(array &$matrix, int $row, int $col): void
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r === 0 || $r === 6 || $c === 0 || $c === 6 ||
                    ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $matrix[$row + $r][$col + $c] = 1;
                } else {
                    $matrix[$row + $r][$col + $c] = 0;
                }
            }
        }
    }

    /**
     * Add white separators around finder patterns
     */
    private static function addSeparators(array &$matrix, int $dim): void
    {
        // Top-left
        for ($i = 0; $i < 8; $i++) {
            if ($i < $dim && 7 < $dim) $matrix[7][$i] = 0;
            if ($i < $dim && 7 < $dim) $matrix[$i][7] = 0;
        }
        // Top-right
        for ($i = 0; $i < 8; $i++) {
            if (7 < $dim && ($dim - 8 + $i) >= 0 && ($dim - 8 + $i) < $dim) {
                $matrix[7][$dim - 8 + $i] = 0;
            }
            if ($i < $dim && ($dim - 8) >= 0 && ($dim - 8) < $dim) {
                $matrix[$i][$dim - 8] = 0;
            }
        }
        // Bottom-left
        for ($i = 0; $i < 8; $i++) {
            if (($dim - 8) >= 0 && ($dim - 8) < $dim && $i < $dim) {
                $matrix[$dim - 8][$i] = 0;
            }
            if (($dim - 8 + $i) >= 0 && ($dim - 8 + $i) < $dim && 7 < $dim) {
                $matrix[$dim - 8 + $i][7] = 0;
            }
        }
    }

    /**
     * Add 5x5 Alignment Pattern
     */
    private static function addAlignmentPattern(array &$matrix, int $centerRow, int $centerCol): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                if ($r === -2 || $r === 2 || $c === -2 || $c === 2 || ($r === 0 && $c === 0)) {
                    $matrix[$centerRow + $r][$centerCol + $c] = 1;
                } else {
                    $matrix[$centerRow + $r][$centerCol + $c] = 0;
                }
            }
        }
    }

    /**
     * Reserve Format Information space around finders
     */
    private static function reserveFormatAreas(array &$matrix, int $dim): void
    {
        for ($i = 0; $i < 9; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = -1;
            if ($matrix[$i][8] === null) $matrix[$i][8] = -1;
        }
        for ($i = $dim - 8; $i < $dim; $i++) {
            if ($matrix[8][$i] === null) $matrix[8][$i] = -1;
        }
        for ($i = $dim - 8; $i < $dim; $i++) {
            if ($matrix[$i][8] === null) $matrix[$i][8] = -1;
        }
        // Dark module
        $matrix[$dim - 8][8] = 1;
    }

    /**
     * Place data bits in 2-column wide zigzag columns from right to left
     */
    private static function placeDataBits(array &$matrix, int $dim, array $codewords): void
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $bitIdx = 0;
        $bitLen = strlen($bits);
        $upwards = true;

        for ($col = $dim - 1; $col > 0; $col -= 2) {
            // Skip vertical timing column
            if ($col === 6) {
                $col--;
            }

            $rows = $upwards ? range($dim - 1, 0) : range(0, $dim - 1);

            foreach ($rows as $r) {
                foreach ([$col, $col - 1] as $c) {
                    if ($matrix[$r][$c] === null) {
                        $bit = ($bitIdx < $bitLen) ? (int)$bits[$bitIdx] : 0;
                        $matrix[$r][$c] = $bit;
                        $bitIdx++;
                    }
                }
            }

            $upwards = !$upwards;
        }
    }

    /**
     * Apply Mask Pattern 0: (row + col) % 2 === 0
     */
    private static function applyMask(array &$matrix, int $dim): void
    {
        for ($r = 0; $r < $dim; $r++) {
            for ($c = 0; $c < $dim; $c++) {
                // Only mask data bits (exclude function patterns and format bits)
                if ($matrix[$r][$c] !== -1 && !self::isFunctionPattern($r, $c, $dim)) {
                    if (($r + $c) % 2 === 0) {
                        $matrix[$r][$c] = $matrix[$r][$c] ^ 1;
                    }
                }
            }
        }
    }

    private static function isFunctionPattern(int $r, int $c, int $dim): bool
    {
        // Finder + Separator: Top-Left (9x9)
        if ($r < 9 && $c < 9) return true;
        // Top-Right (9x9)
        if ($r < 9 && $c >= ($dim - 8)) return true;
        // Bottom-Left (9x9)
        if ($r >= ($dim - 8) && $c < 9) return true;
        // Timing
        if ($r === 6 || $c === 6) return true;
        return false;
    }

    /**
     * Add 15-bit Format Information (Level M, Mask 0: 101010000010010)
     */
    private static function addFormatInfo(array &$matrix, int $dim): void
    {
        // Pre-computed BCH format string for Level M with Mask Pattern 0
        $formatBits = '101010000010010';

        // Top-Left horizontal
        $indicesHorizontal = [0, 1, 2, 3, 4, 5, 7, 8];
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$indicesHorizontal[$i]] = (int)$formatBits[$i];
        }

        // Top-Left vertical
        $indicesVertical = [8, 7, 5, 4, 3, 2, 1, 0];
        for ($i = 0; $i < 7; $i++) {
            $matrix[$indicesVertical[$i]][8] = (int)$formatBits[8 + $i];
        }

        // Bottom-Left vertical
        for ($i = 0; $i < 7; $i++) {
            $matrix[$dim - 1 - $i][8] = (int)$formatBits[$i];
        }

        // Top-Right horizontal
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$dim - 8 + $i] = (int)$formatBits[7 + $i];
        }
    }

    /**
     * Reed-Solomon Galois Field GF(256) Error Correction Engine
     */
    private static function calculateReedSolomonEc(array $data, int $ecCount): array
    {
        // GF(256) log and antilog tables (primitive polynomial 0x11D / 285)
        static $gfLog = null, $gfExp = null;
        if ($gfLog === null) {
            $gfLog = array_fill(0, 256, 0);
            $gfExp = array_fill(0, 512, 0);
            $x = 1;
            for ($i = 0; $i < 255; $i++) {
                $gfExp[$i] = $x;
                $gfExp[$i + 255] = $x;
                $gfLog[$x] = $i;
                $x <<= 1;
                if ($x & 256) {
                    $x ^= 285;
                }
            }
        }

        // Compute generator polynomial of degree $ecCount
        $generator = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $newGen = array_fill(0, count($generator) + 1, 0);
            $factor = $gfExp[$i];
            for ($j = 0; $j < count($generator); $j++) {
                $newGen[$j] ^= $generator[$j];
                $term = ($generator[$j] === 0) ? 0 : $gfExp[$gfLog[$generator[$j]] + $i];
                $newGen[$j + 1] ^= $term;
            }
            $generator = $newGen;
        }

        // Polynomial division
        $remainder = array_merge($data, array_fill(0, $ecCount, 0));
        $dataLen = count($data);

        for ($i = 0; $i < $dataLen; $i++) {
            $lead = $remainder[$i];
            if ($lead !== 0) {
                $leadLog = $gfLog[$lead];
                for ($j = 0; $j < count($generator); $j++) {
                    if ($generator[$j] !== 0) {
                        $remainder[$i + $j] ^= $gfExp[$leadLog + $gfLog[$generator[$j]]];
                    }
                }
            }
        }

        return array_slice($remainder, $dataLen);
    }
}
