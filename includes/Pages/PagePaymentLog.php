<?php
namespace App\Pages;

use App\Auth;
use App\Database;
use App\Interfaces\IBeLoggedMust;
use App\Settings;
use App\Template;
use App\Services\Interfaces\IServicePurchaseWeb;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentLog extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'payment_log';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('payment_log');
    }

    protected function content($get, $post)
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
        $rows_count = $db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()");

        $payment_logs = "";
        while ($row = $db->fetchArrayAssoc($result)) {
            $date = htmlspecialchars($row['timestamp']);
            $cost = number_format($row['cost'] / 100.0, 2) . " " . $settings['currency'];

            if (
                ($service_module = $heart->getServiceModule($row['service'])) !== null &&
                $service_module instanceof IServicePurchaseWeb
            ) {
                $log_info = $service_module->purchaseInfo("payment_log", $row);
                $desc = $log_info['text'];
                $class = $log_info['class'];
            } else {
                $temp_service = $heart->getService($row['service']);
                $temp_server = $heart->getServer($row['server']);
                $desc = $lang->sprintf(
                    $lang->translate('service_was_bought'),
                    $temp_service['name'],
                    $temp_server['name']
                );
                $class = "outcome";
                unset($temp_service);
                unset($temp_server);
            }

            $row['auth_data'] = htmlspecialchars($row['auth_data']);
            $row['email'] = htmlspecialchars($row['email']);

            $payment_log_brick = $template->render(
                "payment_log_brick",
                compact('date', 'cost', 'desc')
            );
            $payment_logs .= create_dom_element(
                "div",
                $payment_log_brick,
                $data = [
                    'class' => "brick " . $class,
                ]
            );
        }

        $pagination = get_pagination(
            $rows_count,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $get,
            10
        );
        $pagination_class = strlen($pagination) ? "" : "display_none";

        return $template->render(
            "payment_log",
            compact('payment_logs', 'pagination_class', 'pagination')
        );
    }
}
