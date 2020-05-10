<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\EnumRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServiceExistsRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\PromoCode\QuantityType;
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
                "quantity_type" => $request->request->get("quantity_type"),
                "quantity" => $request->request->get("quantity"),
                "uid" => $request->request->get("uid") ?: null,
                "server_id" => $request->request->get("server_id") ?: null,
                "service_id" => $request->request->get("service_id") ?: null,
            ],
            [
                "code" => [new RequiredRule(), new MaxLengthRule(16)],
                "quantity_type" => [new RequiredRule(), new EnumRule(QuantityType::class)],
                "quantity" => [new RequiredRule(), new IntegerRule()],
                "uid" => [new UserExistsRule()],
                "server_id" => [new ServerExistsRule()],
                "service_id" => [new ServiceExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $code = $validated["code"];
        $quantityType = new QuantityType($validated["quantity_type"]);
        $quantity = $validated["quantity"];
        $uid = $validated["uid"];
        $serverId = $validated["server_id"];
        $serviceId = $validated["service_id"];

        $serviceCode = $promoCodeRepository->create(
            $code,
            $quantityType,
            $quantity,
            $serviceId,
            $serverId,
            $uid
        );
        $logger->logWithActor("log_promo_code_added", $code, $serviceId);

        return new SuccessApiResponse($lang->t("promo_code_added"), [
            "data" => [
                "id" => $serviceCode->getId(),
            ],
        ]);
    }
}
