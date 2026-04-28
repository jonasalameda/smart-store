<?php

declare(strict_types=1);

namespace App\Domain\Services;

/**
 * Loads UI translation strings for the active session locale (en | fr).
 */
final class LocalizationService
{
    private const ALLOWED = ['en', 'fr'];

    /** @var array<string, array<string, string>> */
    private static array $cache = [];

    public function getLocale(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $locale = (string) ($_SESSION['locale'] ?? 'en');

        return in_array($locale, self::ALLOWED, true) ? $locale : 'en';
    }

    public function translate(string $key): string
    {
        $locale = $this->getLocale();
        if (!isset(self::$cache[$locale])) {
            $path = APP_BASE_DIR_PATH . '/app/Lang/' . $locale . '.php';
            /** @var array<string, string> $map */
            $map = is_file($path) ? require $path : [];
            self::$cache[$locale] = $map;
        }

        return self::$cache[$locale][$key] ?? $key;
    }
}
