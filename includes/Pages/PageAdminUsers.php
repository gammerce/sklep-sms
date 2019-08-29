<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Img;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Routes\UrlGenerator;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'users';
    protected $privilege = 'view_users';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('users');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('username')));
        $table->addHeadCell(new Cell($this->lang->translate('firstname')));
        $table->addHeadCell(new Cell($this->lang->translate('surname')));
        $table->addHeadCell(new Cell($this->lang->translate('email')));
        $table->addHeadCell(new Cell($this->lang->translate('groups')));
        $table->addHeadCell(new Cell($this->lang->translate('wallet')));

        $where = '';
        if (isset($get['search'])) {
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
                $get['search'],
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
                $group = $this->heart->get_group($gid);
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

            $buttonCharge = $this->createChargeButton($row['username']);
            $bodyRow->addAction($buttonCharge);

            $changePasswordCharge = $this->createPasswordButton($row['username']);
            $bodyRow->addAction($changePasswordCharge);

            if (get_privileges('manage_users')) {
                $bodyRow->setButtonDelete(true);
                $bodyRow->setButtonEdit(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }

    protected function createChargeButton($username)
    {
        $button = new Img();
        $button->setParam('class', 'charge_wallet clickable');
        $button->setParam(
            'title',
            $this->lang->translate('charge') . ' ' . htmlspecialchars($username)
        );
        $button->setParam('src', $this->url->to('images/dollar.png'));
        return $button;
    }

    protected function createPasswordButton($username)
    {
        $button = new Img();
        $button->setParam('class', 'change_password clickable');
        $button->setParam(
            'title',
            $this->lang->translate('change_password') . ' ' . htmlspecialchars($username)
        );
        $button->setParam('src', $this->url->to('images/key.png'));
        return $button;
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privileges("manage_users")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "user_edit":
                // Pobranie użytkownika
                $user = $this->heart->get_user($data['uid']);

                $groups = '';
                foreach ($this->heart->get_groups() as $group) {
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
                $user = $this->heart->get_user($data['uid']);
                $output = $this->template->render(
                    "admin/action_boxes/user_charge_wallet",
                    compact('user')
                );
                break;

            case "change_password":
                $user = $this->heart->get_user($data['uid']);
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
