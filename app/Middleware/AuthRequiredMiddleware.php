<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Core\AppSettings;
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
    private const ADMIN_SESSION_KEY = 'admin_account';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private AppSettings $appSettings,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!$this->isAuthEnabled()) {
            return $handler->handle($request);
        }

        $path = $this->normalizedAppPath($request);

        if ($this->isPublicPath($path, $request->getMethod())) {
            return $handler->handle($request);
        }

        if (!$this->requiresAuth($path)) {
            return $handler->handle($request);
        }

        if ($this->isAdminSession()) {
            return $handler->handle($request);
        }

        $location = $this->appBasePrefix() . '/admin/login';

        return $this->responseFactory
            ->createResponse(302)
            ->withHeader('Location', $location);
    }

    private function isAuthEnabled(): bool
    {
        $features = $this->appSettings->get('features');

        return (bool) ($features['customer_auth_enabled'] ?? true);
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
            || ($path === '/account/logout' && strtoupper($method) === 'GET')
            || $path === '/admin/login'
            || ($path === '/admin/logout' && strtoupper($method) === 'GET')
            || $path === '/'
            || $path === '/customers';
    }

    private function requiresAuth(string $path): bool
    {
        $protectedPrefixes = [
            '/dashboard',
            '/notifications',
            '/rfid/products',
            '/api/fan-response',
        ];

        foreach ($protectedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function isAdminSession(): bool
    {
        $account = $_SESSION[self::ADMIN_SESSION_KEY] ?? null;
        if (!is_array($account) || empty($account['id'])) {
            return false;
        }
        $email = mb_strtolower(trim((string) ($account['email'] ?? '')));
        $allowed = (array) $this->appSettings->get('admin_auth')['emails'];
        $normalized = array_map(static fn ($item): string => mb_strtolower(trim((string) $item)), $allowed);

        return in_array($email, $normalized, true);
    }
}
