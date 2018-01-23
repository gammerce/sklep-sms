<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminGroups extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = 'groups';
    protected $privilage = 'view_groups';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('groups');
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

        $table->addHeadCell(new Cell($lang->translate('name')));

        $result = $db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell($row['name']));

            if (get_privilages('manage_groups')) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages('manage_groups')) {
            $button = new Input();
            $button->setParam('id', 'group_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $lang->translate('add_group'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        global $db, $lang;

        if (!get_privilages("manage_groups")) {
            return [
                'status' => "not_logged_in",
                'text'   => $lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($box_id == "group_edit") {
            $result = $db->query($db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "groups` " .
                "WHERE `id` = '%d'",
                [$data['id']]
            ));

            if (!$db->num_rows($result)) {
                $data['template'] = create_dom_element("form", $lang->translate('no_such_group'), [
                    'class' => 'action_box',
                    'style' => [
                        'padding' => "20px",
                        'color'   => "white",
                    ],
                ]);
            } else {
                $group = $db->fetch_array_assoc($result);
                $group['name'] = htmlspecialchars($group['name']);
            }
        }

        $privilages = "";
        $result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
        while ($row = $db->fetch_array_assoc($result)) {
            if (in_array($row['Field'], ["id", "name"])) {
                continue;
            }

            $values = create_dom_element("option", $lang->strtoupper($lang->translate('no')), [
                'value'    => 0,
                'selected' => $group[$row['Field']] ? "" : "selected",
            ]);

            $values .= create_dom_element("option", $lang->strtoupper($lang->translate('yes')), [
                'value'    => 1,
                'selected' => $group[$row['Field']] ? "selected" : "",
            ]);

            $name = htmlspecialchars($row['Field']);
            $text = $lang->translate('privilage_' . $row['Field']);

            $privilages .= eval($this->template->render("tr_text_select"));
        }

        switch ($box_id) {
            case "group_add":
                $output = eval($this->template->render("admin/action_boxes/group_add"));
                break;

            case "group_edit":
                $output = eval($this->template->render("admin/action_boxes/group_edit"));
                break;
        }

        return [
            'status'   => 'ok',
            'template' => $output,
        ];
    }
}