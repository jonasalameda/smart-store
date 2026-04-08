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
        $sql = 'SELECT id, first_name, last_name, email, password_hash, membership_number, points_total, phone
                FROM customer_accounts WHERE email = :email LIMIT 1';

        return $this->selectOne($sql, ['email' => mb_strtolower($email)]);
    }

    public function findById(int $id): array|false
    {
        $sql = 'SELECT id, first_name, last_name, email, membership_number, points_total, phone, created_at
                FROM customer_accounts WHERE id = :id LIMIT 1';

        return $this->selectOne($sql, ['id' => $id]);
    }

    public function emailExists(string $email): bool
    {
        $n = $this->count(
            'SELECT COUNT(*) FROM customer_accounts WHERE email = :email',
            ['email' => mb_strtolower($email)]
        );

        return $n > 0;
    }

    /**
     * @param array{first_name: string, last_name: string, email: string, password: string, phone?: string|null} $data
     */
    public function createAccount(array $data): int
    {
        $email = mb_strtolower(trim($data['email']));
        $membership = $this->allocateMembershipNumber();

        $this->execute(
            'INSERT INTO customer_accounts (first_name, last_name, email, password_hash, membership_number, points_total, phone)
             VALUES (:first_name, :last_name, :email, :password_hash, :membership_number, 0, :phone)',
            [
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $email,
                'password_hash' => $this->cryptPassword($data['password']),
                'membership_number' => $membership,
                'phone' => isset($data['phone']) && $data['phone'] !== ''
                    ? preg_replace('/\D/', '', (string) $data['phone'])
                    : null,
            ]
        );

        return (int) $this->lastInsertId();
    }

    private function allocateMembershipNumber(): string
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
        $this->execute(
            'UPDATE customer_accounts SET points_total = points_total + :d WHERE id = :id',
            ['d' => $delta, 'id' => $customerAccountId]
        );
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
