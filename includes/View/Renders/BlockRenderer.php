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

    public function render($blockId, Request $request)
    {
        $block = $this->heart->getBlock($blockId);

        if ($block) {
            // TODO Pass page_id
            return $block->getContentEnveloped($request->query->all(), $request->request->all());
        }

        return "";
    }
}
