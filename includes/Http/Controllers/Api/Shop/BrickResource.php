<?php
namespace App\Http\Controllers\Api\Shop;

use App\Managers\BlockManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BrickResource
{
    public function get($bricks, Request $request, BlockManager $blockManager)
    {
        $brickList = explode(",", $bricks);

        $data = [];

        foreach ($brickList as $brick) {
            $fragments = explode(":", $brick);
            $brickName = $fragments[0];
            $block = $blockManager->get($brickName);

            if ($block) {
                $contentId = $block->getContentId();
                $content = $block->getContent($request, array_slice($fragments, 1));
                $data[$contentId]["content"] = $content !== null ? strval($content) : null;
                $data[$contentId]["class"] = $content ? $block->getContentClass() : "";
            }
        }

        return new JsonResponse($data);
    }
}
