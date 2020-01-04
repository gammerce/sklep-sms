<?php
namespace App\Verification;

class DataField
{
    /** @var string */
    private $id;

    /** @var string|null */
    private $name;

    public function __construct($id, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
}
