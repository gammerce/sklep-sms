<?php
namespace App\View\Blocks;

use App\Routes\UrlGenerator;
use App\System\Auth;
use App\System\Heart;
use App\System\Template;

class BlockServicesButtons extends Block
{
    public function getContentClass()
    {
        return "services_buttons";
    }

    public function getContentId()
    {
        return "services_buttons";
    }

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = app()->make(Template::class);

        /** @var Heart $heart */
        $heart = app()->make(Heart::class);

        /** @var UrlGenerator $url */
        $url = app()->make(UrlGenerator::class);

        $services = "";
        foreach ($heart->getServices() as $service) {
            if (
                ($serviceModule = $heart->getServiceModule($service->getId())) === null ||
                !$serviceModule->showOnWeb()
            ) {
                continue;
            }

            if (!$heart->userCanUseService($user->getUid(), $service)) {
                continue;
            }

            $services .= create_dom_element(
                "li",
                create_dom_element("a", $service->getName(), [
                    'href' => $url->to("/page/purchase?service=" . urlencode($service->getId())),
                ])
            );
        }

        return $template->render("services_buttons", compact('services'));
    }
}
