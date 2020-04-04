<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Repositories\GroupRepository;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminGroups extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'groups';
    protected $privilege = 'view_groups';

    /** @var GroupRepository */
    private $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('groups');
        $this->groupRepository = $groupRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('name')));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_groups` LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $table->enablePagination($rowsCount);

        foreach ($statement as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['name']));

            if (get_privileges('manage_groups')) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_groups')) {
            $button = new Input();
            $button->setParam('id', 'group_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->t('add_group'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_groups")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "group_edit") {
            $group = $this->groupRepository->get($query['id']);

            if (!$group) {
                return [
                    'status' => 'ok',
                    'template' => create_dom_element("form", $this->lang->t('no_such_group'), [
                        'class' => 'action_box',
                        'style' => [
                            'padding' => "20px",
                            'color' => "white",
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

            $values = create_dom_element("option", $this->lang->strtoupper($this->lang->t('no')), [
                'value' => 0,
                'selected' => isset($group) && $group->hasPermission($fieldName) ? "" : "selected",
            ]);

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->t('yes')),
                [
                    'value' => 1,
                    'selected' =>
                        isset($group) && $group->hasPermission($fieldName) ? "selected" : "",
                ]
            );

            $privileges .= $this->template->render("tr_text_select", [
                "name" => $fieldName,
                "text" => $this->lang->t('privilege_' . $fieldName),
                "values" => $values,
            ]);
        }

        switch ($boxId) {
            case "group_add":
                $output = $this->template->render(
                    "admin/action_boxes/group_add",
                    compact('privileges')
                );
                break;

            case "group_edit":
                $output = $this->template->render(
                    "admin/action_boxes/group_edit",
                    compact('privileges', 'group')
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
