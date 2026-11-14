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
     * Neutralize dangerous scripts, external entities, animations, and unsafe URIs in SVG uploads
     * via strict DOMDocument allowlist filtering.
     */
    public static function sanitizeSvg(string $svgContent): string
    {
        if (preg_match('/<!(?:ENTITY|DOCTYPE)\b/i', $svgContent)) {
            throw new InvalidArgumentException("Disallowed XML external entity or DOCTYPE declaration detected in SVG.");
        }

        $allowedElements = [
            'svg', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
            'polygon', 'g', 'defs', 'lineargradient', 'radialgradient', 'stop', 'title', 'desc',
        ];
        $allowedAttributes = [
            'xmlns', 'viewBox', 'fill', 'stroke', 'stroke-width', 'stroke-linecap',
            'stroke-linejoin', 'stroke-dasharray', 'stroke-opacity', 'fill-opacity',
            'opacity', 'd', 'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
            'cx', 'cy', 'r', 'rx', 'ry', 'points', 'transform', 'id', 'class',
            'offset', 'stop-color', 'stop-opacity', 'gradientUnits', 'gradientTransform',
            'shape-rendering',
        ];

        $dom = new \DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadXML($svgContent, LIBXML_NONET | LIBXML_NOENT);
            $root = $dom->documentElement;
            if (!$loaded || $root === null || strtolower($root->localName ?: $root->nodeName) !== 'svg') {
                throw new InvalidArgumentException("Invalid or malformed SVG XML document.");
            }
            self::sanitizeSvgNode($root, $allowedElements, $allowedAttributes);
            return (string)$dom->saveXML($root);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prevErrors);
        }
    }

    private static function sanitizeSvgNode(\DOMNode $node, array $allowedElements, array $allowedAttributes): void
    {
        if (in_array($node->nodeType, [XML_COMMENT_NODE, XML_PI_NODE, XML_ENTITY_REF_NODE, XML_ENTITY_NODE, XML_DOCUMENT_TYPE_NODE], true)) {
            $node->parentNode?->removeChild($node);
            return;
        }

        if ($node instanceof \DOMElement) {
            $tagName = strtolower($node->localName ?: $node->nodeName);
            if (!in_array($tagName, $allowedElements, true)) {
                $node->parentNode?->removeChild($node);
                return;
            }

            foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                if (!in_array($attr->nodeName, $allowedAttributes, true)) {
                    $node->removeAttributeNode($attr);
                    continue;
                }

                $decoded = html_entity_decode((string)$attr->value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $normalized = strtolower((string)preg_replace('/[\x00-\x20]+/', '', $decoded));

                if (
                    str_contains($normalized, 'javascript:') ||
                    str_contains($normalized, 'vbscript:') ||
                    str_contains($normalized, 'data:') ||
                    str_contains($normalized, 'expression(')
                ) {
                    $node->removeAttributeNode($attr);
                }
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            self::sanitizeSvgNode($child, $allowedElements, $allowedAttributes);
        }
    }
}

