<?php

declare(strict_types=1);

namespace App\Controllers;

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;
// use PHPMailer\PHPMailer\SMTP;

// require 'path/to/PHPMailer/src/Exception.php';
// require 'path/to/PHPMailer/src/PHPMailer.php';
// require 'path/to/PHPMailer/src/SMTP.php';
// require 'vendor/autoload.php';

use App\Domain\Models\CustomerModel;
use App\Helpers\Email;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// $mail = new PHPMailer(true);

// saving for later, when we have more time
// try {
//     //Server settings
//     $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
//     $mail->isSMTP();                                            //Send using SMTP
//     $mail->Host       = 'smtp.office365.com';                     //Set the SMTP server to send through
//     $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
//     $mail->Username   = 'user@example.com';                     //SMTP username
//     $mail->Password   = 'secret';                               //SMTP password
//     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
//     $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

//     //Recipients
//     $mail->setFrom('from@example.com', 'Mailer');
//     $mail->addAddress('joe@example.net', 'Joe User');     //Add a recipient
//     $mail->addAddress('ellen@example.com');               //Name is optional
//     $mail->addReplyTo('info@example.com', 'Information');
//     $mail->addCC('cc@example.com');
//     $mail->addBCC('bcc@example.com');

//     //Attachments
//     $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
//     $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

//     //Content
//     $mail->isHTML(true);                                  //Set email format to HTML
//     $mail->Subject = 'Here is the subject';
//     $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
//     $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

//     $mail->send();
//     echo 'Message has been sent';
// } catch (Exception $e) {
//     echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
// }

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

        return $this->render($response, 'customerFormView.php', $data);
    }

    public function handleDeleteCustomer(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        $this->customer_model->deleteCustomerById($id);
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
            if ($customer['email'] === $customer_data['email']) {
                // if error in whatever do buzzer and eretrun whatever
                //shell_exec("python <?= APP_BASE_DIR_PATH /public/assets/python/LED_Buzzer.py error");
                shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py error");

                $data = [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'error' => "A customer with this email already exists.",
                    'customers' => $customers
                ];
                return $this->render($response, 'customerFormView.php', $data);
            }
        }

        foreach ($customers as $customer) {
            if ($customer['phone'] === $customer_data['phone']) {
                // if error in whatever do buzzer and eretrun whatever
                //shell_exec("python <?= APP_BASE_DIR_PATH /public/assets/python/LED_Buzzer.py error");
                shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py error");

                $data = [
                    'title' => 'Home',
                    'message' => 'Welcome to the home page',
                    'error' => "A customer with this ephonemail already exists.",
                    'customers' => $customers
                ];
                return $this->render($response, 'customerFormView.php', $data);
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
            return $this->render($response, 'customerFormView.php', $data);
        } else {
            // if the thign wahws thinged then success and led goes green
            shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/LED_Buzzer.py success");
            $customers = $this->customer_model->getCustomers();
            $data['data'] = [
                'title' => 'Home',
                'message' => 'Welcome to the home page',
                'customers' => $customers,
                'success' => 'Customer added successfully!',
            ];

            $message_body = sprintf(
                "You have successfully registered in the smart-store application. \nName: %s %s\nEmail: %s\nPhone: %d\nAddress: %s",
                $customer_data["first_name"],
                $customer_data["last_name"],
                $customer_data["email"],
                $customer_data["phone"],
                $customer_data["address"]
            );


            // mail($customer_data["email"], "A customer was created", $message_body);

            $this->email_helper->sendEmail(
                "markololo2468@gmail.com", //replace with customer email after testing
                "Registration at smart-store",
                $message_body
            );

            return $this->render($response, 'customerFormView.php', $data);
        }
        // return $this->redirect($request, $response, 'customers.index', $data);
    }

    public function error(Request $request, Response $response, array $args): Response
    {
        return $this->render($response, 'errorView.php');
    }
}
