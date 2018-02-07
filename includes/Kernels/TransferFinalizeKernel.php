<?php
namespace App\Kernels;

use App\Middlewares\IsUpToDate;
use App\Payment;
use App\TranslationManager;
use App\Middlewares\DecodeGetAttributes;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferFinalizeKernel extends Kernel
{
    protected $middlewares = [
        DecodeGetAttributes::class,
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
    ];

    public function run(Request $request)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $langShop = $translationManager->shop();

        $payment = new Payment($_GET['service']);
        $transfer_finalize = $payment->getPaymentModule()->finalizeTransfer($_GET, $_POST);

        if ($transfer_finalize->getStatus() === false) {
            log_info($langShop->sprintf(
                $langShop->translate('payment_not_accepted'),
                $transfer_finalize->getOrderid(),
                $transfer_finalize->getAmount(),
                $transfer_finalize->getTransferService()
            ));
        } else {
            $payment->transferFinalize($transfer_finalize);
        }

        return new Response($transfer_finalize->getOutput(), 200, [
            'Content-type' => 'text/plaint; charset="UTF-8"',
        ]);
    }
}
