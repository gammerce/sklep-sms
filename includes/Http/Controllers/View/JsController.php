<?php
namespace App\Http\Controllers\View;

use App\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JsController
{
    public function get(Request $request, Template $template)
    {
        $output = '';

        if ($request->query->get('script') == "language") {
            $output = $template->render("js/language.js", [], true, false);
        }

        return new Response($output, 200, [
            'Content-type' => 'text/javascript; charset=UTF-8',
        ]);
    }
}
