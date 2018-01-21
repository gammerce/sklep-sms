<?php
namespace App\Kernels;

use App\Payment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransferFinalizeKernel extends Kernel
{
    public function handle(Request $request)
    {
        global $lang_shop;

        $payment = new Payment($_GET['service']);
        $transfer_finalize = $payment->getPaymentModule()->finalizeTransfer($_GET, $_POST);

        if ($transfer_finalize->getStatus() === false) {
            log_info($lang_shop->sprintf(
                $lang_shop->translate('payment_not_accepted'),
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
