<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminAntispamQuestions extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'antispam_questions';
    protected $privilage = 'view_antispam_questions';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('antispam_questions');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('question')));
        $table->addHeadCell(new Cell($this->lang->translate('answers')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `" .
                TABLE_PREFIX .
                "antispam_questions` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell($row['question']));
            $body_row->addCell(new Cell($row['answers']));
            if (get_privilages("manage_antispam_questions")) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages("manage_antispam_questions")) {
            $button = new Input();
            $button->setParam('id', 'antispam_question_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $this->lang->translate('add_antispam_question'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_antispam_questions")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "antispam_question_add":
                $output = $this->template->render("admin/action_boxes/antispam_question_add");
                break;

            case "antispam_question_edit":
                $row = $this->db->fetch_array_assoc(
                    $this->db->query(
                        $this->db->prepare(
                            "SELECT * FROM `" .
                                TABLE_PREFIX .
                                "antispam_questions` " .
                                "WHERE `id` = '%d'",
                            [$data['id']]
                        )
                    )
                );
                $row['question'] = htmlspecialchars($row['question']);
                $row['answers'] = htmlspecialchars($row['answers']);

                $output = $this->template->render(
                    "admin/action_boxes/antispam_question_edit",
                    compact('row')
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
