<?php
namespace App\Verification\Results;


class SmsSuccessResult {
    /** @var bool */
    public $free;

    public function __construct($free = false)
    {
        $this->free = $free;
    }
}