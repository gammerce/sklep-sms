<?php
namespace App\Payment\General;

use App\Payment\Interfaces\IPurchaseRenderer;
use App\Payment\ServiceCode\PurchaseRenderer as ServiceCodePurchaseRenderer;
use App\Payment\Sms\PurchaseRenderer as SmsPurchaseRenderer;
use App\Payment\Transfer\PurchaseRenderer as TransferPurchaseRenderer;
use App\Payment\Wallet\PurchaseRenderer as WalletPurchaseRenderer;
use App\System\Application;

class PurchaseRendererFactory
{
    /** @var Application */
    private $app;

    private $renderersClasses = [
        ServiceCodePurchaseRenderer::class,
        SmsPurchaseRenderer::class,
        TransferPurchaseRenderer::class,
        WalletPurchaseRenderer::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return IPurchaseRenderer[]
     */
    public function all()
    {
        return collect($this->renderersClasses)
            ->map(function ($class) {
                return $this->app->make($class);
            })
            ->all();
    }
}
