<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServiceExistsRule;
use App\Http\Validation\Rules\SmsPriceExistsRule;
use App\Http\Validation\Validator;

class PriceService
{
    public function createValidator(array $body)
    {
        $transferPrice = $this->getFloatValueAsInt($body, "transfer_price");
        $directBillingPrice = $this->getFloatValueAsInt($body, "direct_billing_price");

        return new Validator(
            array_merge($body, [
                "direct_billing_price" => $directBillingPrice,
                "server_id" => as_int(array_get($body, "server_id")),
                "sms_price" => as_int(array_get($body, "sms_price")),
                "transfer_price" => $transferPrice,
                "quantity" => as_int(array_get($body, "quantity")),
            ]),
            [
                "direct_billing_price" => [new MinValueRule(0.01)],
                "service_id" => [new RequiredRule(), new ServiceExistsRule()],
                "server_id" => [new ServerExistsRule()],
                "sms_price" => [new SmsPriceExistsRule()],
                "transfer_price" => [new MinValueRule(1)],
                "quantity" => [new RequiredRule(), new NumberRule()],
            ]
        );
    }

    private function getFloatValueAsInt(array $body, $key)
    {
        return strlen(array_get($body, $key)) ? intval(array_get($body, $key) * 100) : null;
    }
}
