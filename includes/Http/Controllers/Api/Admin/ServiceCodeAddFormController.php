<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\PlainResponse;
use App\System\Heart;
use App\System\Template;

class ServiceCodeAddFormController
{
    public function get($serviceId, Heart $heart, Template $template)
    {
        $serviceModule = $heart->getServiceModule($serviceId);

        $servers = [];
        foreach ($heart->getServers() as $id => $server) {
            if ($heart->serverServiceLinked($id, $serviceModule->service->getId())) {
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
