<?php
namespace App\Payment;

use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Repositories\PaymentTransferRepository;
use App\Services\Interfaces\IServicePurchase;
use App\System\Database;
use App\System\FileSystemContract;
use App\System\Heart;
use App\System\Path;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentService
{
    /** @var Database */
    private $db;

    /** @var Path */
    private $path;

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var Translator */
    private $langShop;

    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var FileSystemContract */
    private $fileSystem;

    public function __construct(
        Database $db,
        Path $path,
        Heart $heart,
        PaymentTransferRepository $paymentTransferRepository,
        TranslationManager $translationManager,
        FileSystemContract $fileSystem
    ) {
        $this->db = $db;
        $this->path = $path;
        $this->heart = $heart;
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->fileSystem = $fileSystem;
    }

    /**
     * Prepares data for transfer payment
     *
     * @param SupportTransfer $paymentModule
     * @param Purchase        $purchase
     * @return array
     */
    public function payWithTransfer(SupportTransfer $paymentModule, Purchase $purchase)
    {
        $serialized = serialize($purchase);
        $dataFilename = time() . "-" . md5($serialized);
        $path = $this->path->to('data/transfers/' . $dataFilename);
        $this->fileSystem->put($path, $serialized);

        return [
            'status' => "transfer",
            'text' => $this->lang->t('transfer_prepared'),
            'positive' => true,
            'data' => [
                'data' => $paymentModule->prepareTransfer($purchase, $dataFilename),
            ],
        ];
    }

    /**
     * @param TransferFinalize $transferFinalize
     * @return bool
     */
    public function transferFinalize(TransferFinalize $transferFinalize)
    {
        $paymentTransfer = $this->paymentTransferRepository->get($transferFinalize->getOrderId());

        // Avoid multiple authorization of the same order
        if ($paymentTransfer) {
            return false;
        }

        if (
            !$transferFinalize->getDataFilename() ||
            !$this->fileSystem->exists(
                $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
            )
        ) {
            log_to_db($this->langShop->t('transfer_no_data_file', $transferFinalize->getOrderId()));

            return false;
        }

        /** @var Purchase $purchase */
        $purchase = unserialize(
            $this->fileSystem->get(
                $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
            )
        );

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->heart->getUser($purchase->user->getUid());

        $this->paymentTransferRepository->create(
            $transferFinalize->getOrderId(),
            $purchase->getPayment('cost'),
            $transferFinalize->getTransferService(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform()
        );
        $this->fileSystem->delete(
            $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
        );

        if (($serviceModule = $this->heart->getServiceModule($purchase->getService())) === null) {
            log_to_db(
                $this->langShop->t(
                    'transfer_bad_module',
                    $transferFinalize->getOrderId(),
                    $purchase->getService()
                )
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            log_to_db(
                $this->langShop->t(
                    'transfer_no_purchase',
                    $transferFinalize->getOrderId(),
                    $purchase->getService()
                )
            );

            return false;
        }

        $purchase->setPayment([
            'method' => Purchase::METHOD_TRANSFER,
            'payment_id' => $transferFinalize->getOrderId(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        log_to_db(
            $this->langShop->t(
                'payment_transfer_accepted',
                $boughtServiceId,
                $transferFinalize->getOrderId(),
                $transferFinalize->getAmount(),
                $transferFinalize->getTransferService(),
                $purchase->user->getUsername(),
                $purchase->user->getUid(),
                $purchase->user->getLastIp()
            )
        );

        return true;
    }
}
