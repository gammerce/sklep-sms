<?php
namespace App\Payment\General;

final class BillingAddress
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
}
