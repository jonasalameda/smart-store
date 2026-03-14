<?php

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;

class CustomerModel extends BaseModel
{
    public function __construct(PDOService $pdo_service)
    {
        parent::__construct($pdo_service);
    }

    public function getCustomers()
    {
        $query = "SELECT * FROM customers";

        $customers = $this->selectAll($query);

        return $customers;
    }

    public function getOneCustomer($id)
    {
        $query = "SELECT * FROM customers WHERE id = :customer_id";

        $customer = $this->selectOne($query, ['customer_id' => $id]);

        return $customer;
    }

        public function deleteCustomerById($id)
    {
        $query = "DELETE FROM customers WHERE id = :id";

        $customer = $this->selectOne($query, ['id' => $id]);

        return $customer;
    }

    public function addCustomer(array $customer_data)
    {
        $this->execute(
            'INSERT INTO `customers` (first_name, last_name, email, phone, address) VALUES (:first_name, :last_name, :email, :phone, :address)',
            [
                'first_name' => $customer_data['first_name'],
                'last_name' => $customer_data['last_name'],
                'email' => $customer_data['email'],
                'phone' => $customer_data['phone'],
                'address' => $customer_data['address'],
            ]
        );

        return $this->lastInsertId();
    }
}
