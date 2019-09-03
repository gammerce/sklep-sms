<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminTariffs extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'tariffs';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('tariffs');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('tariff'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('provision')));

        foreach ($this->heart->getTariffs() as $tariff) {
            $bodyRow = new BodyRow();

            $provision = number_format($tariff->getProvision() / 100.0, 2);

            $bodyRow->setDbId($tariff->getId());
            $bodyRow->addCell(new Cell("{$provision} {$this->settings['currency']}"));

            $bodyRow->setButtonEdit(true);
            if (!$tariff->isPredefined()) {
                $bodyRow->setButtonDelete(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        $button = new Input();
        $button->setParam('id', 'tariff_button_add');
        $button->setParam('type', 'button');
        $button->setParam('class', 'button');
        $button->setParam('value', $this->lang->translate('add_tariff'));
        $wrapper->addButton($button);

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_settings")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($boxId) {
            case "tariff_add":
                $output = $this->template->render("admin/action_boxes/tariff_add");
                break;

            case "tariff_edit":
                $tariff = $this->heart->getTariff($data['id']);
                $provision = number_format($tariff->getProvision() / 100.0, 2);

                $output = $this->template->render(
                    "admin/action_boxes/tariff_edit",
                    compact('provision', 'tariff')
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
