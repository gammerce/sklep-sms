<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Managers\ServiceModuleManager;
use App\Support\Template;

class ServiceCodeAddFormController
{
    public function get(
        $serviceId,
        ServerManager $serverManager,
        ServerServiceManager $serverServiceManager,
        ServiceModuleManager $serviceModuleManager,
        Template $template
    ) {
        $serviceModule = $serviceModuleManager->get($serviceId);

        $servers = [];
        foreach ($serverManager->getServers() as $id => $server) {
            if ($serverServiceManager->serverServiceLinked($id, $serviceModule->service->getId())) {
                $servers[] = create_dom_element("option", $server->getName(), [
                    'value' => $server->getId(),
                ]);
            }
        }

        $output = $template->renderNoComments("admin/action_boxes/service_code_add_additional", [
            'servers' => implode("", $servers),
        ]);

        return new PlainResponse($output);
    }
}
