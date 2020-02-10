<?php
namespace App\View\Pages;

use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pagination;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentLog extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'payment_log';

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(PriceTextService $priceTextService)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payment_log');
        $this->priceTextService = $priceTextService;
    }

    protected function content(array $query, array $body)
    {
        $heart = $this->heart;
        $lang = $this->lang;

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        /** @var Pagination $pagination */
        $pagination = $this->app->make(Pagination::class);

        $statement = $db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM ({$settings['transactions_query']}) as t " .
                "WHERE t.uid = ? " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?"
        );
        $statement->execute([$user->getUid(), get_row_limit($this->currentPage->getPageNumber(), 10)]);
        $rowsCount = $db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $paymentLogs = "";
        foreach ($statement as $row) {
            $date = $row['timestamp'];
            $cost = $this->priceTextService->getPriceText($row['cost']);

            $serviceModule = $heart->getServiceModule($row['service']);
            if ($serviceModule instanceof IServicePurchaseWeb) {
                $logInfo = $serviceModule->purchaseInfo("payment_log", $row);
                $desc = $logInfo['text'];
                $class = $logInfo['class'];
            } else {
                $service = $heart->getService($row['service']);
                $server = $heart->getServer($row['server']);
                $desc = $lang->t(
                    'service_was_bought',
                    $service ? $service->getName() : '',
                    $server ? $server->getName() : ''
                );
                $class = "outcome";
            }

            $paymentLogs .= $template->render(
                "payment_log_brick",
                compact('class', 'date', 'cost', 'desc')
            );
        }

        $paginationContent = $pagination->getPagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            10
        );
        $paginationClass = $paginationContent ? "" : "display_none";

        return $template->render(
            "payment_log",
            compact('paymentLogs', 'paginationClass', 'paginationContent')
        );
    }
}
