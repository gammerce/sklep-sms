<?php
namespace App\Payment;

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
        $output = [];

        if ($server && $server->getSmsPlatformId()) {
            $smsPlatformId = $server->getSmsPlatformId();
        } else {
            $smsPlatformId = $this->settings->getSmsPlatformId();
        }
        $transferPlatformId = $this->settings->getTransferPlatformId();
        $smsModule = $this->heart->getPaymentModuleByPlatformId($smsPlatformId);
        $transferModule = $this->heart->getPaymentModuleByPlatformId($transferPlatformId);

        if ($smsModule instanceof SupportSms) {
            $availableSmsPrices = array_map(function (SmsNumber $smsNumber) {
                return $smsNumber->getPrice();
            }, $smsModule::getSmsNumbers());
        } else {
            $availableSmsPrices = [];
        }

        $prices = $this->priceRepository->findByServiceServer($service, $server);
        foreach ($prices as $price) {
            $item = [
                'id' => $price->getId(),
                'quantity' => $price->getQuantity(),
                'sms_price' => null,
                'transfer_price' => null,
            ];

            if ($transferModule && $price->hasTransferPrice()) {
                $item['transfer_price'] = $price->getTransferPrice();
            }

            if (
                $smsModule &&
                $price->hasSmsPrice() &&
                in_array($price->getSmsPrice(), $availableSmsPrices, true)
            ) {
                $item['sms_price'] = $price->getSmsPrice();
            }

            if ($item['sms_price'] !== null || $item['transfer_price'] !== null) {
                $output[] = $item;
            }
        }

        return $output;
    }
}
