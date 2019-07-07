<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminServiceCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'service_codes';
    protected $privilage = 'view_service_codes';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('service_codes');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('code')));
        $table->addHeadCell(new Cell($this->lang->translate('service')));
        $table->addHeadCell(new Cell($this->lang->translate('server')));
        $table->addHeadCell(new Cell($this->lang->translate('amount')));
        $table->addHeadCell(new Cell($this->lang->translate('user')));
        $table->addHeadCell(new Cell($this->lang->translate('date_of_creation')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.tariff, pl.amount AS `tariff_amount`,
			u.username, u.uid, sc.amount, sc.data, sc.timestamp, s.tag " .
                "FROM `" .
                TABLE_PREFIX .
                "service_codes` AS sc " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "services` AS s ON sc.service = s.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "servers` AS srv ON sc.server = srv.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "users` AS u ON sc.uid = u.uid " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "pricelist` AS pl ON sc.tariff = pl.tariff AND sc.service = pl.service
			AND (pl.server = '-1' OR sc.server = pl.server) " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $username = $row['uid']
                ? $row['username'] . " ({$row['uid']})"
                : $this->lang->translate('none');

            if ($row['tariff_amount']) {
                $amount = $row['tariff_amount'] . ' ' . $row['tag'];
            } else {
                if ($row['tariff']) {
                    $amount = $this->lang->translate('tariff') . ': ' . $row['tariff'];
                } else {
                    if ($row['amount']) {
                        $amount = $row['amount'];
                    } else {
                        $amount = $this->lang->translate('none');
                    }
                }
            }

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell(htmlspecialchars($row['code'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['service'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['server'])));
            $body_row->addCell(new Cell($amount));
            $body_row->addCell(new Cell($username));
            $body_row->addCell(new Cell(convertDate($row['timestamp'])));

            if (get_privilages('manage_service_codes')) {
                $body_row->setButtonDelete(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages('manage_service_codes')) {
            $button = new Input();
            $button->setParam('id', 'service_code_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $this->lang->translate('add_code'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_service_codes")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "code_add":
                // Pobranie usług
                $services = "";
                foreach ($this->heart->get_services() as $id => $row) {
                    if (
                        ($service_module = $this->heart->get_service_module($id)) === null ||
                        !($service_module instanceof IService_ServiceCodeAdminManage)
                    ) {
                        continue;
                    }

                    $services .= create_dom_element("option", $row['name'], [
                        'value' => $row['id'],
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/service_code_add",
                    compact('services')
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
