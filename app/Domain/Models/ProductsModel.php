<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;

class ProductsModel extends BaseModel
{
    public function __construct(PDOService $pdo_service)
    {
        parent::__construct($pdo_service);
    }

    public function getAllProducts()
    {
        return $this->selectAll('SELECT * FROM product');
    }

    /**
     * Catalog rows with latest on-hand stock from stock_reception.
     *
     * @return list<array<string, mixed>>
     */
    public function getProductsWithStockSummary(): array
    {
        return $this->selectAll(
            'SELECT p.*, COALESCE(latest.current_stock, 0) AS stock_qty, latest.date_received AS last_received_at
             FROM product p
             LEFT JOIN stock_reception latest ON latest.id = (
                 SELECT MAX(sr.id) FROM stock_reception sr WHERE sr.product_id = p.id
             )
             ORDER BY p.id ASC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getReceptionHistoryForProduct(int $productId): array
    {
        return $this->selectAll(
            'SELECT id, quantity_received, date_received, current_stock AS cumulative_total
             FROM stock_reception
             WHERE product_id = :pid
             ORDER BY date_received ASC, id ASC',
            ['pid' => $productId]
        );
    }

    public function getOneProduct($id)
    {
        $sql = "SELECT * FROM product WHERE id = :id";

        return $this->selectOne($sql, ['id' => $id]);
    }

    public function getProductByUPC($upc)
    {
        $sql = "SELECT * FROM product WHERE upc = :upc";
        return $this->selectOne($sql, ['upc' => $upc]);
    }

    public function getProductByEPC($epc)
    {
        $sql = "SELECT * FROM product WHERE epc = :epc";
        return $this->selectOne($sql, ['epc' => $epc]);
    }

    // public function addProduct(array $data)
    // {
    //     $this->execute(
    //         'INSERT INTO product (name, category, price, upc, epc, manufacturer, shelf_life_days)
    //          VALUES (:name, :category, :price, :upc, :epc, :manufacturer, :shelf_life_days)',
    //         [
    //             'name'           => $data['name'],
    //             'category'       => $data['category'] ?? null,
    //             'price'          => $data['price'],
    //             'upc'            => $data['upc'] ?? null,
    //             'epc'            => $data['epc'] ?? null,
    //             'manufacturer'   => $data['manufacturer'] ?? null,
    //             'shelf_life_days'=> $data['shelf_life_days'] ?? null,
    //         ]
    //     );

    //     return $this->lastInsertId();
    // }

    // public function updateProduct($id, array $data)
    // {
    //     return $this->execute(
    //         'UPDATE product SET name = :name, category = :category, price = :price,
    //          upc = :upc, epc = :epc, manufacturer = :manufacturer,
    //          shelf_life_days = :shelf_life_days WHERE id = :id',
    //         [
    //             'id'             => $id,
    //             'name'           => $data['name'],
    //             'category'       => $data['category'] ?? null,
    //             'price'          => $data['price'],
    //             'upc'            => $data['upc'] ?? null,
    //             'epc'            => $data['epc'] ?? null,
    //             'manufacturer'   => $data['manufacturer'] ?? null,
    //             'shelf_life_days'=> $data['shelf_life_days'] ?? null,
    //         ]
    //     );
    // }
   public function addProduct(array $data)
    {
        $this->execute(
            'INSERT INTO product (name, category_id, price, upc, epc, manufacturer, shelf_life_days)
            VALUES (:name, :category_id, :price, :upc, :epc, :manufacturer, :shelf_life_days)',
            [
                'name'           => $data['name'],
                'category_id'    => $data['category_id'] ?? null,
                'price'          => $data['price'],
                'upc'            => $data['upc'] ?? null,
                'epc'            => $data['epc'] ?? null,
                'manufacturer'   => $data['manufacturer'] ?? null,
                'shelf_life_days'=> $data['shelf_life_days'] ?? null,
            ]
        );

        return $this->lastInsertId();
    }

    public function updateProduct($id, array $data)
    {
        return $this->execute(
            'UPDATE product SET name = :name, category_id = :category_id, price = :price,
            upc = :upc, epc = :epc, manufacturer = :manufacturer,
            shelf_life_days = :shelf_life_days WHERE id = :id',
            [
                'id'             => $id,
                'name'           => $data['name'],
                'category_id'    => $data['category_id'] ?? null,
                'price'          => $data['price'],
                'upc'            => $data['upc'] ?? null,
                'epc'            => $data['epc'] ?? null,
                'manufacturer'   => $data['manufacturer'] ?? null,
                'shelf_life_days'=> $data['shelf_life_days'] ?? null,
            ]
        );
    }
    /**
     * Remove a product and dependent rows (stock receptions, line items) so FK constraints succeed.
     */
    public function deleteProduct(int|string $id): void
    {
        $pid = (int) $id;
        if ($pid <= 0) {
            return;
        }

        $this->beginTransaction();
        try {
            $this->execute('DELETE FROM stock_reception WHERE product_id = :id', ['id' => $pid]);
            $this->execute('DELETE FROM purchase_item WHERE product_id = :id', ['id' => $pid]);
            $this->execute('DELETE FROM product WHERE id = :id', ['id' => $pid]);
            $this->commit();
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getAllStock()
    {
        return $this->selectAll(
            "SELECT p.id, p.name, p.category, p.price,
                    COALESCE(sr.current_stock, 0) AS current_stock, sr.date_received
             FROM product p
             LEFT JOIN stock_reception sr ON sr.product_id = p.id
             ORDER BY p.id, sr.date_received DESC"
        );
    }

    public function getStockByProduct($product_id)
    {
        $sql = "SELECT * FROM stock_reception WHERE product_id = :product_id ORDER BY id DESC";
        return $this->selectAll($sql, ['product_id' => $product_id]);
    }

    public function getCurrentStockByProduct($product_id): int
    {
        $sql = "SELECT current_stock FROM stock_reception WHERE product_id = :product_id ORDER BY id DESC LIMIT 1";
        $row = $this->selectOne($sql, ['product_id' => $product_id]);
        return $row ? (int) $row['current_stock'] : 0;
    }

    public function receiveStock(array $data)
    {
        $this->execute(
            'INSERT INTO stock_reception(product_id, quantity_received, date_received, current_stock)
             VALUES (:product_id, :quantity_received, :date_received, :current_stock)',
            [
                'product_id'        => $data['product_id'],
                'quantity_received' => $data['quantity_received'],
                'date_received'     => $data['date_received'],
                'current_stock'     => $data['current_stock'],
            ]
        );

        return $this->lastInsertId();
    }

    public function updateStock($product_id, int $new_stock)
    {
        $sql = 'UPDATE stock_reception SET current_stock = :current_stock
                WHERE product_id = :product_id ORDER BY id DESC LIMIT 1';
        return $this->execute($sql, ['product_id' => $product_id, 'current_stock' => $new_stock]);
    }


    public function findByRfid(string $rfid): array
    {
        $sql = "SELECT * FROM product WHERE epc = :epc";
        $result = $this->selectOne($sql, ['epc' => $rfid]);
        return $result ? [$result] : [];
    }

    public function getAllCategories(): array
    {
        return $this->selectAll('SELECT id, name FROM category ORDER BY name ASC');
    }
}
