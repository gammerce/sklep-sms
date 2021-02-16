<?php
namespace App\Verification\Results;

class SmsSuccessResult
{
    private bool $free;
    private ?int $income;

    public function __construct($free = false, $income = null)
    {
        $this->free = $free;
        $this->income = $income;
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function getIncome(): ?int
    {
        return $this->income;
    }
}
