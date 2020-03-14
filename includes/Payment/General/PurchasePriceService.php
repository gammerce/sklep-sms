<?php
namespace App\Payment\General;

use App\Models\Price;
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

    public function getServicePrices(Service $service, Server $server = null)
    {
        if ($server && $server->getSmsPlatformId()) {
            $smsPlatformId = $server->getSmsPlatformId();
        } else {
            $smsPlatformId = $this->settings->getSmsPlatformId();
        }
        $smsModule = $this->heart->getPaymentModuleByPlatformId($smsPlatformId);

        if ($smsModule instanceof SupportSms) {
            $availableSmsPrices = collect($smsModule::getSmsNumbers())
                ->map(function (SmsNumber $smsNumber) {
                    return $smsNumber->getPrice();
                })
                ->all();
        } else {
            $availableSmsPrices = [];
        }

        $prices = $this->priceRepository->findByServiceServer($service, $server);

        return collect($prices)
            ->map(function (Price $price) use ($smsModule, $availableSmsPrices) {
                $item = [
                    'id' => $price->getId(),
                    'quantity' => $price->getQuantity(),
                    'direct_billing_price' => null,
                    'sms_price' => null,
                    'transfer_price' => null,
                ];

                if ($price->hasDirectBillingPrice()) {
                    $item['direct_billing_price'] = $price->getDirectBillingPrice();
                }

                if ($price->hasTransferPrice()) {
                    $item['transfer_price'] = $price->getTransferPrice();
                }

                if (
                    $smsModule &&
                    $price->hasSmsPrice() &&
                    in_array($price->getSmsPrice(), $availableSmsPrices, true)
                ) {
                    $item['sms_price'] = $price->getSmsPrice();
                }

                return $item;
            })
            ->filter(function (array $item) {
                return $item['direct_billing_price'] !== null ||
                    $item['sms_price'] !== null ||
                    $item['transfer_price'] !== null;
            })
            ->all();
    }
}
