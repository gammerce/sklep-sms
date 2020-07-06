<?php
namespace App\Support;

use App\Loggers\DatabaseLogger;
use App\Loggers\FileLogger;
use App\System\Settings;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    /** @var array */
    private $config;

    /** @var Settings */
    private $settings;

    /** @var DatabaseLogger */
    private $databaseLogger;

    /** @var FileLogger */
    private $fileLogger;

    public function __construct(
        Settings $settings,
        DatabaseLogger $databaseLogger,
        FileLogger $fileLogger,
        array $config = []
    ) {
        $this->settings = $settings;
        $this->config = $config;
        $this->databaseLogger = $databaseLogger;
        $this->fileLogger = $fileLogger;
    }

    public function send($email, $name, $subject, $text)
    {
        // Recipient's email address
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        $name = htmlspecialchars($name);

        if (!strlen($email)) {
            return "wrong_email";
        }

        if ($this->shouldUseSignedSend()) {
            return $this->signedSend($email, $name, $subject, $text);
        }

        return $this->simpleSend($email, $name, $subject, $text);
    }

    private function signedSend($email, $name, $subject, $text)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->XMailer = " ";
            $mail->CharSet = "UTF-8";
            $mail->Host = $this->config["Host"];
            $mail->SMTPAuth = true;
            $mail->Username = $this->getSenderMail();
            $mail->Password = $this->config["Password"];
            $mail->SMTPSecure = $this->config["Secure"];
            $mail->Port = $this->config["Port"];

            $mail->setFrom($this->getSenderMail(), $this->getSenderName());
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $text;

            if (is_debug()) {
                $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
            }

            $mail->send();

            $this->databaseLogger->log("log_email_was_sent", $email, $text);
            return "sent";
        } catch (Exception $e) {
            $this->fileLogger->error($e->getMessage());
            return "not_sent";
        }
    }

    private function simpleSend($email, $name, $subject, $text)
    {
        $header = "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\n";
        $header .= "From: {$this->getSenderName()} < {$this->getSenderMail()} >\n";
        $header .= "To: {$name} < {$email} >\n";
        $header .= "X-Sender: {$this->getSenderName()} < {$this->getSenderMail()} >\n";
        $header .= 'X-Mailer: PHP/' . phpversion();
        $header .= "X-Priority: 1 (Highest)\n";
        $header .= "X-MSMail-Priority: High\n";
        $header .= "Importance: High\n";
        $header .= "Return-Path: {$this->getSenderMail()}\n"; // Return email for errors

        if (!mail($email, $subject, $text, $header)) {
            return "not_sent";
        }

        $this->databaseLogger->log("log_email_was_sent", $email, $text);

        return "sent";
    }

    private function shouldUseSignedSend()
    {
        return strlen(array_get($this->config, "Host")) &&
            strlen(array_get($this->config, "Password"));
    }

    private function getSenderMail()
    {
        return $this->config["Username"] ?: $this->settings["sender_email"];
    }

    private function getSenderName()
    {
        return $this->settings["sender_email_name"];
    }
}
