<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\DateTimeRule;
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
                "usage_limit" => $request->request->get("usage_limit") ?: null,
                "expires_at" => $request->request->get("expires_at") ?: null,
                "user_id" => $request->request->get("user_id") ?: null,
                "server_id" => $request->request->get("server_id") ?: null,
                "service_id" => $request->request->get("service_id") ?: null,
            ],
            [
                "code" => [new RequiredRule(), new MaxLengthRule(16)],
                "quantity_type" => [new RequiredRule(), new EnumRule(QuantityType::class)],
                "quantity" => [new RequiredRule(), new IntegerRule()],
                "usage_limit" => [new IntegerRule()],
                "expires_at" => [new DateTimeRule()],
                "user_id" => [new UserExistsRule()],
                "server_id" => [new ServerExistsRule()],
                "service_id" => [new ServiceExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $code = as_string($validated["code"]);
        $quantityType = new QuantityType($validated["quantity_type"]);
        $quantity = as_int($validated["quantity"]);
        $usageLimit = as_int($validated["usage_limit"]);
        $expiresAt = as_datetime($validated["expires_at"]);
        $userId = as_int($validated["user_id"]);
        $serverId = as_int($validated["server_id"]);
        $serviceId = as_string($validated["service_id"]);

        $promoCode = $promoCodeRepository->create(
            $code,
            $quantityType,
            $quantity,
            $usageLimit,
            $expiresAt,
            $serviceId,
            $serverId,
            $userId
        );
        $logger->logWithActor("log_promo_code_added", $code, $serviceId);

        return new SuccessApiResponse($lang->t("promo_code_added"), [
            "data" => [
                "id" => $promoCode->getId(),
            ],
        ]);
    }
}
