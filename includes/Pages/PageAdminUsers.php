<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Link;
use App\Html\SimpleText;
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

        $this->heart->pageTitle = $this->title = $this->lang->translate('users');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('username')));
        $table->addHeadCell(new HeadCell($this->lang->translate('firstname')));
        $table->addHeadCell(new HeadCell($this->lang->translate('surname')));
        $table->addHeadCell(new HeadCell($this->lang->translate('email')));
        $table->addHeadCell(new HeadCell($this->lang->translate('groups')));
        $table->addHeadCell(new HeadCell($this->lang->translate('wallet')));

        $where = '';
        if (isset($query['search'])) {
            searchWhere(
                [
                    "`uid`",
                    "`username`",
                    "`forename`",
                    "`surname`",
                    "`email`",
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
            "SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `forename`, `surname`, `email`, `groups`, `wallet` " .
                "FROM `" .
                TABLE_PREFIX .
                "users` " .
                $where .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $row['groups'] = explode(";", $row['groups']);
            $groups = [];
            foreach ($row['groups'] as $gid) {
                $group = $this->heart->getGroup($gid);
                $groups[] = "{$group['name']} ({$group['id']})";
            }
            $groups = implode("; ", $groups);

            $bodyRow->setDbId($row['uid']);
            $bodyRow->addCell(new Cell(htmlspecialchars($row['username'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['forename'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['surname'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['email'])));
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
        $button->addContent(new SimpleText($this->lang->translate('charge')));
        return $button;
    }

    protected function createPasswordButton()
    {
        $button = new Link();
        $button->addClass('dropdown-item change_password');
        $button->addContent(new SimpleText($this->lang->translate('change_password')));
        return $button;
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_users")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($boxId) {
            case "user_edit":
                // Pobranie użytkownika
                $user = $this->heart->getUser($data['uid']);

                $groups = '';
                foreach ($this->heart->getGroups() as $group) {
                    $groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", [
                        'value' => $group['id'],
                        'selected' => in_array($group['id'], $user->getGroups()) ? "selected" : "",
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/user_edit",
                    compact('user', 'groups')
                );
                break;

            case "charge_wallet":
                $user = $this->heart->getUser($data['uid']);
                $output = $this->template->render(
                    "admin/action_boxes/user_charge_wallet",
                    compact('user')
                );
                break;

            case "change_password":
                $user = $this->heart->getUser($data['uid']);
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
