<?php
namespace App\Support;

use App\Loggers\DatabaseLogger;
use App\System\Settings;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /** @var array */
    private $config;

    /** @var Settings */
    private $settings;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(Settings $settings, DatabaseLogger $logger, array $config = [])
    {
        $this->settings = $settings;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function send($email, $name, $subject, $text)
    {
        if ($this->shouldUseSignedSend()) {
            return $this->signedSend($email, $name, $subject, $text);
        }

        return $this->simpleSend($email, $name, $subject, $text);
    }

    private function signedSend($email, $name, $subject, $text)
    {
        // Recipient's email address
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        $name = htmlspecialchars($name);
        $senderEmail = $this->settings["sender_email"];
        $senderName = $this->settings["sender_email_name"];

        if (!strlen($email)) {
            return "wrong_email";
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->XMailer = " ";
            $mail->CharSet = "UTF-8";
            $mail->Host = $this->config["Host"];
            $mail->SMTPAuth = true;
            $mail->Username = $senderEmail;
            $mail->Password = $this->config["Password"];
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;

            //Recipients
            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($email, $name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $text;

            $mail->send();

            $this->logger->log("log_email_was_sent", $email, $text);

            return "sent";
        } catch (Exception $e) {
            return "not_sent";
        }
    }

    private function simpleSend($email, $name, $subject, $text)
    {
        // Recipient's email address
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        $name = htmlspecialchars($name);
        $senderEmail = $this->settings["sender_email"];
        $senderName = $this->settings["sender_email_name"];

        if (!strlen($email)) {
            return "wrong_email";
        }

        $header = "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\n";
        $header .= "From: {$senderName} < {$senderEmail} >\n";
        $header .= "To: {$name} < {$email} >\n";
        $header .= "X-Sender: {$senderName} < {$senderEmail} >\n";
        $header .= 'X-Mailer: PHP/' . phpversion();
        $header .= "X-Priority: 1 (Highest)\n";
        $header .= "X-MSMail-Priority: High\n";
        $header .= "Importance: High\n";
        $header .= "Return-Path: {$senderEmail}\n"; // Return email for errors

        if (!mail($email, $subject, $text, $header)) {
            return "not_sent";
        }

        $this->logger->log("log_email_was_sent", $email, $text);

        return "sent";
    }

    private function shouldUseSignedSend()
    {
        return strlen(array_get($this->config, "Host")) &&
            strlen(array_get($this->config, "Password"));
    }
}
