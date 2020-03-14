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
        $dataFilename = time() . "-" . md5($serialized);
        $path = $this->path->to('data/transfers/' . $dataFilename);
        $this->fileSystem->put($path, $serialized);

        return $dataFilename;
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
}
