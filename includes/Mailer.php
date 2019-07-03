<?php
namespace App;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /** @var array */
    protected $config;

    /** @var Settings */
    protected $settings;

    /** @var Translator */
    protected $langShop;

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        array $config = []
    ) {
        $this->settings = $settings;
        $this->config = $config;
        $this->langShop = $translationManager->shop();
    }

    public function send($email, $name, $subject, $text)
    {
        if ($this->shouldUseSignedSend()) {
            return $this->signedSend($email, $name, $subject, $text);
        }

        return $this->simpleSend($email, $name, $subject, $text);
    }

    public function signedSend($email, $name, $subject, $text)
    {
        ////////// USTAWIENIA //////////
        $email = filter_var($email, FILTER_VALIDATE_EMAIL); // Adres e-mail adresata
        $name = htmlspecialchars($name);
        $sender_email = $this->settings['sender_email'];
        $sender_name = $this->settings['sender_email_name'];

        if (!strlen($email)) {
            return "wrong_email";
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->XMailer = ' ';
            $mail->CharSet = 'UTF-8';
            $mail->Host = $this->config['Host'];
            $mail->SMTPAuth = true;
            $mail->Username = $sender_email;
            $mail->Password = $this->config['Password'];
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            //Recipients
            $mail->setFrom($sender_email, $sender_name);
            $mail->addAddress($email, $name);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $text;

            $mail->send();

            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('email_was_sent'),
                    $email,
                    $text
                )
            );

            return "sent";
        } catch (Exception $e) {
            return "not_sent";
        }
    }

    public function simpleSend($email, $name, $subject, $text)
    {
        /** @var Settings $settings */
        $settings = app()->make(Settings::class);

        ////////// USTAWIENIA //////////
        $email = filter_var($email, FILTER_VALIDATE_EMAIL); // Adres e-mail adresata
        $name = htmlspecialchars($name);
        $sender_email = $settings['sender_email'];
        $sender_name = $settings['sender_email_name'];

        if (!strlen($email)) {
            return "wrong_email";
        }

        $header = "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: text/html; charset=UTF-8\n";
        $header .= "From: {$sender_name} < {$sender_email} >\n";
        $header .= "To: {$name} < {$email} >\n";
        $header .= "X-Sender: {$sender_name} < {$sender_email} >\n";
        $header .= 'X-Mailer: PHP/' . phpversion();
        $header .= "X-Priority: 1 (Highest)\n";
        $header .= "X-MSMail-Priority: High\n";
        $header .= "Importance: High\n";
        $header .= "Return-Path: {$sender_email}\n"; // Return path for errors

        if (!mail($email, $subject, $text, $header)) {
            return "not_sent";
        }

        log_info(
            $this->langShop->sprintf($this->langShop->translate('email_was_sent'), $email, $text)
        );

        return "sent";
    }

    protected function shouldUseSignedSend()
    {
        return class_exists(PHPMailer::class) && !empty($this->config);
    }
}
