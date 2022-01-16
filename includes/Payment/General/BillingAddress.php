<?php
namespace App\Payment\General;

use Illuminate\Contracts\Support\Arrayable;

final class BillingAddress implements Arrayable
{
    private string $name;
    private string $vatID;
    private string $street;
    private string $postalCode;
    private string $city;

    public function __construct(
        string $name,
        string $vatID,
        string $street,
        string $postalCode,
        string $city
    ) {
        $this->name = $name;
        $this->vatID = $vatID;
        $this->street = $street;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public static function empty(): self
    {
        return new self("", "", "", "", "");
    }

    public static function fromArray($data): self
    {
        return new self(
            array_get($data, "name") ?? "",
            array_get($data, "vat_id") ?? "",
            array_get($data, "street") ?? "",
            array_get($data, "postal_code") ?? "",
            array_get($data, "city") ?? ""
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVatID(): string
    {
        return $this->vatID;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function toArray()
    {
        return [
            "name" => $this->name,
            "vat_id" => $this->vatID,
            "street" => $this->street,
            "postal_code" => $this->postalCode,
            "city" => $this->city,
        ];
    }
}
