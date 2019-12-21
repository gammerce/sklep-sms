<?php
namespace App\Models;

class PaymentPlatform
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $platform;

    /** @var array */
    private $data;

    public function __construct($id, $name, $platform, array $data)
    {
        $this->id = $id;
        $this->name = $name;
        $this->platform = $platform;
        $this->data = $data;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return string */
    public function getPlatform()
    {
        return $this->platform;
    }

    /** @return array */
    public function getData()
    {
        return $this->data;
    }
}
