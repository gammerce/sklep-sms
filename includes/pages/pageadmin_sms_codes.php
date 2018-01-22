<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminSmsCodes extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = 'sms_codes';
    protected $privilage = 'view_sms_codes';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('sms_codes');
    }

    protected function content($get, $post)
    {
        global $db, $lang;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('sms_code')));
        $table->addHeadCell(new Cell($lang->translate('tariff')));

        $result = $db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
            "FROM `" . TABLE_PREFIX . "sms_codes` " .
            "WHERE `free` = '1' " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell(htmlspecialchars($row['code'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['tariff'])));

            if (get_privilages('manage_sms_codes')) {
                $body_row->setButtonDelete(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages('manage_sms_codes')) {
            $button = new Input();
            $button->setParam('id', 'sms_code_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $lang->translate('add_code'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        global $heart, $lang, $templates;

        if (!get_privilages("manage_sms_codes")) {
            return [
                'status' => "not_logged_in",
                'text'   => $lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "sms_code_add":
                $tariffs = "";
                foreach ($heart->getTariffs() as $tariff) {
                    $tariffs .= create_dom_element("option", $tariff->getId(), [
                        'value' => $tariff->getId(),
                    ]);
                }

                $output = eval($templates->render("admin/action_boxes/sms_code_add"));
                break;
        }

        return [
            'status'   => 'ok',
            'template' => $output,
        ];
    }
}