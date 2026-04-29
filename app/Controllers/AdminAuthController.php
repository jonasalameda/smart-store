<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CustomerAccountModel;
use App\Helpers\FlashHelper;
use DI\Container;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminAuthController extends BaseController
{
    private const SESSION_KEY = 'admin_account';

    public function __construct(
        Container $container,
        private CustomerAccountModel $customer_accounts,
    ) {
        parent::__construct($container);
    }

    public function loginForm(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($_SESSION[self::SESSION_KEY]['id'])) {
            return $this->redirect($request, $response, 'dashboard.index');
        }

        return $this->render($response, 'admin/login.php', [
            'data' => [
                'pageTitle' => __('staff.page_title'),
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
            return $this->render($response, 'admin/login.php', [
                'data' => [
                    'pageTitle' => __('staff.page_title'),
                    'error' => __('staff.error_enter_both'),
                ],
            ]);
        }

        if (!$this->isAllowedAdminEmail($email)) {
            return $this->render($response, 'admin/login.php', [
                'data' => [
                    'pageTitle' => __('staff.page_title'),
                    'error' => __('staff.error_not_allowed'),
                ],
            ]);
        }

        try {
            $row = $this->customer_accounts->findByEmailWithCredentials($email);
        } catch (PDOException) {
            $row = false;
        }
        if ($row === false || !$this->customer_accounts->isPasswordValid($password, (string) ($row['password_hash'] ?? ''))) {
            return $this->render($response, 'admin/login.php', [
                'data' => [
                    'pageTitle' => __('staff.page_title'),
                    'error' => __('staff.error_invalid'),
                ],
            ]);
        }

        $_SESSION[self::SESSION_KEY] = [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'name' => trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
        ];

        FlashHelper::set('success', __('flash.logged_in'));

        return $this->redirect($request, $response, 'dashboard.index');
    }

    public function logout(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);
        FlashHelper::set('info', __('flash.logged_out'));

        return $this->redirect($request, $response, 'admin.login.form');
    }

    private function isAllowedAdminEmail(string $email): bool
    {
        $allowed = (array) $this->settings->get('admin_auth')['emails'];
        $needle = mb_strtolower(trim($email));
        $normalized = array_map(static fn ($item): string => mb_strtolower(trim((string) $item)), $allowed);

        return in_array($needle, $normalized, true);
    }
}
