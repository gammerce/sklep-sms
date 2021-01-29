<?php
namespace App\Http\Services;

use App\Exceptions\InvalidPaymentModuleException;
use App\Managers\PaymentModuleManager;
use App\Verification\Exceptions\ProcessDataFieldsException;

class PaymentPlatformService
{
    private PaymentModuleManager $paymentModuleManager;

    public function __construct(PaymentModuleManager $paymentModuleManager)
    {
        $this->paymentModuleManager = $paymentModuleManager;
    }

    /**
     * @param string $moduleId
     * @param array $data
     * @return array
     * @throws InvalidPaymentModuleException
     * @throws ProcessDataFieldsException
     */
    public function processDataFields($moduleId, array $data)
    {
        $moduleClass = $this->paymentModuleManager->getClass($moduleId);

        $filteredData = [];
        $dataFields = $this->paymentModuleManager->dataFields($moduleId);

        foreach ($dataFields as $dataField) {
            $filteredData[$dataField->getId()] = array_get($data, $dataField->getId());
        }

        return $moduleClass::processDataFields($filteredData);
    }
}
