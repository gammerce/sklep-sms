<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServiceExistsRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\PromoCodeRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PromoCodeCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        PromoCodeRepository $promoCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                "code" => $request->request->get("code"),
                "uid" => $request->request->get("uid") ?: null,
                "server_id" => $request->request->get("server_id") ?: null,
                "service_id" => $request->request->get("service_id") ?: null,
            ],
            [
                "code" => [new RequiredRule(), new MaxLengthRule(16)],
                "uid" => [new UserExistsRule()],
                "server_id" => [new ServerExistsRule()],
                "service_id" => [new ServiceExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $code = $validated["code"];
        $uid = $validated["uid"];
        $serverId = $validated["server_id"];
        $serviceId = $validated["service_id"];

        $serviceCode = $promoCodeRepository->create($code, $serviceId, $serverId, $uid);

        $logger->logWithActor("log_code_added", $code, $serviceId);

        return new SuccessApiResponse($lang->t("code_added"), [
            "data" => [
                "id" => $serviceCode->getId(),
            ],
        ]);
    }
}
