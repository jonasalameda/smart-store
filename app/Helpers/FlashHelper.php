<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Session flash messages for dismissible toasts / alerts.
 *
 * @phpstan-type FlashPayload array{type: string, message: string}
 */
final class FlashHelper
{
    private const SESSION_KEY = 'flash';

    public static function set(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return FlashPayload|null
     */
    public static function consume(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $flash = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);
        if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
            return null;
        }

        return [
            'type' => (string) $flash['type'],
            'message' => (string) $flash['message'],
        ];
    }
}
