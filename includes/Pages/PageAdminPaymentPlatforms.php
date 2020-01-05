<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Option;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Models\PaymentPlatform;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PaymentPlatformRepository;

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
        $this->heart->pageTitle = $this->title = $this->lang->translate('payment_platforms');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('name')));
        $table->addHeadCell(new HeadCell($this->lang->translate('module')));

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

            $bodyRow->setDbId($paymentPlatform->getId());
            $bodyRow->addCell(new Cell($paymentPlatform->getName()));
            $bodyRow->addCell(new Cell($paymentPlatform->getModuleId()));

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
                return new Option($paymentModuleId, $paymentModuleId);
            }, $this->heart->getPaymentModuleIds());

            // TODO Display additional fields on selecting payment platform
            // TODO Use data fields from transaction_services
            // TODO Do not allow selecting default for server's payment platform if settings are empty

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
                return $this->lang->strtoupper($this->lang->translate('sms_code'));
            case 'account_id':
                return $this->lang->strtoupper($this->lang->translate('account_id'));
            default:
                return $this->lang->strtoupper($name);
        }
    }
}
