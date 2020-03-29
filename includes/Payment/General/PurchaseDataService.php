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
     * @return string
     */
    public function storePurchase(Purchase $purchase)
    {
        $serialized = $this->purchaseSerializer->serialize($purchase);
        $fileName = $this->generateIdentifier($serialized);
        $path = $this->path->to('data/transfers/' . $fileName);
        $this->fileSystem->put($path, $serialized);

        return $fileName;
    }

    /**
     * @param string $fileName
     * @param Purchase $purchase
     */
    public function updatePurchase($fileName, Purchase $purchase)
    {
        $serialized = $this->purchaseSerializer->serialize($purchase);
        $path = $this->path->to('data/transfers/' . $fileName);
        $this->fileSystem->put($path, $serialized);
    }

    /**
     * @param string $fileName
     * @return Purchase|null
     */
    public function restorePurchase($fileName)
    {
        if (!$fileName || !$this->fileSystem->exists($this->path->to("data/transfers/$fileName"))) {
            return null;
        }

        return $this->purchaseSerializer->deserialize(
            $this->fileSystem->get($this->path->to("data/transfers/$fileName"))
        );
    }

    /**
     * @param string $fileName
     */
    public function deletePurchase($fileName)
    {
        $this->fileSystem->delete($this->path->to("data/transfers/$fileName"));
    }

    /**
     * 32 characters long unique purchase identifier
     *
     * @param string $serialized
     * @return string
     */
    private function generateIdentifier($serialized)
    {
        $fileName = hash("sha256", microtime() . $serialized);
        return substr($fileName, 0, 32);
    }
}
