<?php
namespace App\Pages;

use App\Html\UnescapedSimpleText;
use App\Interfaces\IBeLoggedMust;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\System\Auth;
use App\System\Database;
use App\System\Settings;
use App\System\Template;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentLog extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'payment_log';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('payment_log');
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

        $result = $db->query(
            $db->prepare(
                "SELECT SQL_CALC_FOUND_ROWS * FROM ({$settings['transactions_query']}) as t " .
                    "WHERE t.uid = '%d' " .
                    "ORDER BY t.timestamp DESC " .
                    "LIMIT " .
                    get_row_limit($this->currentPage->getPageNumber(), 10),
                [$user->getUid()]
            )
        );
        $rowsCount = $db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()");

        $paymentLogs = "";
        while ($row = $db->fetchArrayAssoc($result)) {
            $date = $row['timestamp'];
            $cost = number_format($row['cost'] / 100.0, 2) . " " . $settings['currency'];

            $serviceModule = $heart->getServiceModule($row['service']);
            if ($serviceModule instanceof IServicePurchaseWeb) {
                $logInfo = $serviceModule->purchaseInfo("payment_log", $row);
                $desc = $logInfo['text'];
                $class = $logInfo['class'];
            } else {
                $service = $heart->getService($row['service']);
                $server = $heart->getServer($row['server']);
                $desc = $lang->sprintf(
                    $lang->translate('service_was_bought'),
                    $service ? $service->getName() : '',
                    $server ? $server->getName() : ''
                );
                $class = "outcome";
            }

            $paymentLogBrick = $template->render(
                "payment_log_brick",
                compact('date', 'cost', 'desc')
            );
            $paymentLogs .= create_dom_element(
                "div",
                new UnescapedSimpleText($paymentLogBrick),
                $data = [
                    'class' => "brick " . $class,
                ]
            );
        }

        $pagination = get_pagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            10
        );
        $paginationClass = $pagination ? "" : "display_none";

        return $template->render(
            "payment_log",
            compact('paymentLogs', 'paginationClass', 'pagination')
        );
    }
}
