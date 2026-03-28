<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Fridge alert email (fan flow). Called from dashboard.js via GET /send-alert.
 * Use either temp=… or humidity=… (not both).
 */
class SendAlertController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function handle(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        if (!isset($params['fridge'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Missing parameters',
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(400);
        }

        $fridge = (string) $params['fridge'];
        $isHumidity = isset($params['humidity']) && $params['humidity'] !== '';
        $isTemp = isset($params['temp']) && $params['temp'] !== '';

        if ($isHumidity === $isTemp) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Send exactly one of: temp, humidity',
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(400);
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'emmanuelaigbokhan133@gmail.com';
            $mail->Password   = 'hello1234!';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('emmanuelaigbokhan133@gmail.com', 'Smart Store');
            $mail->addAddress('recepientttt@email.com');

            if ($isHumidity) {
                $humidity = (string) $params['humidity'];
                $mail->Subject = "Fridge {$fridge} Humidity Alert";
                $mail->Body    = "The current humidity is {$humidity}%. Please check ventilation or dehumidification if needed.";
            } else {
                $temp = (string) $params['temp'];
                $mail->Subject = "Fridge {$fridge} Temperature Alert";
                $mail->Body    = "The current temperature is {$temp}°C. Would you like to turn on the fan? Reply YES by clicking the link below:<br>
                      <a href='http://yourserver.com/fan-control.php?fridge=" . htmlspecialchars($fridge, ENT_QUOTES, 'UTF-8') . "&action=on'>Turn ON Fan</a>";
            }

            $mail->isHTML(true);
            $mail->send();

            if ($isHumidity) {
                $humidity = (string) $params['humidity'];
                $systemTitle = "Fridge {$fridge} humidity alert";
                $systemBody = "Humidity alert: current reading is {$humidity}%. Email notification was sent.";
                $notification = [
                    'title' => $systemTitle,
                    'body' => $systemBody,
                    'fridge' => $fridge,
                    'humidity' => $humidity,
                ];
            } else {
                $temp = (string) $params['temp'];
                $systemTitle = "Fridge {$fridge} temperature alert";
                $systemBody = "Temperature alert: current reading is {$temp}°C. Email notification was sent.";
                $notification = [
                    'title' => $systemTitle,
                    'body' => $systemBody,
                    'fridge' => $fridge,
                    'temperature' => $temp,
                ];
            }

            $payload = [
                'status' => 'success',
                'message' => 'Email sent',
                'system_notification' => $notification,
            ];

            $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        } catch (MailerException $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $mail->ErrorInfo,
            ], JSON_UNESCAPED_UNICODE));

            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withStatus(500);
        }
    }
}
