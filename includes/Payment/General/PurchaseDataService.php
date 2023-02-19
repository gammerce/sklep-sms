<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Support\FileSystemContract;
use App\Support\Path;

class PurchaseDataService
{
    private PurchaseSerializer $purchaseSerializer;
    private Path $path;
    private FileSystemContract $fileSystem;

    public function __construct(
        PurchaseSerializer $purchaseSerializer,
        Path $path,
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

    public function restorePurchase(string $transactionId): ?Purchase
    {
        $purchase = $this->restorePurchaseForcefully($transactionId);

        if (!$purchase || $purchase->isAttempted()) {
            return null;
        }

        return $purchase;
    }

    public function restorePurchaseForcefully(string $transactionId): ?Purchase
    {
        if (!$transactionId) {
            return null;
        }

        $path = $this->getPath($transactionId);

        if (!$this->fileSystem->exists($path)) {
            return null;
        }

        return $this->purchaseSerializer->deserialize($this->fileSystem->get($path));
    }

    private function getPath(string $transactionId): string
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
