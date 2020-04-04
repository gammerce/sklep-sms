<?php
namespace App\View\Renders;

use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class BlockRenderer
{
    /** @var Heart */
    private $heart;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
    }

    public function render($blockId, Request $request, array $params = [])
    {
        $block = $this->heart->getBlock($blockId);

        if ($block) {
            return $block->getContentEnveloped(
                $request->query->all(),
                $request->request->all(),
                $params
            );
        }

        return "";
    }
}
