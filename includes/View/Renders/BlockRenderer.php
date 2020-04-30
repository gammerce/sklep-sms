<?php
namespace App\View\Renders;

use App\Managers\BlockManager;
use App\View\Html\RawText;
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

        if (!$block) {
            return "";
        }

        $content = $block->getContent($request, $params);

        return create_dom_element("div", new RawText($content), [
            'id' => $block->getContentId(),
            'class' => $content !== null ? $block->getContentClass() : "",
        ]);
    }
}
