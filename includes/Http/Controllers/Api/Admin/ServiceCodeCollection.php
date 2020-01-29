<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\PriceExistsRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServiceCodeRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCodeCollection
{
    public function post(
        $serviceId,
        Request $request,
        TranslationManager $translationManager,
        ServiceCodeRepository $serviceCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                'code' => $request->request->get("code"),
                'price_id' => $request->request->get("price_id"),
                'uid' => $request->request->get("uid") ?: null,
                'server_id' => $request->request->get("server_id") ?: null,
            ],
            [
                'code' => [new RequiredRule(), new MaxLengthRule(16)],
                'price_id' => [new RequiredRule(), new PriceExistsRule()],
                'uid' => [new UserExistsRule()],
                'server_id' => [new ServerExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $code = $validated["code"];
        $priceId = $validated["price_id"];
        $uid = $validated["uid"];
        $serverId = $validated["server_id"];

        $serviceCode = $serviceCodeRepository->create($code, $serviceId, $priceId, $serverId, $uid);

        $logger->logWithActor('log_code_added', $code, $serviceId);

        return new SuccessApiResponse($lang->t('code_added'), [
            'data' => [
                'id' => $serviceCode->getId(),
            ],
        ]);
    }
}
