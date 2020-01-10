<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\System\Heart;
use App\View\CurrentPage;
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
        CurrentPage $currentPage,
        ShopRenderer $shopRenderer,
        BlockRenderer $blockRenderer
    ) {
        if (!$heart->pageExists($pageId, "user")) {
            throw new EntityNotFoundException();
        }

        $currentPage->setPid($pageId);

        $content = $blockRenderer->render("content", $request);
        $output = $shopRenderer->render($content, $heart->pageTitle, $request);

        return new Response($output);
    }
}
