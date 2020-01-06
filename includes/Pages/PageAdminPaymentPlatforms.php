<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Input;
use App\Html\Option;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Models\PaymentPlatform;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PaymentPlatformRepository;

// TODO Action box for creating model

class PageAdminPaymentPlatforms extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'payment_platforms';
    protected $privilege = 'manage_settings';

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(PaymentPlatformRepository $paymentPlatformRepository)
    {
        parent::__construct();

        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->heart->pageTitle = $this->title = $this->lang->t('payment_platforms');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $button = new Input();
        $button->setParam('id', 'payment_platform_button_add');
        $button->setParam('type', 'button');
        $button->addClass('button is-small');
        $button->setParam('value', $this->lang->t('add_payment_platform'));
        $wrapper->addButton($button);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('name')));
        $table->addHeadCell(new HeadCell($this->lang->t('module')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "payment_platforms` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
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

            return $this->template->render("admin/action_boxes/payment_platform_create", [
                'paymentModules' => implode("", $paymentModules),
            ]);
        }

        if ($boxId === "edit") {
            $paymentPlatformId = array_get($query, 'id');
            $paymentPlatform = $this->paymentPlatformRepository->getOrFail($paymentPlatformId);
            $dataFields = $this->renderDataFields($paymentPlatform);

            return $this->template->render(
                "admin/action_boxes/payment_platform_edit",
                compact('paymentPlatform', 'dataFields')
            );
        }

        return '';
    }

    private function renderDataFields(PaymentPlatform $paymentPlatform)
    {
        $paymentModule = $this->heart->getPaymentModule($paymentPlatform);

        $dataFields = [];
        foreach ($paymentModule->getDataFields() as $dataField) {
            $text = $dataField->getName() ?: $this->getCustomDataText($dataField->getId());
            $value = array_get($paymentPlatform->getData(), $dataField->getId());

            $dataFields[] = $this->template->render("tr_name_input", [
                "name" => $dataField->getId(),
                "value" => $value,
                "text" => $text,
            ]);
        }

        return implode("", $dataFields);
    }

    private function getCustomDataText($name)
    {
        switch ($name) {
            case 'sms_text':
                return $this->lang->strtoupper($this->lang->t('sms_code'));
            case 'account_id':
                return $this->lang->strtoupper($this->lang->t('account_id'));
            default:
                return $this->lang->strtoupper($name);
        }
    }
}
