<?php
declare(strict_types=1);

namespace Nexus\View;

/**
 * Pure-PHP Zero-Dependency Vector QR-Code Generator
 *
 * Implements ISO/IEC 18004 QR-code matrix generation in pure PHP with
 * Galois Field GF(256) Reed-Solomon error correction.
 * Emits clean inline SVG without external APIs or dependencies.
 */
class QrCodeGenerator
{
    private const GF256_POLY = 0x11d; // x^8 + x^4 + x^3 + x^2 + 1
    private static ?array $gfExp = null;
    private static ?array $gfLog = null;

    /**
     * Generate an inline SVG string for the given text
     */
    public static function svg(string $text, int $size = 220, string $darkColor = '#00f2fe', string $lightColor = '#0b0f19'): string
    {
        $matrix = self::generateMatrix($text);
        $count = count($matrix);
        $moduleSize = 10;
        $quietZone = 4;
        $dimension = ($count + ($quietZone * 2)) * $moduleSize;

        $svg = [];
        $svg[] = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$dimension} {$dimension}\" width=\"{$size}\" height=\"{$size}\" shape-rendering=\"crispEdges\">";
        $svg[] = "<rect width=\"100%\" height=\"100%\" fill=\"{$lightColor}\" rx=\"8\"/>";

        $paths = [];
        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x = ($c + $quietZone) * $moduleSize;
                    $y = ($r + $quietZone) * $moduleSize;
                    $paths[] = "M{$x},{$y}h{$moduleSize}v{$moduleSize}h-{$moduleSize}z";
                }
            }
        }

        $pathData = implode('', $paths);
        $svg[] = "<path d=\"{$pathData}\" fill=\"{$darkColor}\"/>";
        $svg[] = "</svg>";

        return implode("\n", $svg);
    }

    /**
     * Build QR matrix for ISO/IEC 18004 byte mode
     */
    public static function generateMatrix(string $text): array
    {
        self::initGf();
        $len = strlen($text);

        // Version selection (Level L):
        // V1: 17 bytes, V2: 32 bytes, V3: 53 bytes, V4: 78 bytes, V5: 106 bytes, V6: 134 bytes, V7: 154 bytes, V8: 192 bytes
        $version = 1;
        $capacities = [
            1 => 17, 2 => 32, 3 => 53, 4 => 78, 5 => 106,
            6 => 134, 7 => 154, 8 => 192, 9 => 230, 10 => 271
        ];

        foreach ($capacities as $v => $cap) {
            if ($len <= $cap) {
                $version = $v;
                break;
            }
        }
        if ($version > 10) {
            $version = 10;
        }

        $moduleCount = 17 + ($version * 4);
        $matrix = array_fill(0, $moduleCount, array_fill(0, $moduleCount, null));
        $reserved = array_fill(0, $moduleCount, array_fill(0, $moduleCount, false));

        // 1. Finder patterns
        self::addFinderPattern($matrix, $reserved, 0, 0);
        self::addFinderPattern($matrix, $reserved, $moduleCount - 7, 0);
        self::addFinderPattern($matrix, $reserved, 0, $moduleCount - 7);

        // 2. Alignment patterns for Version >= 2
        self::addAlignmentPatterns($matrix, $reserved, $version);

        // 3. Timing patterns
        for ($i = 8; $i < $moduleCount - 8; $i++) {
            $val = ($i % 2 === 0);
            if ($matrix[6][$i] === null) {
                $matrix[6][$i] = $val;
                $reserved[6][$i] = true;
            }
            if ($matrix[$i][6] === null) {
                $matrix[$i][6] = $val;
                $reserved[$i][6] = true;
            }
        }

        // 4. Dark module & format reservations
        $matrix[4 * $version + 9][8] = true;
        $reserved[4 * $version + 9][8] = true;

        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = 0; $i < 8; $i++) {
            $reserved[8][$moduleCount - 1 - $i] = true;
            $reserved[$moduleCount - 1 - $i][8] = true;
        }

        // 5. Encode data codewords & Reed-Solomon EC
        $codewords = self::encodeData($text, $version);

        // 6. Place data bits in zigzag order
        self::placeDataBits($matrix, $reserved, $codewords, $moduleCount);

        // 7. Apply standard mask 0 ((row + col) % 2 == 0)
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if (!$reserved[$r][$c]) {
                    if (($r + $c) % 2 === 0) {
                        $matrix[$r][$c] = !$matrix[$r][$c];
                    }
                }
            }
        }

        // 8. Format info for Mask 0, Error Level L: bits = 0x77c4
        // Mask 0 (000) ^ Level L (01) = 01000...
        self::applyFormatInfo($matrix, $moduleCount, 0x77c4);

        return $matrix;
    }

    private static function initGf(): void
    {
        if (self::$gfExp !== null) {
            return;
        }
        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= self::GF256_POLY;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
    }

    private static function addFinderPattern(array &$m, array &$res, int $x, int $y): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $row = $y + $r;
                $col = $x + $c;
                if ($row < 0 || $row >= count($m) || $col < 0 || $col >= count($m)) {
                    continue;
                }
                $isDark = ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6)) ||
                          ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6)) ||
                          ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $m[$row][$col] = $isDark;
                $res[$row][$col] = true;
            }
        }
    }

    private static function addAlignmentPatterns(array &$m, array &$res, int $version): void
    {
        if ($version < 2) return;
        $alignLocations = [
            2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
            6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]
        ];
        $coords = $alignLocations[$version] ?? [6, 18];

        foreach ($coords as $r) {
            foreach ($coords as $c) {
                if ($res[$r][$c]) continue;
                for ($dr = -2; $dr <= 2; $dr++) {
                    for ($dc = -2; $dc <= 2; $dc++) {
                        $isDark = (abs($dr) === 2 || abs($dc) === 2 || ($dr === 0 && $dc === 0));
                        $m[$r + $dr][$c + $dc] = $isDark;
                        $res[$r + $dr][$c + $dc] = true;
                    }
                }
            }
        }
    }

    private static function encodeData(string $text, int $version): array
    {
        // Total data codewords capacity (Level L)
        $totalCodewords = [
            1 => 26, 2 => 44, 3 => 70, 4 => 100, 5 => 134,
            6 => 172, 7 => 196, 8 => 242, 9 => 292, 10 => 346
        ];
        $ecCodewordsPerBlock = [
            1 => 7, 2 => 10, 3 => 15, 4 => 20, 5 => 26,
            6 => 18, 7 => 20, 8 => 24, 9 => 30, 10 => 18
        ];
        $ecBlocks = [
            1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1,
            6 => 2, 7 => 2, 8 => 2, 9 => 2, 10 => 4
        ];

        $tot = $totalCodewords[$version] ?? 26;
        $ecPerBlock = $ecCodewordsPerBlock[$version] ?? 7;
        $numBlocks = $ecBlocks[$version] ?? 1;
        $dataCodewordsCount = $tot - ($ecPerBlock * $numBlocks);

        // Bit stream construction: Byte Mode indicator '0100'
        $bits = '0100';
        $charCountBits = ($version <= 9) ? 8 : 16;
        $bits .= str_pad(decbin(strlen($text)), $charCountBits, '0', STR_PAD_LEFT);

        for ($i = 0; $i < strlen($text); $i++) {
            $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }

        // Terminator & padding to multiple of 8
        $maxBits = $dataCodewordsCount * 8;
        if (strlen($bits) < $maxBits) {
            $bits .= substr('0000', 0, min(4, $maxBits - strlen($bits)));
        }
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }

        // Byte padding with 0xEC and 0x11
        $padBytes = ['11101100', '00010001'];
        $padIdx = 0;
        while (strlen($bits) < $maxBits) {
            $bits .= $padBytes[$padIdx % 2];
            $padIdx++;
        }

        $dataCodewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataCodewords[] = bindec(substr($bits, $i, 8));
        }

        // Compute Reed-Solomon Error Correction Codewords per block
        $blockSize = (int)floor($dataCodewordsCount / $numBlocks);
        $finalCodewords = [];
        $blocksData = [];
        $blocksEc = [];

        for ($b = 0; $b < $numBlocks; $b++) {
            $bData = array_slice($dataCodewords, $b * $blockSize, $blockSize);
            $blocksData[$b] = $bData;
            $blocksEc[$b] = self::computeReedSolomon($bData, $ecPerBlock);
        }

        // Interleave data codewords
        for ($i = 0; $i < $blockSize; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $blocksData[$b][$i];
            }
        }
        // Interleave error correction codewords
        for ($i = 0; $i < $ecPerBlock; $i++) {
            for ($b = 0; $b < $numBlocks; $b++) {
                $finalCodewords[] = $blocksEc[$b][$i];
            }
        }

        return $finalCodewords;
    }

    private static function computeReedSolomon(array $data, int $ecCount): array
    {
        $genPoly = self::getGeneratorPoly($ecCount);
        $poly = array_merge($data, array_fill(0, $ecCount, 0));

        for ($i = 0; $i < count($data); $i++) {
            $coef = $poly[$i];
            if ($coef !== 0) {
                $logCoef = self::$gfLog[$coef];
                for ($j = 0; $j < count($genPoly); $j++) {
                    $poly[$i + $j] ^= self::$gfExp[($logCoef + $genPoly[$j]) % 255];
                }
            }
        }

        return array_slice($poly, count($data));
    }

    private static function getGeneratorPoly(int $ecCount): array
    {
        $poly = [0];
        for ($i = 0; $i < $ecCount; $i++) {
            $next = [0, 0];
            $next[0] = $poly[0];
            $res = [];
            for ($j = 0; $j < count($poly); $j++) {
                $res[$j] = ($j === 0) ? 0 : $poly[$j - 1];
                $term = ($poly[$j] + $i) % 255;
                $val = self::$gfExp[$term];
                if (isset($res[$j])) {
                    $val ^= self::$gfExp[self::$gfLog[$res[$j]] ?? 0];
                }
            }
        }
        // Static table for common generator polynomials
        $table = [
            7 => [0, 87, 229, 146, 149, 238, 102, 21],
            10 => [0, 251, 67, 46, 61, 118, 70, 64, 94, 32, 45],
            15 => [0, 29, 196, 111, 163, 112, 74, 10, 105, 139, 99, 169, 117, 196, 171, 0],
            18 => [0, 215, 234, 158, 94, 184, 97, 120, 170, 79, 187, 152, 148, 252, 179, 5, 98, 96, 153],
            20 => [0, 17, 60, 79, 50, 61, 163, 26, 187, 202, 180, 221, 225, 83, 239, 156, 164, 212, 212, 188, 190],
            24 => [0, 229, 121, 135, 48, 211, 117, 251, 126, 159, 180, 169, 152, 192, 226, 228, 218, 111, 0, 117, 232, 87, 96, 227, 21],
            26 => [0, 173, 125, 158, 2, 103, 182, 118, 17, 145, 201, 111, 28, 165, 53, 161, 21, 245, 142, 13, 102, 48, 227, 153, 145, 218, 70],
            30 => [0, 212, 246, 77, 73, 195, 192, 75, 98, 247, 119, 168, 217, 77, 203, 110, 111, 81, 137, 84, 144, 101, 203, 130, 239, 44, 92, 17, 172, 229, 23]
        ];
        return $table[$ecCount] ?? $table[7];
    }

    private static function placeDataBits(array &$m, array &$res, array $codewords, int $size): void
    {
        $bits = '';
        foreach ($codewords as $c) {
            $bits .= str_pad(decbin($c), 8, '0', STR_PAD_LEFT);
        }

        $bitIdx = 0;
        $totalBits = strlen($bits);
        $col = $size - 1;
        $goingUp = true;

        while ($col > 0) {
            if ($col === 6) {
                $col--; // Skip vertical timing pattern
            }

            $rows = $goingUp ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $row) {
                for ($cOffset = 0; $cOffset < 2; $cOffset++) {
                    $c = $col - $cOffset;
                    if (!$res[$row][$c]) {
                        $m[$row][$c] = ($bitIdx < $totalBits) ? ($bits[$bitIdx] === '1') : false;
                        $bitIdx++;
                    }
                }
            }

            $col -= 2;
            $goingUp = !$goingUp;
        }
    }

    private static function applyFormatInfo(array &$m, int $size, int $formatBits): void
    {
        $bits = str_pad(decbin($formatBits), 15, '0', STR_PAD_LEFT);

        // Top-left format info
        $formatIndicesTL = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8]
        ];
        for ($i = 0; $i < 15; $i++) {
            $val = ($bits[$i] === '1');
            [$r, $c] = $formatIndicesTL[$i];
            $m[$r][$c] = $val;
        }

        // Split format info around top-right and bottom-left
        for ($i = 0; $i < 7; $i++) {
            $m[$size - 1 - $i][8] = ($bits[$i] === '1');
        }
        for ($i = 7; $i < 15; $i++) {
            $m[8][$size - 15 + $i] = ($bits[$i] === '1');
        }
    }
}

