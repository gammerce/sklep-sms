<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Support\FileSystemContract;
use App\Support\BasePath;

class PurchaseDataService
{
    private PurchaseSerializer $purchaseSerializer;
    private BasePath $path;
    private FileSystemContract $fileSystem;

    public function __construct(
        PurchaseSerializer $purchaseSerializer,
        BasePath $path,
        FileSystemContract $fileSystem
    ) {
        $this->purchaseSerializer = $purchaseSerializer;
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function storePurchase(Purchase $purchase): void
    {
        $path = $this->getPath($purchase->getId());
        $serialized = $this->purchaseSerializer->serialize($purchase);
        $this->fileSystem->put($path, $serialized);
    }

    /**
     * @param string $transactionId
     * @return Purchase|null
     */
    public function restorePurchase($transactionId): ?Purchase
    {
        if (!$transactionId) {
            return null;
        }

        $path = $this->getPath($transactionId);

        if (!$this->fileSystem->exists($path)) {
            return null;
        }

        $purchase = $this->purchaseSerializer->deserialize($this->fileSystem->get($path));

        if (!$purchase || $purchase->isAttempted()) {
            return null;
        }

        return $purchase;
    }

    public function deletePurchase(Purchase $purchase): void
    {
        $path = $this->getPath($purchase->getId());
        $this->fileSystem->delete($path);
        $purchase->markAsDeleted();
    }

    private function getPath($transactionId): string
    {
        $transactionFilename = $this->prepareFilename($transactionId);
        return $this->path->to("data/transactions/$transactionFilename");
    }

    private function prepareFilename($transactionId): string
    {
        $identifier = get_identifier();
        $filename = $identifier ? "{$identifier}_{$transactionId}" : $transactionId;
        return escape_filename($filename);
    }
}
