<?php
namespace App\View\Blocks;

use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\PageResolver;
use Symfony\Component\HttpFoundation\Request;

class BlockAdminContent extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "admincontent";

    private PageResolver $pageResolver;

    public function __construct(PageResolver $pageResolver)
    {
        $this->pageResolver = $pageResolver;
    }

    public function getContentClass(): string
    {
        return "";
    }

    public function getContent(Request $request, array $params): string
    {
        return (string) $this->pageResolver->resolveAdmin($params[0])->getContent($request);
    }
}
