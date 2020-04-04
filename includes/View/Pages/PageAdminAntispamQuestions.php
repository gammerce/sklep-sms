<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Repositories\AntiSpamQuestionRepository;
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

    /** @var AntiSpamQuestionRepository */
    private $antiSpamQuestionRepository;

    public function __construct(AntiSpamQuestionRepository $antiSpamQuestionRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('antispam_questions');
        $this->antiSpamQuestionRepository = $antiSpamQuestionRepository;
    }

    protected function content(array $query, array $body)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_antispam_questions` LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $bodyRow = (new BodyRow())
                    ->setDbId($row['id'])
                    ->addCell(new Cell(new UnescapedSimpleText($row['question'])))
                    ->addCell(new Cell($row['answers']));

                if (get_privileges("manage_antispam_questions")) {
                    $bodyRow->setDeleteAction(true);
                    $bodyRow->setEditAction(true);
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('question')))
            ->addHeadCell(new HeadCell($this->lang->t('answers')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->title)->setTable($table);

        if (get_privileges("manage_antispam_questions")) {
            $button = (new Input())
                ->setParam('id', 'antispam_question_button_add')
                ->setParam('type', 'button')
                ->addClass('button')
                ->setParam('value', $this->lang->t('add_antispam_question'));

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
                $antispamQuestion = $this->antiSpamQuestionRepository->get($query['id']);

                $output = $this->template->render("admin/action_boxes/antispam_question_edit", [
                    'id' => $antispamQuestion->getId(),
                    'question' => $antispamQuestion->getQuestion(),
                    'answers' => implode(";", $antispamQuestion->getAnswers()),
                ]);
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
