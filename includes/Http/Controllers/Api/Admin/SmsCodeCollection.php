<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\SmsCodeRepository;
use App\Translation\TranslationManager;
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
            'code' => [new RequiredRule(), new MaxLengthRule(16)],
            'sms_price' => [new RequiredRule(), new NumberRule()],
        ]);

        $validated = $validator->validateOrFail();

        $code = $validated['code'];
        $smsPrice = $validated['sms_price'];

        $smsCodeRepository->create($lang->strtoupper($code), $smsPrice, true);
        $logger->logWithActor('log_sms_code_added', $code, $smsPrice);

        return new SuccessApiResponse($lang->t('sms_code_add'));
    }
}
