<?php
namespace App\Payment;

use App\Models\Purchase;
use App\Models\TransferFinalize;
use App\Services\Interfaces\IServicePurchase;
use App\System\Database;
use App\System\Heart;
use App\System\Path;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class TransferPaymentService
{
    /** @var Database */
    private $db;

    /** @var Path */
    private $path;

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $langShop;

    public function __construct(Database $db, Path $path, Heart $heart, TranslationManager $translationManager)
    {
        $this->db = $db;
        $this->path = $path;
        $this->heart = $heart;
        $this->langShop = $translationManager->shop();
    }

    /**
     * @param TransferFinalize $transferFinalize
     * @return bool
     */
    public function transferFinalize(TransferFinalize $transferFinalize)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " . "WHERE `id` = '%d'",
                [$transferFinalize->getOrderid()]
            )
        );

        // Próba ponownej autoryzacji
        if ($this->db->numRows($result)) {
            return false;
        }

        // Nie znaleziono pliku z danymi
        if (
            !$transferFinalize->getDataFilename() ||
            !file_exists($this->path->to('data/transfers/' . $transferFinalize->getDataFilename()))
        ) {
            log_to_db(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_no_data_file'),
                    $transferFinalize->getOrderid()
                )
            );

            return false;
        }

        /** @var Purchase $purchase */
        $purchase = unserialize(
            file_get_contents(
                $this->path->to('data/transfers/' . $transferFinalize->getDataFilename())
            )
        );

        // Fix: Refresh user to avoid bugs linked with user wallet
        $purchase->user = $this->heart->getUser($purchase->user->getUid());

        // Dodanie informacji do bazy danych
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                TABLE_PREFIX .
                "payment_transfer` " .
                "SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
                [
                    $transferFinalize->getOrderid(),
                    $purchase->getPayment('cost'),
                    $transferFinalize->getTransferService(),
                    $purchase->user->getLastIp(),
                    $purchase->user->getPlatform(),
                ]
            )
        );
        unlink($this->path->to('data/transfers/' . $transferFinalize->getDataFilename()));

        // Błędny moduł
        if (($serviceModule = $this->heart->getServiceModule($purchase->getService())) === null) {
            log_to_db(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_bad_module'),
                    $transferFinalize->getOrderid(),
                    $purchase->getService()
                )
            );

            return false;
        }

        if (!($serviceModule instanceof IServicePurchase)) {
            log_to_db(
                $this->langShop->sprintf(
                    $this->langShop->translate('transfer_no_purchase'),
                    $transferFinalize->getOrderid(),
                    $purchase->getService()
                )
            );

            return false;
        }

        // Dokonujemy zakupu
        $purchase->setPayment([
            'method' => 'transfer',
            'payment_id' => $transferFinalize->getOrderid(),
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        log_to_db(
            $this->langShop->sprintf(
                $this->langShop->translate('payment_transfer_accepted'),
                $boughtServiceId,
                $transferFinalize->getOrderid(),
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