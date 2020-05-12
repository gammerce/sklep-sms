<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\PromoCode;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\PromoCodeRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

// TODO Promo code add form
// TODO Display more columns (expiration, remaining usages)

class PageAdminPromoCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "promo_codes";

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var PromoCodeRepository */
    private $promoCodeRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PromoCodeRepository $promoCodeRepository,
        Database $db,
        CurrentPage $currentPage,
        ServiceManager $serviceManager,
        ServerManager $serverManager
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->currentPage = $currentPage;
        $this->serviceManager = $serviceManager;
        $this->promoCodeRepository = $promoCodeRepository;
        $this->serverManager = $serverManager;
    }

    public function getPrivilege()
    {
        return "view_promo_codes";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("promo_codes");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS *" . "FROM `ss_promo_codes` AS sc " . "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->promoCodeRepository->mapToModel($row);
            })
            ->map(function (PromoCode $promoCode) {
                return (new BodyRow())
                    ->setDbId($promoCode->getId())
                    ->addCell(new Cell($promoCode->getCode()))
                    ->addCell(new Cell($promoCode->getQuantityFormatted()))
                    ->addCell(new Cell(convert_date($promoCode->getCreatedAt())))
                    ->setDeleteAction(has_privileges("manage_promo_codes"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("code")))
            ->addHeadCell(new HeadCell($this->lang->t("quantity")))
            ->addHeadCell(new HeadCell($this->lang->t("date_of_creation")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (has_privileges("manage_promo_codes")) {
            $button = (new Input())
                ->setParam("id", "promo_code_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_code"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_promo_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "add":
                $services = collect($this->serviceManager->getServices())
                    ->map(function (Service $service) {
                        return new Option($service->getName(), $service->getId());
                    })
                    ->join();

                $servers = collect($this->serverManager->getServers())
                    ->map(function (Server $server) {
                        return new Option($server->getName(), $server->getId());
                    })
                    ->join();

                $output = $this->template->render(
                    "admin/action_boxes/promo_code_add",
                    compact("services", "servers")
                );
                break;

            default:
                $output = "";
        }

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }
}
