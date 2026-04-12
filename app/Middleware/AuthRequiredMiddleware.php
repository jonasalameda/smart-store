<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sends unauthenticated users to the login page for every request except
 * login, register, and logout.
 */
final class AuthRequiredMiddleware implements MiddlewareInterface
{
    private const SESSION_KEY = 'customer_account';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $path = $this->normalizedAppPath($request);

        if ($this->isPublicPath($path, $request->getMethod())) {
            return $handler->handle($request);
        }

        if (!empty($_SESSION[self::SESSION_KEY]['id'])) {
            return $handler->handle($request);
        }

        $location = $this->appBasePrefix() . '/account/login';

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $location);
    }

    private function appBasePrefix(): string
    {
        $name = trim((string) APP_ROOT_DIR_NAME, '/');

        return $name === '' ? '' : '/' . $name;
    }

    /**
     * Path within the app (e.g. /account/login), leading slash, no trailing slash except root.
     */
    private function normalizedAppPath(ServerRequestInterface $request): string
    {
        $uriPath = $request->getUri()->getPath();
        $base = $this->appBasePrefix();
        if ($base !== '' && str_starts_with($uriPath, $base)) {
            $uriPath = substr($uriPath, strlen($base)) ?: '/';
        }
        $uriPath = '/' . trim($uriPath, '/');

        return $uriPath === '' ? '/' : $uriPath;
    }

    private function isPublicPath(string $path, string $method): bool
    {
        return $path === '/account/login'
            || $path === '/account/register'
            || ($path === '/account/logout' && strtoupper($method) === 'GET');
    }
}
