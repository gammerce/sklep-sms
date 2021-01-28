<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\PaymentModuleManager;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\PaymentPlatform;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Link;
use App\View\Html\NoneText;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "servers";

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerServiceManager */
    private $serverServiceManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServiceModuleManager $serviceModuleManager,
        PaymentModuleManager $paymentModuleManager,
        ServerManager $serverManager,
        ServiceManager $serviceManager,
        ServerServiceManager $serverServiceManager
    ) {
        parent::__construct($template, $translationManager);
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->serverManager = $serverManager;
        $this->serviceManager = $serviceManager;
        $this->serverServiceManager = $serverServiceManager;
    }

    public function getPrivilege()
    {
        return Permission::VIEW_SERVERS();
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("servers");
    }

    public function getContent(Request $request)
    {
        $recordId = as_int($request->query->get("record"));

        $bodyRows = collect($this->serverManager->all())
            ->filter(fn(Server $server) => $recordId === null || $server->getId() === $recordId)
            ->map(function (Server $server) use ($recordId) {
                return (new BodyRow())
                    ->setDbId($server->getId())
                    ->addCell(new Cell($server->getName(), "name"))
                    ->addCell(new Cell($server->getIp() . ":" . $server->getPort()))
                    ->addCell(new Cell($server->getType() ?: new NoneText()))
                    ->addCell(new Cell($server->getVersion() ?: new NoneText()))
                    ->addCell(new Cell($server->getLastActiveAt() ?: new NoneText()))
                    ->setDeleteAction(can(Permission::MANAGE_SERVERS()))
                    ->setEditAction(can(Permission::MANAGE_SERVERS()))
                    ->when(
                        can(Permission::MANAGE_SERVERS()),
                        fn(BodyRow $bodyRow) => $bodyRow->addAction(
                            $this->createRegenerateTokenButton()
                        )
                    )
                    ->when(
                        $recordId === $server->getId(),
                        fn(BodyRow $bodyRow) => $bodyRow->addClass("highlighted")
                    );
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

        if (can(Permission::MANAGE_SERVERS())) {
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
        if (cannot(Permission::MANAGE_SERVERS())) {
            throw new UnauthorizedException();
        }

        if ($boxId === "edit") {
            $server = $this->serverManager->get($query["id"]);
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
                return new Option($paymentPlatform->getName(), $paymentPlatform->getId(), [
                    "selected" => selected($isSelected),
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
                    $server &&
                    in_array($paymentPlatform->getId(), $server->getTransferPlatformIds(), true);
                return new Option($paymentPlatform->getName(), $paymentPlatform->getId(), [
                    "selected" => selected($isSelected),
                ]);
            })
            ->join();

        $services = collect($this->serviceManager->all())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule instanceof IServicePurchaseExternal;
            })
            ->map(function (Service $service) use ($server) {
                $isLinked =
                    $server &&
                    $this->serverServiceManager->serverServiceLinked(
                        $server->getId(),
                        $service->getId()
                    );

                return new Option(
                    "{$service->getName()} ({$service->getId()})",
                    $service->getId(),
                    [
                        "selected" => selected($isLinked),
                    ]
                );
            })
            ->join();

        switch ($boxId) {
            case "add":
                return $this->template->render(
                    "admin/action_boxes/server_add",
                    compact("smsPlatforms", "services", "transferPlatforms")
                );

            case "edit":
                return $this->template->render(
                    "admin/action_boxes/server_edit",
                    compact("server", "smsPlatforms", "services", "transferPlatforms")
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
