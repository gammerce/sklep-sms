<?php
namespace App\ServiceModules\ChargeWallet;

use App\View\Interfaces\IBeLoggedMust;
use App\ServiceModules\ServiceModule;

abstract class ServiceChargeWalletSimple extends ServiceModule implements IBeLoggedMust
{
    const MODULE_ID = "charge_wallet";
}
