<?php
namespace App\View\Blocks;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Managers\BlockManager;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;

class BlockResolver
{
    /** @var Auth */
    private $auth;

    /** @var BlockManager */
    private $blockManager;

    public function __construct(Auth $auth, BlockManager $blockManager)
    {
        $this->auth = $auth;
        $this->blockManager = $blockManager;
    }

    /**
     * @param Block|string $block
     * @return Block
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws EntityNotFoundException
     */
    public function resolve($block)
    {
        if (!($block instanceof Block)) {
            $block = $this->blockManager->get($block);
        }

        if (!$block) {
            throw new EntityNotFoundException();
        }

        if ($block instanceof IBeLoggedMust && !$this->auth->check()) {
            throw new UnauthorizedException();
        }

        if ($block instanceof IBeLoggedCannot && $this->auth->check()) {
            throw new ForbiddenException();
        }

        return $block;
    }
}
