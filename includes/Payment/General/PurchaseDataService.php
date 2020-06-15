<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Support\FileSystemContract;
use App\Support\Path;

class PurchaseDataService
{
    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    /** @var Path */
    private $path;

    /** @var FileSystemContract */
    private $fileSystem;

    public function __construct(
        PurchaseSerializer $purchaseSerializer,
        Path $path,
        FileSystemContract $fileSystem
    ) {
        $this->purchaseSerializer = $purchaseSerializer;
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param Purchase $purchase
     */
    public function storePurchase(Purchase $purchase)
    {
        $transactionId = escape_filename($purchase->getId());
        $serialized = $this->purchaseSerializer->serialize($purchase);
        $path = $this->path->to("data/transactions/$transactionId");
        $this->fileSystem->put($path, $serialized);
    }

    /**
     * @param string $transactionId
     * @return Purchase|null
     */
    public function restorePurchase($transactionId)
    {
        $transactionId = escape_filename($transactionId);
        if (
            !$transactionId ||
            !$this->fileSystem->exists($this->path->to("data/transactions/$transactionId"))
        ) {
            return null;
        }

        $purchase = $this->purchaseSerializer->deserialize(
            $this->fileSystem->get($this->path->to("data/transactions/$transactionId"))
        );

        if (!$purchase || $purchase->isAttempted()) {
            return null;
        }

        return $purchase;
    }

    /**
     * @param Purchase $purchase
     */
    public function deletePurchase(Purchase $purchase)
    {
        $transactionId = escape_filename($purchase->getId());
        $this->fileSystem->delete($this->path->to("data/transactions/$transactionId"));
        $purchase->markAsDeleted();
    }
}
