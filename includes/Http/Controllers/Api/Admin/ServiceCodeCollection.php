<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\PriceRepository;
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
        PriceRepository $priceRepository,
        Heart $heart,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $code = $request->request->get("code");
        $priceId = $request->request->get("price_id");
        $uid = $request->request->get("uid") ?: null;
        $serverId = $request->request->get("server_id") ?: null;

        $warnings = [];

        $price = $priceRepository->get($priceId);
        $serviceModule = $heart->getServiceModule($serviceId);

        if (!$serviceModule) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        if (strlen($uid) && ($warning = check_for_warnings("uid", $uid))) {
            $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
        }

        if (!strlen($code)) {
            $warnings['code'][] = $lang->t('field_no_empty');
        } elseif (strlen($code) > 16) {
            $warnings['code'][] = $lang->t('return_code_length_warn');
        }

        if ($serverId) {
            $server = $heart->getServer($serverId);
            if (!$server) {
                $warnings['server'][] = $lang->t('no_server_id');
            }
        }

        if (!$price) {
            // TODO Change translation
            $warnings['price_id'][] = $lang->t('invalid_price');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $serviceCodeRepository->create(
            $code,
            $serviceModule->service->getId(),
            $priceId,
            $serverId,
            $uid
        );

        $logger->logWithActor('log_code_added', $code, $serviceModule->service->getId());

        return new SuccessApiResponse($lang->t('code_added'));
    }
}
