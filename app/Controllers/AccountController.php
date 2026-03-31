<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Phase 3 UI preview — login/register set a demo session; points and history are sample data (no accounts DB).
 */
class AccountController extends BaseController
{
    private const SESSION_KEY = 'phase3_account';

    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function loginForm(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'account/login.php', [
            'data' => [
                'pageTitle' => 'Log in',
                'current_section' => 'account',
                'error' => null,
            ],
        ]);
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = $request->getParsedBody() ?? [];
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->render($response, 'account/login.php', [
                'data' => [
                    'pageTitle' => 'Log in',
                    'current_section' => 'account',
                    'error' => 'Enter email and password (UI preview — any non-empty values work).',
                ],
            ]);
        }

        $_SESSION[self::SESSION_KEY] = [
            'first_name' => 'Demo',
            'last_name' => 'Shopper',
            'email' => $email,
            'membership_number' => 'M00000001',
            'points_total' => 128,
        ];

        return $this->redirect($request, $response, 'account.dashboard');
    }

    public function registerForm(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'account/register.php', [
            'data' => [
                'pageTitle' => 'Register',
                'current_section' => 'account',
                'error' => null,
            ],
        ]);
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = $request->getParsedBody() ?? [];
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $first = trim((string) ($body['first_name'] ?? ''));
        $last = trim((string) ($body['last_name'] ?? ''));

        if ($email === '' || $password === '' || $first === '' || $last === '') {
            return $this->render($response, 'account/register.php', [
                'data' => [
                    'pageTitle' => 'Register',
                    'current_section' => 'account',
                    'error' => 'All fields are required.',
                ],
            ]);
        }

        $_SESSION[self::SESSION_KEY] = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'membership_number' => 'M' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'points_total' => 0,
        ];

        return $this->redirect($request, $response, 'account.dashboard');
    }

    public function logout(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);

        return $this->redirect($request, $response, 'account.login.form');
    }

    public function dashboard(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $account = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($account) || ($account['email'] ?? '') === '') {
            return $this->redirect($request, $response, 'account.login.form');
        }

        $history = self::mockPurchaseHistory();

        return $this->render($response, 'account/dashboard.php', [
            'data' => [
                'pageTitle' => 'My account',
                'current_section' => 'account',
                'account' => $account,
                'history' => $history,
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mockPurchaseHistory(): array
    {
        return [
            [
                'purchased_at' => '2026-03-22 18:40:00',
                'total_amount' => 34.50,
                'points_earned' => 35,
            ],
            [
                'purchased_at' => '2026-03-15 11:05:00',
                'total_amount' => 12.99,
                'points_earned' => 13,
            ],
            [
                'purchased_at' => '2026-03-01 09:12:00',
                'total_amount' => 7.25,
                'points_earned' => 7,
            ],
        ];
    }
}
