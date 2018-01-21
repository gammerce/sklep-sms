<?php
namespace App\Kernels;

use App\Settings;
use App\Template;
use App\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsKernel extends Kernel
{
    public function handle(Request $request)
    {
        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Translator $lang */
        $lang = $this->app->make(Translator::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $output = '';

        if ($_GET['script'] == "language") {
            $output = eval($template->render("js/language.js", true, false));
        }

        return new Response($output, 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
    }
}
