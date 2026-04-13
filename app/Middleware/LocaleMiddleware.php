<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Starts the session when needed, applies ?lang= to $_SESSION['locale'], defaults locale to en.
 */
final class LocaleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['locale']) || !is_string($_SESSION['locale'])) {
            $_SESSION['locale'] = 'en';
        }

        $lang = $request->getQueryParams()['lang'] ?? null;
        if (is_string($lang)) {
            $lang = strtolower($lang);
            if ($lang === 'en' || $lang === 'fr') {
                $_SESSION['locale'] = $lang;
            }
        }

        return $handler->handle($request);
    }
}
