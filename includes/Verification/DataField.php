<?php
namespace App\Verification;

class DataField
{
    private string $id;
    private ?string $name;

    public function __construct($id, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
