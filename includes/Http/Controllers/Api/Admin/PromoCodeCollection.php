<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\DateTimeRule;
use App\Http\Validation\Rules\EnumRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\MinValueRule;
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

        $validator = new Validator($request->request->all(), [
            "code" => [new RequiredRule(), new MaxLengthRule(16)],
            "quantity_type" => [new RequiredRule(), new EnumRule(QuantityType::class)],
            "quantity" => [new RequiredRule(), new IntegerRule(), new MinValueRule(0)],
            "usage_limit" => [new IntegerRule(), new MinValueRule(0)],
            "expires_at" => [new DateTimeRule()],
            "user_id" => [new UserExistsRule()],
            "server_id" => [new ServerExistsRule()],
            "service_id" => [new ServiceExistsRule()],
        ]);

        $validated = $validator->validateOrFail();

        $code = as_string($validated["code"]);
        $quantityType = new QuantityType($validated["quantity_type"]);

        if ($quantityType->equals(QuantityType::FIXED())) {
            $quantity = price_to_int($validated["quantity"]);
        } else {
            $quantity = as_int($validated["quantity"]);
        }

        $usageLimit = as_int($validated["usage_limit"]);
        $expiresAt = as_datetime($validated["expires_at"]);
        $userId = as_int($validated["user_id"]);
        $serverId = as_int($validated["server_id"]);
        $serviceId = as_string($validated["service_id"]) ?: null;

        if ($expiresAt) {
            $expiresAt->setTime(23, 59, 59);
        }

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
