<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\Group;
use App\Repositories\UserRepository;
use App\Services\PriceTextService;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'users';
    protected $privilege = 'view_users';

    /** @var UserRepository */
    private $userRepository;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(UserRepository $userRepository, PriceTextService $priceTextService)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('users');
        $this->userRepository = $userRepository;
        $this->priceTextService = $priceTextService;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('username')));
        $table->addHeadCell(new HeadCell($this->lang->t('firstname')));
        $table->addHeadCell(new HeadCell($this->lang->t('surname')));
        $table->addHeadCell(new HeadCell($this->lang->t('email')));
        $table->addHeadCell(new HeadCell($this->lang->t('sid')));
        $table->addHeadCell(new HeadCell($this->lang->t('groups')));
        $table->addHeadCell(new HeadCell($this->lang->t('wallet')));

        $where = '';
        if (isset($query['search'])) {
            searchWhere(
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
                $query['search'],
                $where
            );
        }

        if (strlen($where)) {
            $where = 'WHERE ' . $where . ' ';
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_users` " .
                $where .
                "LIMIT ?"
        );
        $statement->execute([get_row_limit($this->currentPage->getPageNumber())]);

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $user = $this->userRepository->mapToModel($row);
            $bodyRow = new BodyRow();

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

            $bodyRow->setDbId($user->getUid());
            $bodyRow->addCell(new Cell($user->getUsername()));
            $bodyRow->addCell(new Cell($user->getForename()));
            $bodyRow->addCell(new Cell($user->getSurname()));
            $bodyRow->addCell(new Cell($user->getEmail()));
            $bodyRow->addCell(new Cell($user->getSteamId()));
            $bodyRow->addCell(new Cell($groups));

            $cell = new Cell($this->priceTextService->getPriceText($user->getWallet()));
            $cell->setParam('headers', 'wallet');
            $bodyRow->addCell($cell);

            $buttonCharge = $this->createChargeButton();
            $bodyRow->addAction($buttonCharge);

            $changePasswordCharge = $this->createPasswordButton();
            $bodyRow->addAction($changePasswordCharge);

            if (get_privileges('manage_users')) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }

    protected function createChargeButton()
    {
        $button = new Link();
        $button->addClass('dropdown-item charge_wallet');
        $button->addContent($this->lang->t('charge'));
        return $button;
    }

    protected function createPasswordButton()
    {
        $button = new Link();
        $button->addClass('dropdown-item change_password');
        $button->addContent($this->lang->t('change_password'));
        return $button;
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_users")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "user_edit":
                $user = $this->heart->getUser($query['uid']);

                $groups = '';
                foreach ($this->heart->getGroups() as $group) {
                    $groups .= create_dom_element(
                        "option",
                        "{$group->getName()} ( {$group->getId()} )",
                        [
                            'value' => $group->getId(),
                            'selected' => in_array($group->getId(), $user->getGroups())
                                ? "selected"
                                : "",
                        ]
                    );
                }

                $output = $this->template->render("admin/action_boxes/user_edit", [
                    "email" => $user->getEmail(),
                    "username" => $user->getUsername(),
                    "surname" => $user->getSurname(),
                    "forename" => $user->getForename(),
                    "steamId" => $user->getSteamId(),
                    "uid" => $user->getUid(),
                    "wallet" => number_format($user->getWallet() / 100.0, 2),
                    "groups" => $groups,
                ]);
                break;

            case "charge_wallet":
                $user = $this->heart->getUser($query['uid']);
                $output = $this->template->render(
                    "admin/action_boxes/user_charge_wallet",
                    compact('user')
                );
                break;

            case "change_password":
                $user = $this->heart->getUser($query['uid']);
                $output = $this->template->render(
                    "admin/action_boxes/user_change_password",
                    compact('user')
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
