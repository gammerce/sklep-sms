<?php
namespace App\Models;

class PaymentPlatform
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $module;

    /** @var array */
    private $data;

    public function __construct($id, $name, $module, array $data)
    {
        $this->id = $id;
        $this->name = $name;
        $this->module = $module;
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
    public function getModule()
    {
        return $this->module;
    }

    /** @return array */
    public function getData()
    {
        return $this->data;
    }
}
