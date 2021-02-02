<?php
namespace App\Models;

class PaymentPlatform
{
    private int $id;
    private string $name;
    private string $module;
    private array $data;

    public function __construct($id, $name, $module, array $data)
    {
        $this->id = $id;
        $this->name = $name;
        $this->module = $module;
        $this->data = $data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getModuleId(): string
    {
        return $this->module;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
