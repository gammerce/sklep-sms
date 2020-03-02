<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Repositories\PaymentTransferRepository;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Heart;
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

    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    /** @var FileSystemContract */
    private $fileSystem;

    /** @var DatabaseLogger */
    private $logger;

    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    public function __construct(
        Database $db,
        Path $path,
        Heart $heart,
        PaymentTransferRepository $paymentTransferRepository,
        TranslationManager $translationManager,
        FileSystemContract $fileSystem,
        PurchaseSerializer $purchaseSerializer,
        DatabaseLogger $logger
    ) {
        $this->db = $db;
        $this->path = $path;
        $this->heart = $heart;
        $this->paymentTransferRepository = $paymentTransferRepository;
        $this->lang = $translationManager->user();
        $this->fileSystem = $fileSystem;
        $this->logger = $logger;
        $this->purchaseSerializer = $purchaseSerializer;
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
        $serialized = $this->purchaseSerializer->serialize($purchase);
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
            $this->logger->log('transfer_no_data_file', $transferFinalize->getOrderId());
            return false;
        }

        $purchase = $this->purchaseSerializer->deserialize(
            $this->fileSystem->get(
                $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
            )
        );

        $this->paymentTransferRepository->create(
            $transferFinalize->getOrderId(),
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER),
            $transferFinalize->getTransferService(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $transferFinalize->isTestMode()
        );
        $this->fileSystem->delete(
            $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
        );

        $serviceModule = $this->heart->getServiceModule($purchase->getService());
        if (!$serviceModule) {
            $this->logger->log(
                'transfer_bad_module',
                $transferFinalize->getOrderId(),
                $purchase->getService()
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            $this->logger->log(
                'transfer_no_purchase',
                $transferFinalize->getOrderId(),
                $purchase->getService()
            );

            return false;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_TRANSFER,
            Purchase::PAYMENT_PAYMENT_ID => $transferFinalize->getOrderId(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->log(
            'payment_transfer_accepted',
            $boughtServiceId,
            $transferFinalize->getOrderId(),
            $transferFinalize->getAmount(),
            $transferFinalize->getTransferService(),
            $purchase->user->getUsername(),
            $purchase->user->getUid(),
            $purchase->user->getLastIp()
        );

        return true;
    }
}
