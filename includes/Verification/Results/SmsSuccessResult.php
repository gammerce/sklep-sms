<?php
namespace App\Verification\Results;

class SmsSuccessResult
{
    /** @var bool */
    private $free;

    /** @var int */
    private $income;

    public function __construct($free = false, $income = 0)
    {
        $this->free = $free;
        $this->income = $income;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->free;
    }

    /**
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }
}
