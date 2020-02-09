<?php
namespace App\Payment;

use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Support\Database;
use App\Support\QueryParticle;
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
        $queryParticle = new QueryParticle();

        // Wyszukujemy po id zakupu
        if (isset($data['purchase_id'])) {
            $queryParticle->add("t.id = ?", [$data['purchase_id']]);
        }
        // Wyszukujemy po id płatności
        elseif (isset($data['payment']) && isset($data['payment_id'])) {
            $queryParticle->add(
                "t.payment = ? AND t.payment_id = ?",
                [$data['payment'], $data['payment_id']]
            );
        } else {
            return "";
        }

        // TODO Extract transactions_query from settings
        // TODO Remove usage of prepare
        // TODO Create model for transactions_query

        $statement = $this->db->statement("SELECT * FROM ({$this->settings['transactions_query']}) as t WHERE {$queryParticle}");
        $statement->execute($queryParticle->params());
        $pbs = $statement->fetch();

        if (!$pbs) {
            return "Brak zakupu w bazie.";
        }

        $serviceModule = $this->heart->getServiceModule($pbs['service']);

        return $serviceModule instanceof IServicePurchaseWeb
            ? $serviceModule->purchaseInfo($data['action'], $pbs)
            : "";
    }
}
