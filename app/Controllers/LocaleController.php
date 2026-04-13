<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class LocaleController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function switch(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $q = $request->getQueryParams();
        $lang = strtolower((string) ($q['lang'] ?? 'en'));
        if ($lang !== 'en' && $lang !== 'fr') {
            $lang = 'en';
        }
        $_SESSION['locale'] = $lang;

        $redirect = (string) ($q['redirect'] ?? '/');
        if ($redirect === '' || !str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            $redirect = '/';
        }

        $prefix = '/' . trim((string) APP_ROOT_DIR_NAME, '/');
        $prefix = $prefix === '/' ? '' : $prefix;
        $location = $prefix . $redirect;

        return $response->withStatus(302)->withHeader('Location', $location);
    }
}
