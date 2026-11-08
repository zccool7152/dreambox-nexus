<?php
declare(strict_types=1);

namespace Nexus\View;

use RuntimeException;

/**
 * Lightweight Safe Server-Side Template Engine
 */
class View
{
    private string $viewsPath;
    private array $sections = [];
    private ?string $currentSection = null;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?? dirname(__DIR__, 2) . '/resources/views';
    }

    public function render(string $view, array $data = [], ?string $cspNonce = null): string
    {
        $file = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($file)) {
            throw new RuntimeException("View template [{$view}] not found at '{$file}'.");
        }

        $data['nonce'] = $cspNonce ?? \Nexus\Security\Headers::getNonce();
        $data['appBase'] = \Nexus\Core\Request::getAppBase();
        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

