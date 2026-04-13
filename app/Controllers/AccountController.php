<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CustomerAccountModel;
use DI\Container;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Phase 3 customer self-service accounts: register, log in, points, purchase history (DB-backed).
 */
class AccountController extends BaseController
{
    private const SESSION_KEY = 'customer_account';
    private const AUTH_TEMP_DISABLED = false;

    public function __construct(
        Container $container,
        private CustomerAccountModel $customer_accounts,
    ) {
        parent::__construct($container);
    }

    public function loginForm(Request $request, Response $response, array $args): Response
    {
        if (self::AUTH_TEMP_DISABLED) {
            return $this->authDisabledResponse($response);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($_SESSION[self::SESSION_KEY]['id'])) {
            return $this->redirect($request, $response, 'account.dashboard');
        }

        $query = $request->getQueryParams();

        return $this->render($response, 'account/login.php', [
            'data' => [
                'pageTitle' => 'Log in',
                'current_section' => 'account',
                'current_page' => 'account_login',
                'error' => null,
                'success' => $this->bannerFromQuery($query['msg'] ?? null, 'login'),
            ],
        ]);
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        if (self::AUTH_TEMP_DISABLED) {
            return $this->authDisabledResponse($response);
        }

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
                    'current_page' => 'account_login',
                    'error' => 'Please enter your email and password.',
                    'success' => null,
                ],
            ]);
        }

        try {
            $row = $this->customer_accounts->findByEmailWithCredentials($email);
        } catch (PDOException) {
            return $this->render($response, 'account/login.php', [
                'data' => [
                    'pageTitle' => 'Log in',
                    'current_section' => 'account',
                    'current_page' => 'account_login',
                    'error' => $this->dbSetupMessage(),
                    'success' => null,
                ],
            ]);
        }

        if ($row === false || !$this->customer_accounts->isPasswordValid($password, (string) $row['password_hash'])) {
            return $this->render($response, 'account/login.php', [
                'data' => [
                    'pageTitle' => 'Log in',
                    'current_section' => 'account',
                    'current_page' => 'account_login',
                    'error' => 'Invalid email or password.',
                    'success' => null,
                ],
            ]);
        }

        $_SESSION[self::SESSION_KEY] = $this->sessionFromCustomerRow($row);

        return $this->redirect($request, $response, 'account.dashboard', [], ['msg' => 'logged_in']);
    }

    public function registerForm(Request $request, Response $response, array $args): Response
    {
        if (self::AUTH_TEMP_DISABLED) {
            return $this->authDisabledResponse($response);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($_SESSION[self::SESSION_KEY]['id'])) {
            return $this->redirect($request, $response, 'account.dashboard');
        }

        return $this->render($response, 'account/register.php', [
            'data' => [
                'pageTitle' => 'Register',
                'current_section' => 'account',
                'current_page' => 'account_register',
                'error' => null,
            ],
        ]);
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        if (self::AUTH_TEMP_DISABLED) {
            return $this->authDisabledResponse($response);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = $request->getParsedBody() ?? [];
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $password2 = (string) ($body['password_confirm'] ?? '');
        [$first, $last] = $this->splitCustomerName(trim((string) ($body['first_name'] ?? '')));
        $phone = trim((string) ($body['phone'] ?? ''));

        $error = $this->validateRegistrationInput($first, $last, $email, $password, $password2, $phone);
        if ($error !== null) {
            return $this->render($response, 'account/register.php', [
                'data' => [
                    'pageTitle' => 'Register',
                    'current_section' => 'account',
                    'current_page' => 'account_register',
                    'error' => $error,
                    'form' => $body,
                ],
            ]);
        }

        try {
            if ($this->customer_accounts->emailExists($email)) {
                return $this->render($response, 'account/register.php', [
                    'data' => [
                        'pageTitle' => 'Register',
                        'current_section' => 'account',
                        'current_page' => 'account_register',
                        'error' => 'An account with this email already exists.',
                        'form' => $body,
                    ],
                ]);
            }

            $id = $this->customer_accounts->createAccount([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'password' => $password,
                'phone' => $phone !== '' ? $phone : null,
            ]);
        } catch (PDOException) {
            return $this->render($response, 'account/register.php', [
                'data' => [
                    'pageTitle' => 'Register',
                    'current_section' => 'account',
                    'current_page' => 'account_register',
                    'error' => $this->dbSetupMessage(),
                    'form' => $body,
                ],
            ]);
        }

        $fresh = $this->customer_accounts->findById($id);
        if ($fresh !== false) {
            $_SESSION[self::SESSION_KEY] = $this->sessionFromCustomerRow($fresh);
        }

        return $this->redirect($request, $response, 'account.dashboard', [], ['msg' => 'registered']);
    }

    public function logout(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY]);

        return $this->redirect($request, $response, 'account.login.form', [], ['msg' => 'logged_out']);
    }

    public function dashboard(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionAccount = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($sessionAccount) || !isset($sessionAccount['id'])) {
            if (!self::AUTH_TEMP_DISABLED) {
                return $this->redirect($request, $response, 'account.login.form');
            }

            $query = $request->getQueryParams();
            $previewCustomerId = max(1, (int) ($query['customer_id'] ?? 1));
            $previewAccount = $this->customer_accounts->findById($previewCustomerId);
            if ($previewAccount === false) {
                return $this->render($response, 'account/dashboard.php', [
                    'data' => [
                        'pageTitle' => 'My account',
                        'current_section' => 'account',
                        'current_page' => 'account',
                        'error' => 'No preview customer account found. Try /account?customer_id=1',
                        'account' => [],
                        'history' => [],
                        'success' => null,
                    ],
                ]);
            }

            $sessionAccount = $this->sessionFromCustomerRow($previewAccount);
        }

        $customerId = (int) $sessionAccount['id'];

        try {
            $account = $this->customer_accounts->findById($customerId);
        } catch (PDOException) {
            return $this->render($response, 'account/dashboard.php', [
                'data' => [
                    'pageTitle' => 'My account',
                    'current_section' => 'account',
                    'current_page' => 'account',
                    'error' => $this->dbSetupMessage(),
                    'account' => $sessionAccount,
                    'history' => [],
                    'success' => null,
                ],
            ]);
        }

        if ($account === false) {
            unset($_SESSION[self::SESSION_KEY]);

            return $this->redirect($request, $response, 'account.login.form');
        }

        $_SESSION[self::SESSION_KEY] = $this->sessionFromCustomerRow($account);

        $query = $request->getQueryParams();
        $history = [];
        try {
            $history = $this->customer_accounts->listPurchasesForCustomer($customerId);
        } catch (PDOException) {
            // Table missing — show dashboard with DB error
            return $this->render($response, 'account/dashboard.php', [
                'data' => [
                    'pageTitle' => 'My account',
                    'current_section' => 'account',
                    'current_page' => 'account',
                    'error' => $this->dbSetupMessage(),
                    'account' => $this->sessionFromCustomerRow($account),
                    'history' => [],
                    'success' => null,
                ],
            ]);
        }

        return $this->render($response, 'account/dashboard.php', [
            'data' => [
                'pageTitle' => 'My account',
                'current_section' => 'account',
                'current_page' => 'account',
                'account' => $this->sessionFromCustomerRow($account),
                'history' => $history,
                'error' => null,
                'success' => $this->bannerFromQuery($query['msg'] ?? null, 'dashboard'),
            ],
        ]);
    }

    public function receipt(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sessionAccount = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($sessionAccount) || !isset($sessionAccount['id'])) {
            return $this->redirect($request, $response, 'account.login.form');
        }

        $purchaseId = (int) ($args['id'] ?? 0);
        if ($purchaseId < 1) {
            return $this->redirect($request, $response, 'account.dashboard');
        }

        $customerId = (int) $sessionAccount['id'];

        try {
            $detail = $this->customer_accounts->getPurchaseDetailForCustomer($purchaseId, $customerId);
        } catch (PDOException) {
            return $this->render($response, 'account/receipt.php', [
                'data' => [
                    'pageTitle' => 'Receipt',
                    'current_section' => 'account',
                    'current_page' => 'account_receipt',
                    'error' => $this->dbSetupMessage(),
                    'detail' => null,
                ],
            ]);
        }

        if ($detail === null) {
            return $this->redirect($request, $response, 'account.dashboard', [], ['msg' => 'receipt_missing']);
        }

        return $this->render($response, 'account/receipt.php', [
            'data' => [
                'pageTitle' => 'Receipt',
                'current_section' => 'account',
                'current_page' => 'account_receipt',
                'error' => null,
                'detail' => $detail,
                'account' => $this->sessionFromCustomerRow(
                    $this->customer_accounts->findById($customerId) ?: $sessionAccount
                ),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, first_name: string, last_name: string, email: string, membership_number: string, points_total: int, phone?: string|null}
     */
    private function sessionFromCustomerRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'first_name' => (string) $row['first_name'],
            'last_name' => (string) $row['last_name'],
            'email' => (string) $row['email'],
            'membership_number' => (string) $row['membership_number'],
            'points_total' => (int) $row['points_total'],
            'phone' => isset($row['phone']) ? $row['phone'] : null,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitCustomerName(string $fullName): array
    {
        if ($fullName === '') {
            return ['', ''];
        }
        $parts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY);
        $first = $parts[0] ?? '';
        $last = isset($parts[1]) ? trim((string) $parts[1]) : '';

        return [$first, $last];
    }

    private function validateRegistrationInput(
        string $first,
        string $last,
        string $email,
        string $password,
        string $passwordConfirm,
        string $phone,
    ): ?string {
        if ($first === '') {
            return 'Customer name is required.';
        }
        if ($phone === '') {
            return 'Telephone is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters.';
        }
        if ($password !== $passwordConfirm) {
            return 'Passwords do not match.';
        }

        return null;
    }

    private function dbSetupMessage(): string
    {
        return 'Customer accounts are not ready yet. Ensure the customer_accounts tables exist in your database.';
    }

    private function authDisabledResponse(Response $response): Response
    {
        $view = $this->render($response, 'account/auth-disabled.php', [
            'data' => [
                'pageTitle' => 'Customer portal unavailable',
                'message' => 'Login and registration are temporarily disabled. Please try again later.',
            ],
        ]);

        return $view->withStatus(503);
    }

    private function bannerFromQuery(?string $msg, string $context): ?string
    {
        return match ($msg) {
            'registered' => 'Welcome! Your membership account is active.',
            'logged_in' => $context === 'dashboard' ? 'Signed in successfully.' : null,
            'logged_out' => $context === 'login' ? 'You have been signed out.' : null,
            'receipt_missing' => $context === 'dashboard' ? 'That receipt could not be found.' : null,
            default => null,
        };
    }
}
