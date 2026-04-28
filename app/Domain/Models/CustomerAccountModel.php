<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PasswordTrait;
use App\Helpers\Core\PDOService;
use PDOException;

class CustomerAccountModel extends BaseModel
{
    use PasswordTrait;

    public function __construct(PDOService $pdo_service)
    {
        parent::__construct($pdo_service);
    }

    /**
     * Customer portal uses the legacy `customer` table only (CustomerID, Email, PasswordHash, …).
     * No `customer_accounts` table is required.
     */
    public function findByEmailWithCredentials(string $email): array|false
    {
        $normalizedEmail = mb_strtolower($email);
        $legacy = $this->selectOne(
            'SELECT CustomerID AS id, FirstName AS first_name, LastName AS last_name, Email AS email,
                    PasswordHash AS password_hash, MembershipNumber AS membership_number,
                    TotalPoints AS points_total, PhoneNumber AS phone, CreatedAt AS created_at
             FROM customer WHERE LOWER(Email) = :email LIMIT 1',
            ['email' => $normalizedEmail]
        );

        return $legacy === false ? false : $this->normalizeCustomerRow($legacy);
    }

    public function findById(int $id): array|false
    {
        $legacy = $this->selectOne(
            'SELECT CustomerID AS id, FirstName AS first_name, LastName AS last_name, Email AS email,
                    MembershipNumber AS membership_number, TotalPoints AS points_total,
                    PhoneNumber AS phone, CreatedAt AS created_at, PasswordHash AS password_hash
             FROM customer WHERE CustomerID = :id LIMIT 1',
            ['id' => $id]
        );

        return $legacy === false ? false : $this->normalizeCustomerRow($legacy);
    }

    public function emailExists(string $email): bool
    {
        $normalizedEmail = mb_strtolower($email);

        return $this->count(
            'SELECT COUNT(*) FROM customer WHERE LOWER(Email) = :email',
            ['email' => $normalizedEmail]
        ) > 0;
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, password: string, phone?: string|null} $data
     */
    public function createAccount(array $data): int
    {
        $email = mb_strtolower(trim($data['email']));
        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name']);
        $phone = isset($data['phone']) && $data['phone'] !== ''
            ? preg_replace('/\D/', '', (string) $data['phone'])
            : null;
        $passwordHash = $this->cryptPassword($data['password']);

        $membership = $this->allocateLegacyMembershipNumber();
        $nextId = $this->allocateNextCustomerPrimaryKey();
        $this->execute(
            'INSERT INTO customer (CustomerID, FirstName, LastName, Email, PhoneNumber, MembershipNumber, TotalPoints, PasswordHash, CreatedAt, EmailVerified)
             VALUES (:id, :fn, :ln, :email, :phone, :mem, 0, :ph, NOW(), 0)',
            [
                'id' => $nextId,
                'fn' => $firstName,
                'ln' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'mem' => $membership,
                'ph' => $passwordHash,
            ]
        );

        return $nextId;
    }

    /** Membership numbers like M100001 — unique in `customer`. */
    private function allocateLegacyMembershipNumber(): string
    {
        for ($i = 0; $i < 40; $i++) {
            $candidate = 'M' . str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
            $n = $this->count(
                'SELECT COUNT(*) FROM customer WHERE MembershipNumber = :m',
                ['m' => $candidate]
            );
            if ($n === 0) {
                return $candidate;
            }
        }

        return 'M' . str_pad((string) time() % 1_000_000, 6, '0', STR_PAD_LEFT);
    }

    private function allocateNextCustomerPrimaryKey(): int
    {
        $row = $this->selectOne('SELECT COALESCE(MAX(CustomerID), 0) AS m FROM customer');
        $max = (int) ($row['m'] ?? 0);

        return $max + 1;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPurchasesForCustomer(int $customerAccountId): array
    {
        $fromPurchase = $this->selectAll(
            'SELECT id, purchase_date AS purchased_at, total_amount, points_earned
             FROM purchase
             WHERE customer_id = :cid
             ORDER BY purchase_date DESC, id DESC',
            ['cid' => $customerAccountId]
        );
        if ($fromPurchase !== []) {
            return $fromPurchase;
        }

        try {
            return $this->selectAll(
                'SELECT id, purchased_at, total_amount, points_earned
                 FROM customer_purchases
                 WHERE customer_account_id = :cid
                 ORDER BY purchased_at DESC, id DESC',
                ['cid' => $customerAccountId]
            );
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_purchases')) {
                throw $e;
            }
        }

        return [];
    }

