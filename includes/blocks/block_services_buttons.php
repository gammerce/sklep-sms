<?php

use App\Auth;
use App\Heart;
use App\Template;
use App\TranslationManager;

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

        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Heart $heart */
        $heart = app()->make(Heart::class);

        $services = "";
        foreach ($heart->get_services() as $service) {
            if (($service_module = $heart->get_service_module($service['id'])) === null || !$service_module->show_on_web()) {
                continue;
            }

            if (!$heart->user_can_use_service($user->getUid(), $service)) {
                continue;
            }

            $services .= create_dom_element("li", create_dom_element("a", $service['name'], [
                'href' => "index.php?pid=purchase&service=" . urlencode($service['id']),
            ]));
        }

        return $template->render("services_buttons", compact('services'));
    }
}