<?php
namespace App\Models;

class FinalizedPayment
{
    /**
     * Status płatności, czy wszystkie dane są prawidłowe
     *
     * @var bool
     */
    private $status = false;

    /**
     * ID płatności
     *
     * @var string
     */
    private $orderId = '';

    /**
     * Kwota płatności
     *
     * @var double
     */
    private $amount = 0.0;

    /**
     * Nazwa pliku z danymi zakupu
     * ( parametr $dataFilename z metody prepareTransfer )
     *
     * @var string
     */
    private $dataFilename = '';

    /**
     * Id usługi w danym serwisie
     *
     * @var string
     */
    private $externalServiceId = '';

    /**
     * Co ma zostać wyświetlone na stronie
     *
     * @var string
     */
    private $output = '';

    /**
     * Is it test payment
     *
     * @var bool
     */
    private $testMode = false;

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = (bool) $status;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = (string) $orderId;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = (float) $amount;
    }

    /**
     * @return string
     */
    public function getDataFilename()
    {
        return $this->dataFilename;
    }

    /**
     * @param string $dataFilename
     */
    public function setDataFilename($dataFilename)
    {
        $this->dataFilename = (string) $dataFilename;
    }

    /**
     * @return string
     */
    public function getExternalServiceId()
    {
        return $this->externalServiceId;
    }

    /**
     * @param string $externalServiceId
     */
    public function setExternalServiceId($externalServiceId)
    {
        $this->externalServiceId = (string) $externalServiceId;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = (string) $output;
    }

    /**
     * @param bool $value
     */
    public function setTestMode($value)
    {
        $this->testMode = (bool) $value;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }
}
