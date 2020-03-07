<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Models\Price;
use App\Models\Service;
use App\Repositories\PriceRepository;

class PriceAvailableRule extends BaseRule
{
    /** @var PriceRepository */
    private $priceRepository;

    /** @var Service */
    private $service;

    public function __construct(Service $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->priceRepository = app()->make(PriceRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $price = $this->priceRepository->get($value);
        $serviceId = $this->service->getId();
        $serverId = array_get($data, 'server_id');

        if (!$this->isPriceAvailable($serviceId, $serverId, $price)) {
            return [$this->lang->t('service_not_affordable')];
        }

        return [];
    }

    private function isPriceAvailable($serviceId, $serverId, Price $price = null)
    {
        return $price && $price->concernService($serviceId) && $price->concernServer($serverId);
    }
}
