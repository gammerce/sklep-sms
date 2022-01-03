<?php
namespace App\Payment\General;

final class BillingAddress
{
    private string $name;
    private string $vatID;
    private string $address;
    private string $postalCode;
    private string $city;

    public function __construct(
        string $name,
        string $vatID,
        string $address,
        string $postalCode,
        string $city
    ) {
        $this->name = $name;
        $this->vatID = $vatID;
        $this->address = $address;
        $this->postalCode = $postalCode;
        $this->city = $city;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVatID(): string
    {
        return $this->vatID;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }
}
