<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminTransactionServices extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = 'transaction_services';
    protected $privilage = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('transaction_services');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('name')));
        $table->addHeadCell(new Cell($this->lang->translate('sms_service')));
        $table->addHeadCell(new Cell($this->lang->translate('transfer_service')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "transaction_services` " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $sms_service = $row['sms'] ? $this->lang->strtoupper($this->lang->translate('yes')) : $this->lang->strtoupper($this->lang->translate('no'));
            $transfer_service = $row['transfer'] ? $this->lang->strtoupper($this->lang->translate('yes')) : $this->lang->strtoupper($this->lang->translate('no'));

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell($row['name']));
            $body_row->addCell(new Cell($sms_service));
            $body_row->addCell(new Cell($transfer_service));

            $body_row->setButtonEdit(true);

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_settings")) {
            return [
                'status' => "not_logged_in",
                'text'   => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "transaction_service_edit":
                // Pobranie danych o metodzie płatności
                $result = $this->db->query($this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "transaction_services` " .
                    "WHERE `id` = '%s'",
                    [$data['id']]
                ));
                $transaction_service = $this->db->fetch_array_assoc($result);

                $transaction_service['id'] = htmlspecialchars($transaction_service['id']);
                $transaction_service['name'] = htmlspecialchars($transaction_service['name']);
                $transaction_service['data'] = json_decode($transaction_service['data']);

                $data_values = "";
                foreach ($transaction_service['data'] as $name => $value) {
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
                    $data_values .= $this->template->render2("tr_name_input", compact('text', 'name', 'value'));
                }

                $output = $this->template->render2(
                    "admin/action_boxes/transaction_service_edit",
                    compact('transaction_service', 'data_values')
                );
                break;

            default:
                $output = '';
        }

        return [
            'status'   => 'ok',
            'template' => $output,
        ];
    }
}
