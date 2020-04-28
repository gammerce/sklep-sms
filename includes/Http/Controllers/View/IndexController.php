<?php
namespace App\Http\Controllers\View;

use App\Exceptions\EntityNotFoundException;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Support\FileSystem;
use App\Support\Path;
use App\View\Blocks\BlockContent;
use App\Managers\PageManager;
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
        FileSystem $fileSystem,
        Path $path,
        UrlGenerator $url,
        BlockRenderer $blockRenderer,
        WebsiteHeader $websiteHeader
    ) {
        $page = $pageManager->getUser($pageId);

        if (!$page) {
            throw new EntityNotFoundException();
        }

        $scriptPath = "build/js/shop/pages/{$page->getId()}/";
        if ($fileSystem->exists($path->to($scriptPath))) {
            foreach ($fileSystem->scanDirectory($path->to($scriptPath)) as $file) {
                if (ends_at($file, ".js")) {
                    $websiteHeader->addScript($url->versioned($scriptPath . $file));
                }
            }
        }

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
