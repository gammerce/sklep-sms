<?php
namespace App\Payment\General;

use App\Managers\PaymentModuleManager;
use App\Models\Price;
use App\Models\QuantityPrice;
use App\Models\Server;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Repositories\PriceRepository;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class PurchasePriceService
{
    /** @var Settings */
    private $settings;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        Settings $settings,
        PriceRepository $priceRepository,
        PaymentModuleManager $paymentModuleManager
    ) {
        $this->settings = $settings;
        $this->priceRepository = $priceRepository;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    /**
     * @param Service $service
     * @param Server|null $server
     * @return QuantityPrice[]
     */
    public function getServicePrices(Service $service, Server $server = null)
    {
        $output = [];
        $prices = $this->priceRepository->findByServiceServer($service, $server);

        foreach ($prices as $price) {
            // Use -1 as null value because frontend treats null as an empty string.
            // What is more in PHP it's prohibited to use null as array key.
            $quantity = $price->getQuantity() === null ? -1 : $price->getQuantity();

            /** @var QuantityPrice $item */
            $item = array_get($output, $quantity, new QuantityPrice($quantity));

            if ($this->isAvailableUsingDirectBilling($price)) {
                $item->directBillingPrice = as_int($price->getDirectBillingPrice());
                $item->directBillingDiscount = $price->getDiscount();
            }

            if ($this->isAvailableUsingSms($price, $server)) {
                $item->smsPrice = as_int($price->getSmsPrice());
                $item->smsDiscount = $price->getDiscount();
            }

            if ($this->isAvailableUsingWallet($price)) {
                $item->transferPrice = as_int($price->getTransferPrice());
                $item->transferDiscount = $price->getDiscount();
            }

            if ($item) {
                $output[$quantity] = $item;
            }
        }

        return $output;
    }

    /**
     * @param int $quantity
     * @param Service $service
     * @param Server|null $server
     * @return QuantityPrice|null
     */
    public function getServicePriceByQuantity($quantity, Service $service, Server $server = null)
    {
        $quantityPrices = $this->getServicePrices($service, $server);
        return array_get($quantityPrices, $quantity);
    }

    private function isAvailableUsingSms(Price $price, Server $server = null)
    {
        if (!$price->hasSmsPrice()) {
            return false;
        }

        if ($server && $server->getSmsPlatformId()) {
            $smsPlatformId = $server->getSmsPlatformId();
        } else {
            $smsPlatformId = $this->settings->getSmsPlatformId();
        }

        $smsModule = $this->paymentModuleManager->getByPlatformId($smsPlatformId);

        if ($smsModule instanceof SupportSms) {
            return collect($smsModule->getSmsNumbers())->some(function (SmsNumber $smsNumber) use (
                $price
            ) {
                return $smsNumber->getPrice()->equal($price->getSmsPrice());
            });
        }

        return false;
    }

    private function isAvailableUsingWallet(Price $price)
    {
        return $price->hasTransferPrice();
    }

    private function isAvailableUsingDirectBilling(Price $price)
    {
        return $price->hasDirectBillingPrice();
    }
}
