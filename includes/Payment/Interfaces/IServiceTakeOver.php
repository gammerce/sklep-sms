<?php
namespace App\Payment\Interfaces;

interface IServiceTakeOver
{
    /**
     * @param int $paymentId
     * @param string $serviceId
     * @param int $authData
     * @param int $serverId
     * @return bool
     */
    public function isValid($paymentId, $serviceId, $authData, $serverId): bool;
}
