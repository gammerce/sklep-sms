<?php
namespace App\Controllers;

use App\Payment;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferController
{
    public function get(Request $request, $transferService, TranslationManager $translationManager)
    {
        $langShop = $translationManager->shop();

        $payment = new Payment($transferService);
        $transferFinalize = $payment
            ->getPaymentModule()
            ->finalizeTransfer($request->query->all(), $request->request->all());

        if ($transferFinalize->getStatus() === false) {
            log_info($langShop->sprintf(
                $langShop->translate('payment_not_accepted'),
                $transferFinalize->getOrderid(),
                $transferFinalize->getAmount(),
                $transferFinalize->getTransferService()
            ));
        } else {
            $payment->transferFinalize($transferFinalize);
        }

        return new Response($transferFinalize->getOutput(), 200, [
            'Content-type' => 'text/plaint; charset="UTF-8"',
        ]);
    }
}