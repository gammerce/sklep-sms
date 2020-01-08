<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServiceCodeRepository;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCodeCollection
{
    public function post(
        $serviceId,
        Request $request,
        TranslationManager $translationManager,
        ServiceCodeRepository $serviceCodeRepository,
        Heart $heart,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $uid = $request->request->get("uid");
        $code = $request->request->get("code");

        $warnings = [];

        if (($serviceModule = $heart->getServiceModule($serviceId)) === null) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        // Id użytkownika
        if (strlen($uid) && ($warning = check_for_warnings("uid", $uid))) {
            $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
        }

        // Kod
        if (!strlen($code)) {
            $warnings['code'][] = $lang->t('field_no_empty');
        } else {
            if (strlen($code) > 16) {
                $warnings['code'][] = $lang->t('return_code_length_warn');
            }
        }

        // Łączymy zwrócone błędy
        $warnings = array_merge(
            (array) $warnings,
            (array) $serviceModule->serviceCodeAdminAddValidate($request->request->all())
        );

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        // Pozyskujemy dane kodu do dodania
        $codeData = $serviceModule->serviceCodeAdminAddInsert($request->request->all());

        $serviceCodeRepository->create(
            $code,
            $serviceModule->service->getId(),
            $uid ?: 0,
            array_get($codeData, 'server', 0),
            array_get($codeData, 'amount', 0),
            array_get($codeData, 'tariff', 0),
            $codeData['data']
        );

        $logger->logWithActor('log_code_added', $code, $serviceModule->service->getId());

        return new SuccessApiResponse($lang->t('code_added'));
    }
}
