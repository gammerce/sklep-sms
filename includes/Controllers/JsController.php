<?php
namespace App\Controllers;

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
            'Content-type' => 'text/plain; charset="UTF-8"'
        ]);
    }
}
