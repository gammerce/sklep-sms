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
            // Nie ma takiego bloku do odświeżenia
            if (($block = $heart->getBlock($brick)) === null) {
                continue;
            }

            $data[$block->getContentId()]['content'] = $block->getContent(
                $request->query->all(),
                $request->request->all()
            );
            if ($data[$block->getContentId()]['content'] !== null) {
                $data[$block->getContentId()]['class'] = $block->getContentClass();
            } else {
                $data[$block->getContentId()]['class'] = "";
            }
        }

        return new PlainResponse(json_encode($data));
    }
}
