<?php
namespace App\Controllers;

use App\Application;
use App\Heart;
use App\License;
use App\Settings;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtraStuffController
{
    public function action(
        Request $request,
        Application $app,
        Template $template,
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        License $license
    ) {
        $lang = $translationManager->user();

        // Jezeli jest popup, to wyswietl info w nowym oknie
        $popup = $request->query->get("action");
        if ($popup) {
            // Usuwamy napis popup z linku
            $url = preg_replace(
                '/' . preg_quote("&popup={$popup}", '/') . '$/',
                '',
                $request->server->get('REQUEST_URI')
            );

            $output = create_dom_element(
                "script",
                'window.open("' . str_replace('"', '\"', $url) . '", "", "height=720,width=1280");',
                [
                    'type' => "text/javascript",
                ]
            );

            return new Response($output);
        }

        $action = $request->query->get("action");

        switch ($action) {
            case "service_long_description":
                $output = "";
                $service = $request->query->get("service");

                if (($service_module = $heart->get_service_module($service)) !== null) {
                    $output = $service_module->description_full_get();
                }

                $heart->page_title =
                    $lang->translate('description') . ": " . $service_module->service['name'];

                $heart->style_add(
                    $settings['shop_url_slash'] .
                        "styles/extra_stuff/long_desc.css?version=" .
                        $app->version()
                );
                $header = $template->render("header", compact('heart', 'license'));

                $output = create_dom_element(
                    "html",
                    create_dom_element("head", $header) . create_dom_element("body", $output)
                );

                return new Response($output);
        }

        return new Response();
    }
}
