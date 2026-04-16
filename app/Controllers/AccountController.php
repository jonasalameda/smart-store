<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CustomerAccountModel;
use App\Helpers\FlashHelper;
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

    /** Same key as {@see AdminAuthController}; used for staff dashboard access. */
    private const ADMIN_SESSION_KEY = 'admin_account';

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
            return $this->redirectPostCustomerLogin($request, $response, (string) ($_SESSION[self::SESSION_KEY]['email'] ?? ''));
        }

        $query = $request->getQueryParams();

        return $this->render($response, 'account/login.php', [
            'data' => [
                'pageTitle' => __('account.login_title'),
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
                    'pageTitle' => __('account.login_title'),
                    'current_section' => 'account',
                    'current_page' => 'account_login',
                    'error' => __('errors.enter_email_password'),
                    'success' => null,
                ],
            ]);
        }

        try {
            $row = $this->customer_accounts->findByEmailWithCredentials($email);
        } catch (PDOException) {
            return $this->render($response, 'account/login.php', [
                'data' => [
                    'pageTitle' => __('account.login_title'),
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
                    'pageTitle' => __('account.login_title'),
                    'current_section' => 'account',
                    'current_page' => 'account_login',
                    'error' => __('errors.invalid_login'),
                    'success' => null,
                ],
            ]);
        }

        $_SESSION[self::SESSION_KEY] = $this->sessionFromCustomerRow($row);
        unset($_SESSION[self::ADMIN_SESSION_KEY]);
        FlashHelper::set('success', __('flash.logged_in'));

        return $this->redirect($request, $response, 'account.dashboard');
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
            return $this->redirectPostCustomerLogin($request, $response, (string) ($_SESSION[self::SESSION_KEY]['email'] ?? ''));
        }

        return $this->render($response, 'account/register.php', [
            'data' => [
                'pageTitle' => __('account.register_title'),
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
                    'pageTitle' => __('account.register_title'),
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
                        'pageTitle' => __('account.register_title'),
                        'current_section' => 'account',
                        'current_page' => 'account_register',
                        'error' => __('errors.email_exists'),
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
                    'pageTitle' => __('account.register_title'),
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

        unset($_SESSION[self::ADMIN_SESSION_KEY]);
        FlashHelper::set('success', __('flash.registered'));

        return $this->redirect($request, $response, 'account.dashboard');
    }

    public function logout(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION[self::SESSION_KEY], $_SESSION[self::ADMIN_SESSION_KEY]);
        FlashHelper::set('info', __('flash.logged_out'));

        return $this->redirect($request, $response, 'account.login.form');
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
                        'pageTitle' => __('account.dashboard_title'),
                        'current_section' => 'account',
                        'current_page' => 'account',
                        'error' => 'No preview customer account found. Try /account?customer_id=1',
                        'account' => [],
                        'history' => [],
                        'recent_purchases' => [],
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
                    'pageTitle' => __('account.dashboard_title'),
                    'current_section' => 'account',
                    'current_page' => 'account',
                    'error' => $this->dbSetupMessage(),
                    'account' => $sessionAccount,
                    'history' => [],
                    'recent_purchases' => [],
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
            return $this->render($response, 'account/dashboard.php', [
                'data' => [
                    'pageTitle' => __('account.dashboard_title'),
                    'current_section' => 'account',
                    'current_page' => 'account',
                    'error' => $this->dbSetupMessage(),
                    'account' => $this->sessionFromCustomerRow($account),
                    'history' => [],
                    'recent_purchases' => [],
                    'success' => null,
                ],
            ]);
        }

        $recentPurchases = array_slice($history, 0, 5);

        return $this->render($response, 'account/dashboard.php', [
            'data' => [
                'pageTitle' => __('account.dashboard_title'),
                'current_section' => 'account',
                'current_page' => 'account',
                'account' => $this->sessionFromCustomerRow($account),
                'history' => $history,
                'recent_purchases' => $recentPurchases,
                'error' => null,
                'success' => $this->bannerFromQuery($query['msg'] ?? null, 'dashboard'),
            ],
        ]);
    }

    public function search(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sessionAccount = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($sessionAccount) || !isset($sessionAccount['id'])) {
            return $this->redirect($request, $response, 'account.login.form');
        }
        $customerId = (int) $sessionAccount['id'];
        $q = $request->getQueryParams();
        $from = isset($q['from']) ? trim((string) $q['from']) : '';
        $to = isset($q['to']) ? trim((string) $q['to']) : '';
        $product = isset($q['product']) ? trim((string) $q['product']) : '';

        $results = [];
        try {
            $results = $this->customer_accounts->searchPurchasesForCustomer(
                $customerId,
                $from !== '' ? $from : null,
                $to !== '' ? $to : null,
                $product !== '' ? $product : null,
            );
        } catch (PDOException) {
            $results = [];
        }

        $account = $this->customer_accounts->findById($customerId);
        $accountRow = $account !== false ? $this->sessionFromCustomerRow($account) : $sessionAccount;

        return $this->render($response, 'account/search.php', [
            'data' => [
                'pageTitle' => __('account.search_title'),
                'current_section' => 'account',
                'current_page' => 'account_search',
                'account' => $accountRow,
                'from' => $from,
                'to' => $to,
                'product' => $product,
                'results' => $results,
            ],
        ]);
    }

    public function summary(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sessionAccount = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($sessionAccount) || !isset($sessionAccount['id'])) {
            return $this->redirect($request, $response, 'account.login.form');
        }
        $customerId = (int) $sessionAccount['id'];

        $totals = ['total_spent' => 0.0, 'total_points' => 0, 'purchase_count' => 0];
        $byMonth = [];
        try {
            $t = $this->customer_accounts->getSpendingSummaryTotals($customerId);
            if ($t !== null) {
                $totals = $t;
            }
            $byMonth = $this->customer_accounts->getSpendingByMonth($customerId);
               } catch (PDOException) {
        }

        $account = $this->customer_accounts->findById($customerId);
        $accountRow = $account !== false ? $this->sessionFromCustomerRow($account) : $sessionAccount;

        return $this->render($response, 'account/summary.php', [
            'data' => [
                'pageTitle' => __('account.summary_title'),
                'current_section' => 'account',
                'current_page' => 'account_summary',
                'account' => $accountRow,
                'totals' => $totals,
                'by_month' => $byMonth,
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
                    'pageTitle' => __('receipt_page.title'),
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
                'pageTitle' => __('receipt_page.title'),
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

    private function redirectPostCustomerLogin(Request $request, Response $response, string $email): Response
    {
        return $this->redirect($request, $response, 'account.dashboard');
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
            return __('errors.name_required');
        }
        if ($phone === '') {
            return __('errors.phone_required');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return __('errors.email_invalid');
        }
        if (strlen($password) < 6) {
            return __('errors.password_short');
        }
        if ($password !== $passwordConfirm) {
            return __('errors.password_mismatch');
        }

        return null;
    }

    private function dbSetupMessage(): string
    {
        return __('errors.db_account_load');
    }

    private function authDisabledResponse(Response $response): Response
    {
        $view = $this->render($response, 'account/auth-disabled.php', [
            'data' => [
                'pageTitle' => __('portal_disabled.title'),
                'message' => __('portal_disabled.message'),
            ],
        ]);

        return $view->withStatus(503);
    }

    private function bannerFromQuery(?string $msg, string $context): ?string
    {
        return match ($msg) {
            'registered' => __('account.banner_registered'),
            'logged_in' => $context === 'dashboard' ? __('account.banner_logged_in') : null,
            'logged_out' => $context === 'login' ? __('account.banner_logged_out') : null,
            'receipt_missing' => $context === 'dashboard' ? __('account.banner_receipt_missing') : null,
            default => null,
        };
    }
}
