<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Input;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Services\Interfaces\IServiceServiceCodeAdminManage;

class PageAdminServiceCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'service_codes';
    protected $privilege = 'view_service_codes';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('service_codes');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('code')));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('server')));
        $table->addHeadCell(new HeadCell($this->lang->translate('amount')));
        $table->addHeadCell(new HeadCell($this->lang->translate('user')));
        $table->addHeadCell(new HeadCell($this->lang->translate('date_of_creation')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.tariff, pl.amount AS `tariff_amount`,
			u.username, u.uid, sc.amount, sc.data, sc.timestamp, s.tag " .
                "FROM `" .
                TABLE_PREFIX .
                "service_codes` AS sc " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "services` AS s ON sc.service = s.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "servers` AS srv ON sc.server = srv.id " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "users` AS u ON sc.uid = u.uid " .
                "LEFT JOIN `" .
                TABLE_PREFIX .
                "pricelist` AS pl ON sc.tariff = pl.tariff AND sc.service = pl.service
			AND (pl.server = '-1' OR sc.server = pl.server) " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $username = $row['uid']
                ? $row['username'] . " ({$row['uid']})"
                : $this->lang->translate('none');

            if ($row['tariff_amount']) {
                $amount = $row['tariff_amount'] . ' ' . $row['tag'];
            } else {
                if ($row['tariff']) {
                    $amount = $this->lang->translate('tariff') . ': ' . $row['tariff'];
                } else {
                    if ($row['amount']) {
                        $amount = $row['amount'];
                    } else {
                        $amount = $this->lang->translate('none');
                    }
                }
            }

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['code']));
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['server']));
            $bodyRow->addCell(new Cell($amount));
            $bodyRow->addCell(new Cell($username));
            $bodyRow->addCell(new Cell(convertDate($row['timestamp'])));

            if (get_privileges('manage_service_codes')) {
                $bodyRow->setDeleteAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_service_codes')) {
            $button = new Input();
            $button->setParam('id', 'service_code_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->translate('add_code'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_service_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "code_add":
                // Pobranie usług
                $services = "";
                foreach ($this->heart->getServices() as $id => $service) {
                    if (
                        ($serviceModule = $this->heart->getServiceModule($id)) === null ||
                        !($serviceModule instanceof IServiceServiceCodeAdminManage)
                    ) {
                        continue;
                    }

                    $services .= create_dom_element("option", $service->getName(), [
                        'value' => $service->getId(),
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/service_code_add",
                    compact('services')
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
