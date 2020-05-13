<?php
namespace App\Http\Controllers\View;

use App\Support\Template;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Response;

class LanguageJsController
{
    public function get(Template $template, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();
        $output = $template->renderNoComments("js/language.js", [
            "translations" => json_encode($lang->getTranslations()),
        ]);

        return new Response($output, 200, [
            "Content-type" => "text/javascript; charset=UTF-8",
        ]);
    }
}
