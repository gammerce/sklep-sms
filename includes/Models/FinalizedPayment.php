<?php
namespace App\Models;

use App\Support\Money;

class FinalizedPayment
{
    /**
     * Payment status, is it valid
     */
    private bool $status = false;

    /**
     * Payment ID
     */
    private string $orderId = "";

    /**
     * Payment value gross
     */
    private Money $cost;

    /**
     * How much money is received
     */
    private Money $income;

    /**
     * Filename of transaction. ID from Purchase object.
     */
    private string $transactionId = "";

    /**
     * Service ID from the external system
     */
    private string $externalServiceId = "";

    /**
     * What should be displayed as a response
     */
    private string $output = "";

    /**
     * Is it test payment
     */
    private bool $testMode = false;

    public function __construct()
    {
        $this->cost = new Money(0);
        $this->income = new Money(0);
    }

    public function isSuccessful(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function setStatus($status): self
    {
        $this->status = (bool) $status;
        return $this;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId): self
    {
        $this->orderId = (string) $orderId;
        return $this;
    }

    public function getCost(): Money
    {
        return $this->cost;
    }

    /**
     * @param Money|int $cost
     * @return $this
     */
    public function setCost($cost): self
    {
        $this->cost = new Money($cost);
        return $this;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId): self
    {
        $this->transactionId = (string) $transactionId;
        return $this;
    }

    public function getExternalServiceId(): string
    {
        return $this->externalServiceId;
    }

    /**
     * @param string $externalServiceId
     * @return $this
     */
    public function setExternalServiceId($externalServiceId): self
    {
        $this->externalServiceId = (string) $externalServiceId;
        return $this;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * @param string $output
     * @return $this
     */
    public function setOutput($output): self
    {
        $this->output = (string) $output;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setTestMode($value): self
    {
        $this->testMode = (bool) $value;
        return $this;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function getIncome(): Money
    {
        return $this->income;
    }

    /**
     * @param Money|int $income
     * @return $this
     */
    public function setIncome($income): self
    {
        $this->income = new Money($income);
        return $this;
    }
}
