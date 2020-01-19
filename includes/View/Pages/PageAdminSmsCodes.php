<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
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
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('sms_code')));
        $table->addHeadCell(new HeadCell($this->lang->t('sms_price')));

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
            $smsCode = $this->smsCodeRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($smsCode->getId());
            $bodyRow->addCell(new Cell($smsCode->getCode()));
            $bodyRow->addCell(
                new Cell($this->priceTextService->getSmsGrossText($smsCode->getSmsPrice()))
            );

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
                $smsPrices = "";
                foreach ($this->smsPriceRepository->all() as $smsPrice) {
                    $smsPrices .= create_dom_element(
                        "option",
                        $this->priceTextService->getSmsGrossText($smsPrice),
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
