<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;

class PurchaseModel extends BaseModel
{
    public function __construct(PDOService $pdo_service)
    {
        parent::__construct($pdo_service);
    }

    public function getOnePurchase($id)
    {
        return $this->selectOne('SELECT * FROM `purchase` WHERE id = :id', ['id' => $id]);
    }

    public function getPurchasesByCustomer($customer_id)
    {
        $sql = 'SELECT * FROM `purchase` WHERE customer_id = :customer_id ORDER BY purchase_date DESC';

        return $this->selectAll($sql, ['customer_id' => $customer_id]);
    }

    public function createPurchase(array $data)
    {
        $this->execute(
            'INSERT INTO `purchase` (customer_id, total_amount, points_earned, purchase_date, payment_method, receipt_sent)
             VALUES (:customer_id, :total_amount, :points_earned, :purchase_date, :payment_method, :receipt_sent)',
            [
                'customer_id'    => $data['customer_id'] ?? null,
                'total_amount'   => $data['total_amount'],
                'points_earned'  => $data['points_earned'] ?? 0,
                'purchase_date'  => $data['purchase_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'receipt_sent'   => (int) ($data['receipt_sent'] ?? false),
            ]
        );

        return $this->lastInsertId();
    }

    public function addPurchaseItem(array $item)
    {
        $this->execute(
            'INSERT INTO `purchase_item` (purchase_id, product_id, quantity, unit_price, subtotal)
             VALUES (:purchase_id, :product_id, :quantity, :unit_price, :subtotal)',
            [
                'purchase_id' => $item['purchase_id'],
                'product_id'  => $item['product_id'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'subtotal'    => $item['subtotal'],
            ]
        );
    }

    public function getPurchaseItems($purchase_id)
    {
        $sql = 'SELECT pi.*, p.name AS product_name, p.category
                FROM `purchase_item` pi
                JOIN `product` p ON p.id = pi.product_id
                WHERE pi.purchase_id = :purchase_id';

        return $this->selectAll($sql, ['purchase_id' => $purchase_id]);
    }

    public function getReceipt($purchase_id)
    {
        $sql = 'SELECT * FROM `receipt` WHERE purchase_id = :purchase_id';

        return $this->selectAll($sql, ['purchase_id' => $purchase_id]);
    }

    public function markReceiptSent($purchase_id)
    {
        $sql = 'UPDATE `purchase` SET receipt_sent = TRUE WHERE id = :id';

        return $this->execute($sql, ['id' => $purchase_id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSalesByProductReport(?string $from, ?string $to, ?int $categoryId = null): array
    {
        $sql = 'SELECT p.id AS product_id, p.name AS product_name,
                       COALESCE(SUM(pi.quantity), 0) AS sold_qty,
                       COALESCE(SUM(pi.subtotal), 0) AS sales_value
                FROM product p
                LEFT JOIN purchase_item pi ON pi.product_id = p.id
                LEFT JOIN purchase pu ON pu.id = pi.purchase_id
                WHERE 1=1';
        $params = [];

        if ($from !== null && $from !== '') {
            $sql .= ' AND (pu.purchase_date IS NULL OR DATE(pu.purchase_date) >= :from)';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND (pu.purchase_date IS NULL OR DATE(pu.purchase_date) <= :to)';
            $params['to'] = $to;
        }
        if ($categoryId !== null && $categoryId > 0) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }

        $sql .= ' GROUP BY p.id, p.name ORDER BY sold_qty DESC, p.name ASC';

        return $this->selectAll($sql, $params);
    }

    public function getSalesTotalForRange(?string $from, ?string $to): float
    {
        $sql = 'SELECT COALESCE(SUM(total_amount), 0) AS total_sales FROM purchase WHERE 1=1';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(purchase_date) <= :to';
            $params['to'] = $to;
        }
        $row = $this->selectOne($sql, $params);

        return (float) ($row['total_sales'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSalesTrendByDay(?string $from, ?string $to): array
    {
        $sql = 'SELECT DATE(purchase_date) AS day_key,
                       COALESCE(SUM(total_amount), 0) AS sales_total
                FROM purchase
                WHERE 1=1';
        $params = [];
        if ($from !== null && $from !== '') {
            $sql .= ' AND DATE(purchase_date) >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND DATE(purchase_date) <= :to';
            $params['to'] = $to;
        }
        $sql .= ' GROUP BY DATE(purchase_date) ORDER BY DATE(purchase_date) ASC';

        return $this->selectAll($sql, $params);
    }

    /**
     * @return array{total_customers: int, new_customers: int, returning_customers: int}
     */
    public function getCustomerActivitySummary(?string $from, ?string $to): array
    {
        $sql = 'SELECT
                    COUNT(DISTINCT p.customer_id) AS total_customers,
                    COALESCE(SUM(CASE WHEN firsts.first_purchase BETWEEN :from_new AND :to_new THEN 1 ELSE 0 END), 0) AS new_customers
                FROM (
                    SELECT customer_id, MIN(purchase_date) AS first_purchase
                    FROM purchase
                    WHERE customer_id IS NOT NULL
                    GROUP BY customer_id
                ) firsts
                LEFT JOIN purchase p ON p.customer_id = firsts.customer_id
                WHERE p.customer_id IS NOT NULL AND p.purchase_date BETWEEN :from_range AND :to_range';

        $start = ($from !== null && $from !== '') ? $from : '1970-01-01';
        $end = ($to !== null && $to !== '') ? $to : date('Y-m-d');
        $params = [
            'from_new' => $start . ' 00:00:00',
            'to_new' => $end . ' 23:59:59',
            'from_range' => $start . ' 00:00:00',
            'to_range' => $end . ' 23:59:59',
        ];
        $row = $this->selectOne($sql, $params);
        $total = (int) ($row['total_customers'] ?? 0);
        $new = (int) ($row['new_customers'] ?? 0);

        return [
            'total_customers' => $total,
            'new_customers' => $new,
            'returning_customers' => max(0, $total - $new),
        ];
    }
}
