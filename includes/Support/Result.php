<?php
namespace App\Support;

class Result
{
    /** @var string */
    private $status;

    /** @var bool */
    private $positive;

    /** @var string */
    private $text;

    /** @var array */
    private $data;

    public function __construct($status, $text, $positive, array $data = [])
    {
        $this->status = $status;
        $this->text = $text;
        $this->positive = $positive;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isPositive()
    {
        return $this->positive;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getDatum($key)
    {
        return array_get($this->data, $key);
    }
}
