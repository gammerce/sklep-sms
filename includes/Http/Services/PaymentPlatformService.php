<?php
namespace App\Http\Services;

use App\System\Heart;

class PaymentPlatformService
{
    /** @var Heart */
    private $heart;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
    }

    public function getValidatedData($moduleId, array $data)
    {
        $filteredData = [];
        $dataFields = $this->heart->getPaymentModuleDataFields($moduleId);

        foreach ($dataFields as $dataField) {
            $filteredData[$dataField->getId()] = array_get($data, $dataField->getId());
        }

        return $filteredData;
    }
}
