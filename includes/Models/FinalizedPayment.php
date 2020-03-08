<?php
namespace App\Models;

class FinalizedPayment
{
    /**
     * Payment status, is it valid
     *
     * @var bool
     */
    private $status = false;

    /**
     * Payment ID
     *
     * @var string
     */
    private $orderId = '';

    /**
     * Payment value gross
     *
     * @var int
     */
    private $amount = 0;

    /**
     * How much money is received
     *
     * @var int
     */
    private $income = 0;

    /**
     * Filename of transaction
     *
     * @var string
     */
    private $dataFilename = '';

    /**
     * Service ID from the external system
     *
     * @var string
     */
    private $externalServiceId = '';

    /**
     * What should be displayed as a response
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
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = (int) $amount;
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

    /**
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param int $income
     */
    public function setIncome($income)
    {
        $this->income = (int) $income;
    }
}
