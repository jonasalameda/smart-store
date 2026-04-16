<?php

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use App\Helpers\EmailHelper;

class CustomerModel extends BaseModel
{
    /** Select list: DB PascalCase → stable PHP keys for views and checkout. */
    private const SQL_SELECT = <<<'SQL'
SELECT
    CustomerID AS id,
    FirstName AS first_name,
    LastName AS last_name,
    Email AS email,
    PhoneNumber AS phone,
    MembershipNumber AS membership_number,
    TotalPoints AS total_points,
    PasswordHash AS password_hash,
    CreatedAt AS created_at,
    EmailVerified AS email_verified,
    TRIM(CONCAT(COALESCE(FirstName, ''), ' ', COALESCE(LastName, ''))) AS name
FROM customer
SQL;

    public function __construct(PDOService $pdo_service, private EmailHelper $email_helper)
    {
        parent::__construct($pdo_service);
    }

    public function getCustomers()
    {
        return $this->selectAll(self::SQL_SELECT . ' ORDER BY CustomerID');
    }

    public function getOneCustomer($id)
    {
        return $this->selectOne(
            self::SQL_SELECT . ' WHERE CustomerID = :customer_id LIMIT 1',
            ['customer_id' => $id]
        );
    }

    public function getCustomerByUsername($name)
    {
        $name = trim((string) $name);

        return $this->selectOne(
            self::SQL_SELECT . ' WHERE TRIM(CONCAT(FirstName, \' \', LastName)) = :name LIMIT 1',
            ['name' => $name]
        );
    }

    public function getCustomerByEmail($email)
    {
        $email = mb_strtolower(trim((string) $email));

        return $this->selectOne(
            self::SQL_SELECT . ' WHERE LOWER(Email) = :email LIMIT 1',
            ['email' => $email]
        );
    }

    public function deleteCustomerById(int $id): void
    {
        $this->execute('DELETE FROM customer WHERE CustomerID = :id', ['id' => $id]);
    }

    /**
     * @param string|int $membership_number Value like M206634 or digits only
     */
    public function getCustomerByMembership(string|int $membership_number)
    {
        $m = trim((string) $membership_number);
        $row = $this->selectOne(
            self::SQL_SELECT . ' WHERE MembershipNumber = :m LIMIT 1',
            ['m' => $m]
        );
        if ($row !== false) {
            return $row;
        }
        if (ctype_digit($m)) {
            return $this->selectOne(
                self::SQL_SELECT . ' WHERE MembershipNumber = :m2 LIMIT 1',
                ['m2' => 'M' . str_pad($m, 6, '0', STR_PAD_LEFT)]
            );
        }

        return false;
    }

    /**
     * Staff form sends first_name (full or first only), optional password/membership.
     *
     * @return int New CustomerID, or 0 on failure
     */
    public function addCustomer(array $data): int
    {
        try {
            $rawName = trim((string) ($data['first_name'] ?? $data['name'] ?? ''));
            if ($rawName === '') {
                return 0;
            }
            [$firstName, $lastName] = $this->splitName($rawName);

            $email = isset($data['email']) ? trim((string) $data['email']) : '';
            $email = $email !== '' ? mb_strtolower($email) : null;

            $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';
            $phone = $phone !== '' ? (preg_replace('/\D/', '', $phone) ?: $phone) : null;

            $plain = trim((string) ($data['password'] ?? ''));
            if ($plain === '') {
                $plain = 'TempStore123!';
            }
            $hashedPassword = password_hash($plain, PASSWORD_BCRYPT);

            $membership = isset($data['membership_number']) ? trim((string) $data['membership_number']) : '';
            if ($membership === '') {
                $membership = $this->allocateMembershipNumber();
            }

            $idRow = $this->selectOne('SELECT COALESCE(MAX(CustomerID), 0) AS m FROM customer');
            $nextId = (int) ($idRow['m'] ?? 0) + 1;

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
                    'ph' => $hashedPassword,
                ]
            );

            return $nextId;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function allocateMembershipNumber(): string
    {
        for ($i = 0; $i < 25; $i++) {
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

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $full): array
    {
        $parts = preg_split('/\s+/', $full, 2, PREG_SPLIT_NO_EMPTY);
        $first = $parts[0] ?? '';
        $last = isset($parts[1]) ? trim((string) $parts[1]) : '';

        return [$first, $last];
    }

    /**
     * Verify user credentials by email or full name and password.
     */
    public function verifyCredentials(string $identifier, string $password): ?array
    {
        $user = $this->getCustomerByEmail($identifier);
        if ($user === false) {
            $user = $this->getCustomerByUsername($identifier);
        }
        if ($user === false) {
            return null;
        }

        if (password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return $user;
        }

        return null;
    }

    public function getCustomerOrderHistory($user_id)
    {
        return [];
    }

    public function updateCustomer($id, array $data)
    {
        return $this->execute(
            'UPDATE customer SET FirstName = :fn, LastName = :ln, Email = :email, PhoneNumber = :phone
             WHERE CustomerID = :id',
            [
                'id' => $id,
                'fn' => $data['first_name'] ?? $data['name'] ?? '',
                'ln' => $data['last_name'] ?? '',
                'email' => isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null,
                'phone' => $data['phone'] ?? null,
            ]
        );
    }

    public function addPoints($id, int $points)
    {
        return $this->execute(
            'UPDATE customer SET TotalPoints = TotalPoints + :points WHERE CustomerID = :id',
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
            $fridge,
            $temperature,
            $threshold
        );

        $this->email_helper->sendEmail("mmkprogrammerk80@gmail.com", $subject, $body);

        return true;
    }
}
