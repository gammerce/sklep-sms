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
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('name')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetch_array_assoc($result)) {
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
            $button->setParam('value', $this->lang->translate('add_group'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_groups")) {
            return [
                'status' => "not_logged_in",
                'text'   => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($box_id == "group_edit") {
            $result = $this->db->query($this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "groups` " .
                "WHERE `id` = '%d'",
                [$data['id']]
            ));

            if (!$this->db->num_rows($result)) {
                $data['template'] = create_dom_element("form", $this->lang->translate('no_such_group'), [
                    'class' => 'action_box',
                    'style' => [
                        'padding' => "20px",
                        'color'   => "white",
                    ],
                ]);
            } else {
                $group = $this->db->fetch_array_assoc($result);
                $group['name'] = htmlspecialchars($group['name']);
            }
        }

        $privilages = "";
        $result = $this->db->query("DESCRIBE " . TABLE_PREFIX . "groups");
        while ($row = $this->db->fetch_array_assoc($result)) {
            if (in_array($row['Field'], ["id", "name"])) {
                continue;
            }

            $values = create_dom_element("option", $this->lang->strtoupper($this->lang->translate('no')), [
                'value'    => 0,
                'selected' => $group[$row['Field']] ? "" : "selected",
            ]);

            $values .= create_dom_element("option", $this->lang->strtoupper($this->lang->translate('yes')), [
                'value'    => 1,
                'selected' => $group[$row['Field']] ? "selected" : "",
            ]);

            $name = htmlspecialchars($row['Field']);
            $text = $this->lang->translate('privilage_' . $row['Field']);

            $privilages .= $this->template->render2("tr_text_select", compact('name', 'text', 'values'));
        }

        switch ($box_id) {
            case "group_add":
                $output = $this->template->render2("admin/action_boxes/group_add", compact('privilages'));
                break;

            case "group_edit":
                $output = $this->template->render2("admin/action_boxes/group_edit", compact('privilages', 'group'));
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
