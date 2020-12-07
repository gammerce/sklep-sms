<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Loggers\FileLogger;
use App\Requesting\Requester;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class CaptchaRule extends BaseRule
{
    /** @var Requester */
    private $requester;

    /** @var Settings */
    private $settings;

    /** @var FileLogger */
    private $fileLogger;

    public function __construct()
    {
        parent::__construct();
        $this->requester = app()->make(Requester::class);
        $this->settings = app()->make(Settings::class);
        $this->fileLogger = app()->make(FileLogger::class);
    }

    public function validate($attribute, $value, array $data)
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
                return [];
            }

            $this->fileLogger->error("Captcha failed", [
                "status" => $response->getStatusCode(),
                "result" => $result,
            ]);
        }

        return ["Captcha failed"];
    }
}
