<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminSmsCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'sms_codes';
    protected $privilege = 'view_sms_codes';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('sms_codes');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('sms_code')));
        $table->addHeadCell(new HeadCell($this->lang->t('tariff')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `" .
                TABLE_PREFIX .
                "sms_codes` " .
                "WHERE `free` = '1' " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['code']));
            $bodyRow->addCell(new Cell($row['tariff']));

            if (get_privileges('manage_sms_codes')) {
                $bodyRow->setDeleteAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_sms_codes')) {
            $button = new Input();
            $button->setParam('id', 'sms_code_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->t('add_code'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_sms_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "sms_code_add":
                $tariffs = "";
                foreach ($this->heart->getTariffs() as $tariff) {
                    $tariffs .= create_dom_element("option", $tariff->getId(), [
                        'value' => $tariff->getId(),
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/sms_code_add",
                    compact('tariffs')
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
