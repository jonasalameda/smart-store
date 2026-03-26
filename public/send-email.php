<?php

declare(strict_types=1);

/**
 * Fan / fridge temperature alert email (called from dashboard.js when temp exceeds threshold).
 * After a successful send, the JSON includes system_notification for the browser Notification API.
 */

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['fridge'], $_GET['temp'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$fridge = (string) $_GET['fridge'];
$temp = (string) $_GET['temp'];

require dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    $mail->Subject = "Fridge {$fridge} Temperature Alert";
    $mail->Body    = "The current temperature is {$temp}°C. Would you like to turn on the fan? Reply YES by clicking the link below:<br>
                      <a href='http://yourserver.com/fan-control.php?fridge=" . htmlspecialchars($fridge, ENT_QUOTES, 'UTF-8') . "&action=on'>Turn ON Fan</a>";

    $mail->isHTML(true);
    $mail->send();

    $systemTitle = "Fridge {$fridge} temperature alert";
    $systemBody = "Temperature alert: current reading is {$temp}°C. Email notification was sent.";

    echo json_encode([
        'status' => 'success',
        'message' => 'Email sent',
        'system_notification' => [
            'title' => $systemTitle,
            'body' => $systemBody,
            'fridge' => $fridge,
            'temperature' => $temp,
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $mail->ErrorInfo,
    ]);
}
