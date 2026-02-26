<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\CustomerModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CustomerController extends BaseController
{
    //NOTE: Passing the entire container violates the Dependency Inversion Principle and creates a service locator anti-pattern.
    // However, it is a simple and effective way to pass the container to the controller given the small scope of the application and the fact that this application is to be used in a classroom setting where students are not yet familiar with the Dependency Inversion Principle.
    public function __construct(Container $container, private CustomerModel $customer_model)
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

        return $this->render($response, 'customerFormView.php', $data);
    }

    public function add(Request $request, Response $response, array $args): Response
    {
        $customers = $this->customer_model->getCustomers();

        $customer_data = $request->getParsedBody();

        foreach ($customers as $customer) {
            if (in_array($customer_data['email'], $customer)) {
                // if error in whatever do buzzer and eretrun whatever
                shell_exec("python <?= APP_BASE_DIR_PATH ?>/public/assets/python/LED_Buzzer.py error");

                $data = [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'error' => "Error",
                ];

                return $this->redirect($request, $response, 'customers.index', $data);
            }
        }


        $customer_id = $this->customer_model->addCustomer($request->getParsedBody());

        $data = [
            'title' => 'Create',
            'message' => 'Welcome to the home page',
            'customers' => $customers,
        ];

        if (!isset($customer_id)) {
            // if no customer id that means something went wrong then error
            shell_exec("python <?= APP_BASE_DIR_PATH ?>/public/assets/python/LED_Buzzer.py error");
        } else {
            // if the thign wahws thinged then success and led goes green
            shell_exec("python <?= APP_BASE_DIR_PATH ?>/public/assets/python/LED_Buzzer.py success");
        }

        return $this->redirect($request, $response, 'customers.index', $data);
    }

    public function error(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'errorView.php');
    }
}
