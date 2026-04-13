<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CustomerModel;
use App\Helpers\Email;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CustomerController extends BaseController
{
    public function __construct(Container $container, private CustomerModel $customer_model, private EmailHelper $email_helper)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $customers = $this->customer_model->getCustomers();
        $data['data'] = [
            'title' => 'Home',
            'message' => 'Welcome to the home page',
            'customers' => $customers,
        ];

        return $this->render($response, 'admin/customers.php', $data);
    }

    public function handleDeleteCustomer(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $this->customer_model->deleteCustomerById($id);

        return $this->redirect($request, $response, 'customers.index');
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        $customers = $this->customer_model->getCustomers();

        $customer_data = $request->getParsedBody();

        $newEmail = isset($customer_data['email']) ? mb_strtolower(trim((string) $customer_data['email'])) : '';

        foreach ($customers as $customer) {
            $existingEmail = isset($customer['email']) ? mb_strtolower(trim((string) $customer['email'])) : '';
            if ($newEmail !== '' && $existingEmail === $newEmail) {
                // if error in whatever do buzzer and eretrun whatever
                //shell_exec("python <?= APP_BASE_DIR_PATH /public/assets/python/LED_Buzzer.py error");
                shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py error");

                $data = [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'error' => "A customer with this email already exists.",
                    'customers' => $customers
                ];
                return $this->render($response, 'admin/customers.php', ['data' => $data]);
            }
        }

        $newPhone = isset($customer_data['phone']) ? preg_replace('/\D/', '', (string) $customer_data['phone']) : '';

        foreach ($customers as $customer) {
            $existingPhone = isset($customer['phone']) ? preg_replace('/\D/', '', (string) $customer['phone']) : '';
            if ($newPhone !== '' && $existingPhone === $newPhone) {
                // if error in whatever do buzzer and eretrun whatever
                //shell_exec("python <?= APP_BASE_DIR_PATH /public/assets/python/LED_Buzzer.py error");
                shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py error");

                $data = [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'error' => "A customer with this phone already exists.",
                    'customers' => $customers
                ];
                return $this->render($response, 'admin/customers.php', ['data' => $data]);
            }
        }

        $customer_id = $this->customer_model->addCustomer($customer_data);

        $data = [
            'title' => 'Create',
            'message' => 'Welcome to the home page',
            'customers' => $customers,
            'error' => 'Failed to add customer. Please try again.'
        ];

        if (!$customer_id) {
            // if no customer id that means something went wrong then error
            shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py error");
            return $this->render($response, 'admin/customers.php', ['data' => $data]);
        } else {
            // if the thign wahws thinged then success and led goes green
            shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py success");
            $customers = $this->customer_model->getCustomers();

            $row = $this->customer_model->getOneCustomer($customer_id);
            $membershipDisplay = $row !== null && isset($row['membership_number'])
                ? (string) $row['membership_number']
                : '';

            $message_body = sprintf(
                "You have successfully registered in the smart-store application. \nName: %s\nEmail: %s\nPhone: %s\nAddress: %s\nMembership Number: %s\nTemporary password (change after first login): TempStore123!",
                (string) ($customer_data['first_name'] ?? $customer_data['name'] ?? ''),
                (string) ($customer_data['email'] ?? ''),
                (string) ($customer_data['phone'] ?? ''),
                (string) ($customer_data['address'] ?? ''),
                $membershipDisplay
            );

            // mail($customer_data["email"], "A customer was created", $message_body);

            $this->email_helper->sendEmail(
                "markololo2468@gmail.com", //replace with customer email after testing
                "Registration at smart-store",
                $message_body
            );

            return $this->render($response, 'admin/customers.php', [
                'data' => [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'customers' => $customers,
                    'success' => 'Customer added successfully!',
                ],
            ]);
        }
        // return $this->redirect($request, $response, 'customers.index', $data);
    }

    public function error(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'errorView.php');
    }

}
