<?php

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use App\Helpers\EmailHelper;

class CustomerModel extends BaseModel
{
    public function __construct(PDOService $pdo_service, private EmailHelper $email_helper)
    {
        parent::__construct($pdo_service);
    }

    public function getCustomers()
    {
        $query = "SELECT * FROM customer";

        $customers = $this->selectAll($query);

        return $customers;
    }

    public function getOneCustomer($id)
    {
        $query = "SELECT * FROM customer WHERE id = :customer_id";

        $customer = $this->selectOne($query, ['customer_id' => $id]);

        return $customer;
    }

    public function getCustomerByEmail($email)
    {
        return $this->selectOne("SELECT * FROM customer WHERE email = :email", ['email' => $email]);
    }
    public function deleteCustomerById($id)
    {
        $query = "DELETE FROM customer WHERE id = :id";

        $customer = $this->selectOne($query, ['id' => $id]);

        return $customer;
    }

    public function getCustomerByMembership($membership_number)
    {
        return $this->selectOne(
            "SELECT * FROM CUSTOMER WHERE membership_number = :membership_number",
            ['membership_number' => $membership_number]
        );
    }

    public function addCustomer(array $data)
    {
        $this->execute(
            'INSERT INTO customer (name, email, phone, membership_number, total_points, preferred_language, address)
             VALUES (:name, :email, :phone, :membership_number, 0, :preferred_language, :address)',
            [
                'name'               => $data['name'],
                'email'              => $data['email'] ?? null,
                'phone'              => $data['phone'] ?? null,
                'membership_number'  => $data['membership_number'],
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'address'            => $data['address'] ?? null,
            ]
        );

        return $this->lastInsertId();
    }
    public function updateCustomer($id, array $data)
    {
        return $this->execute(
            'UPDATE customer SET name = :name, email = :email, phone = :phone,
             preferred_language = :preferred_language, address = :address WHERE id = :id',
            [
                'id'                 => $id,
                'name'               => $data['name'],
                'email'              => $data['email'] ?? null,
                'phone'              => $data['phone'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'address'            => $data['address'] ?? null,
            ]
        );
    }
    public function addPoints($id, int $points)
    {
        return $this->execute(
            'UPDATE customer SET total_points = total_points + :points WHERE id = :id',
            ['id' => $id, 'points' => $points]
        );
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
