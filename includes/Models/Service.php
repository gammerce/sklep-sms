<?php
namespace App\Models;

class Service
{
    private string $id;
    private string $name;
    private string $shortDescription;
    private string $description;
    private int $types;
    private string $tag;

    /**
     * ServiceModule identifier
     */
    private string $module;

    private array $groups;
    private string $flags;
    private int $order;
    private ?array $data;

    public function __construct(
        $id,
        $name,
        $shortDescription,
        $description,
        $types,
        $tag,
        $module,
        array $groups,
        $flags,
        $order,
        ?array $data
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->shortDescription = $shortDescription;
        $this->description = $description;
        $this->types = $types;
        $this->tag = $tag;
        $this->module = $module;
        $this->groups = $groups;
        $this->flags = $flags;
        $this->order = $order;
        $this->data = $data ?: [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameI18n(): string
    {
        return __($this->name);
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getShortDescriptionI18n(): string
    {
        return __($this->shortDescription);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDescriptionI18n(): string
    {
        return __($this->description);
    }

    public function getTypes(): int
    {
        return $this->types;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getFlags(): string
    {
        return $this->flags;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * VAT tax rate
     */
    public function getTaxRate(): int
    {
        return array_get($this->data, "tax_rate", 0);
    }

    /**
     * In case of ryczałt ewidencjonowany
     */
    public function getFlatRateTax(): ?string
    {
        return array_get($this->data, "flat_rate_tax");
    }

    public function getPKWiUSymbol(): ?string
    {
        return array_get($this->data, "pkwiu");
    }
}
