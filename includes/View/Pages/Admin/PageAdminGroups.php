<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Repositories\GroupRepository;
use App\Support\Database;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Form;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminGroups extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "groups";

    private GroupRepository $groupRepository;
    private Database $db;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        GroupRepository $groupRepository,
        Database $db,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);
        $this->groupRepository = $groupRepository;
        $this->db = $db;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::GROUPS_VIEW();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("groups");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_groups` LIMIT ?, ?"
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(
                fn(array $row) => (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($row["name"]))
                    ->setDeleteAction(can(Permission::GROUPS_MANAGEMENT()))
                    ->setEditAction(can(Permission::GROUPS_MANAGEMENT()))
            )
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (can(Permission::GROUPS_MANAGEMENT())) {
            $button = (new Input())
                ->setParam("id", "group_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_group"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::GROUPS_MANAGEMENT())) {
            throw new UnauthorizedException();
        }

        if ($boxId == "edit") {
            $group = $this->groupRepository->get($query["id"]);

            if (!$group) {
                return new Form($this->lang->t("no_such_group"), [
                    "class" => "action_box",
                    "style" => [
                        "padding" => "20px",
                        "color" => "white",
                    ],
                ]);
            }
        } else {
            $group = null;
        }

        $permissions = collect($this->groupRepository->getPermissions())
            ->map(function (Permission $permission) use ($group) {
                $option = new Option(
                    $this->lang->t("privilege_{$permission->getValue()}"),
                    $permission->getValue()
                );

                if ($group && $group->hasPermission($permission)) {
                    $option->setParam("selected", "selected");
                }

                return $option;
            })
            ->join();

        switch ($boxId) {
            case "add":
                return $this->template->render(
                    "admin/action_boxes/group_add",
                    compact("permissions")
                );

            case "edit":
                return $this->template->render(
                    "admin/action_boxes/group_edit",
                    compact("permissions", "group")
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
