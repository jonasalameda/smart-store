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
}
