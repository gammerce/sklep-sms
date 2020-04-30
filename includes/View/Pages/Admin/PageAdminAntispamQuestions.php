<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Repositories\AntiSpamQuestionRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\RawText;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminAntispamQuestions extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "antispam_questions";

    /** @var AntiSpamQuestionRepository */
    private $antiSpamQuestionRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        AntiSpamQuestionRepository $antiSpamQuestionRepository,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);
        $this->antiSpamQuestionRepository = $antiSpamQuestionRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getPrivilege()
    {
        return "view_antispam_questions";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("antispam_questions");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_antispam_questions` LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell(new RawText($row["question"])))
                    ->addCell(new Cell($row["answers"]))
                    ->setDeleteAction(has_privileges("manage_antispam_questions"))
                    ->setEditAction(has_privileges("manage_antispam_questions"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("question")))
            ->addHeadCell(new HeadCell($this->lang->t("answers")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (has_privileges("manage_antispam_questions")) {
            $button = (new Input())
                ->setParam("id", "antispam_question_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_antispam_question"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_antispam_questions")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "antispam_question_add":
                $output = $this->template->render("admin/action_boxes/antispam_question_add");
                break;

            case "antispam_question_edit":
                $antispamQuestion = $this->antiSpamQuestionRepository->get($query["id"]);

                $output = $this->template->render("admin/action_boxes/antispam_question_edit", [
                    "id" => $antispamQuestion->getId(),
                    "question" => $antispamQuestion->getQuestion(),
                    "answers" => implode(";", $antispamQuestion->getAnswers()),
                ]);
                break;

            default:
                $output = "";
        }

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }
}
