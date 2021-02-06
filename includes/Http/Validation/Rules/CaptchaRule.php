<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Loggers\FileLogger;
use App\Requesting\Requester;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class CaptchaRule extends BaseRule
{
    private Requester $requester;
    private Settings $settings;
    private FileLogger $fileLogger;

    public function __construct()
    {
        parent::__construct();
        $this->requester = app()->make(Requester::class);
        $this->settings = app()->make(Settings::class);
        $this->fileLogger = app()->make(FileLogger::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        $response = $this->requester->post(
            "https://license.sklep-sms.pl/v1/captcha",
            [
                "response" => $value,
                "remoteip" => get_ip(app()->make(Request::class)),
            ],
            [
                "Authorization" => "Bearer {$this->settings->getLicenseToken()}",
            ]
        );

        if ($response) {
            $result = $response->json();

            if (array_get($result, "success")) {
                return;
            }

            $this->fileLogger->error("Captcha failed", [
                "status" => $response->getStatusCode(),
                "result" => $result,
            ]);
        }

        throw new ValidationException("Captcha failed");
    }
}
