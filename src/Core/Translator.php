<?php
declare(strict_types=1);

namespace Nexus\Core;

/**
 * Multilingual Localization (i18n) Engine
 */
class Translator
{
    private string $langPath;
    private string $defaultLocale;
    private array $loaded = [];

    public function __construct(?string $langPath = null, string $defaultLocale = 'en')
    {
        $this->langPath = $langPath ?? dirname(__DIR__, 2) . '/lang';
        $this->defaultLocale = $defaultLocale;
    }

    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->defaultLocale;
        [$file, $item] = $this->parseKey($key);

        $line = $this->getLine($locale, $file, $item) ?? $this->getLine('en', $file, $item) ?? $key;

        foreach ($replace as $placeholder => $value) {
            $line = str_replace(
                [':' . $placeholder, '{' . $placeholder . '}'],
                (string)$value,
                $line
            );
        }

        return $line;
    }

    private function parseKey(string $key): array
    {
        $segments = explode('.', $key, 2);
        return count($segments) === 2 ? $segments : ['messages', $key];
    }

    private function getLine(string $locale, string $file, string $item): ?string
    {
        if (!isset($this->loaded[$locale][$file])) {
            $path = "{$this->langPath}/{$locale}/{$file}.php";
            if (file_exists($path)) {
                $this->loaded[$locale][$file] = require $path;
            } else {
                $this->loaded[$locale][$file] = [];
            }
        }

        return $this->loaded[$locale][$file][$item] ?? null;
    }
}

