<?php
namespace App\Payment;

use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\System\Database;
use App\System\Heart;
use App\System\Settings;

class PurchaseInformation
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    public function __construct(Database $db, Settings $settings, Heart $heart)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->heart = $heart;
    }

    //
    // $data:
    // 	purchase_id - id zakupu
    // 	payment - metoda płatności
    // 	payment_id - id płatności
    // 	action - jak sformatowac dane
    //
    public function get(array $data)
    {
        // Wyszukujemy po id zakupu
        if (isset($data['purchase_id'])) {
            $where = $this->db->prepare("t.id = '%d'", [$data['purchase_id']]);
        }
        // Wyszukujemy po id płatności
        elseif (isset($data['payment']) && isset($data['payment_id'])) {
            $where = $this->db->prepare("t.payment = '%s' AND t.payment_id = '%s'", [
                $data['payment'],
                $data['payment_id'],
            ]);
        } else {
            return "";
        }

        $pbs = $this->db
            ->query(
                "SELECT * FROM ({$this->settings['transactions_query']}) as t " . "WHERE {$where}"
            )
            ->fetch();

        if (!$pbs) {
            return "Brak zakupu w bazie.";
        }

        $serviceModule = $this->heart->getServiceModule($pbs['service']);

        return $serviceModule !== null && $serviceModule instanceof IServicePurchaseWeb
            ? $serviceModule->purchaseInfo($data['action'], $pbs)
            : "";
    }
}
