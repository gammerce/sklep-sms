<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\Managers\PageManager;
use App\View\Blocks\BlockContent;
use App\View\Renders\BlockRenderer;
use App\View\Renders\ShopRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController
{
    public function action(
        $pageId = 'home',
        Request $request,
        PageManager $pageManager,
        ShopRenderer $shopRenderer,
        BlockRenderer $blockRenderer
    ) {
        $page = $pageManager->getUser($pageId);

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $page->addScripts($request);

        $content = $blockRenderer->render(BlockContent::BLOCK_ID, $request, [$page]);
        $output = $shopRenderer->render(
            $content,
            $page->getId(),
            $page->getTitle($request),
            $request
        );

        return new Response($output);
    }
}
