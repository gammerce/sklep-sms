<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Models\Group;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "users";

    /** @var UserRepository */
    private $userRepository;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Heart */
    private $heart;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        PriceTextService $priceTextService,
        Heart $heart,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);

        $this->userRepository = $userRepository;
        $this->priceTextService = $priceTextService;
        $this->heart = $heart;
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getPrivilege()
    {
        return "view_users";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("users");
    }

    public function getContent(Request $request)
    {
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
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->userRepository->mapToModel($row);
            })
            ->map(function (User $user) use ($recordId) {
                $groups = collect($user->getGroups())
                    ->map(function ($groupId) {
                        return $this->heart->getGroup($groupId);
                    })
                    ->filter(function ($group) {
                        return !!$group;
                    })
                    ->map(function (Group $group) {
                        return "{$group->getName()} ({$group->getId()})";
                    })
                    ->join("; ");

                return (new BodyRow())
                    ->setDbId($user->getUid())
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
                    ->setDeleteAction(has_privileges("manage_users"))
                    ->setEditAction(has_privileges("manage_users"))
                    ->when($recordId === $user->getUid(), function (BodyRow $bodyRow) {
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
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }

    private function createChargeButton()
    {
        return (new Link($this->lang->t("charge")))->addClass("dropdown-item charge_wallet");
    }

    private function createPasswordButton()
    {
        return (new Link($this->lang->t("change_password")))->addClass(
            "dropdown-item change_password"
        );
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_users")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "user_edit":
                $user = $this->heart->getUser($query["uid"]);

                $groups = collect($this->heart->getGroups())
                    ->map(function (Group $group) use ($user) {
                        return create_dom_element(
                            "option",
                            "{$group->getName()} ( {$group->getId()} )",
                            [
                                "value" => $group->getId(),
                                "selected" => in_array($group->getId(), $user->getGroups())
                                    ? "selected"
                                    : "",
                            ]
                        );
                    })
                    ->join();

                $output = $this->template->render("admin/action_boxes/user_edit", [
                    "email" => $user->getEmail(),
                    "username" => $user->getUsername(),
                    "surname" => $user->getSurname(),
                    "forename" => $user->getForename(),
                    "steamId" => $user->getSteamId(),
                    "uid" => $user->getUid(),
                    "wallet" => $this->priceTextService->getPlainPrice($user->getWallet()),
                    "groups" => $groups,
                ]);
                break;

            case "charge_wallet":
                $user = $this->heart->getUser($query["uid"]);
                $output = $this->template->render(
                    "admin/action_boxes/user_charge_wallet",
                    compact("user")
                );
                break;

            case "change_password":
                $user = $this->heart->getUser($query["uid"]);
                $output = $this->template->render(
                    "admin/action_boxes/user_change_password",
                    compact("user")
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
