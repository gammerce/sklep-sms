<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\HtmlResponse;
use App\View\Pages\Admin\PageAdminIncome;
use Symfony\Component\HttpFoundation\Request;

class IncomeController
{
    public function get(Request $request, PageAdminIncome $page)
    {
        return new HtmlResponse($page->getContent($request));
    }
}
