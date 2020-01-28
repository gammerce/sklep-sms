<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Models\Service;
use App\Payment\PurchaseValidationService;
use App\Repositories\PriceRepository;

class PriceAvailableRule extends BaseRule
{
    /** @var PriceRepository */
    private $priceRepository;

    /** @var PurchaseValidationService */
    private $purchaseValidationService;

    /** @var Service */
    private $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->priceRepository = app()->make(PriceRepository::class);
        $this->purchaseValidationService = app()->make(PurchaseValidationService::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $price = $this->priceRepository->get($value);
        $serviceId = $this->service->getId();
        $serverId = array_get($data, 'server_id');

        if (
            !$price ||
            !$this->purchaseValidationService->isPriceAvailable2($price, $serviceId, $serverId)
        ) {
            return [$this->lang->t('service_not_affordable')];
        }

        return [];
    }
}
