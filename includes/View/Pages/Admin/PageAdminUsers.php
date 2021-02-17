<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\GroupManager;
use App\Managers\UserManager;
use App\Models\Group;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\PriceTextService;
use App\Support\QueryParticle;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\User\PermissionService;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DOMElement;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "users";

    private Auth $auth;
    private Database $db;
    private GroupManager $groupManager;
    private PermissionService $permissionService;
    private PaginationFactory $paginationFactory;
    private PriceTextService $priceTextService;
    private UserManager $userManager;
    private UserRepository $userRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        Database $db,
        GroupManager $groupManager,
        PermissionService $permissionService,
        PaginationFactory $paginationFactory,
        PriceTextService $priceTextService,
        UserManager $userManager,
        UserRepository $userRepository
    ) {
        parent::__construct($template, $translationManager);

        $this->auth = $auth;
        $this->db = $db;
        $this->groupManager = $groupManager;
        $this->permissionService = $permissionService;
        $this->paginationFactory = $paginationFactory;
        $this->priceTextService = $priceTextService;
        $this->userManager = $userManager;
        $this->userRepository = $userRepository;
    }

    public function getPrivilege(): Permission
    {
        return Permission::VIEW_USERS();
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("users");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $recordId = as_int($request->query->get("record"));
        $search = $request->query->get("search");

        $queryParticle = new QueryParticle();

        if ($recordId) {
            $queryParticle->add("`uid` = ?", [$recordId]);
        } elseif ($search) {
            $queryParticle->extend(
                create_search_query(
                    [
                        "`uid`",
                        "`username`",
                        "`forename`",
                        "`surname`",
                        "`email`",
                        "`steam_id`",
                        "`groups`",
                        "`wallet`",
                    ],
                    $search
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle}";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_users` {$where} LIMIT ?, ?"
        );
        $statement->execute(array_merge($queryParticle->params(), $pagination->getSqlLimit()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->userRepository->mapToModel($row))
            ->map(function (User $user) use ($recordId) {
                $groups = collect($user->getGroups())
                    ->map(fn($groupId) => $this->groupManager->get($groupId))
                    ->filter(fn($group) => !!$group)
                    ->map(fn(Group $group) => "{$group->getName()} ({$group->getId()})")
                    ->join("; ");

                return (new BodyRow())
                    ->setDbId($user->getId())
                    ->addCell(new Cell($user->getUsername()))
                    ->addCell(new Cell($user->getForename()))
                    ->addCell(new Cell($user->getSurname()))
                    ->addCell(new Cell($user->getEmail()))
                    ->addCell(new Cell($user->getSteamId()))
                    ->addCell(new Cell($groups))
                    ->addCell(
                        new Cell(
                            $this->priceTextService->getPriceText($user->getWallet()),
                            "wallet"
                        )
                    )
                    ->addAction($this->createChargeButton())
                    ->addAction($this->createPasswordButton())
                    ->setDeleteAction(can(Permission::MANAGE_USERS()))
                    ->setEditAction(can(Permission::MANAGE_USERS()))
                    ->when($recordId === $user->getId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass("highlighted");
                    });
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("username")))
            ->addHeadCell(new HeadCell($this->lang->t("firstname")))
            ->addHeadCell(new HeadCell($this->lang->t("surname")))
            ->addHeadCell(new HeadCell($this->lang->t("email")))
            ->addHeadCell(new HeadCell($this->lang->t("sid")))
            ->addHeadCell(new HeadCell($this->lang->t("groups")))
            ->addHeadCell(new HeadCell($this->lang->t("wallet")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }

    private function createChargeButton(): DOMElement
    {
        return (new Link($this->lang->t("charge")))->addClass("dropdown-item charge_wallet");
    }

    private function createPasswordButton(): DOMElement
    {
        return (new Link($this->lang->t("change_password")))->addClass(
            "dropdown-item change_password"
        );
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::MANAGE_USERS())) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "edit":
                $user = $this->userManager->get($query["user_id"]);
                $groups = $this->getGroupsSection($user);

                return $this->template->render("admin/action_boxes/user_edit", [
                    "email" => $user->getEmail(),
                    "username" => $user->getUsername(),
                    "surname" => $user->getSurname(),
                    "forename" => $user->getForename(),
                    "steamId" => $user->getSteamId(),
                    "userId" => $user->getId(),
                    "wallet" => $user->getWallet(),
                    "groups" => $groups,
                ]);

            case "charge_wallet":
                $user = $this->userManager->get($query["user_id"]);
                return $this->template->render(
                    "admin/action_boxes/user_charge_wallet",
                    compact("user")
                );

            case "change_password":
                $user = $this->userManager->get($query["user_id"]);
                return $this->template->render(
                    "admin/action_boxes/user_change_password",
                    compact("user")
                );

            default:
                throw new EntityNotFoundException();
        }
    }

    private function getGroupsSection(User $user): string
    {
        if (!$this->permissionService->canChangeUserGroup($this->auth->user(), $user)) {
            return $this->template->render("admin/components/action_box/text_row", [
                "title" => $this->lang->t("groups"),
                "text" => $this->lang->t("user_groups_hint"),
            ]);
        }

        $groups = collect($this->groupManager->all())
            ->filter(
                fn(Group $group) => $this->permissionService->canUserAssignGroup(
                    $this->auth->user(),
                    $group
                )
            )
            ->map(function (Group $group) use ($user) {
                $selected = in_array($group->getId(), $user->getGroups());
                return new Option("{$group->getName()} ({$group->getId()})", $group->getId(), [
                    "selected" => selected($selected),
                ]);
            })
            ->join();

        return $this->template->render("admin/components/action_box/multi_select", [
            "id" => "groups",
            "title" => $this->lang->t("groups"),
            "subtitle" => "",
            "items" => $groups,
            "size" => 4,
        ]);
    }
}
