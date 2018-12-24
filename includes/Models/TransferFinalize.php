<?php
namespace App\Models;

// TODO Use marking transfer as a test
class TransferFinalize
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
    private $orderid = '';

    /**
     * Kwota płatności
     *
     * @var double
     */
    private $amount = 0.0;

    /**
     * Nazwa pliku z danymi zakupu
     * ( parametr $data_filename z metody prepareTransfer )
     *
     * @var string
     */
    private $data_filename = '';

    /**
     * Id usługi w danym serwisie
     *
     * @var string
     */
    private $transfer_service = '';

    /**
     * Co ma zostać wyświetlone na stronie
     *
     * @var string
     */
    private $output = '';

    /**
     * Czy to płatność testowa
     *
     * @var boolean
     */
    private $test = false;

    /**
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = (bool)$status;
    }

    /**
     * @return string
     */
    public function getOrderid()
    {
        return $this->orderid;
    }

    /**
     * @param string $orderid
     */
    public function setOrderid($orderid)
    {
        $this->orderid = (string)$orderid;
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
        $this->amount = (double)$amount;
    }

    /**
     * @return string
     */
    public function getDataFilename()
    {
        return $this->data_filename;
    }

    /**
     * @param string $data_filename
     */
    public function setDataFilename($data_filename)
    {
        $this->data_filename = (string)$data_filename;
    }

    /**
     * @return string
     */
    public function getTransferService()
    {
        return $this->transfer_service;
    }

    /**
     * @param string $transfer_service
     */
    public function setTransferService($transfer_service)
    {
        $this->transfer_service = (string)$transfer_service;
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
        $this->output = (string)$output;
    }

    public function markAsTest()
    {
        $this->test = true;
    }

    public function isTest()
    {
        return $this->test;
    }
}