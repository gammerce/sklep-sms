<?php
namespace App\Pages;

use App\Exceptions\UnauthorizedException;
use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\HeadCell;
use App\Html\Input;
use App\Html\Structure;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PaymentPlatformRepository;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Verification\Abstracts\SupportSms;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'servers';
    protected $privilege = 'manage_servers';

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(PaymentPlatformRepository $paymentPlatformRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('servers');
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('name')));
        $table->addHeadCell(
            new Cell($this->lang->translate('ip') . ':' . $this->lang->translate('port'))
        );
        $table->addHeadCell(new HeadCell($this->lang->translate('version')));

        foreach ($this->heart->getServers() as $server) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($server->getId());
            $bodyRow->addCell(new Cell($server->getName()));
            $bodyRow->addCell(new Cell($server->getIp() . ':' . $server->getPort()));
            $bodyRow->addCell(new Cell($server->getVersion()));

            if (get_privileges("manage_servers")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges("manage_servers")) {
            $button = new Input();
            $button->setParam('id', 'server_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->translate('add_server'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_servers")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "server_edit") {
            $server = $this->heart->getServer($query['id']);
        }

        $smsServices = "";
        foreach ($this->paymentPlatformRepository->all() as $paymentPlatform) {
            $paymentModule = $this->heart->getPaymentModule($paymentPlatform->getModule());
            if ($paymentModule instanceof SupportSms) {
                $smsServices .= create_dom_element("option", $paymentPlatform->getName(), [
                    'value' => $paymentPlatform->getId(),
                    'selected' =>
                        isset($server) && $paymentPlatform->getId() == $server->getSmsService() ? "selected" : "",
                ]);
            }
        }

        $services = "";
        foreach ($this->heart->getServices() as $service) {
            // Dana usługa nie może być kupiona na serwerze
            $serviceModule = $this->heart->getServiceModule($service->getId());
            if (!($serviceModule instanceof IServiceAvailableOnServers)) {
                continue;
            }

            $values = create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('no')),
                [
                    'value' => 0,
                    'selected' =>
                        isset($server) &&
                        $this->heart->serverServiceLinked($server->getId(), $service->getId())
                            ? ""
                            : "selected",
                ]
            );

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('yes')),
                [
                    'value' => 1,
                    'selected' =>
                        isset($server) &&
                        $this->heart->serverServiceLinked($server->getId(), $service->getId())
                            ? "selected"
                            : "",
                ]
            );

            $name = $service->getId();
            $text = "{$service->getName()} ( {$service->getId()} )";

            $services .= $this->template->render(
                "tr_text_select",
                compact('name', 'text', 'values')
            );
        }

        switch ($boxId) {
            case "server_add":
                $output = $this->template->render(
                    "admin/action_boxes/server_add",
                    compact('smsServices', 'services')
                );
                break;

            case "server_edit":
                $output = $this->template->render(
                    "admin/action_boxes/server_edit",
                    compact('server', 'smsServices', 'services')
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
