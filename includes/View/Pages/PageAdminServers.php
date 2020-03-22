<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\PaymentPlatform;
use App\Models\Service;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "servers";
    protected $privilege = "manage_servers";

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(PaymentPlatformRepository $paymentPlatformRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("servers");
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t("id"), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t("name")));
        $table->addHeadCell(new HeadCell($this->lang->t("ip") . ":" . $this->lang->t("port")));
        $table->addHeadCell(new HeadCell($this->lang->t("platform")));
        $table->addHeadCell(new HeadCell($this->lang->t("version")));
        $table->addHeadCell(new HeadCell($this->lang->t("last_active_at")));

        foreach ($this->heart->getServers() as $server) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($server->getId());

            $nameCell = (new Cell($server->getName()))->setParam("headers", "name");
            $bodyRow->addCell($nameCell);

            $bodyRow->addCell(new Cell($server->getIp() . ":" . $server->getPort()));
            $bodyRow->addCell(new Cell($server->getType() ?: "n/a"));
            $bodyRow->addCell(new Cell($server->getVersion() ?: "n/a"));
            $bodyRow->addCell(new Cell($server->getLastActiveAt() ?: "n/a"));

            $bodyRow->addAction($this->createRegenerateTokenButton());

            if (get_privileges("manage_servers")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges("manage_servers")) {
            $addButton = $this->createAddButton();
            $wrapper->addButton($addButton);
        }

        return $wrapper->toHtml();
    }

    private function createRegenerateTokenButton()
    {
        return (new Link())
            ->addClass("dropdown-item regenerate-token")
            ->addContent($this->lang->t("regenerate_token"));
    }

    private function createAddButton()
    {
        return (new Input())
            ->setParam("id", "server_button_add")
            ->setParam("type", "button")
            ->addClass("button")
            ->setParam("value", $this->lang->t("add_server"));
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_servers")) {
            throw new UnauthorizedException();
        }

        if ($boxId === "server_edit") {
            $server = $this->heart->getServer($query["id"]);
        } else {
            $server = null;
        }

        $smsPlatforms = collect($this->paymentPlatformRepository->all())
            ->filter(function (PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->heart->getPaymentModule($paymentPlatform);
                return $paymentModule instanceof SupportSms;
            })
            ->map(function (PaymentPlatform $paymentPlatform) use ($server) {
                $isSelected = $server && $paymentPlatform->getId() == $server->getSmsPlatformId();
                return create_dom_element("option", $paymentPlatform->getName(), [
                    "value" => $paymentPlatform->getId(),
                    "selected" => $isSelected ? "selected" : "",
                ]);
            })
            ->join();

        $transferPlatforms = collect($this->paymentPlatformRepository->all())
            ->filter(function (PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->heart->getPaymentModule($paymentPlatform);
                return $paymentModule instanceof SupportTransfer;
            })
            ->map(function (PaymentPlatform $paymentPlatform) use ($server) {
                $isSelected =
                    $server && $paymentPlatform->getId() == $server->getTransferPlatformId();
                return create_dom_element("option", $paymentPlatform->getName(), [
                    "value" => $paymentPlatform->getId(),
                    "selected" => $isSelected ? "selected" : "",
                ]);
            })
            ->join();

        $services = collect($this->heart->getServices())
            ->filter(function (Service $service) {
                $serviceModule = $this->heart->getServiceModule($service->getId());
                return $serviceModule instanceof IServicePurchaseExternal;
            })
            ->map(function (Service $service) use ($server) {
                $isLinked =
                    $server &&
                    $this->heart->serverServiceLinked($server->getId(), $service->getId());
                $options = [
                    create_dom_element("option", $this->lang->strtoupper($this->lang->t("no")), [
                        "value" => 0,
                        "selected" => $isLinked ? "" : "selected",
                    ]),
                    create_dom_element("option", $this->lang->strtoupper($this->lang->t("yes")), [
                        "value" => 1,
                        "selected" => $isLinked ? "selected" : "",
                    ]),
                ];
                return $this->template->render("tr_text_select", [
                    "name" => $service->getId(),
                    "text" => "{$service->getName()} ( {$service->getId()} )",
                    "values" => implode("", $options),
                ]);
            })
            ->join();

        switch ($boxId) {
            case "server_add":
                $output = $this->template->render(
                    "admin/action_boxes/server_add",
                    compact("smsPlatforms", "services", "transferPlatforms")
                );
                break;

            case "server_edit":
                $output = $this->template->render(
                    "admin/action_boxes/server_edit",
                    compact("server", "smsPlatforms", "services", "transferPlatforms")
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
