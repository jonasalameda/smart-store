<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(!isset($_GET['fridge']) || !isset($_GET['temp'])){
    die(json_encode(["status"=>"error","message"=>"Missing parameters"]));
}

$fridge = $_GET['fridge'];
$temp = $_GET['temp'];

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

    $mail->Subject = "Fridge $fridge Temperature Alert";
    $mail->Body    = "The current temperature is $temp°C. Would you like to turn on the fan? Reply YES by clicking the link below:<br>
                      <a href='http://yourserver.com/fan-control.php?fridge=$fridge&action=on'>Turn ON Fan</a>";

    $mail->isHTML(true);
    $mail->send();

    echo json_encode(["status"=>"success","message"=>"Email sent"]);

} catch (Exception $e){
    echo json_encode(["status"=>"error","message"=>$mail->ErrorInfo]);
}