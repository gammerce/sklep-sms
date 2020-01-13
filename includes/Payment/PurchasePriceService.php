<?php
namespace App\Payment;

use App\Models\Server;
use App\Models\Service;
use App\Models\SmsNumber;
use App\Repositories\PriceRepository;
use App\Repositories\SmsNumberRepository;
use App\System\Heart;
use App\System\Settings;

class PurchasePriceService
{
    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    /** @var SmsNumberRepository */
    private $smsNumberRepository;

    /** @var PriceRepository */
    private $priceRepository;

    public function __construct(
        Settings $settings,
        Heart $heart,
        SmsNumberRepository $smsNumberRepository,
        PriceRepository $priceRepository
    ) {
        $this->settings = $settings;
        $this->heart = $heart;
        $this->smsNumberRepository = $smsNumberRepository;
        $this->priceRepository = $priceRepository;
    }

    public function getServicePrices(Service $service, Server $server = null)
    {
        $output = [];

        $smsPlatformId =
            $server ? $server->getSmsPlatformId() : null ?: $this->settings->getSmsPlatformId();
        $transferPlatformId = $this->settings->getTransferPlatformId();
        $smsModule = $this->heart->getPaymentModuleByPlatformId($smsPlatformId);
        $transferModule = $this->heart->getPaymentModuleByPlatformId($transferPlatformId);

        $availableSmsPrices = array_map(function (SmsNumber $smsNumber) {
            return $smsNumber->getPrice();
        }, $this->smsNumberRepository->findByPaymentModule($smsModule->getModuleId()));

        $prices = $this->priceRepository->findByServiceServer($service, $server);
        foreach ($prices as $price) {
            $item = [
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
                $output[] = $price;
            }
        }

        return $output;
    }
}
