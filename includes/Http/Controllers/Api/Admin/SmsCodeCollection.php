<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
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

        $smsPrice = $request->request->get("sms_price");
        $code = $request->request->get("code");

        $warnings = [];

        if ($warning = check_for_warnings("number", $smsPrice)) {
            $warnings['sms_price'] = array_merge((array) $warnings['sms_price'], $warning);
        }

        if ($warning = check_for_warnings("sms_code", $code)) {
            $warnings['code'] = array_merge((array) $warnings['code'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $smsCodeRepository->create($lang->strtoupper($code), $smsPrice, true);
        $logger->logWithActor('log_sms_code_added', $code, $smsPrice);

        return new SuccessApiResponse($lang->t('sms_code_add'));
    }
}