    /**
     * @return array{purchase: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function getPurchaseDetailForCustomer(int $purchaseId, int $customerAccountId): ?array
    {
        $purchase = $this->selectOne(
            'SELECT id, purchase_date AS purchased_at, total_amount, points_earned
             FROM purchase
             WHERE id = :pid AND customer_id = :cid',
            ['pid' => $purchaseId, 'cid' => $customerAccountId]
        );

        if ($purchase !== false) {
            $items = $this->selectAll(
                'SELECT pr.name AS product_name, pi.quantity, pi.unit_price, pi.subtotal AS line_total
                 FROM purchase_item pi
                 JOIN product pr ON pr.id = pi.product_id
                 WHERE pi.purchase_id = :pid
                 ORDER BY pi.id ASC',
                ['pid' => $purchaseId]
            );

            return ['purchase' => $purchase, 'items' => $items];
        }

        try {
            $purchase = $this->selectOne(
                'SELECT id, customer_account_id, purchased_at, total_amount, points_earned
                 FROM customer_purchases
                 WHERE id = :pid AND customer_account_id = :cid',
                ['pid' => $purchaseId, 'cid' => $customerAccountId]
            );
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_purchases')) {
                throw $e;
            }
            $purchase = false;
        }

        if ($purchase === false) {
            return null;
        }

        $items = $this->selectAll(
            'SELECT product_name, quantity, unit_price, line_total
             FROM customer_purchase_items
             WHERE purchase_id = :pid
             ORDER BY id ASC',
            ['pid' => $purchaseId]
        );

        return ['purchase' => $purchase, 'items' => $items];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchPurchasesForCustomer(int $customerId, ?string $from, ?string $to, ?string $productName): array
    {
        $sql = 'SELECT DISTINCT p.id, p.purchase_date AS purchased_at, p.total_amount, p.points_earned,
                        (SELECT COUNT(*) FROM purchase_item x WHERE x.purchase_id = p.id) AS items_count
                 FROM purchase p
                 LEFT JOIN purchase_item pi ON pi.purchase_id = p.id
                 LEFT JOIN product pr ON pr.id = pi.product_id
                 WHERE p.customer_id = :cid';
        $params = ['cid' => $customerId];

        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(p.purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(p.purchase_date) <= :to';
            $params['to'] = $to;
        }
        $name = trim((string) $productName);
        if ($name !== '') {
            $sql .= ' AND pr.name LIKE :pname';
            $params['pname'] = '%' . $name . '%';
        }

        $sql .= ' ORDER BY p.purchase_date DESC, p.id DESC';

        try {
            return $this->selectAll($sql, $params);
        } catch (PDOException) {
            return [];
        }
    }

    /**
     * @return array{total_spent: float, total_points: int, purchase_count: int}|null
     */
    public function getSpendingSummaryTotals(int $customerId): ?array
    {
        $row = $this->selectOne(
            'SELECT COALESCE(SUM(total_amount), 0) AS total_spent,
                    COALESCE(SUM(points_earned), 0) AS total_points,
                    COUNT(*) AS purchase_count
             FROM purchase
             WHERE customer_id = :cid',
            ['cid' => $customerId]
        );
        if ($row === false) {
            return null;
        }

        return [
            'total_spent' => (float) $row['total_spent'],
            'total_points' => (int) $row['total_points'],
            'purchase_count' => (int) $row['purchase_count'],
        ];
    }

