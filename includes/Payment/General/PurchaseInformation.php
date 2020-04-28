<?php
namespace App\Payment\General;

use App\Repositories\TransactionRepository;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Support\Database;
use App\Support\QueryParticle;
use App\View\ServiceModuleManager;

class PurchaseInformation
{
    /** @var Database */
    private $db;

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Database $db,
        ServiceModuleManager $serviceModuleManager,
        TransactionRepository $transactionRepository
    ) {
        $this->db = $db;
        $this->transactionRepository = $transactionRepository;
        $this->serviceModuleManager = $serviceModuleManager;
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
            $queryParticle->add("t.payment = ? AND t.payment_id = ?", [
                $data['payment'],
                $data['payment_id'],
            ]);
        } else {
            return "";
        }

        $statement = $this->db->statement(
            "SELECT * FROM ({$this->transactionRepository->getQuery()}) as t WHERE {$queryParticle}"
        );
        $statement->execute($queryParticle->params());

        if (!$statement->rowCount()) {
            return "Brak zakupu w bazie.";
        }

        $transaction = $this->transactionRepository->mapToModel($statement->fetch());

        $serviceModule = $this->serviceModuleManager->get($transaction->getServiceId());

        return $serviceModule instanceof IServicePurchaseWeb
            ? $serviceModule->purchaseInfo($data['action'], $transaction)
            : "";
    }
}
