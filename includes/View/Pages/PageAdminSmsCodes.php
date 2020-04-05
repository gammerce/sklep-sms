<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\SmsCode;
use App\Repositories\SmsCodeRepository;
use App\Repositories\SmsPriceRepository;
use App\Services\PriceTextService;
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

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    public function __construct(
        SmsPriceRepository $smsPriceRepository,
        SmsCodeRepository $smsCodeRepository,
        PriceTextService $priceTextService
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('sms_codes');
        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
        $this->smsCodeRepository = $smsCodeRepository;
    }

    protected function content(array $query, array $body)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_sms_codes` " .
                "WHERE `free` = '1' " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->smsCodeRepository->mapToModel($row);
            })
            ->map(function (SmsCode $smsCode) {
                return (new BodyRow())
                    ->setDbId($smsCode->getId())
                    ->addCell(new Cell($smsCode->getCode()))
                    ->addCell(
                        new Cell(
                            $this->priceTextService->getPriceGrossText($smsCode->getSmsPrice())
                        )
                    )
                    ->addCell(
                        new Cell(
                            as_date_string($smsCode->getExpiresAt()) ?: $this->lang->t("never")
                        )
                    )
                    ->setDeleteAction(has_privileges('manage_sms_codes'));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('sms_code')))
            ->addHeadCell(new HeadCell($this->lang->t('sms_price')))
            ->addHeadCell(new HeadCell($this->lang->t('expires')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->title)->setTable($table);

        if (has_privileges('manage_sms_codes')) {
            $button = (new Input())
                ->setParam('id', 'sms_code_button_add')
                ->setParam('type', 'button')
                ->addClass('button')
                ->setParam('value', $this->lang->t('add_code'));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_sms_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "sms_code_add":
                $smsPrices = "";
                foreach ($this->smsPriceRepository->all() as $smsPrice) {
                    $smsPrices .= create_dom_element(
                        "option",
                        $this->priceTextService->getPriceGrossText($smsPrice),
                        [
                            'value' => $smsPrice,
                        ]
                    );
                }

                $output = $this->template->render(
                    "admin/action_boxes/sms_code_add",
                    compact('smsPrices')
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
