<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PaymentPlatformRepository;

class PageAdminPaymentPlatforms extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'payment_platforms';
    protected $privilege = 'manage_settings';

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct()
    {
        parent::__construct();

        $this->paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);
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
            $bodyRow->addCell(new Cell($paymentPlatform->getModule()));

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

        $output = $this->getActionBoxContent($boxId, $query['id']);

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }

    private function getActionBoxContent($boxId, $paymentPlatformId)
    {
        switch ($boxId) {
            case "edit":
                $paymentPlatform = $this->paymentPlatformRepository->get($paymentPlatformId);

                $dataValues = "";
                foreach ($paymentPlatform->getData() as $name => $value) {
                    $text = $this->getCustomDataText($name);
                    $dataValues .= $this->template->render(
                        "tr_name_input",
                        compact('text', 'name', 'value')
                    );
                }

                return $this->template->render(
                    "admin/action_boxes/payment_platform_edit",
                    compact('paymentPlatform', 'dataValues')
                );

            default:
                return '';
        }
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
