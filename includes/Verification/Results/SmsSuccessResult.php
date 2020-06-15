<?php
namespace App\Verification\Results;

class SmsSuccessResult
{
    /** @var bool */
    private $free;

    /** @var int|null */
    private $income;

    public function __construct($free = false, $income = null)
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
     * @return int|null
     */
    public function getIncome()
    {
        return $this->income;
    }
}
