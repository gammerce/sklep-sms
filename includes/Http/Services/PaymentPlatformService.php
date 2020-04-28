<?php
namespace App\Http\Services;

use App\Managers\PaymentModuleManager;

class PaymentPlatformService
{
    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(PaymentModuleManager $paymentModuleManager)
    {
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getValidatedData($moduleId, array $data)
    {
        $filteredData = [];
        $dataFields = $this->paymentModuleManager->dataFields($moduleId);

        foreach ($dataFields as $dataField) {
            $filteredData[$dataField->getId()] = array_get($data, $dataField->getId());
        }

        return $filteredData;
    }
}
