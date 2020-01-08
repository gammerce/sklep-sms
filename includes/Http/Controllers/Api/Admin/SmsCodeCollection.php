<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SmsCodeCollection
{
    public function post(
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $tariff = $request->request->get("tariff");
        $code = $request->request->get("code");

        $warnings = [];

        // Taryfa
        if ($warning = check_for_warnings("number", $tariff)) {
            $warnings['tariff'] = array_merge((array) $warnings['tariff'], $warning);
        }

        // Kod SMS
        if ($warning = check_for_warnings("sms_code", $code)) {
            $warnings['code'] = array_merge((array) $warnings['code'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $db->query(
            $db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "sms_codes` (`code`, `tariff`) " .
                    "VALUES( '%s', '%d' )",
                [$lang->strtoupper($code), $tariff]
            )
        );

        $logger->logWithActor('log_sms_code_added', $code, $tariff);

        return new SuccessApiResponse($lang->t('sms_code_add'));
    }
}
