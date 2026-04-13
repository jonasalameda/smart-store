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

    public function findByEmailWithCredentials(string $email): array|false
    {
        $normalizedEmail = mb_strtolower($email);
        try {
            $sql = 'SELECT id, first_name, last_name, email, password_hash, membership_number, points_total, phone
                    FROM customer_accounts WHERE email = :email LIMIT 1';
            return $this->selectOne($sql, ['email' => $normalizedEmail]);
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_accounts')) {
                throw $e;
            }
        }

        $legacy = $this->selectOne(
            'SELECT id, name, email, password_hash, membership_number, total_points, phone
             FROM customer WHERE email = :email LIMIT 1',
            ['email' => $normalizedEmail]
        );

        return $legacy === false ? false : $this->mapLegacyCustomerRow($legacy);
    }

    public function findById(int $id): array|false
    {
        try {
            $sql = 'SELECT id, first_name, last_name, email, membership_number, points_total, phone, created_at
                    FROM customer_accounts WHERE id = :id LIMIT 1';
            return $this->selectOne($sql, ['id' => $id]);
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_accounts')) {
                throw $e;
            }
        }

        $legacy = $this->selectOne(
            'SELECT id, name, email, membership_number, total_points, phone, created_at
             FROM customer WHERE id = :id LIMIT 1',
            ['id' => $id]
        );

        return $legacy === false ? false : $this->mapLegacyCustomerRow($legacy);
    }

    public function emailExists(string $email): bool
    {
        $normalizedEmail = mb_strtolower($email);
        try {
            $n = $this->count(
                'SELECT COUNT(*) FROM customer_accounts WHERE email = :email',
                ['email' => $normalizedEmail]
            );
            return $n > 0;
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_accounts')) {
                throw $e;
            }
        }

        $n = $this->count(
            'SELECT COUNT(*) FROM customer WHERE email = :email',
            ['email' => $normalizedEmail]
        );

        return $n > 0;
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, password: string, phone?: string|null, address?: string|null} $data
     */
    public function createAccount(array $data): int
    {
        $email = mb_strtolower(trim($data['email']));
        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name']);
        $phone = isset($data['phone']) && $data['phone'] !== ''
            ? preg_replace('/\D/', '', (string) $data['phone'])
            : null;
        $address = isset($data['address']) && trim((string) $data['address']) !== ''
            ? trim((string) $data['address'])
            : null;
        $passwordHash = $this->cryptPassword($data['password']);

        try {
            $membership = $this->allocateMembershipNumberString();
            $this->execute(
                'INSERT INTO customer_accounts (first_name, last_name, email, password_hash, membership_number, points_total, phone)
                 VALUES (:first_name, :last_name, :email, :password_hash, :membership_number, 0, :phone)',
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'membership_number' => $membership,
                    'phone' => $phone,
                ]
            );

            return (int) $this->lastInsertId();
        } catch (PDOException $e) {
            if (!$this->shouldFallbackToLegacyCustomerTable($e)) {
                throw $e;
            }
        }

        // Legacy `customer` table: membership_number is INT; id may have no AUTO_INCREMENT in some dumps.
        $membershipInt = $this->allocateNextLegacyCustomerMembershipNumber();
        $nextId = $this->allocateNextLegacyCustomerId();
        $this->execute(
            'INSERT INTO customer (id, name, email, phone, membership_number, total_points, preferred_language, address, password_hash)
             VALUES (:id, :name, :email, :phone, :membership_number, 0, :preferred_language, :address, :password_hash)',
            [
                'id' => $nextId,
                'name' => trim($firstName . ' ' . $lastName) ?: $firstName,
                'email' => $email,
                'phone' => $phone,
                'membership_number' => $membershipInt,
                'preferred_language' => 'en',
                'address' => $address,
                'password_hash' => $passwordHash,
            ]
        );

        return $nextId;
    }

    /**
     * String membership IDs for customer_accounts (VARCHAR).
     */
    private function allocateMembershipNumberString(): string
    {
        for ($i = 0; $i < 25; $i++) {
            $candidate = 'M' . str_pad((string) random_int(0, 99_999_999), 8, '0', STR_PAD_LEFT);
            $exists = $this->count(
                'SELECT COUNT(*) FROM customer_accounts WHERE membership_number = :m',
                ['m' => $candidate]
            );
            if ($exists === 0) {
                return $candidate;
            }
        }

        throw new PDOException('Could not allocate a unique membership number.');
    }

    /**
     * Next INT membership for legacy `customer` table (schema uses INT UNIQUE).
     */
    private function allocateNextLegacyCustomerMembershipNumber(): int
    {
        $row = $this->selectOne('SELECT COALESCE(MAX(membership_number), 100000) AS m FROM customer');
        $max = (int) ($row['m'] ?? 100000);

        return $max + 1;
    }

    private function allocateNextLegacyCustomerId(): int
    {
        $row = $this->selectOne('SELECT COALESCE(MAX(id), 0) AS m FROM customer');
        $max = (int) ($row['m'] ?? 0);

        return $max + 1;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPurchasesForCustomer(int $customerAccountId): array
    {
        return $this->selectAll(
            'SELECT id, purchased_at, total_amount, points_earned
             FROM customer_purchases
             WHERE customer_account_id = :cid
             ORDER BY purchased_at DESC, id DESC',
            ['cid' => $customerAccountId]
        );
    }

    /**
     * @return array{purchase: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function getPurchaseDetailForCustomer(int $purchaseId, int $customerAccountId): ?array
    {
        $purchase = $this->selectOne(
            'SELECT id, customer_account_id, purchased_at, total_amount, points_earned
             FROM customer_purchases
             WHERE id = :pid AND customer_account_id = :cid',
            ['pid' => $purchaseId, 'cid' => $customerAccountId]
        );

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

    public function addPoints(int $customerAccountId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }
        try {
            $this->execute(
                'UPDATE customer_accounts SET points_total = points_total + :d WHERE id = :id',
                ['d' => $delta, 'id' => $customerAccountId]
            );
        } catch (PDOException $e) {
            if (!$this->isMissingTableException($e, 'customer_accounts')) {
                throw $e;
            }
            $this->execute(
                'UPDATE customer SET total_points = total_points + :d WHERE id = :id',
                ['d' => $delta, 'id' => $customerAccountId]
            );
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapLegacyCustomerRow(array $row): array
    {
        $parts = preg_split('/\s+/', trim((string) ($row['name'] ?? '')), 2, PREG_SPLIT_NO_EMPTY);
        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';

        return [
            'id' => $row['id'],
            'first_name' => $first,
            'last_name' => $last,
            'email' => $row['email'] ?? '',
            'password_hash' => $row['password_hash'] ?? null,
            'membership_number' => $row['membership_number'] ?? '',
            'points_total' => $row['total_points'] ?? 0,
            'phone' => $row['phone'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    private function isMissingTableException(PDOException $e, string $table): bool
    {
        $message = mb_strtolower($e->getMessage());
        $tableLower = mb_strtolower($table);

        return str_contains($message, $tableLower)
            && (
                str_contains($message, 'doesn\'t exist')
                || str_contains($message, "doesn't exist")
                || str_contains($message, 'no such table')
                || str_contains($message, 'undefined table')
                || str_contains($message, 'base table or view not found')
            );
    }

    /**
     * Use legacy `customer` when customer_accounts is missing or the migration/schema does not match this code.
     */
    private function shouldFallbackToLegacyCustomerTable(PDOException $e): bool
    {
        if ($this->isMissingTableException($e, 'customer_accounts')) {
            return true;
        }
        $msg = mb_strtolower($e->getMessage());

        return str_contains($msg, 'customer_accounts')
            && str_contains($msg, 'unknown column');
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
