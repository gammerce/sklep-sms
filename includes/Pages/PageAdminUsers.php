<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Link;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'users';
    protected $privilege = 'view_users';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('users');
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

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = 'WHERE ' . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `forename`, `surname`, `email`, `steam_id`, `groups`, `wallet` " .
                "FROM `" .
                TABLE_PREFIX .
                "users` " .
                $where .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            $row['groups'] = explode(";", $row['groups']);
            $groups = [];
            foreach ($row['groups'] as $gid) {
                $group = $this->heart->getGroup($gid);
                $groups[] = "{$group['name']} ({$group['id']})";
            }
            $groups = implode("; ", $groups);

            $bodyRow->setDbId($row['uid']);
            $bodyRow->addCell(new Cell($row['username']));
            $bodyRow->addCell(new Cell($row['forename']));
            $bodyRow->addCell(new Cell($row['surname']));
            $bodyRow->addCell(new Cell($row['email']));
            $bodyRow->addCell(new Cell($row['steam_id']));
            $bodyRow->addCell(new Cell($groups));

            $cell = new Cell(
                number_format($row['wallet'] / 100.0, 2) . ' ' . $this->settings['currency']
            );
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
                    $groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", [
                        'value' => $group['id'],
                        'selected' => in_array($group['id'], $user->getGroups()) ? "selected" : "",
                    ]);
                }

                $output = $this->template->render("admin/action_boxes/user_edit", [
                    "email" => $user->getEmail(),
                    "username" => $user->getUsername(),
                    "surname" => $user->getSurname(),
                    "forename" => $user->getForename(),
                    "steamId" => $user->getSteamId(),
                    "uid" => $user->getUid(),
                    "wallet" => $user->getWallet(true),
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