    /**
     * @return array{total_spent: float, total_points: int, purchase_count: int}|null
     */
    public function getSpendingSummaryTotalsByRange(int $customerId, ?string $from, ?string $to): ?array
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) AS total_spent,
                       COALESCE(SUM(points_earned), 0) AS total_points,
                       COUNT(*) AS purchase_count
                FROM purchase
                WHERE customer_id = :cid';
        $params = ['cid' => $customerId];
        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(purchase_date) <= :to';
            $params['to'] = $to;
        }
        $row = $this->selectOne($sql, $params);
        if ($row === false) {
            return null;
        }

        return [
            'total_spent' => (float) $row['total_spent'],
            'total_points' => (int) $row['total_points'],
            'purchase_count' => (int) $row['purchase_count'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSpendingByMonth(int $customerId): array
    {
        return $this->selectAll(
            'SELECT DATE_FORMAT(purchase_date, \'%Y-%m\') AS ym,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(total_amount), 0) AS spent
             FROM purchase
             WHERE customer_id = :cid
             GROUP BY ym
             ORDER BY ym DESC',
            ['cid' => $customerId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSpendingByMonthByRange(int $customerId, ?string $from, ?string $to): array
    {
        $sql = 'SELECT DATE_FORMAT(purchase_date, \'%Y-%m\') AS ym,
                       COUNT(*) AS cnt,
                       COALESCE(SUM(total_amount), 0) AS spent
                FROM purchase
                WHERE customer_id = :cid';
        $params = ['cid' => $customerId];
        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(purchase_date) <= :to';
            $params['to'] = $to;
        }
        $sql .= ' GROUP BY ym ORDER BY ym DESC';

        return $this->selectAll($sql, $params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getItemPurchaseInstances(int $customerId, string $productName, ?string $from, ?string $to): array
    {
        $name = trim($productName);
        if ($name === '') {
            return [];
        }

        $sql = 'SELECT p.purchase_date AS purchased_at, pr.name AS product_name,
                       pi.quantity, pi.unit_price, pi.subtotal
                FROM purchase p
                JOIN purchase_item pi ON pi.purchase_id = p.id
                JOIN product pr ON pr.id = pi.product_id
                WHERE p.customer_id = :cid AND pr.name LIKE :needle';
        $params = ['cid' => $customerId, 'needle' => '%' . $name . '%'];
        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(p.purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(p.purchase_date) <= :to';
            $params['to'] = $to;
        }
        $sql .= ' ORDER BY p.purchase_date DESC, pi.id DESC';

        return $this->selectAll($sql, $params);
    }

    public function addPoints(int $customerAccountId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }
        $this->execute(
            'UPDATE customer SET TotalPoints = TotalPoints + :d WHERE CustomerID = :id',
            ['d' => $delta, 'id' => $customerAccountId]
        );
    }

    /**
     * @param array<string, mixed> $row Row with snake_case aliases from SELECT AS, or legacy name/total_points keys
     * @return array<string, mixed>
     */
    private function normalizeCustomerRow(array $row): array
    {
        $first = (string) ($row['first_name'] ?? '');
        $last = (string) ($row['last_name'] ?? '');
        if ($first === '' && isset($row['name'])) {
            $parts = preg_split('/\s+/', trim((string) $row['name']), 2, PREG_SPLIT_NO_EMPTY);
            $first = $parts[0] ?? '';
            $last = isset($parts[1]) ? trim((string) $parts[1]) : '';
        }

        return [
            'id' => (int) ($row['id'] ?? $row['CustomerID'] ?? 0),
            'first_name' => $first,
            'last_name' => $last,
            'email' => (string) ($row['email'] ?? $row['Email'] ?? ''),
            'password_hash' => (string) ($row['password_hash'] ?? $row['PasswordHash'] ?? ''),
            'membership_number' => (string) ($row['membership_number'] ?? $row['MembershipNumber'] ?? ''),
            'points_total' => (int) ($row['points_total'] ?? $row['total_points'] ?? $row['TotalPoints'] ?? 0),
            'phone' => $row['phone'] ?? $row['PhoneNumber'] ?? null,
            'created_at' => $row['created_at'] ?? $row['CreatedAt'] ?? null,
        ];
    }

    private function isMissingTableException(PDOException $e, string $table): bool
    {
        $message = mb_strtolower($e->getMessage());
        $tableLower = mb_strtolower($table);

        if (!str_contains($message, $tableLower)) {
            return false;
        }

        return str_contains($message, 'doesn\'t exist')
            || str_contains($message, "doesn't exist")
            || str_contains($message, "n'existe pas")
            || str_contains($message, 'nexiste pas')
            || str_contains($message, 'no such table')
            || str_contains($message, 'undefined table')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, '1146')
            || str_contains($message, '42s02');
    }

    /**
     * Called after a successful checkout: one receipt row, line items, and loyalty points.
     *
     * @param list<array{product_name: string, quantity: int, unit_price: float, line_total: float}> $lines
     */
    public function recordPurchase(int $customerAccountId, float $totalAmount, int $pointsEarned, array $lines): int
    {
        $this->beginTransaction();
        try {
            $this->execute(
                'INSERT INTO customer_purchases (customer_account_id, total_amount, points_earned)
                 VALUES (:cid, :total, :pts)',
                [
                    'cid' => $customerAccountId,
                    'total' => round($totalAmount, 2),
                    'pts' => $pointsEarned,
                ]
            );
            $purchaseId = (int) $this->lastInsertId();

            foreach ($lines as $line) {
                $this->execute(
                    'INSERT INTO customer_purchase_items (purchase_id, product_name, quantity, unit_price, line_total)
                     VALUES (:pid, :name, :qty, :up, :lt)',
                    [
                        'pid' => $purchaseId,
                        'name' => (string) $line['product_name'],
                        'qty' => max(1, (int) $line['quantity']),
                        'up' => round((float) $line['unit_price'], 2),
                        'lt' => round((float) $line['line_total'], 2),
                    ]
                );
            }

            $this->addPoints($customerAccountId, $pointsEarned);
            $this->commit();

            return $purchaseId;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
}
