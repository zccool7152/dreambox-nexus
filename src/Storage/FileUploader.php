<?php
declare(strict_types=1);

namespace Nexus\Storage;

use Nexus\Core\Request;
use RuntimeException;
use InvalidArgumentException;

/**
 * Hardened File Upload Pipeline
 *
 * Implements MIME magic-byte sniffing (never trusting client headers),
 * UUID randomized filenames, and SVG script neutralization.
 */
class FileUploader
{
    private Storage $storage;
    private array $allowedMimes;
    private int $maxBytes;

    public function __construct(
        Storage $storage,
        array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        int $maxBytes = 10485760 // 10MB
    ) {
        $this->storage = $storage;
        $this->allowedMimes = $allowedMimes;
        $this->maxBytes = $maxBytes;
    }

    public function upload(array $fileArray, bool $isPublic = false): array
    {
        if (!isset($fileArray['tmp_name']) || !is_uploaded_file($fileArray['tmp_name'])) {
            throw new InvalidArgumentException("No valid uploaded file found.");
        }

        if (($fileArray['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Upload failed with error code: " . $fileArray['error']);
        }

        $size = (int)($fileArray['size'] ?? 0);
        if ($size > $this->maxBytes) {
            throw new InvalidArgumentException("File exceeds maximum allowed size of {$this->maxBytes} bytes.");
        }

        $tmpPath = $fileArray['tmp_name'];

        // 1. Inspect binary magic bytes using finfo_file (never trust client MIME)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!in_array($detectedMime, $this->allowedMimes, true)) {
            throw new InvalidArgumentException("Illegal file MIME type detected: '{$detectedMime}'. Upload rejected.");
        }

        // 2. Derive extension from verified MIME
        $extension = match ($detectedMime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            default => 'bin',
        };

        // 3. Generate UUID filename to prevent directory traversal & overwrite attacks
        $uuid = Request::generateUuidV4();
        $storedFilename = "{$uuid}.{$extension}";

        $contents = (string)file_get_contents($tmpPath);
        if ($detectedMime === 'image/svg+xml') {
            $contents = self::sanitizeSvg($contents);
        }

        // 4. Save to isolated storage outside web root
        $savedPath = $this->storage->put($storedFilename, $contents, $isPublic);

        return [
            'stored_filename' => $storedFilename,
            'original_filename' => basename((string)($fileArray['name'] ?? 'file')),
            'mime_type' => $detectedMime,
            'size' => $size,
            'path' => $savedPath,
        ];
    }

    /**
     * Neutralize dangerous scripts, external entities, event handlers, and javascript: URIs in SVG uploads.
     */
    public static function sanitizeSvg(string $svgContent): string
    {
        if (preg_match('/<!(?:ENTITY|DOCTYPE)\b[^>]*\b(?:SYSTEM|PUBLIC)\b/i', $svgContent) || stripos($svgContent, '<!ENTITY') !== false) {
            throw new InvalidArgumentException("Disallowed XML external entity declaration detected in SVG.");
        }

        // Remove executable or embedded active content elements
        $cleaned = preg_replace('/<\s*(script|foreignObject|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $svgContent) ?? $svgContent;
        $cleaned = preg_replace('/<\s*(script|foreignObject|iframe|object|embed)\b[^>]*\/\s*>/is', '', $cleaned) ?? $cleaned;

        // Strip inline event handler attributes (onload=, onclick=, onerror=, etc.)
        $cleaned = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned) ?? $cleaned;

        // Neutralize javascript:, vbscript:, and non-safe data: URIs in attributes
        $cleaned = preg_replace(
            '/\b(href|xlink:href|src|action|formaction)\s*=\s*(["\'])\s*(?:javascript:|vbscript:|data:(?!image\/(?:png|jpeg|webp);))[^"\']*\2/i',
            '$1=$2#$2',
            $cleaned
        ) ?? $cleaned;
        $cleaned = preg_replace(
            '/\b(href|xlink:href|src|action|formaction)\s*=\s*(?:javascript:|vbscript:|data:(?!image\/(?:png|jpeg|webp);))[^\s>]+/i',
            '$1="#"',
            $cleaned
        ) ?? $cleaned;

        return $cleaned;
    }
}

