<?php
namespace App\Models;

use App\Support\Money;

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
     * @var Money
     */
    private $cost;

    /**
     * How much money is received
     *
     * @var Money
     */
    private $income;

    /**
     * Filename of transaction
     *
     * @var string
     */
    private $transactionId = '';

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

    public function __construct()
    {
        $this->cost = new Money(0);
        $this->income = new Money(0);
    }

    /**
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = (bool) $status;
        return $this;
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
     * @return $this
     */
    public function setOrderId($orderId)
    {
        $this->orderId = (string) $orderId;
        return $this;
    }

    /**
     * @return Money
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @param int|Money $cost
     * @return $this
     */
    public function setCost($cost)
    {
        $this->cost = new Money($cost);
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = (string) $transactionId;
        return $this;
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
     * @return $this
     */
    public function setExternalServiceId($externalServiceId)
    {
        $this->externalServiceId = (string) $externalServiceId;
        return $this;
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
     * @return $this
     */
    public function setOutput($output)
    {
        $this->output = (string) $output;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setTestMode($value)
    {
        $this->testMode = (bool) $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->testMode;
    }

    /**
     * @return Money
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param int|Money $income
     * @return $this
     */
    public function setIncome($income)
    {
        $this->income = new Money($income);
        return $this;
    }
}
