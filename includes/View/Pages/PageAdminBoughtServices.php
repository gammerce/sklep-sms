<?php
namespace App\View\Pages;

use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\UnescapedSimpleText;
use App\View\Html\Wrapper;

class PageAdminBoughtServices extends PageAdmin
{
    const PAGE_ID = 'bought_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('bought_services');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('payment_admin')));
        $table->addHeadCell(new HeadCell($this->lang->t('payment_id')));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('amount')));
        $table->addHeadCell(
            new HeadCell("{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}")
        );
        $table->addHeadCell(new HeadCell($this->lang->t('additional')));
        $table->addHeadCell(new HeadCell($this->lang->t('email')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';

        if (isset($query['search'])) {
            searchWhere(
                [
                    "t.id",
                    "t.payment",
                    "t.payment_id",
                    "t.uid",
                    "t.ip",
                    "t.email",
                    "t.auth_data",
                    "CAST(t.timestamp as CHAR)",
                ],
                $query['search'],
                $where
            );
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                $where .
                "ORDER BY t.timestamp DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            // Pobranie danych o usłudze, która została kupiona
            $service = $this->heart->getService($row['service']);

            // Pobranie danych o serwerze na ktorym zostala wykupiona usługa
            $server = $this->heart->getServer($row['server']);

            $username = $row['uid'] ? "{$row['username']} ({$row['uid']})" : $this->lang->t('none');

            // Przerobienie ilosci
            $amount =
                $row['amount'] != -1
                    ? $row['amount'] . ' ' . ($service ? $service->getTag() : '')
                    : $this->lang->t('forever');

            $row['extra_data'] = json_decode($row['extra_data'], true);
            $extraData = [];
            foreach ($row['extra_data'] as $key => $value) {
                if (!strlen($value)) {
                    continue;
                }

                if ($key == "password") {
                    $key = $this->lang->t('password');
                } elseif ($key == "type") {
                    $key = $this->lang->t('type');
                    $value = ExtraFlagType::getTypeName($value);
                }

                $extraData[] = htmlspecialchars("$key: $value");
            }
            $extraData = implode('<br />', $extraData);

            // Pobranie linku płatności
            $paymentLink = new Link();
            $paymentLink->addClass("dropdown-item");
            $paymentLink->setParam(
                'href',
                $this->url->to("/admin/payment_{$row['payment']}?payid={$row['payment_id']}")
            );
            $paymentLink->setParam('target', '_blank');
            $paymentLink->addContent($this->lang->t('see_payment'));

            $bodyRow->addAction($paymentLink);

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['payment']));
            $bodyRow->addCell(new Cell($row['payment_id']));
            $bodyRow->addCell(new Cell($username));
            $bodyRow->addCell(new Cell($server ? $server->getName() : $this->lang->t('none')));
            $bodyRow->addCell(new Cell($service ? $service->getName() : $this->lang->t('none')));
            $bodyRow->addCell(new Cell($amount));
            $bodyRow->addCell(new Cell($row['auth_data']));
            $bodyRow->addCell(new Cell(new UnescapedSimpleText($extraData)));
            $bodyRow->addCell(new Cell($row['email']));
            $bodyRow->addCell(new Cell($row['ip']));

            $cell = new Cell(convert_date($row['timestamp']));
            $cell->setParam('headers', 'date');
            $bodyRow->addCell($cell);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
