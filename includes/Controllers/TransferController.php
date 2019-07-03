<?php
namespace App\Controllers;

use App\Payment;
use App\TranslationManager;
use App\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferController
{
    /** @var Translator */
    private $langShop;

    public function __construct(TranslationManager $translationManager)
    {
        $this->langShop = $translationManager->shop();
    }

    public function action(Request $request, $transferService)
    {
        $payment = new Payment($transferService);
        $transferFinalize = $payment
            ->getPaymentModule()
            ->finalizeTransfer($request->query->all(), $request->request->all());

        if ($transferFinalize->getStatus() === false) {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('payment_not_accepted'),
                    $transferFinalize->getOrderid(),
                    $transferFinalize->getAmount(),
                    $transferFinalize->getTransferService()
                )
            );
        } else {
            $payment->transferFinalize($transferFinalize);
        }

        return new Response($transferFinalize->getOutput(), 200, [
            'Content-type' => 'text/plaint; charset="UTF-8"'
        ]);
    }

    public function oldAction(Request $request)
    {
        return $this->action($request, $request->query->get('service'));
    }
}
