<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Link;
use App\Html\SimpleText;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Services\ExtraFlags\ExtraFlagType;

class PageAdminBoughtServices extends PageAdmin
{
    const PAGE_ID = 'bought_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('bought_services');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('payment_admin')));
        $table->addHeadCell(new HeadCell($this->lang->translate('payment_id')));
        $table->addHeadCell(new HeadCell($this->lang->translate('user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('server')));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('amount')));
        $table->addHeadCell(
            new HeadCell(
                "{$this->lang->translate('nick')}/{$this->lang->translate(
                    'ip'
                )}/{$this->lang->translate('sid')}"
            )
        );
        $table->addHeadCell(new HeadCell($this->lang->translate('additional')));
        $table->addHeadCell(new HeadCell($this->lang->translate('email')));
        $table->addHeadCell(new HeadCell($this->lang->translate('ip')));
        $table->addHeadCell(new HeadCell($this->lang->translate('date')));

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

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            // Pobranie danych o usłudze, która została kupiona
            $service = $this->heart->getService($row['service']);

            // Pobranie danych o serwerze na ktorym zostala wykupiona usługa
            $server = $this->heart->getServer($row['server']);

            $username = $row['uid']
                ? htmlspecialchars($row['username']) . " ({$row['uid']})"
                : $this->lang->translate('none');

            // Przerobienie ilosci
            $amount =
                $row['amount'] != -1
                    ? $row['amount'] . ' ' . $service['tag']
                    : $this->lang->translate('forever');

            // Rozkulbaczenie extra daty
            $row['extra_data'] = json_decode($row['extra_data'], true);
            $extraData = [];
            foreach ($row['extra_data'] as $key => $value) {
                if (!strlen($value)) {
                    continue;
                }

                $value = htmlspecialchars($value);

                if ($key == "password") {
                    $key = $this->lang->translate('password');
                } elseif ($key == "type") {
                    $key = $this->lang->translate('type');
                    $value = ExtraFlagType::getTypeName($value);
                }

                $extraData[] = $key . ': ' . $value;
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
            $paymentLink->addContent(new SimpleText($this->lang->translate('see_payment')));

            $bodyRow->addAction($paymentLink);

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['payment']));
            $bodyRow->addCell(new Cell($row['payment_id']));
            $bodyRow->addCell(new Cell($username));
            $bodyRow->addCell(new Cell($server['name']));
            $bodyRow->addCell(new Cell($service['name']));
            $bodyRow->addCell(new Cell($amount));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['auth_data'])));
            $bodyRow->addCell(new Cell($extraData));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['email'])));
            $bodyRow->addCell(new Cell($row['ip']));

            $cell = new Cell(convertDate($row['timestamp']));
            $cell->setParam('headers', 'date');
            $bodyRow->addCell($cell);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
