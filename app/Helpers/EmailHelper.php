<?php

declare(strict_types=1);

namespace App\Helpers;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Webklex\PHPIMAP\ClientManager;

class EmailHelper
{
    private string $smtpHost = 'smtp.gmail.com';
    private int $smtpPort = 587;
    private string $smtpSecure = 'tls';
    // private string $senderEmail = '***@gmail.com'; //now stored in env.php and called from container, just like the db variables for PDO
    private string $senderName = 'Smart Store';
    // private string $smtpPassword = '***';

    private string $imapHost = 'imap.gmail.com';
    private int $imapPort = 993;
    private string $imapEncryption = 'ssl';
    // private string $imapUsername = '***@gmail.com';
    // private string $imapPassword = '***';

    /**
     * Construcor to acces the vars stored in the env.php from container
     */
    public function __construct(
        private string $smtpUsername,
        private string $smtpPassword,
        private string $imapUsername,
        private string $imapPassword,
    ) {
        //
    }

    public function sendEmail(string $receiverMail, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom($this->smtpUsername, $this->senderName);
            $mail->addAddress($receiverMail);

            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("EmailHelper sendEmail error: " . $mail->ErrorInfo);
            return false;
        }
    }
    /*
    $mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '***@gmail.com';
    $mail->Password   = 'l**';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('**@gmail.com', 'Smart Store');
    $mail->addAddress('**@email.com');

    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email from smart-store app!';

    $mail->send();

    echo "Email senttttt!";
} catch (Exception $e) {
    echo "Mailer Exception: {$mail->ErrorInfo}";
}
    */

    /**
     * Read the latest email from inbox.
     *
     * @param int $limit Number of messages to retrieve
     * @return array Array of messages with subject and body
     */
    public function readEmails(int $limit = 1): array
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => $this->imapHost,
            'port'          => $this->imapPort,
            'encryption'    => $this->imapEncryption,
            'validate_cert' => true,
            'username'      => $this->imapUsername,
            'password'      => $this->imapPassword,
            'protocol'      => 'imap',
        ]);

        try {
            $client->connect();
            $folder = $client->getFolder('INBOX');
            // $messages = $folder->messages()->all()->limit($limit)->get();
            $messages = $folder->messages()->all()->limit($limit)->get()->first();

            $result = []; // array so if we wanna read a lot of msgs, for later
            foreach ($messages as $message) {
                $result[] = [
                    'subject' => $message->getSubject(),
                    'body'    => $message->getTextBody(),
                ];
            }

            return $result;

        } catch (\Exception $e) {
            error_log("EmailHelper readEmails got error: " . $e->getMessage());
            return [];
        }
    }

    public function readReply(string $subject): bool
    {
        $cm = new ClientManager();
        $client = $cm->make([
            'host'          => $this->imapHost,
            'port'          => $this->imapPort,
            'encryption'    => $this->imapEncryption,
            'validate_cert' => true,
            'username'      => $this->imapUsername,
            'password'      => $this->imapPassword,
            'protocol'      => 'imap',
        ]);

        try {
            $client->connect();
            $folder = $client->getFolder('INBOX');
            $message = $folder->messages()->all()->limit(1)->setFetchOrder('desc')->get()->first();

            if (!$message) {
                // just to not crash code in case of problem
                return false;
            }

            $messageSubject = strtolower($message->getSubject() ?? '');
            $messageBody = strtolower($message->getTextBody() ?? '');

            if (str_contains($messageSubject, 're:') || str_contains($messageSubject, strtolower($subject))) {
                if (str_contains($messageBody, 'yes')) {
                    return true;
                }
                if (str_contains($messageBody, 'no')) {
                    return false;
                }
            }

            return false;

        } catch (\Exception $e) {
            error_log("EmailHelper readReply error: " . $e->getMessage());
            return false;
        }
    }
}
