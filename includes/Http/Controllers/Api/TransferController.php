<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\Payment\TransferPaymentService;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;
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

    public function action(
        $transferService,
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService
    ) {
        /** @var SupportTransfer $paymentModule */
        $paymentModule = $heart->getPaymentModule($transferService);

        if (!($paymentModule instanceof SupportTransfer)) {
            return new PlainResponse("Invalid payment module [${transferService}].");
        }

        $transferFinalize = $paymentModule->finalizeTransfer(
            $request->query->all(),
            $request->request->all()
        );

        if ($transferFinalize->getStatus() === false) {
            log_to_db(
                $this->langShop->sprintf(
                    $this->langShop->translate('payment_not_accepted'),
                    $transferFinalize->getOrderid(),
                    $transferFinalize->getAmount(),
                    $transferFinalize->getTransferService()
                )
            );
        } else {
            $transferPaymentService->transferFinalize($transferFinalize);
        }

        return new Response($transferFinalize->getOutput(), 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
    }

    public function oldAction(
        Request $request,
        Heart $heart,
        TransferPaymentService $transferPaymentService
    ) {
        return $this->action(
            $request->query->get('service'),
            $request,
            $heart,
            $transferPaymentService
        );
    }
}
