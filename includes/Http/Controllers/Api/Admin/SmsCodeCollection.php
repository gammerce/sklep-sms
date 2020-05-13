<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\DateTimeRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\SmsCodeRepository;
use App\Translation\TranslationManager;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

class SmsCodeCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        SmsCodeRepository $smsCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator($request->request->all(), [
            "code" => [new RequiredRule(), new MaxLengthRule(16)],
            "expires_at" => [new DateTimeRule()],
            "sms_price" => [new RequiredRule(), new NumberRule()],
        ]);

        $validated = $validator->validateOrFail();

        $code = as_string($validated["code"]);
        $smsPrice = as_int($validated["sms_price"]);
        $expiresAt = as_datetime($validated["expires_at"]);

        if ($expiresAt) {
            $expiresAt->setTime(23, 59, 59);
        }

        $smsCode = $smsCodeRepository->create(
            $lang->strtoupper($code),
            $smsPrice,
            true,
            $expiresAt
        );
        $logger->logWithActor("log_sms_code_added", $code, $smsPrice);

        return new SuccessApiResponse($lang->t("sms_code_add"), [
            "data" => [
                "id" => $smsCode->getId(),
            ],
        ]);
    }
}
