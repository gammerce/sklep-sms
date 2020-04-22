<?php
namespace App\Payment\General;

use App\Models\Price;
use App\Models\QuantityPrice;
use App\Models\Server;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Repositories\PriceRepository;
use App\System\Heart;
use App\System\Settings;
use App\Verification\Abstracts\SupportSms;

class PurchasePriceService
{
    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    /** @var PriceRepository */
    private $priceRepository;

    public function __construct(Settings $settings, Heart $heart, PriceRepository $priceRepository)
    {
        $this->settings = $settings;
        $this->heart = $heart;
        $this->priceRepository = $priceRepository;
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
                $item->directBillingPrice = $price->getDirectBillingPrice();
                $item->directBillingDiscount = $price->getDiscount();
            }

            if ($this->isAvailableUsingSms($price, $server)) {
                $item->smsPrice = $price->getSmsPrice();
                $item->smsDiscount = $price->getDiscount();
            }

            if ($this->isAvailableUsingTransfer($price, $server)) {
                $item->transferPrice = $price->getTransferPrice();
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

        $smsModule = $this->heart->getPaymentModuleByPlatformId($smsPlatformId);

        if ($smsModule instanceof SupportSms) {
            return collect($smsModule::getSmsNumbers())->some(function (SmsNumber $smsNumber) use (
                $price
            ) {
                return $smsNumber->getPrice() === $price->getSmsPrice();
            });
        }

        return false;
    }

    private function isAvailableUsingTransfer(Price $price, Server $server = null)
    {
        if (!$price->hasTransferPrice()) {
            return false;
        }

        return ($server && $server->getTransferPlatformId()) ||
            $this->settings->getTransferPlatformId();
    }

    private function isAvailableUsingDirectBilling(Price $price)
    {
        return $price->hasDirectBillingPrice();
    }
}
