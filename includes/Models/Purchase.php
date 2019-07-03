<?php
namespace App\Models;

use App\Auth;

/**
 * Obiekty tej klasy są używane podczas przeprowadzania zakupu
 */
class Purchase
{
    /**
     * @var string
     */
    private $service = null;

    /**
     * Szczegóły zamawianej usługi
     *
     * @var array
     */
    private $order = null;

    /**
     * @var User
     */
    public $user;

    /**
     * @var Tariff
     */
    private $tariff = null;

    /**
     * @var string
     */
    private $email = null;

    /**
     * Szczegóły płatności
     *
     * @var array
     */
    private $payment = null;

    /**
     * Opis zakupu ( przydaje się przy płatności przelewem )
     *
     * @var string
     */
    private $desc = null;

    public function __construct()
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);

        $this->user = $auth->user();
    }

    public function setService($service)
    {
        $this->service = (string) $service;
    }

    public function setOrder($order)
    {
        foreach ($order as $key => $value) {
            $this->order[$key] = $value;
        }
    }

    /**
     * @param Tariff $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    public function setEmail($email)
    {
        $this->email = (string) $email;
    }

    public function setPayment($payment)
    {
        foreach ($payment as $key => $value) {
            $this->payment[$key] = $value;
        }
    }

    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getOrder($key = null)
    {
        if ($key === null) {
            return $this->order;
        }

        return if_isset($this->order[$key], null);
    }

    /**
     * @param string|null $key
     *
     * @return mixed
     */
    public function getPayment($key = null)
    {
        if ($key === null) {
            return $this->payment;
        }

        return if_isset($this->payment[$key], null);
    }

    /**
     * @return Tariff
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param bool $escaped
     *
     * @return string
     */
    public function getEmail($escaped = false)
    {
        return $escaped ? htmlspecialchars($this->email) : $this->email;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }
}
