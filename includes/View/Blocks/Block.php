<?php
namespace App\View\Blocks;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Request;

abstract class Block
{
    /**
     * @param Request $request
     * @param array $params
     * @return string
     * @throws UnauthorizedException
     * @throws ForbiddenException
     * @throws EntityNotFoundException
     */
    abstract public function getContent(Request $request, array $params);

    /**
     * @return string
     */
    abstract public function getContentClass();
}
