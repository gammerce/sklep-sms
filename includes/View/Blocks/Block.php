<?php
namespace App\View\Blocks;

use App\Exceptions\AccessProhibitedException;
use App\Exceptions\UnauthorizedException;
use App\View\Html\I_ToHtml;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

abstract class Block
{
    /**
     * @param Request $request
     * @param array $params
     * @return I_ToHtml|string|null
     * @throws UnauthorizedException
     * @throws AccessProhibitedException
     */
    public function getContent(Request $request, array $params)
    {
        if (
            ($this instanceof IBeLoggedMust && !is_logged()) ||
            ($this instanceof IBeLoggedCannot && is_logged())
        ) {
            return null;
        }

        return $this->content($request, $params);
    }

    /**
     * @param Request $request
     * @param array $params
     * @return I_ToHtml|string|null
     * @throws UnauthorizedException
     * @throws AccessProhibitedException
     */
    abstract protected function content(Request $request, array $params);

    abstract public function getContentId();

    abstract public function getContentClass();
}
