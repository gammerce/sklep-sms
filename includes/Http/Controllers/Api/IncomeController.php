<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\HtmlResponse;
use App\View\Pages\Admin\PageAdminIncome;
use Symfony\Component\HttpFoundation\Request;

class IncomeController
{
    public function get(Request $request, PageAdminIncome $page)
    {
        // TODO Check it

        return new HtmlResponse(
            $page->getContent($request->query->all(), $request->request->all())
        );
    }
}
