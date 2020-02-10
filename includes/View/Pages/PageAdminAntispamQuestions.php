<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\UnescapedSimpleText;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminAntispamQuestions extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'antispam_questions';
    protected $privilege = 'view_antispam_questions';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('antispam_questions');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('question')));
        $table->addHeadCell(new HeadCell($this->lang->t('answers')));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " . "FROM `ss_antispam_questions` " . "LIMIT ?"
        );
        $statement->execute([get_row_limit($this->currentPage->getPageNumber())]);

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell(new UnescapedSimpleText($row['question'])));
            $bodyRow->addCell(new Cell($row['answers']));
            if (get_privileges("manage_antispam_questions")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges("manage_antispam_questions")) {
            $button = new Input();
            $button->setParam('id', 'antispam_question_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->t('add_antispam_question'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_antispam_questions")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "antispam_question_add":
                $output = $this->template->render("admin/action_boxes/antispam_question_add");
                break;

            case "antispam_question_edit":
                $statement = $this->db->statement(
                    "SELECT * FROM `ss_antispam_questions` WHERE `id` = ?"
                );
                $statement->execute([$query['id']]);
                $row = $statement->fetch();

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
