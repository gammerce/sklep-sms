<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Repositories\ServiceCodeRepository;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminServiceCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'service_codes';
    protected $privilege = 'view_service_codes';

    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    public function __construct(ServiceCodeRepository $serviceCodeRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('service_codes');
        $this->serviceCodeRepository = $serviceCodeRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('code')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(new HeadCell($this->lang->t('quantity')));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('date_of_creation')));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.price, u.username, u.uid, sc.timestamp " .
                "FROM `ss_service_codes` AS sc " .
                "LEFT JOIN `ss_services` AS s ON sc.service = s.id " .
                "LEFT JOIN `ss_servers` AS srv ON sc.server = srv.id " .
                "LEFT JOIN `ss_users` AS u ON sc.uid = u.uid " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $bodyRow = new BodyRow();

            $username = $row['uid']
                ? $row['username'] . " ({$row['uid']})"
                : $this->lang->t('none');

            $quantity = "{$this->lang->t('price')} #{$row['price']}";

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($row['code']));
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['server']));
            $bodyRow->addCell(new Cell($quantity));
            $bodyRow->addCell(new Cell($username));
            $bodyRow->addCell(new Cell(convert_date($row['timestamp'])));

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
            $button->setParam('value', $this->lang->t('add_code'));
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
                $services = [];
                foreach ($this->heart->getServices() as $id => $service) {
                    $services[] = create_dom_element("option", $service->getName(), [
                        'value' => $service->getId(),
                    ]);
                }

                $output = $this->template->render("admin/action_boxes/service_code_add", [
                    'services' => implode("", $services),
                ]);
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
