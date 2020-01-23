<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\Http\Services\DataFieldService;
use App\View\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PaymentPlatformRepository;

class PageAdminPaymentPlatforms extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'payment_platforms';
    protected $privilege = 'manage_settings';

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var DataFieldService */
    private $dataFieldService;

    public function __construct(
        PaymentPlatformRepository $paymentPlatformRepository,
        DataFieldService $dataFieldService
    ) {
        parent::__construct();

        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->dataFieldService = $dataFieldService;
        $this->heart->pageTitle = $this->title = $this->lang->t('payment_platforms');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $button = new Input();
        $button->setParam('id', 'payment_platform_button_add');
        $button->setParam('type', 'button');
        $button->addClass('button');
        $button->setParam('value', $this->lang->t('add_payment_platform'));
        $wrapper->addButton($button);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('name')));
        $table->addHeadCell(new HeadCell($this->lang->t('module')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_payment_platforms` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $paymentPlatform = $this->paymentPlatformRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            $nameCell = new Cell($paymentPlatform->getName());
            $nameCell->setParam('headers', 'name');

            $bodyRow->setDbId($paymentPlatform->getId());
            $bodyRow->addCell($nameCell);
            $bodyRow->addCell(new Cell($this->lang->t($paymentPlatform->getModuleId())));

            $bodyRow->setEditAction(true);
            $bodyRow->setDeleteAction(true);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_settings")) {
            throw new UnauthorizedException();
        }

        $output = $this->getActionBoxContent($boxId, $query);

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }

    private function getActionBoxContent($boxId, array $query)
    {
        if ($boxId === "create") {
            $paymentModules = array_map(function ($paymentModuleId) {
                return new Option($this->lang->t($paymentModuleId), $paymentModuleId);
            }, $this->heart->getPaymentModuleIds());

            return $this->template->render("admin/action_boxes/payment_platform_add", [
                'paymentModules' => implode("", $paymentModules),
            ]);
        }

        if ($boxId === "edit") {
            $paymentPlatformId = array_get($query, 'id');
            $paymentPlatform = $this->paymentPlatformRepository->getOrFail($paymentPlatformId);
            $dataFields = $this->dataFieldService->renderDataFields(
                $paymentPlatform->getModuleId(),
                $paymentPlatform->getData()
            );

            return $this->template->render(
                "admin/action_boxes/payment_platform_edit",
                compact('paymentPlatform', 'dataFields')
            );
        }

        return '';
    }
}
