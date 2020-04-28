<?php
namespace App\View\Renders;

use App\Managers\BlockManager;
use Symfony\Component\HttpFoundation\Request;

class BlockRenderer
{
    /** @var BlockManager */
    private $blockManager;

    public function __construct(BlockManager $blockManager)
    {
        $this->blockManager = $blockManager;
    }

    public function render($blockId, Request $request, array $params = [])
    {
        $block = $this->blockManager->get($blockId);
        return $block ? $block->getContentEnveloped($request, $params) : "";
    }
}
