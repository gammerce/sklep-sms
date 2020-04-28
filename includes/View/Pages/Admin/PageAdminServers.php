<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Models\PaymentPlatform;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Request;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "servers";

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var Heart */
    private $heart;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServiceModuleManager $serviceModuleManager,
        PaymentModuleManager $paymentModuleManager,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->heart = $heart;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getPrivilege()
    {
        return "manage_servers";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("servers");
    }

    public function getContent(Request $request)
    {
        $recordId = as_int($request->query->get("record"));

        $bodyRows = collect($this->heart->getServers())
            ->filter(function (Server $server) use ($recordId) {
                return $recordId === null || $server->getId() === $recordId;
            })
            ->map(function (Server $server) use ($recordId) {
                return (new BodyRow())
                    ->setDbId($server->getId())
                    ->addCell(new Cell($server->getName(), "name"))
                    ->addCell(new Cell($server->getIp() . ":" . $server->getPort()))
                    ->addCell(new Cell($server->getType() ?: "n/a"))
                    ->addCell(new Cell($server->getVersion() ?: "n/a"))
                    ->addCell(new Cell($server->getLastActiveAt() ?: "n/a"))
                    ->addAction($this->createRegenerateTokenButton())
                    ->setDeleteAction(has_privileges("manage_servers"))
                    ->setEditAction(has_privileges("manage_servers"))
                    ->when($recordId === $server->getId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass("highlighted");
                    });
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addHeadCell(new HeadCell($this->lang->t("ip") . ":" . $this->lang->t("port")))
            ->addHeadCell(new HeadCell($this->lang->t("platform")))
            ->addHeadCell(new HeadCell($this->lang->t("version")))
            ->addHeadCell(new HeadCell($this->lang->t("last_active_at")))
            ->addBodyRows($bodyRows);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (has_privileges("manage_servers")) {
            $wrapper->addButton($this->createAddButton());
        }

        return $wrapper->toHtml();
    }

    private function createRegenerateTokenButton()
    {
        return (new Link($this->lang->t("regenerate_token")))->addClass(
            "dropdown-item regenerate-token"
        );
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
        if (!has_privileges("manage_servers")) {
            throw new UnauthorizedException();
        }

        if ($boxId === "server_edit") {
            $server = $this->heart->getServer($query["id"]);
        } else {
            $server = null;
        }

        $smsPlatforms = collect($this->paymentPlatformRepository->all())
            ->filter(function (PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
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
                $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
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
                $serviceModule = $this->serviceModuleManager->get($service->getId());
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
