<?php
namespace App\Kernels;

use App\Heart;
use App\License;
use App\Settings;
use App\Template;
use App\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtraStuffKernel extends Kernel
{
    public function handle(Request $request)
    {
        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var Translator $lang */
        $lang = $this->app->make(Translator::class);

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var License $license */
        $license = $this->app->make(License::class);

        // Jezeli jest popup, to wyswietl info w nowym oknie
        if ($_GET['popup']) {
            // Usuwamy napis popup z linku
            $url = preg_replace(
                '/' . preg_quote("&popup={$_GET['popup']}", '/') . '$/',
                '',
                $request->server->get('REQUEST_URI')
            );

            $output = create_dom_element("script",
                'window.open("' . str_replace('"', '\"', $url) . '", "", "height=720,width=1280");', [
                    'type' => "text/javascript",
                ]);

            return new Response($output);
        }

        $action = $_GET['action'];

        switch ($action) {
            case "service_long_description":
                $output = "";

                if (($service_module = $heart->get_service_module($_GET['service'])) !== null) {
                    $output = $service_module->description_full_get();
                }

                $heart->page_title = $lang->translate('description') . ": " . $service_module->service['name'];

                $heart->style_add($settings['shop_url_slash'] . "styles/extra_stuff/long_desc.css?version=" . VERSION);
                $header = eval($template->render("header"));

                $output = create_dom_element(
                    "html", create_dom_element("head", $header) . create_dom_element("body", $output)
                );

                return new Response($output);
        }

        return new Response();
    }
}
