<?php
namespace App\Http\Controllers\View;

use App\Support\Template;
use Symfony\Component\HttpFoundation\Response;

class LanguageJsController
{
    public function get(Template $template)
    {
        $output = $template->render("js/language.js", [], true, false);

        return new Response($output, 200, [
            'Content-type' => 'text/javascript; charset=UTF-8',
        ]);
    }
}
