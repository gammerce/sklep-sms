<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\View\Blocks\BlockResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BrickResource
{
    public function get($bricks, Request $request, BlockResolver $blockResolver)
    {
        $data = [];
        $brickList = explode(",", $bricks);

        foreach ($brickList as $brick) {
            $fragments = explode(":", $brick);
            $blockId = $fragments[0];

            $content = null;
            $class = null;

            try {
                $block = $blockResolver->resolve($blockId);
                $content = $block->getContent($request, array_slice($fragments, 1));
                $class = $block->getContentClass();
            } catch (UnauthorizedException $e) {
                //
            } catch (ForbiddenException $e) {
                //
            } catch (EntityNotFoundException $e) {
                //
            }

            $data[$blockId] = compact("content", "class");
        }

        return new JsonResponse($data);
    }
}
