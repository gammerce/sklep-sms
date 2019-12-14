<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\HtmlResponse;
use App\Pages\PageAdminIncome;
use App\System\Auth;
use Symfony\Component\HttpFoundation\Request;

class IncomeController
{
    public function get(Auth $auth, Request $request)
    {
        $user = $auth->user();

        $user->setPrivileges([
            'acp' => true,
            'view_income' => true,
        ]);
        $page = new PageAdminIncome();

        return new HtmlResponse(
            $page->getContent($request->query->all(), $request->request->all())
        );
    }
}
