<?php
namespace App\Http\Services;

use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MaxValueRule;
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
        $directBillingPrice = price_to_int(array_get($body, "direct_billing_price"));
        $transferPrice = price_to_int(array_get($body, "transfer_price"));

        return new Validator(
            array_merge($body, [
                "direct_billing_price" => $directBillingPrice,
                "server_id" => as_int(array_get($body, "server_id")),
                "sms_price" => as_int(array_get($body, "sms_price")),
                "transfer_price" => $transferPrice,
                "quantity" => as_int(array_get($body, "quantity")),
                "discount" => as_int(array_get($body, "discount")),
            ]),
            [
                "direct_billing_price" => [new MinValueRule(0.01)],
                "service_id" => [new RequiredRule(), new ServiceExistsRule()],
                "server_id" => [new ServerExistsRule()],
                "sms_price" => [new SmsPriceExistsRule()],
                "transfer_price" => [new MinValueRule(1)],
                "quantity" => [new NumberRule()],
                "discount" => [new IntegerRule(), new MinValueRule(1), new MaxValueRule(100)],
            ]
        );
    }
}
