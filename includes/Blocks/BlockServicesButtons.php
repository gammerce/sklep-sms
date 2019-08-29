<?php
namespace App\Blocks;

use App\Auth;
use App\Heart;
use App\Routes\UrlGenerator;
use App\Template;

class BlockServicesButtons extends Block
{
    public function get_content_class()
    {
        return "services_buttons";
    }

    public function get_content_id()
    {
        return "services_buttons";
    }

    protected function content($get, $post)
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
                ($service_module = $heart->getServiceModule($service['id'])) === null ||
                !$service_module->show_on_web()
            ) {
                continue;
            }

            if (!$heart->userCanUseService($user->getUid(), $service)) {
                continue;
            }

            $services .= create_dom_element(
                "li",
                create_dom_element("a", $service['name'], [
                    'href' => $url->to("/page/purchase?service=" . urlencode($service['id'])),
                ])
            );
        }

        return $template->render("services_buttons", compact('services'));
    }
}
