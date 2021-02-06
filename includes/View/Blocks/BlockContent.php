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

    public function getContentClass(): string
    {
        return "site-content";
    }

    public function getContent(Request $request, array $params): string
    {
        return (string) $this->pageResolver->resolveUser($params[0])->getContent($request);
    }
}
