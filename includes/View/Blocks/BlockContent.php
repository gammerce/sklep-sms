<?php
namespace App\View\Blocks;

use App\View\Pages\PageResolver;
use Symfony\Component\HttpFoundation\Request;

class BlockContent extends Block
{
    const BLOCK_ID = "content";

    private PageResolver $pageResolver;

    public function __construct(PageResolver $pageResolver)
    {
        $this->pageResolver = $pageResolver;
    }

    public function getContentClass()
    {
        return "site-content";
    }

    public function getContent(Request $request, array $params)
    {
        return (string) $this->pageResolver->resolveUser($params[0])->getContent($request);
    }
}
