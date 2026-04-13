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
        return $this->selectAll("SELECT * FROM product");
    }

    public function getOneProduct($id)
    {
        $sql = "SELECT * FROM product WHERE id = :id";

        return $this->selectOne($sql, ['id' => $id]);
    }

    public function getProductByUPC($upc)
    {
        $sql = "SELECT * FROM PRODUCT WHERE upc = :upc";
        return $this->selectOne($sql, ['upc' => $upc]);
    }

    public function getProductByEPC($epc)
    {
        $sql = "SELECT * FROM PRODUCT WHERE epc = :epc";
        return $this->selectOne($sql, ['epc' => $epc]);
    }

    public function addProduct(array $data)
    {
        $this->execute(
            'INSERT INTO PRODUCT (name, category, price, upc, epc, manufacturer, shelf_life_days)
             VALUES (:name, :category, :price, :upc, :epc, :manufacturer, :shelf_life_days)',
            [
                'name'           => $data['name'],
                'category'       => $data['category'] ?? null,
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
            'UPDATE PRODUCT SET name = :name, category = :category, price = :price,
             upc = :upc, epc = :epc, manufacturer = :manufacturer,
             shelf_life_days = :shelf_life_days WHERE id = :id',
            [
                'id'             => $id,
                'name'           => $data['name'],
                'category'       => $data['category'] ?? null,
                'price'          => $data['price'],
                'upc'            => $data['upc'] ?? null,
                'epc'            => $data['epc'] ?? null,
                'manufacturer'   => $data['manufacturer'] ?? null,
                'shelf_life_days'=> $data['shelf_life_days'] ?? null,
            ]
        );
    }

    public function deleteProduct($id)
    {
        return $this->execute("DELETE FROM PRODUCT WHERE id = :id", ['id' => $id]);
    }

    public function getAllStock()
    {
        return $this->selectAll(
            "SELECT p.id, p.name, p.category, p.price,
                    COALESCE(sr.current_stock, 0) AS current_stock, sr.date_received
             FROM PRODUCT p
             LEFT JOIN STOCK_RECEPTION sr ON sr.product_id = p.id
             ORDER BY p.id, sr.date_received DESC"
        );
    }

    public function getStockByProduct($product_id)
    {
        $sql = "SELECT * FROM stock_reception WHERE product_id = :product_id ORDER BY date_received DESC";
        return $this->selectAll($sql, ['product_id' => $product_id]);
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
        $sql = 'UPDATE STOCK_RECEPTION SET current_stock = :current_stock
                WHERE product_id = :product_id ORDER BY date_received DESC LIMIT 1';
        return $this->execute($sql, ['product_id' => $product_id, 'current_stock' => $new_stock]);
    }


    public function findByRfid(string $rfid): array
    {
        $sql = "SELECT * FROM PRODUCT WHERE epc = :epc";
        $result = $this->selectOne($sql, ['epc' => $rfid]);
        return $result ? [$result] : [];
    }

}
