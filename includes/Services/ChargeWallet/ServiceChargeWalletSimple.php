<?php
namespace App\Services\ChargeWallet;

use App\Interfaces\IBeLoggedMust;
use App\Services\Service;

class ServiceChargeWalletSimple extends Service implements IBeLoggedMust
{
    const MODULE_ID = "charge_wallet";
}
