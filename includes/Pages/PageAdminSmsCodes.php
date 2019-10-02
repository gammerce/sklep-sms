<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\HeadCell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminSmsCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'sms_codes';
    protected $privilege = 'view_sms_codes';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('sms_codes');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('sms_code')));
        $table->addHeadCell(new HeadCell($this->lang->translate('tariff')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `" .
                TABLE_PREFIX .
                "sms_codes` " .
                "WHERE `free` = '1' " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell(htmlspecialchars($row['code'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['tariff'])));

            if (get_privileges('manage_sms_codes')) {
                $bodyRow->setDeleteAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_sms_codes')) {
            $button = new Input();
            $button->setParam('id', 'sms_code_button_add');
            $button->setParam('type', 'button');
            $button->setParam('class', 'button');
            $button->setParam('value', $this->lang->translate('add_code'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_sms_codes")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($boxId) {
            case "sms_code_add":
                $tariffs = "";
                foreach ($this->heart->getTariffs() as $tariff) {
                    $tariffs .= create_dom_element("option", $tariff->getId(), [
                        'value' => $tariff->getId(),
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/sms_code_add",
                    compact('tariffs')
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
