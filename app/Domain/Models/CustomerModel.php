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
    
    public function sendTemperatureAlert(float $temperature, float $threshold, string $fridge): bool
    {
        if ($temperature <= $threshold) {
            return false;
        }

        $subject = "Smart Store alert: {$fridge} temperature over threshold";
        $body = sprintf(
            "Alert: The current temperature is %s  %.1f°C (threshold is %.1f°C). Would you like to turn on the fan?.",
            $fridge, $temperature, $threshold
        );

        $sentAll = true;

        $ok = $this->email_helper->sendEmail("mmkprogrammerk80@gmail.com", $subject, $body);

        return $sentAll;
    }
}
