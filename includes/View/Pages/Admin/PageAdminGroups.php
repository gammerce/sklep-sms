<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Repositories\GroupRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminGroups extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "groups";

    /** @var GroupRepository */
    private $groupRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        GroupRepository $groupRepository,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);
        $this->groupRepository = $groupRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getPrivilege()
    {
        return "view_groups";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("groups");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_groups` LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($row["name"]))
                    ->setDeleteAction(has_privileges("manage_groups"))
                    ->setEditAction(has_privileges("manage_groups"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (has_privileges("manage_groups")) {
            $button = (new Input())
                ->setParam("id", "group_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_group"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_groups")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "group_edit") {
            $group = $this->groupRepository->get($query["id"]);

            if (!$group) {
                return [
                    "status" => "ok",
                    "template" => create_dom_element("form", $this->lang->t("no_such_group"), [
                        "class" => "action_box",
                        "style" => [
                            "padding" => "20px",
                            "color" => "white",
                        ],
                    ]),
                ];
            }
        }

        $privileges = "";
        foreach ($this->groupRepository->getFields() as $fieldName) {
            if (in_array($fieldName, ["id", "name"])) {
                continue;
            }

            $values = create_dom_element("option", $this->lang->strtoupper($this->lang->t("no")), [
                "value" => 0,
                "selected" => isset($group) && $group->hasPermission($fieldName) ? "" : "selected",
            ]);

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->t("yes")),
                [
                    "value" => 1,
                    "selected" =>
                        isset($group) && $group->hasPermission($fieldName) ? "selected" : "",
                ]
            );

            $privileges .= $this->template->render("shop/components/general/tr_text_select", [
                "name" => $fieldName,
                "text" => $this->lang->t("privilege_" . $fieldName),
                "values" => $values,
            ]);
        }

        switch ($boxId) {
            case "group_add":
                $output = $this->template->render(
                    "admin/action_boxes/group_add",
                    compact("privileges")
                );
                break;

            case "group_edit":
                $output = $this->template->render(
                    "admin/action_boxes/group_edit",
                    compact("privileges", "group")
                );
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
