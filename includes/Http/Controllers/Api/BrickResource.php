<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;
use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class BrickResource
{
    public function get($bricks, Request $request, Heart $heart)
    {
        $brickList = explode(",", $bricks);

        $data = [];

        foreach ($brickList as $brick) {
            if ($block = $heart->getBlock($brick)) {
                $content = $block->getContent($request->query->all(), $request->request->all());
                $data[$block->getContentId()]['content'] =
                    $content !== null ? strval($content) : null;
                $data[$block->getContentId()]['class'] = $content ? $block->getContentClass() : "";
            }
        }

        return new PlainResponse(json_encode($data));
    }
}
