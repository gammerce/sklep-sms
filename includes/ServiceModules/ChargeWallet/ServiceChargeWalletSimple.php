<?php
namespace App\ServiceModules\ChargeWallet;

use App\Interfaces\IBeLoggedMust;
use App\ServiceModules\Service;

abstract class ServiceChargeWalletSimple extends Service implements IBeLoggedMust
{
    const MODULE_ID = "charge_wallet";
}
