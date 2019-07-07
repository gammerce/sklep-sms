<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Img;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Routes\UrlGenerator;

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'users';
    protected $privilage = 'view_users';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('users');
    }

    protected function content($get, $post)
    {
        /** @var UrlGenerator $url */
        $url = $this->app->make(UrlGenerator::class);

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

        $table->setDbRowsAmount($this->db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $row['groups'] = explode(";", $row['groups']);
            $groups = [];
            foreach ($row['groups'] as $gid) {
                $group = $this->heart->get_group($gid);
                $groups[] = "{$group['name']} ({$group['id']})";
            }
            $groups = implode("; ", $groups);

            $body_row->setDbId($row['uid']);
            $body_row->addCell(new Cell(htmlspecialchars($row['username'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['forename'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['surname'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['email'])));
            $body_row->addCell(new Cell($groups));

            $cell = new Cell(
                number_format($row['wallet'] / 100.0, 2) . ' ' . $this->settings['currency']
            );
            $cell->setParam('headers', 'wallet');
            $body_row->addCell($cell);

            $button_charge = new Img();
            $button_charge->setParam('class', 'charge_wallet');
            $button_charge->setParam(
                'title',
                $this->lang->translate('charge') . ' ' . htmlspecialchars($row['username'])
            );
            $button_charge->setParam('src', $url->to('images/dollar.png'));
            $body_row->addAction($button_charge);

            if (get_privilages('manage_users')) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_users")) {
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

            default:
                $output = '';
        }

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }
}
