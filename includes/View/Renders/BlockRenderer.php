<?php
namespace App\View\Renders;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\View\Blocks\BlockResolver;
use App\View\Html\Div;
use App\View\Html\DOMElement;
use App\View\Html\RawHtml;
use Symfony\Component\HttpFoundation\Request;

class BlockRenderer
{
    private BlockResolver $blockResolver;

    public function __construct(BlockResolver $blockResolver)
    {
        $this->blockResolver = $blockResolver;
    }

    /**
     * @param string $blockId
     * @param Request $request
     * @param array $params
     * @return DOMElement|null
     * @throws EntityNotFoundException
     */
    public function render($blockId, Request $request, array $params = []): ?DOMElement
    {
        try {
            $block = $this->blockResolver->resolve($blockId);
        } catch (EntityNotFoundException | ForbiddenException | UnauthorizedException $e) {
            return null;
        }

        return new Div(new RawHtml($block->getContent($request, $params)), [
            "id" => $blockId,
            "class" => $block->getContentClass(),
        ]);
    }
}
