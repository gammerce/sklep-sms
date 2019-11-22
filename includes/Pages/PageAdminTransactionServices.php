<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminTransactionServices extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'transaction_services';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('transaction_services');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('name')));
        $table->addHeadCell(new HeadCell($this->lang->translate('sms_service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('transfer_service')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "transaction_services` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $smsService = $row['sms']
                ? $this->lang->strtoupper($this->lang->translate('yes'))
                : $this->lang->strtoupper($this->lang->translate('no'));
            $transferService = $row['transfer']
                ? $this->lang->strtoupper($this->lang->translate('yes'))
                : $this->lang->strtoupper($this->lang->translate('no'));

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['name']));
            $bodyRow->addCell(new Cell($smsService));
            $bodyRow->addCell(new Cell($transferService));

            $bodyRow->setEditAction(true);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_settings")) {
            return [
                'status' => "no_access",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($boxId) {
            case "transaction_service_edit":
                // Pobranie danych o metodzie płatności
                $result = $this->db->query(
                    $this->db->prepare(
                        "SELECT * FROM `" .
                            TABLE_PREFIX .
                            "transaction_services` " .
                            "WHERE `id` = '%s'",
                        [$query['id']]
                    )
                );
                $transactionService = $this->db->fetchArrayAssoc($result);

                $transactionService['id'] = htmlspecialchars($transactionService['id']);
                $transactionService['name'] = htmlspecialchars($transactionService['name']);
                $transactionService['data'] = json_decode($transactionService['data']);

                $dataValues = "";
                foreach ($transactionService['data'] as $name => $value) {
                    switch ($name) {
                        case 'sms_text':
                            $text = $this->lang->strtoupper($this->lang->translate('sms_code'));
                            break;
                        case 'account_id':
                            $text = $this->lang->strtoupper($this->lang->translate('account_id'));
                            break;
                        default:
                            $text = $this->lang->strtoupper($name);
                            break;
                    }
                    $dataValues .= $this->template->render(
                        "tr_name_input",
                        compact('text', 'name', 'value')
                    );
                }

                $output = $this->template->render(
                    "admin/action_boxes/transaction_service_edit",
                    compact('transactionService', 'dataValues')
                );
                break;

            default:
                $output = '';
        }

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }
}
