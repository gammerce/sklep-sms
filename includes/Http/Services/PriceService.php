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
        if (strlen(array_get($body, 'transfer_price'))) {
            $transferPrice = array_get($body, 'transfer_price') * 100;
        } else {
            $transferPrice = null;
        }

        return new Validator(
            array_merge($body, [
                'server_id' => as_int(array_get($body, 'server_id')),
                'sms_price' => as_int(array_get($body, 'sms_price')),
                'transfer_price' => $transferPrice,
                'quantity' => as_int(array_get($body, 'quantity')),
            ]),
            [
                'service_id' => [new RequiredRule(), new ServiceExistsRule()],
                'server_id' => [new ServerExistsRule()],
                'sms_price' => [new SmsPriceExistsRule()],
                'transfer_price' => [new MinValueRule(1)],
                'quantity' => [new RequiredRule(), new NumberRule()],
            ]
        );
    }
}
