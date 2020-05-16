<?php
namespace App\Payment\General;

use MyCLabs\Enum\Enum;

/**
 * @method static PaymentMethod ADMIN()
 * @method static PaymentMethod DIRECT_BILLING()
 * @method static PaymentMethod SMS()
 * @method static PaymentMethod TRANSFER()
 * @method static PaymentMethod WALLET()
 */
final class PaymentMethod extends Enum
{
    const ADMIN = "admin";
    const DIRECT_BILLING = "direct_billing";
    const SMS = "sms";
    const TRANSFER = "transfer";
    const WALLET = "wallet";
}
