<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\System\Heart;
use App\View\Renders\BlockRenderer;
use App\View\Renders\ShopRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function action(
        $pageId = 'home',
        Request $request,
        Heart $heart,
        ShopRenderer $shopRenderer,
        BlockRenderer $blockRenderer
    ) {
        $page = $heart->getPage($pageId, "user");

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $content = $blockRenderer->render("content", $request, [$page]);
        $output = $shopRenderer->render(
            $content,
            $page->getId(),
            $page->getTitle($request),
            $request
        );

        return new Response($output);
    }
}
